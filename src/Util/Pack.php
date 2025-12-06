<?php

namespace SmBc\Util;

class Pack
{
    /**
     * @param string $bytes
     * @param int $offset
     * @return int
     */
    public static function bigEndianToInt(string $bytes, int $offset): int
    {
        $n = (ord($bytes[$offset]) << 24);
        $n |= (ord($bytes[$offset + 1]) << 16);
        $n |= (ord($bytes[$offset + 2]) << 8);
        $n |= ord($bytes[$offset + 3]);
        return $n;
    }

    /**
     * @param int $value
     * @param string $bytes
     * @param int $offset
     * @return void
     */
    public static function intToBigEndian(int $value, string &$bytes, int $offset): void
    {
        $bytes[$offset]     = chr(($value >> 24) & 0xff);
        $bytes[$offset + 1] = chr(($value >> 16) & 0xff);
        $bytes[$offset + 2] = chr(($value >> 8) & 0xff);
        $bytes[$offset + 3] = chr($value & 0xff);
    }

    /**
     * @param string $bytes
     * @param int $offset
     * @return int
     */
    public static function bigEndianToLong(string $bytes, int $offset): int
    {
        $hi = self::bigEndianToInt($bytes, $offset);
        $lo = self::bigEndianToInt($bytes, $offset + 4);
        return (($hi & 0xffffffff) << 32) | ($lo & 0xffffffff);
    }

    /**
     * @param int $value
     * @param string $bytes
     * @param int $offset
     * @return void
     */
    public static function longToBigEndian(int $value, string &$bytes, int $offset): void
    {
        $hi = ($value >> 32); // Signed shift, but that's usually fine for split
        $lo = $value;
        self::intToBigEndian($hi, $bytes, $offset);
        self::intToBigEndian($lo, $bytes, $offset + 4);
    }
}
