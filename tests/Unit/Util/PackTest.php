<?php

namespace SmBc\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use SmBc\Util\Pack;

class PackTest extends TestCase
{
    public function testBigEndianToInt(): void
    {
        $bytes = "\x01\x02\x03\x04";
        $result = Pack::bigEndianToInt($bytes, 0);
        $this->assertEquals(0x01020304, $result);
    }

    public function testBigEndianToIntOffset(): void
    {
        $bytes = "\xff\x01\x02\x03\x04\xff";
        $result = Pack::bigEndianToInt($bytes, 1);
        $this->assertEquals(0x01020304, $result);
    }

    public function testBigEndianToIntMax(): void
    {
        $bytes = "\xff\xff\xff\xff";
        $result = Pack::bigEndianToInt($bytes, 0);
        // In PHP 64-bit, this is 0xFFFFFFFF (4294967295), which is > 0.
        // In JS Int32, this is -1.
        // We stick to unsigned 32-bit interpretation in PHP (subset of 64-bit int).
        $this->assertEquals(0xFFFFFFFF, $result);
    }

    public function testIntToBigEndian(): void
    {
        $bytes = str_repeat("\x00", 4);
        Pack::intToBigEndian(0x01020304, $bytes, 0);
        $this->assertEquals("\x01\x02\x03\x04", $bytes);
    }

    public function testIntToBigEndianOffset(): void
    {
        $bytes = str_repeat("\x00", 6);
        Pack::intToBigEndian(0x01020304, $bytes, 1);
        $this->assertEquals("\x00\x01\x02\x03\x04\x00", $bytes);
    }

    public function testIntToBigEndianNegative(): void
    {
        $bytes = str_repeat("\x00", 4);
        // -1 in PHP 64-bit is ...FFFFFFFF.
        // We only take the lowest 32 bits.
        Pack::intToBigEndian(-1, $bytes, 0);
        $this->assertEquals("\xff\xff\xff\xff", $bytes);
    }

    public function testBigEndianToLong(): void
    {
        $bytes = "\x01\x02\x03\x04\x05\x06\x07\x08";
        $result = Pack::bigEndianToLong($bytes, 0);
        $this->assertEquals(0x0102030405060708, $result);
    }

    public function testLongToBigEndian(): void
    {
        $bytes = str_repeat("\x00", 8);
        Pack::longToBigEndian(0x0102030405060708, $bytes, 0);
        $this->assertEquals("\x01\x02\x03\x04\x05\x06\x07\x08", $bytes);
    }
}
