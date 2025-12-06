<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Paddings;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Paddings\ZeroBytePadding;

class ZeroBytePaddingTest extends TestCase
{
    private ZeroBytePadding $padding;

    protected function setUp(): void
    {
        $this->padding = new ZeroBytePadding();
        $this->padding->init();
    }

    public function testPaddingName(): void
    {
        $this->assertEquals("ZeroByte", $this->padding->getPaddingName());
    }

    public function testAddPadding(): void
    {
        $block = str_repeat("\xFF", 16);
        $data = "Hello";
        
        for ($i = 0; $i < strlen($data); $i++) {
            $block[$i] = $data[$i];
        }
        
        $added = $this->padding->addPadding($block, 5);
        
        $this->assertEquals(11, $added);
        $this->assertEquals("\x00", $block[5]);
        $this->assertEquals("\x00", $block[15]);
    }

    public function testPadCount(): void
    {
        $block = "Hello" . str_repeat("\x00", 11);
        
        $count = $this->padding->padCount($block);
        $this->assertEquals(11, $count);
    }

    public function testFullBlockPadding(): void
    {
        $block = str_repeat("\xFF", 16);
        $added = $this->padding->addPadding($block, 0);
        
        $this->assertEquals(16, $added);
        $this->assertEquals("\x00", $block[0]);
        $this->assertEquals("\x00", $block[15]);
    }

    public function testNoPadding(): void
    {
        // When data ends with non-zero byte
        $block = "0123456789ABCDEF";
        $count = $this->padding->padCount($block);
        
        $this->assertEquals(0, $count);
    }

    public function testDataEndingWithZero(): void
    {
        // This is a limitation of zero byte padding
        // It cannot distinguish between padding and actual zero bytes
        $block = "Hello\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
        $count = $this->padding->padCount($block);
        
        // Will count all trailing zeros as padding
        $this->assertEquals(11, $count);
    }

    public function testRoundTripNonZeroData(): void
    {
        for ($dataLen = 1; $dataLen < 16; $dataLen++) {
            $data = substr("123456789ABCDEF", 0, $dataLen);
            $block = str_repeat("\xFF", 16);
            
            for ($i = 0; $i < $dataLen; $i++) {
                $block[$i] = $data[$i];
            }
            
            $this->padding->addPadding($block, $dataLen);
            $padCount = $this->padding->padCount($block);
            
            $this->assertEquals(16 - $dataLen, $padCount);
            $this->assertEquals($data, substr($block, 0, $dataLen));
        }
    }
}
