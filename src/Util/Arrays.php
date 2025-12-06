<?php

namespace SmBc\Util;

class Arrays
{
    /**
     * @param string ...$arrays
     * @return string
     */
    public static function concatenate(string ...$arrays): string
    {
        return implode('', $arrays);
    }

    /**
     * @param string $array Passed by reference
     * @param int $value
     * @return void
     */
    public static function fill(string &$array, int $value): void
    {
        $len = strlen($array);
        $array = str_repeat(chr($value & 0xff), $len);
    }

    /**
     * @param string $a
     * @param string $b
     * @return bool
     */
    public static function areEqual(string $a, string $b): bool
    {
        return $a === $b;
    }

    /**
     * @param string $a
     * @param string $b
     * @return bool
     */
    public static function constantTimeAreEqual(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    /**
     * @param string $source
     * @param int $from
     * @param int $to
     * @return string
     */
    public static function copyOfRange(string $source, int $from, int $to): string
    {
        if ($to <= $from) {
            return "";
        }
        $len = $to - $from;
        // PHP substr clamps length if it exceeds string
        $res = substr($source, $from, $len);
        return ($res === false) ? "" : $res;
    }
}
