<?php

namespace SmBc\Crypto;

interface ExtendedDigest extends Digest
{
    public function getByteLength(): int;
}
