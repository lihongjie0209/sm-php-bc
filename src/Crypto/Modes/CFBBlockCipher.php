<?php

declare(strict_types=1);

namespace SmBc\Crypto\Modes;

use SmBc\Crypto\BlockCipher;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ParametersWithIV;
use SmBc\Exceptions\DataLengthException;

/**
 * Implements a Cipher-FeedBack (CFB) mode on top of a simple cipher.
 * 
 * Reference: org.bouncycastle.crypto.modes.CFBBlockCipher
 */
class CFBBlockCipher implements BlockCipher
{
    private readonly BlockCipher $cipher;
    private readonly int $cipherBlockSize;
    private readonly int $blockSize;

    private string $IV;
    private string $cfbV;
    private string $cfbOutV;
    private string $inBuf;

    private bool $encrypting = false;
    private int $byteCount = 0;

    /**
     * Basic constructor.
     * 
     * @param BlockCipher $cipher The block cipher to be used as the basis of the feedback mode
     * @param int $bitBlockSize The block size in bits (note: a multiple of 8)
     */
    public function __construct(BlockCipher $cipher, int $bitBlockSize)
    {
        $cipherBlockSize = $cipher->getBlockSize();

        if ($bitBlockSize > $cipherBlockSize * 8 ||
            $bitBlockSize < 8 ||
            $bitBlockSize % 8 !== 0) {
            throw new \InvalidArgumentException("CFB{$bitBlockSize} not supported");
        }

        $this->cipher = $cipher;
        $this->cipherBlockSize = $cipherBlockSize;
        $this->blockSize = $bitBlockSize / 8;

        $this->IV = str_repeat("\x00", $cipherBlockSize);
        $this->cfbV = str_repeat("\x00", $cipherBlockSize);
        $this->cfbOutV = str_repeat("\x00", $cipherBlockSize);
        $this->inBuf = str_repeat("\x00", $this->blockSize);
    }

    /**
     * Initialize the cipher and, possibly, the initialisation vector (IV).
     * If an IV isn't passed as part of the parameter, the IV will be all zeros.
     * An IV which is too short is handled in FIPS compliant fashion.
     * 
     * @param bool $encrypting If true the cipher is initialised for encryption, if false for decryption
     * @param CipherParameters $params The key and other data required by the cipher
     */
    public function init(bool $encrypting, CipherParameters $params): void
    {
        $this->encrypting = $encrypting;

        if ($params instanceof ParametersWithIV) {
            $iv = $params->getIV();

            if (strlen($iv) < strlen($this->IV)) {
                // Prepend the supplied IV with zeros (per FIPS PUB 81)
                $this->IV = str_repeat("\x00", strlen($this->IV) - strlen($iv)) . $iv;
            } else {
                $this->IV = substr($iv, 0, strlen($this->IV));
            }

            $this->reset();

            // If null it's an IV changed only
            $underlyingParams = $params->getParameters();
            if ($underlyingParams !== null) {
                $this->cipher->init(true, $underlyingParams);
            }
        } else {
            $this->reset();

            // If it's null, key is to be reused
            if ($params !== null) {
                $this->cipher->init(true, $params);
            }
        }
    }

    /**
     * Return the algorithm name and mode.
     */
    public function getAlgorithmName(): string
    {
        return $this->cipher->getAlgorithmName() . '/CFB' . ($this->blockSize * 8);
    }

    /**
     * Return the block size we are operating at.
     */
    public function getBlockSize(): int
    {
        return $this->blockSize;
    }

    /**
     * Return the underlying cipher.
     */
    public function getUnderlyingCipher(): BlockCipher
    {
        return $this->cipher;
    }

    /**
     * Process one block of input from the array in and write it to the out array.
     */
    public function processBlock(string $input, int $inOff, string &$output, int $outOff): int
    {
        $this->processBytes($input, $inOff, $this->blockSize, $output, $outOff);
        return $this->blockSize;
    }

    /**
     * Process bytes in CFB mode.
     */
    public function processBytes(string $input, int $inOff, int $len, string &$output, int $outOff): int
    {
        if ($inOff + $len > strlen($input)) {
            throw new DataLengthException('input buffer too small');
        }

        if ($outOff + $len > strlen($output)) {
            throw new DataLengthException('output buffer too short');
        }

        for ($i = 0; $i < $len; $i++) {
            $output[$outOff + $i] = chr(
                $this->encrypting
                    ? $this->encryptByte(ord($input[$inOff + $i]))
                    : $this->decryptByte(ord($input[$inOff + $i]))
            );
        }

        return $len;
    }

    /**
     * Encrypt a single byte.
     */
    private function encryptByte(int $inputByte): int
    {
        if ($this->byteCount === 0) {
            $this->cipher->processBlock($this->cfbV, 0, $this->cfbOutV, 0);
        }

        $rv = ord($this->cfbOutV[$this->byteCount]) ^ $inputByte;
        $this->inBuf[$this->byteCount++] = chr($rv);

        if ($this->byteCount === $this->blockSize) {
            $this->byteCount = 0;

            // Shift cfbV left by blockSize bytes and append inBuf
            $this->cfbV = substr($this->cfbV, $this->blockSize) . 
                          substr($this->inBuf, 0, $this->blockSize);
        }

        return $rv;
    }

    /**
     * Decrypt a single byte.
     */
    private function decryptByte(int $inputByte): int
    {
        if ($this->byteCount === 0) {
            $this->cipher->processBlock($this->cfbV, 0, $this->cfbOutV, 0);
        }

        $this->inBuf[$this->byteCount] = chr($inputByte);
        $rv = ord($this->cfbOutV[$this->byteCount++]) ^ $inputByte;

        if ($this->byteCount === $this->blockSize) {
            $this->byteCount = 0;

            // Shift cfbV left by blockSize bytes and append inBuf
            $this->cfbV = substr($this->cfbV, $this->blockSize) . 
                          substr($this->inBuf, 0, $this->blockSize);
        }

        return $rv;
    }

    /**
     * Return the current state of the initialisation vector.
     */
    public function getCurrentIV(): string
    {
        return $this->cfbV;
    }

    /**
     * Reset the chaining vector back to the IV and reset the underlying cipher.
     */
    public function reset(): void
    {
        $this->cfbV = $this->IV;
        $this->inBuf = str_repeat("\x00", $this->blockSize);
        $this->byteCount = 0;

        $this->cipher->reset();
    }
}
