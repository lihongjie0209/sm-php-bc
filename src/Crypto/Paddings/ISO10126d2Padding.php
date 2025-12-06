<?php

declare(strict_types=1);

namespace SmBc\Crypto\Paddings;

/**
 * ISO10126-2 padding implementation.
 * 
 * Padding format: Random bytes followed by the padding length.
 * Example: [data][random][random][0x03]
 * 
 * Based on: org.bouncycastle.crypto.paddings.ISO10126d2Padding
 */
class ISO10126d2Padding implements BlockCipherPadding
{
    private $random;

    /**
     * Initialize the padder with optional random source.
     */
    public function init($random = null): void
    {
        $this->random = $random;
    }

    /**
     * Return the name of the algorithm.
     */
    public function getPaddingName(): string
    {
        return "ISO10126-2";
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
        $code = $len - $inOff;

        // Fill with random bytes except the last byte
        while ($inOff < $len - 1) {
            if ($this->random !== null) {
                $input[$inOff] = chr($this->random->nextInt() & 0xFF);
            } else {
                $input[$inOff] = chr(random_int(0, 255));
            }
            $inOff++;
        }

        // Last byte is the padding length
        $input[$inOff] = chr($code);

        return $code;
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
        $count = ord($input[$len - 1]);

        if ($count < 1 || $count > $len) {
            throw new \RuntimeException("Invalid ISO10126-2 padding");
        }

        return $count;
    }
}
