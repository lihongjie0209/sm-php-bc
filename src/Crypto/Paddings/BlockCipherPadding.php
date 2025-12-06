<?php

declare(strict_types=1);

namespace SmBc\Crypto\Paddings;

/**
 * Block cipher padding interface.
 * 
 * Based on: org.bouncycastle.crypto.paddings.BlockCipherPadding
 */
interface BlockCipherPadding
{
    /**
     * Initialise the padder.
     * 
     * @param mixed $random Random number generator (if needed)
     */
    public function init($random = null): void;

    /**
     * Return the name of the algorithm the padder implements.
     * 
     * @return string Padding name
     */
    public function getPaddingName(): string;

    /**
     * Add padding to the input array.
     * 
     * @param string $input Input data (passed by reference)
     * @param int $inOff Offset where padding should start
     * @return int Number of padding bytes added
     */
    public function addPadding(string &$input, int $inOff): int;

    /**
     * Return the number of padding bytes present in the block.
     * 
     * @param string $input Padded block
     * @return int Number of padding bytes
     * @throws \RuntimeException If padding is invalid
     */
    public function padCount(string $input): int;
}
