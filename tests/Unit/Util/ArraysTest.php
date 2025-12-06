<?php

namespace SmBc\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use SmBc\Util\Arrays;

class ArraysTest extends TestCase
{
    public function testConcatenate(): void
    {
        $a = "abc";
        $b = "def";
        $c = "ghi";
        $this->assertEquals("abcdefghi", Arrays::concatenate($a, $b, $c));
    }

    public function testFill(): void
    {
        $a = "aaaa";
        Arrays::fill($a, 0x62); // 'b'
        $this->assertEquals("bbbb", $a);
    }

    public function testAreEqual(): void
    {
        $this->assertTrue(Arrays::areEqual("abc", "abc"));
        $this->assertFalse(Arrays::areEqual("abc", "abd"));
    }

    public function testConstantTimeAreEqual(): void
    {
        $this->assertTrue(Arrays::constantTimeAreEqual("abc", "abc"));
        $this->assertFalse(Arrays::constantTimeAreEqual("abc", "abd"));
    }

    public function testCopyOfRange(): void
    {
        $src = "abcdef";
        $this->assertEquals("bc", Arrays::copyOfRange($src, 1, 3));
        // Clamp behavior
        $this->assertEquals("ef", Arrays::copyOfRange($src, 4, 10));
    }
}
