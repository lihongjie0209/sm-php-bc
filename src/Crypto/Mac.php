<?php

declare(strict_types=1);

namespace SmBc\Crypto;

use SmBc\Crypto\Params\CipherParameters;

/**
 * MAC (Message Authentication Code) interface
 * 
 * Based on: org.bouncycastle.crypto.Mac
 *           sm-js-bc/src/crypto/Mac.ts
 */
interface Mac
{
    /**
     * Initialize the MAC with the given parameters
     * 
     * @param CipherParameters $params The cipher parameters (typically KeyParameter)
     * @return void
     */
    public function init(CipherParameters $params): void;

    /**
     * Return the algorithm name
     * 
     * @return string The algorithm name
     */
    public function getAlgorithmName(): string;

    /**
     * Return the size (in bytes) of the MAC
     * 
     * @return int The MAC size in bytes
     */
    public function getMacSize(): int;

    /**
     * Add a single byte to the MAC calculation
     * 
     * @param int $input The byte to add (0-255)
     * @return void
     */
    public function update(int $input): void;

    /**
     * Add multiple bytes to the MAC calculation
     * 
     * @param string $input The byte array containing the data
     * @param int $inOff The offset into the input array where the data starts
     * @param int $len The length of the data to add
     * @return void
     */
    public function updateBytes(string $input, int $inOff, int $len): void;

    /**
     * Complete the MAC calculation and write the result to the output array
     * 
     * @param string &$out The output array to write the MAC to
     * @param int $outOff The offset into the output array to start writing
     * @return int The number of bytes written
     */
    public function doFinal(string &$out, int $outOff): int;

    /**
     * Reset the MAC to its initial state
     * 
     * @return void
     */
    public function reset(): void;
}
