<?php

declare(strict_types=1);

namespace SmBc\Crypto\Paddings;

/**
 * Zero byte padding implementation.
 * 
 * Padding format: All padding bytes are 0x00.
 * Example: [data][0x00][0x00][0x00]
 * 
 * Note: This padding is problematic if data can end with 0x00 bytes.
 * 
 * Based on: org.bouncycastle.crypto.paddings.ZeroBytePadding
 */
class ZeroBytePadding implements BlockCipherPadding
{
    /**
     * Initialize the padder.
     */
    public function init($random = null): void
    {
        // No initialization needed
    }

    /**
     * Return the name of the algorithm.
     */
    public function getPaddingName(): string
    {
        return "ZeroByte";
    }

    /**
     * Add padding to the input.
     * 
     * @param string $input Input data (passed by reference)
     * @param int $inOff Offset where padding should start
     * @return int Number of padding bytes added
     */
    public function addPadding(string &$input, int $inOff): int
    {
        $len = strlen($input);
        $added = $len - $inOff;

        // Fill with zero bytes
        while ($inOff < $len) {
            $input[$inOff] = "\x00";
            $inOff++;
        }

        return $added;
    }

    /**
     * Return the number of padding bytes present in the block.
     * 
     * @param string $input Padded block
     * @return int Number of padding bytes
     * @throws \RuntimeException If padding is invalid
     */
    public function padCount(string $input): int
    {
        $len = strlen($input);
        $count = $len;

        // Scan backwards for non-zero bytes
        while ($count > 0) {
            if (ord($input[$count - 1]) !== 0x00) {
                break;
            }
            $count--;
        }

        return $len - $count;
    }
}
