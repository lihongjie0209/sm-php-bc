<?php

declare(strict_types=1);

namespace SmBc\Crypto;

use SmBc\Crypto\Params\CipherParameters;

/**
 * Buffered Block Cipher
 * 
 * Provides a buffering wrapper around a block cipher with automatic
 * padding support for arbitrary-length inputs.
 * 
 * This class handles:
 * - Buffering of partial blocks
 * - Automatic padding on final block
 * - Simplified API for processing arbitrary-length data
 * 
 * @package SmBc\Crypto
 */
class BufferedBlockCipher
{
    private BlockCipher $cipher;
    private Paddings\BlockCipherPadding $padding;
    private bool $forEncryption = false;
    private string $buf = '';
    private int $bufOff = 0;
    private int $blockSize;

    /**
     * Create a buffered block cipher with padding.
     *
     * @param BlockCipher $cipher The underlying block cipher
     * @param Paddings\BlockCipherPadding $padding The padding scheme
     */
    public function __construct(BlockCipher $cipher, Paddings\BlockCipherPadding $padding)
    {
        $this->cipher = $cipher;
        $this->padding = $padding;
        $this->blockSize = $cipher->getBlockSize();
        $this->buf = str_repeat("\x00", $this->blockSize);
    }

    /**
     * Get the algorithm name.
     */
    public function getAlgorithmName(): string
    {
        return $this->cipher->getAlgorithmName() . '/' . $this->padding->getPaddingName();
    }

    /**
     * Get the block size.
     */
    public function getBlockSize(): int
    {
        return $this->blockSize;
    }

    /**
     * Get the output size for a given input length.
     *
     * @param int $inputLen Input length in bytes
     * @return int Output length in bytes
     */
    public function getOutputSize(int $inputLen): int
    {
        $total = $inputLen + $this->bufOff;
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
     * Get the update output size.
     *
     * @param int $inputLen Input length
     * @return int Output size
     */
    public function getUpdateOutputSize(int $inputLen): int
    {
        $total = $inputLen + $this->bufOff;
        $leftOver = $total % $this->blockSize;
        return $total - $leftOver;
    }

    /**
     * Initialize the cipher.
     *
     * @param bool $forEncryption True for encryption, false for decryption
     * @param CipherParameters $params Cipher parameters
     */
    public function init(bool $forEncryption, CipherParameters $params): void
    {
        $this->forEncryption = $forEncryption;
        $this->cipher->init($forEncryption, $params);
        $this->reset();
    }

    /**
     * Process a single byte.
     *
     * @param int $in Input byte (0-255)
     * @param string &$out Output buffer
     * @param int $outOff Output offset
     * @return int Number of bytes produced
     */
    public function processByte(int $in, string &$out, int $outOff): int
    {
        $resultLen = 0;

        $this->buf[$this->bufOff++] = chr($in);

        if ($this->bufOff === $this->blockSize) {
            $resultLen = $this->cipher->processBlock($this->buf, 0, $out, $outOff);
            $this->bufOff = 0;
        }

        return $resultLen;
    }

    /**
     * Process a block of bytes.
     *
     * @param string $in Input data
     * @param int $inOff Input offset
     * @param int $len Length to process
     * @param string &$out Output buffer
     * @param int $outOff Output offset
     * @return int Number of bytes produced
     */
    public function processBytes(string $in, int $inOff, int $len, string &$out, int $outOff): int
    {
        if ($len < 0) {
            throw new \InvalidArgumentException('Length cannot be negative');
        }

        $blockSize = $this->getBlockSize();
        $length = $this->getUpdateOutputSize($len);

        if ($length > 0) {
            if (($outOff + $length) > strlen($out)) {
                throw new \RuntimeException('Output buffer too short');
            }
        }

        $resultLen = 0;
        $gapLen = $blockSize - $this->bufOff;

        if ($len > $gapLen) {
            // Fill buffer
            for ($i = 0; $i < $gapLen; $i++) {
                $this->buf[$this->bufOff + $i] = $in[$inOff + $i];
            }

            $resultLen += $this->cipher->processBlock($this->buf, 0, $out, $outOff);
            $this->bufOff = 0;
            $len -= $gapLen;
            $inOff += $gapLen;

            // Process complete blocks
            while ($len > $blockSize) {
                $resultLen += $this->cipher->processBlock($in, $inOff, $out, $outOff + $resultLen);
                $len -= $blockSize;
                $inOff += $blockSize;
            }
        }

        // Copy remaining to buffer
        for ($i = 0; $i < $len; $i++) {
            $this->buf[$this->bufOff + $i] = $in[$inOff + $i];
        }
        $this->bufOff += $len;

        return $resultLen;
    }

    /**
     * Finish processing and apply padding.
     *
     * @param string &$out Output buffer
     * @param int $outOff Output offset
     * @return int Number of bytes produced
     * @throws \RuntimeException
     */
    public function doFinal(string &$out, int $outOff): int
    {
        $resultLen = 0;
        $blockSize = $this->getBlockSize();

        if ($this->forEncryption) {
            // Encryption: add padding
            if ($this->bufOff === $blockSize) {
                // Buffer is full, process it
                if (($outOff + 2 * $blockSize) > strlen($out)) {
                    throw new \RuntimeException('Output buffer too short');
                }

                $resultLen = $this->cipher->processBlock($this->buf, 0, $out, $outOff);
                $this->bufOff = 0;
            }

            // Add padding
            $this->padding->addPadding($this->buf, $this->bufOff);
            $resultLen += $this->cipher->processBlock($this->buf, 0, $out, $outOff + $resultLen);
            $this->reset();
        } else {
            // Decryption: remove padding
            if ($this->bufOff === $blockSize) {
                $resultLen = $this->cipher->processBlock($this->buf, 0, $out, $outOff);
                $this->bufOff = 0;
            } else {
                throw new \RuntimeException('Last block incomplete in decryption');
            }

            // Remove padding
            try {
                $resultLen -= $this->padding->padCount($out, $outOff);
            } catch (\Exception $e) {
                throw new \RuntimeException('Pad block corrupted: ' . $e->getMessage());
            }

            $this->reset();
        }

        return $resultLen;
    }

    /**
     * Reset the cipher.
     */
    public function reset(): void
    {
        // Clear buffer
        for ($i = 0; $i < $this->blockSize; $i++) {
            $this->buf[$i] = "\x00";
        }
        $this->bufOff = 0;

        $this->cipher->reset();
    }
}
