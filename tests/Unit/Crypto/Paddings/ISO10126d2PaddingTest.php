<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Paddings;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Paddings\ISO10126d2Padding;

class ISO10126d2PaddingTest extends TestCase
{
    private ISO10126d2Padding $padding;

    protected function setUp(): void
    {
        $this->padding = new ISO10126d2Padding();
        $this->padding->init();
    }

    public function testPaddingName(): void
    {
        $this->assertEquals("ISO10126-2", $this->padding->getPaddingName());
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
        // Last byte should be the padding length
        $this->assertEquals(chr(11), $block[15]);
    }

    public function testPadCount(): void
    {
        $block = str_repeat("\x00", 16);
        $data = "Hello";
        
        for ($i = 0; $i < strlen($data); $i++) {
            $block[$i] = $data[$i];
        }
        
        $this->padding->addPadding($block, 5);
        $count = $this->padding->padCount($block);
        
        $this->assertEquals(11, $count);
    }

    public function testFullBlockPadding(): void
    {
        $block = str_repeat("\x00", 16);
        $added = $this->padding->addPadding($block, 0);
        
        $this->assertEquals(16, $added);
        $this->assertEquals(chr(16), $block[15]);
    }

    public function testInvalidPaddingTooLarge(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Invalid ISO10126-2 padding");
        
        $block = str_repeat("\x00", 15) . chr(20);
        $this->padding->padCount($block);
    }

    public function testInvalidPaddingZero(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Invalid ISO10126-2 padding");
        
        $block = str_repeat("\x00", 16);
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
