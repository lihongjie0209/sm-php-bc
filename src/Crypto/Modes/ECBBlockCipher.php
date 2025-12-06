<?php

declare(strict_types=1);

namespace SmBc\Crypto\Modes;

use SmBc\Crypto\BlockCipher;
use SmBc\Crypto\Params\CipherParameters;
use RuntimeException;

/**
 * ECB (Electronic Codebook) mode implementation
 * 
 * WARNING: ECB mode is NOT SECURE and should NOT be used in production!
 * Identical plaintext blocks are always encrypted to identical ciphertext blocks,
 * which leaks information patterns.
 * 
 * This implementation is ONLY for:
 * - Compatibility with legacy systems
 * - Testing and educational purposes
 * 
 * Reference: org.bouncycastle.crypto.modes.ECBBlockCipher
 */
class ECBBlockCipher implements BlockCipher
{
    private BlockCipher $cipher;
    private int $blockSize;

    /**
     * Create an ECB mode cipher
     * 
     * @param BlockCipher $cipher The underlying block cipher
     */
    public function __construct(BlockCipher $cipher)
    {
        $this->cipher = $cipher;
        $this->blockSize = $cipher->getBlockSize();
    }

    /**
     * Get the underlying cipher
     * 
     * @return BlockCipher
     */
    public function getUnderlyingCipher(): BlockCipher
    {
        return $this->cipher;
    }

    /**
     * Initialize the cipher
     * 
     * @param bool $encrypting True for encryption, false for decryption
     * @param CipherParameters $params Cipher parameters (must contain key)
     * @throws \InvalidArgumentException
     */
    public function init(bool $encrypting, CipherParameters $params): void
    {
        $this->cipher->init($encrypting, $params);
    }

    /**
     * Get the algorithm name
     * 
     * @return string Algorithm name (e.g., "SM4/ECB")
     */
    public function getAlgorithmName(): string
    {
        return $this->cipher->getAlgorithmName() . '/ECB';
    }

    /**
     * Get the block size in bytes
     * 
     * @return int Block size
     */
    public function getBlockSize(): int
    {
        return $this->blockSize;
    }

    /**
     * Process a single block
     * 
     * ECB mode simply passes each block directly to the underlying cipher
     * without any chaining or IV.
     * 
     * @param string $input Input data (at least blockSize bytes from inOff)
     * @param int $inOff Input offset
     * @param string $output Output buffer (at least blockSize bytes from outOff)
     * @param int $outOff Output offset
     * @return int Number of bytes processed (always blockSize)
     * @throws RuntimeException
     */
    public function processBlock(
        string $input,
        int $inOff,
        string &$output,
        int $outOff
    ): int {
        if ($inOff + $this->blockSize > strlen($input)) {
            throw new RuntimeException('Input buffer too short');
        }

        if ($outOff + $this->blockSize > strlen($output)) {
            throw new RuntimeException('Output buffer too short');
        }

        return $this->cipher->processBlock($input, $inOff, $output, $outOff);
    }

    /**
     * Reset the cipher to its initial state
     */
    public function reset(): void
    {
        $this->cipher->reset();
    }
}
