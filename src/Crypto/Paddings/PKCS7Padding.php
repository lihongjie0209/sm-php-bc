<?php

declare(strict_types=1);

namespace SmBc\Crypto\Paddings;

use RuntimeException;

/**
 * PKCS7 Padding implementation.
 * 
 * PKCS7 padding scheme pads with bytes, each with value equal to the number of padding bytes.
 * For example, if 5 bytes of padding are needed, the padding bytes are: 05 05 05 05 05
 * 
 * Based on: org.bouncycastle.crypto.paddings.PKCS7Padding
 */
class PKCS7Padding implements BlockCipherPadding
{
    /**
     * Initialise the padder.
     */
    public function init($random = null): void
    {
        // Nothing to initialize
    }

    /**
     * Return the name of the algorithm.
     */
    public function getPaddingName(): string
    {
        return 'PKCS7';
    }

    /**
     * Add PKCS7 padding to a block.
     * 
     * @param string $input Input data (passed by reference)
     * @param int $inOff Offset where padding should start
     * @return int Number of padding bytes added
     */
    public function addPadding(string &$input, int $inOff): int
    {
        $code = strlen($input) - $inOff;

        // Padding byte value equals number of padding bytes
        while ($inOff < strlen($input)) {
            $input[$inOff] = chr($code);
            $inOff++;
        }

        return $code;
    }

    /**
     * Return the number of padding bytes in the block.
     * 
     * @param string $input Padded block
     * @return int Number of padding bytes
     * @throws RuntimeException If padding is invalid
     */
    public function padCount(string $input): int
    {
        $count = ord($input[strlen($input) - 1]);

        if ($count < 1 || $count > strlen($input)) {
            throw new RuntimeException('Pad block corrupted');
        }

        // Verify all padding bytes have the same value
        for ($i = 1; $i <= $count; $i++) {
            if (ord($input[strlen($input) - $i]) !== $count) {
                throw new RuntimeException('Pad block corrupted');
            }
        }

        return $count;
    }
}
