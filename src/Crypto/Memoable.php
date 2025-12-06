<?php

namespace SmBc\Crypto;

interface Memoable
{
    public function copy(): Memoable;
    public function reset(Memoable $other): void;
}
