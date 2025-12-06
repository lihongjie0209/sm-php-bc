<?php

declare(strict_types=1);

namespace SmBc\Crypto;

use SmBc\Crypto\Params\CipherParameters;

/**
 * Block cipher interface.
 * 
 * Based on: org.bouncycastle.crypto.BlockCipher
 */
interface BlockCipher
{
    /**
     * Initialize the cipher.
     * 
     * @param bool $forEncryption True for encryption, false for decryption
     * @param CipherParameters $params The key and other parameters
     */
    public function init(bool $forEncryption, CipherParameters $params): void;

    /**
     * Return the name of the algorithm.
     * 
     * @return string Algorithm name
     */
    public function getAlgorithmName(): string;

    /**
     * Return the block size for this cipher (in bytes).
     * 
     * @return int Block size in bytes
     */
    public function getBlockSize(): int;

    /**
     * Process one block of input from the input array and return it in the output array.
     * 
     * @param string $input Input data
     * @param int $inOff Offset into input array
     * @param string $output Output buffer (passed by reference)
     * @param int $outOff Offset into output array
     * @return int Number of bytes processed
     */
    public function processBlock(string $input, int $inOff, string &$output, int $outOff): int;

    /**
     * Reset the cipher to its initial state.
     */
    public function reset(): void;
}
