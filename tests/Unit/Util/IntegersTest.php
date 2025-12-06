<?php

namespace SmBc\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use SmBc\Util\Integers;

class IntegersTest extends TestCase
{
    public function testRotateLeft(): void
    {
        // 0x80000000 rotated left by 1 is 1
        $val = 0x80000000;
        $this->assertEquals(1, Integers::rotateLeft($val, 1));

        // 0x80000000 rotated left by 0 is 0x80000000
        $this->assertEquals(0x80000000, Integers::rotateLeft($val, 0));
    }

    public function testRotateRight(): void
    {
        // 1 rotated right by 1 is 0x80000000
        $val = 1;
        $this->assertEquals(0x80000000, Integers::rotateRight($val, 1));
    }

    public function testNumberOfLeadingZeros(): void
    {
        $this->assertEquals(32, Integers::numberOfLeadingZeros(0));
        $this->assertEquals(0, Integers::numberOfLeadingZeros(0xFFFFFFFF));
        $this->assertEquals(31, Integers::numberOfLeadingZeros(1));
        $this->assertEquals(1, Integers::numberOfLeadingZeros(0x40000000));
    }
}
