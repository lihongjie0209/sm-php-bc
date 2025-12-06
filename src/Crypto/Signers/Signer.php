<?php

namespace SmBc\Crypto\Signers;

use SmBc\Crypto\Params\CipherParameters;

interface Signer
{
    public function getAlgorithmName(): string;
    public function init(bool $forSigning, CipherParameters $param): void;
    public function update(int $b): void;
    public function updateBytes(string $in, int $off, int $len): void;
    public function generateSignature(): string;
    public function verifySignature(string $signature): bool;
    public function reset(): void;
}
