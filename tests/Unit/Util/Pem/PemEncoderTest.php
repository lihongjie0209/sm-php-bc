<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Util\Pem;

use PHPUnit\Framework\TestCase;
use SmBc\Util\Pem\PemEncoder;

class PemEncoderTest extends TestCase
{
    public function testEncodeDecodeBasic(): void
    {
        $data = 'Hello, World!';
        $type = 'TEST DATA';
        
        $pem = PemEncoder::encode($data, $type);
        
        $this->assertStringContainsString('-----BEGIN TEST DATA-----', $pem);
        $this->assertStringContainsString('-----END TEST DATA-----', $pem);
        
        $decoded = PemEncoder::decode($pem);
        
        $this->assertSame($type, $decoded['type']);
        $this->assertSame($data, $decoded['data']);
    }

    public function testEncode64CharLines(): void
    {
        // Create data that will result in more than 64 base64 characters
        $data = str_repeat('A', 100);
        $pem = PemEncoder::encode($data, 'TEST');
        
        $lines = explode("\n", $pem);
        
        // Check that content lines (not BEGIN/END) are max 64 chars
        foreach ($lines as $line) {
            if (strpos($line, '-----') !== 0 && !empty(trim($line))) {
                $this->assertLessThanOrEqual(64, strlen(trim($line)));
            }
        }
    }

    public function testDecodeInvalidPemNoBegin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('missing BEGIN marker');
        
        PemEncoder::decode('Not a PEM string');
    }

    public function testDecodeInvalidPemNoContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no content found');
        
        $pem = "-----BEGIN TEST-----\n-----END TEST-----\n";
        PemEncoder::decode($pem);
    }

    public function testDecodeInvalidBase64(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('base64 decode failed');
        
        $pem = "-----BEGIN TEST-----\n!!!invalid base64!!!\n-----END TEST-----\n";
        PemEncoder::decode($pem);
    }

    public function testIsPem(): void
    {
        $pem = "-----BEGIN TEST-----\ndata\n-----END TEST-----\n";
        $this->assertTrue(PemEncoder::isPem($pem));
        
        $notPem = "Just a regular string";
        $this->assertFalse(PemEncoder::isPem($notPem));
    }

    public function testEncodeDecodeBinaryData(): void
    {
        // Test with binary data
        $data = pack('C*', 0x01, 0x02, 0x03, 0xFF, 0xFE, 0xFD);
        $pem = PemEncoder::encode($data, 'BINARY DATA');
        
        $decoded = PemEncoder::decode($pem);
        
        $this->assertSame($data, $decoded['data']);
        $this->assertSame('BINARY DATA', $decoded['type']);
    }

    public function testDecodeWithWhitespace(): void
    {
        // PEM with extra whitespace should still decode
        $data = 'test data';
        $pem = "-----BEGIN TEST-----\n  " . base64_encode($data) . "  \n-----END TEST-----\n";
        
        $decoded = PemEncoder::decode($pem);
        
        $this->assertSame($data, $decoded['data']);
    }
}
