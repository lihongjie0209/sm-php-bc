<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Paddings;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Paddings\ISO7816d4Padding;

class ISO7816d4PaddingTest extends TestCase
{
    private ISO7816d4Padding $padding;

    protected function setUp(): void
    {
        $this->padding = new ISO7816d4Padding();
        $this->padding->init();
    }

    public function testPaddingName(): void
    {
        $this->assertEquals("ISO7816-4", $this->padding->getPaddingName());
    }

    public function testAddPadding(): void
    {
        $block = str_repeat("\x00", 16);
        $data = "Hello";
        
        for ($i = 0; $i < strlen($data); $i++) {
            $block[$i] = $data[$i];
        }
        
        $added = $this->padding->addPadding($block, 5);
        
        $this->assertEquals(11, $added);
        $this->assertEquals("\x80", $block[5]);
        $this->assertEquals("\x00", $block[6]);
        $this->assertEquals("\x00", $block[15]);
    }

    public function testPadCount(): void
    {
        $block = "Hello\x80" . str_repeat("\x00", 10);
        
        $count = $this->padding->padCount($block);
        $this->assertEquals(11, $count);
    }

    public function testFullBlockPadding(): void
    {
        $block = str_repeat("\x00", 16);
        $added = $this->padding->addPadding($block, 0);
        
        $this->assertEquals(16, $added);
        $this->assertEquals("\x80", $block[0]);
    }

    public function testInvalidPadding(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Invalid ISO7816-4 padding");
        
        $block = "Hello" . str_repeat("\x00", 11);
        $this->padding->padCount($block);
    }

    public function testRoundTrip(): void
    {
        for ($dataLen = 0; $dataLen < 16; $dataLen++) {
            $data = substr("0123456789ABCDEF", 0, $dataLen);
            $block = str_repeat("\x00", 16);
            
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
