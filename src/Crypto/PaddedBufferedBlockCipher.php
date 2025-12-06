<?php

declare(strict_types=1);

namespace SmBc\Crypto;

use SmBc\Crypto\Paddings\BlockCipherPadding;
use SmBc\Crypto\Params\CipherParameters;
use RuntimeException;

/**
 * A buffered block cipher with padding.
 * 
 * This class handles buffering of partial blocks and applies padding
 * to make the plaintext a multiple of the block size.
 * 
 * Based on: org.bouncycastle.crypto.paddings.PaddedBufferedBlockCipher
 */
class PaddedBufferedBlockCipher
{
    private BlockCipher $cipher;
    private BlockCipherPadding $padding;
    
    private bool $forEncryption = false;
    private string $buf;
    private int $bufOff = 0;
    private int $blockSize;

    /**
     * Create a buffered block cipher with the specified padding.
     * 
     * @param BlockCipher $cipher The block cipher to use
     * @param BlockCipherPadding $padding The padding scheme to use
     */
    public function __construct(BlockCipher $cipher, BlockCipherPadding $padding)
    {
        $this->cipher = $cipher;
        $this->padding = $padding;
        $this->blockSize = $cipher->getBlockSize();
        $this->buf = str_repeat("\x00", $this->blockSize);
        $this->bufOff = 0;
    }

    /**
     * Get the underlying cipher.
     * 
     * @return BlockCipher The cipher
     */
    public function getUnderlyingCipher(): BlockCipher
    {
        return $this->cipher;
    }

    /**
     * Initialize the cipher.
     * 
     * @param bool $forEncryption True for encryption, false for decryption
     * @param CipherParameters $params The key and other data required by the cipher
     */
    public function init(bool $forEncryption, CipherParameters $params): void
    {
        $this->forEncryption = $forEncryption;
        $this->reset();
        $this->cipher->init($forEncryption, $params);
        $this->padding->init();
    }

    /**
     * Get the block size for this cipher.
     * 
     * @return int The block size
     */
    public function getBlockSize(): int
    {
        return $this->cipher->getBlockSize();
    }

    /**
     * Get the output size for a given input length.
     * 
     * @param int $length The input length
     * @return int The output size
     */
    public function getOutputSize(int $length): int
    {
        $total = $length + $this->bufOff;
        $leftOver = $total % $this->blockSize;

        if ($leftOver === 0) {
            if ($this->forEncryption) {
                return $total + $this->blockSize;
            }
            return $total;
        }

        return $total - $leftOver + $this->blockSize;
    }

    /**
     * Process a single byte.
     * 
     * @param int $in The byte to process
     * @param string $out The output buffer
     * @param int $outOff The offset in the output buffer
     * @return int The number of bytes produced
     */
    public function processByte(int $in, string &$out, int $outOff): int
    {
        $this->buf[$this->bufOff++] = chr($in);

        if ($this->bufOff === strlen($this->buf)) {
            $resultLen = $this->cipher->processBlock($this->buf, 0, $out, $outOff);
            $this->bufOff = 0;
            return $resultLen;
        }

        return 0;
    }

    /**
     * Process an array of bytes.
     * 
     * @param string $in The input data
     * @param int $inOff The offset in the input buffer
     * @param int $len The length of data to process
     * @param string $out The output buffer
     * @param int $outOff The offset in the output buffer
     * @return int The number of bytes produced
     */
    public function processBytes(string $in, int $inOff, int $len, string &$out, int $outOff): int
    {
        if ($len < 0) {
            throw new RuntimeException('Cannot process negative length');
        }

        $blockSize = $this->getBlockSize();
        $length = $this->getUpdateOutputSize($len);

        if ($length > 0 && ($outOff + $length > strlen($out))) {
            throw new RuntimeException('Output buffer too short');
        }

        $resultLen = 0;
        $gapLen = strlen($this->buf) - $this->bufOff;

        if ($len > $gapLen) {
            // Copy data to fill buffer
            for ($i = 0; $i < $gapLen; $i++) {
                $this->buf[$this->bufOff + $i] = $in[$inOff + $i];
            }

            $resultLen += $this->cipher->processBlock($this->buf, 0, $out, $outOff);
            $this->bufOff = 0;
            $len -= $gapLen;
            $inOff += $gapLen;

            // Process complete blocks
            while ($len > strlen($this->buf)) {
                $resultLen += $this->cipher->processBlock($in, $inOff, $out, $outOff + $resultLen);
                $len -= $blockSize;
                $inOff += $blockSize;
            }
        }

        // Copy remaining data to buffer
        for ($i = 0; $i < $len; $i++) {
            $this->buf[$this->bufOff + $i] = $in[$inOff + $i];
        }
        $this->bufOff += $len;

        return $resultLen;
    }

    /**
     * Finish the encryption/decryption operation.
     * 
     * @param string $out The output buffer
     * @param int $outOff The offset in the output buffer
     * @return int The number of bytes produced
     */
    public function doFinal(string &$out, int $outOff): int
    {
        $blockSize = $this->cipher->getBlockSize();
        $resultLen = 0;

        if ($this->forEncryption) {
            // Add padding
            if ($this->bufOff === $blockSize) {
                $resultLen = $this->cipher->processBlock($this->buf, 0, $out, $outOff);
                $this->bufOff = 0;
            }

            $this->padding->addPadding($this->buf, $this->bufOff);
            $resultLen += $this->cipher->processBlock($this->buf, 0, $out, $outOff + $resultLen);
            $this->reset();
        } else {
            // Decryption - process last block and remove padding
            if ($this->bufOff === $blockSize) {
                $resultLen = $this->cipher->processBlock($this->buf, 0, $out, $outOff);
                $this->bufOff = 0;
            } else {
                $this->reset();
                throw new RuntimeException('Last block incomplete in decryption');
            }

            // Remove padding
            $padCount = $this->padding->padCount(substr($out, $outOff, $blockSize));
            $resultLen -= $padCount;
        }

        return $resultLen;
    }

    /**
     * Reset the cipher.
     */
    public function reset(): void
    {
        $this->bufOff = 0;
        $this->buf = str_repeat("\x00", strlen($this->buf));
        $this->cipher->reset();
    }

    /**
     * Get the size of the output buffer required for an update.
     * 
     * @param int $length The input length
     * @return int The output size
     */
    private function getUpdateOutputSize(int $length): int
    {
        $total = $length + $this->bufOff;
        $leftOver = $total % strlen($this->buf);
        return $total - $leftOver;
    }
}
