<?php

namespace SmBc\Crypto\Modes\GCM;

use SmBc\Util\Pack;

/**
 * Utility functions for GCM mode.
 * Implements Galois field arithmetic for GCM authentication.
 * 
 * @see BouncyCastle GCMUtil.java
 */
class GCMUtil
{
    const BLOCK_SIZE = 16;
    
    private const E1 = 0xe1000000;
    
    /**
     * XOR two blocks in place.
     *
     * @param string $block Block to modify (16 bytes)
     * @param string $val Value to XOR with (16 bytes)
     */
    public static function xor(string &$block, string $val): void
    {
        for ($i = 0; $i < 16; $i++) {
            $block[$i] = chr(ord($block[$i]) ^ ord($val[$i]));
        }
    }
    
    /**
     * Galois field multiplication in GF(2^128).
     * Multiplies two 128-bit blocks.
     *
     * @param string $xBytes First operand (16 bytes)
     * @param string $yBytes Second operand (16 bytes)
     * @return string Result (16 bytes)
     */
    public static function multiply(string $xBytes, string $yBytes): string
    {
        // Convert to 32-bit int arrays (4 ints = 128 bits)
        $x = [];
        $y = [];
        
        for ($i = 0; $i < 4; $i++) {
            $offset = $i * 4;
            $x[$i] = (ord($xBytes[$offset]) << 24) | 
                     (ord($xBytes[$offset + 1]) << 16) | 
                     (ord($xBytes[$offset + 2]) << 8) | 
                     ord($xBytes[$offset + 3]);
            $y[$i] = (ord($yBytes[$offset]) << 24) | 
                     (ord($yBytes[$offset + 1]) << 16) | 
                     (ord($yBytes[$offset + 2]) << 8) | 
                     ord($yBytes[$offset + 3]);
        }
        
        $y0 = $y[0];
        $y1 = $y[1];
        $y2 = $y[2];
        $y3 = $y[3];
        $z0 = 0;
        $z1 = 0;
        $z2 = 0;
        $z3 = 0;
        
        // Process each bit of x
        for ($i = 0; $i < 4; ++$i) {
            $bits = $x[$i];
            for ($j = 0; $j < 32; ++$j) {
                $m1 = $bits >> 31; // Arithmetic shift: -1 if MSB set, 0 otherwise
                $bits = self::toInt32($bits << 1);
                $z0 ^= ($y0 & $m1);
                $z1 ^= ($y1 & $m1);
                $z2 ^= ($y2 & $m1);
                $z3 ^= ($y3 & $m1);
                
                // Shift y right with reduction polynomial
                $m2 = self::toInt32(($y3 << 31) >> 8);
                $y3 = self::toInt32(self::unsignedRightShift($y3, 1) | ($y2 << 31));
                $y2 = self::toInt32(self::unsignedRightShift($y2, 1) | ($y1 << 31));
                $y1 = self::toInt32(self::unsignedRightShift($y1, 1) | ($y0 << 31));
                $y0 = self::toInt32(self::unsignedRightShift($y0, 1) ^ ($m2 & self::E1));
            }
        }
        
        // Convert result back to bytes
        $result = '';
        $vals = [$z0, $z1, $z2, $z3];
        foreach ($vals as $val) {
            $result .= chr(self::unsignedRightShift($val, 24) & 0xff);
            $result .= chr(self::unsignedRightShift($val, 16) & 0xff);
            $result .= chr(self::unsignedRightShift($val, 8) & 0xff);
            $result .= chr($val & 0xff);
        }
        
        return $result;
    }
    
    /**
     * Increment the rightmost 32 bits of a counter block.
     *
     * @param string $counter Counter block to increment (16 bytes, modified in place)
     */
    public static function increment(string &$counter): void
    {
        $c = 1;
        for ($i = 15; $i >= 12; $i--) {
            $c += ord($counter[$i]);
            $counter[$i] = chr($c & 0xff);
            $c >>= 8;
        }
    }
    
    /**
     * Convert to signed 32-bit integer.
     *
     * @param int $value Value to convert
     * @return int Signed 32-bit integer
     */
    private static function toInt32(int $value): int
    {
        $value = $value & 0xFFFFFFFF;
        if ($value & 0x80000000) {
            return $value | ~0xFFFFFFFF;
        }
        return $value;
    }
    
    /**
     * Unsigned right shift.
     *
     * @param int $value Value to shift
     * @param int $shift Number of bits to shift
     * @return int Result of unsigned right shift
     */
    private static function unsignedRightShift(int $value, int $shift): int
    {
        if ($shift == 0) {
            return $value;
        }
        return ($value >> $shift) & ~(1 << (31 - $shift) << 1);
    }
}
