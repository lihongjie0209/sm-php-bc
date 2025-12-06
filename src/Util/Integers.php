<?php

namespace SmBc\Util;

class Integers
{
    public static function numberOfLeadingZeros(int $i): int
    {
        // Emulate 32-bit behavior
        $i &= 0xFFFFFFFF;
        if ($i === 0) {
            return 32;
        }
        
        $n = 1;
        if (($i >> 16) === 0) { $n += 16; $i <<= 16; }
        if (($i >> 24) === 0) { $n +=  8; $i <<=  8; }
        if (($i >> 28) === 0) { $n +=  4; $i <<=  4; }
        if (($i >> 30) === 0) { $n +=  2; $i <<=  2; }
        // $i is now shifted. Check if 31st bit is set.
        // In 64-bit PHP, 31st bit is just a bit.
        // $i is masked to 32 bits initially, but left shifts might make it larger?
        // $i <<= 16. If $i was 0xFFFF, it becomes 0xFFFF0000. Still < 2^63.
        // So >> 31 extracts the 31st bit (0 or 1).
        $n -= ($i >> 31);
        return $n;
    }

    public static function bitCount(int $i): int
    {
        $i &= 0xFFFFFFFF;
        // PHP handles this algo fine on 64-bit ints
        $i = $i - (($i >> 1) & 0x55555555);
        $i = ($i & 0x33333333) + (($i >> 2) & 0x33333333);
        $i = ($i + ($i >> 4)) & 0x0f0f0f0f;
        $i = $i + ($i >> 8);
        $i = $i + ($i >> 16);
        return $i & 0x3f;
    }

    public static function rotateLeft(int $i, int $distance): int
    {
        $i &= 0xFFFFFFFF;
        $distance &= 31;
        return (($i << $distance) | ($i >> (32 - $distance))) & 0xFFFFFFFF;
    }

    public static function rotateRight(int $i, int $distance): int
    {
        $i &= 0xFFFFFFFF;
        $distance &= 31;
        return (($i >> $distance) | ($i << (32 - $distance))) & 0xFFFFFFFF;
    }
    
    public static function numberOfTrailingZeros(int $i): int
    {
        $i &= 0xFFFFFFFF;
        if ($i === 0) return 32;
        
        $n = 31;
        $y = $i << 16; if (($y & 0xFFFFFFFF) != 0) { $n -= 16; $i = $y; }
        $y = $i << 8;  if (($y & 0xFFFFFFFF) != 0) { $n -=  8; $i = $y; }
        $y = $i << 4;  if (($y & 0xFFFFFFFF) != 0) { $n -=  4; $i = $y; }
        $y = $i << 2;  if (($y & 0xFFFFFFFF) != 0) { $n -=  2; $i = $y; }
        // (i << 1) might overflow 32 bits but we mask or just shift.
        // We need check bit 31.
        // In JS: (i << 1) >>> 31.
        // In PHP: (($i << 1) & 0xFFFFFFFF) >> 31
        return $n - ((($i << 1) & 0xFFFFFFFF) >> 31);
    }
}
