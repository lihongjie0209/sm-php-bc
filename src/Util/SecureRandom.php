<?php

namespace SmBc\Util;

class SecureRandom
{
    public function nextBytes(int $length): string
    {
        return random_bytes($length);
    }
    
    public function nextInt(int $max): int
    {
        return random_int(0, $max - 1);
    }
}
