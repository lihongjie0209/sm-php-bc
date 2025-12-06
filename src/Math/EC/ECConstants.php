<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;

/**
 * Common constants used in elliptic curve cryptography.
 */
class ECConstants
{
    public static BigInteger $ZERO;
    public static BigInteger $ONE;
    public static BigInteger $TWO;
    public static BigInteger $THREE;
    public static BigInteger $FOUR;
    public static BigInteger $EIGHT;

    public static function init(): void
    {
        if (isset(self::$ZERO)) return;
        self::$ZERO = new BigInteger(0);
        self::$ONE = new BigInteger(1);
        self::$TWO = new BigInteger(2);
        self::$THREE = new BigInteger(3);
        self::$FOUR = new BigInteger(4);
        self::$EIGHT = new BigInteger(8);
    }
}

// Initialize constants immediately
ECConstants::init();
