<?php

declare(strict_types=1);

namespace SmBc\Crypto\Paddings;

/**
 * ISO7816-4 padding implementation.
 * 
 * Padding format: 0x80 followed by zero bytes.
 * Example: [data][0x80][0x00][0x00]...
 * 
 * Based on: org.bouncycastle.crypto.paddings.ISO7816d4Padding
 */
class ISO7816d4Padding implements BlockCipherPadding
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
        return "ISO7816-4";
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

        // First byte is 0x80
        $input[$inOff] = "\x80";
        $inOff++;

        // Rest are 0x00
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
        $count = $len - 1;

        // Scan backwards for 0x80
        while ($count > 0 && ord($input[$count]) === 0x00) {
            $count--;
        }

        if (ord($input[$count]) !== 0x80) {
            throw new \RuntimeException("Invalid ISO7816-4 padding");
        }

        return $len - $count;
    }
}
