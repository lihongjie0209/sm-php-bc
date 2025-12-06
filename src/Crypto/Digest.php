<?php

namespace SmBc\Crypto;

interface Digest
{
    public function getAlgorithmName(): string;
    public function getDigestSize(): int;
    public function update(int $in): void;
    public function updateBytes(string $input, int $inOff, int $len): void;
    public function doFinal(string &$output, int $outOff): int;
    public function reset(): void;
}
