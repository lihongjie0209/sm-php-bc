<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Paddings;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Paddings\PKCS7Padding;

/**
 * PKCS7Padding Test
 */
class PKCS7PaddingTest extends TestCase
{
    public function testPaddingName(): void
    {
        $padding = new PKCS7Padding();
        $this->assertEquals('PKCS7', $padding->getPaddingName());
    }

    public function testAddPadding1Byte(): void
    {
        $padding = new PKCS7Padding();
        $padding->init();

        $data = str_repeat('A', 15);
        $block = $data . "\x00"; // 16 bytes total
        
        $padCount = $padding->addPadding($block, 15);
        
        $this->assertEquals(1, $padCount);
        $this->assertEquals("\x01", $block[15]);
    }

    public function testAddPadding8Bytes(): void
    {
        $padding = new PKCS7Padding();
        $padding->init();

        $data = str_repeat('A', 8);
        $block = $data . str_repeat("\x00", 8); // 16 bytes total
        
        $padCount = $padding->addPadding($block, 8);
        
        $this->assertEquals(8, $padCount);
        for ($i = 8; $i < 16; $i++) {
            $this->assertEquals("\x08", $block[$i]);
        }
    }

    public function testAddPadding16Bytes(): void
    {
        $padding = new PKCS7Padding();
        $padding->init();

        $block = str_repeat("\x00", 16); // All padding
        
        $padCount = $padding->addPadding($block, 0);
        
        $this->assertEquals(16, $padCount);
        for ($i = 0; $i < 16; $i++) {
            $this->assertEquals("\x10", $block[$i]);
        }
    }

    public function testPadCount1Byte(): void
    {
        $padding = new PKCS7Padding();
        
        $block = str_repeat('A', 15) . "\x01";
        
        $count = $padding->padCount($block);
        $this->assertEquals(1, $count);
    }

    public function testPadCount8Bytes(): void
    {
        $padding = new PKCS7Padding();
        
        $block = str_repeat('A', 8) . str_repeat("\x08", 8);
        
        $count = $padding->padCount($block);
        $this->assertEquals(8, $count);
    }

    public function testPadCount16Bytes(): void
    {
        $padding = new PKCS7Padding();
        
        $block = str_repeat("\x10", 16);
        
        $count = $padding->padCount($block);
        $this->assertEquals(16, $count);
    }

    public function testRoundTrip(): void
    {
        $padding = new PKCS7Padding();
        $padding->init();

        for ($dataLen = 0; $dataLen < 16; $dataLen++) {
            $data = str_repeat('X', $dataLen);
            $block = $data . str_repeat("\x00", 16 - $dataLen);
            
            $padCount = $padding->addPadding($block, $dataLen);
            $retrievedCount = $padding->padCount($block);
            
            $this->assertEquals($padCount, $retrievedCount, "Mismatch at length $dataLen");
            $this->assertEquals(16 - $dataLen, $padCount, "Wrong pad count at length $dataLen");
        }
    }

    public function testInvalidPaddingZero(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pad block corrupted');

        $padding = new PKCS7Padding();
        
        // Last byte is 0, which is invalid
        $block = str_repeat('A', 15) . "\x00";
        
        $padding->padCount($block);
    }

    public function testInvalidPaddingTooLarge(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pad block corrupted');

        $padding = new PKCS7Padding();
        
        // Last byte is 17, larger than block size
        $block = str_repeat('A', 15) . "\x11";
        
        $padding->padCount($block);
    }

    public function testInvalidPaddingInconsistent(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pad block corrupted');

        $padding = new PKCS7Padding();
        
        // Says 5 bytes of padding, but they're not all \x05
        $block = str_repeat('A', 11) . "\x03\x04\x05\x05\x05";
        
        $padding->padCount($block);
    }

    public function testAllPossiblePaddingLengths(): void
    {
        $padding = new PKCS7Padding();
        $padding->init();

        for ($padLen = 1; $padLen <= 16; $padLen++) {
            $dataLen = 16 - $padLen;
            $data = str_repeat(chr($padLen + 64), $dataLen); // Use different chars
            $block = $data . str_repeat("\x00", $padLen);
            
            $padding->addPadding($block, $dataLen);
            
            // Verify padding
            for ($i = $dataLen; $i < 16; $i++) {
                $this->assertEquals(chr($padLen), $block[$i], 
                    "Bad padding byte at position $i for pad length $padLen");
            }
            
            // Verify pad count
            $count = $padding->padCount($block);
            $this->assertEquals($padLen, $count, "Wrong count for pad length $padLen");
        }
    }
}
