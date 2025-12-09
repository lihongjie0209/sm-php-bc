<?php

declare(strict_types=1);

namespace SmBc\Crypto;

use SmBc\Crypto\Params\CipherParameters;

/**
 * Stream cipher interface.
 * 
 * Stream ciphers process data one byte at a time.
 * 
 * Based on: org.bouncycastle.crypto.StreamCipher
 */
interface StreamCipher
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
     * Encrypt/decrypt a single byte.
     * 
     * @param int $input The input byte
     * @return int The output byte
     */
    public function returnByte(int $input): int;

    /**
     * Process a block of bytes.
     * 
     * @param string $input The input bytes
     * @param int $inOff The offset in input to start
     * @param int $length The length of data to process
     * @param string &$output The output buffer
     * @param int $outOff The offset in output to start
     * @return int The number of bytes processed
     */
    public function processBytes(
        string $input,
        int $inOff,
        int $length,
        string &$output,
        int $outOff
    ): int;

    /**
     * Reset the cipher to its initial state.
     */
    public function reset(): void;
}
