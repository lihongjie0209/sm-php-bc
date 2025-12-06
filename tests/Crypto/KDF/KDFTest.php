<?php

declare(strict_types=1);

namespace SmBc\Tests\Crypto\KDF;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\KDF\KDF;

class KDFTest extends TestCase
{
    public function testBasicKDF(): void
    {
        $kdf = new KDF();
        
        // Test with simple input
        $Z = str_repeat("\x01", 32);
        $key = $kdf->deriveKey($Z, 16);
        
        $this->assertEquals(16, strlen($key));
        $this->assertNotEquals(str_repeat("\x00", 16), $key);
    }

    public function testKDFDifferentLengths(): void
    {
        $kdf = new KDF();
        $Z = "shared_secret_test_data_12345";
        
        // Test different output lengths
        $key16 = $kdf->deriveKey($Z, 16);
        $key32 = $kdf->deriveKey($Z, 32);
        $key64 = $kdf->deriveKey($Z, 64);
        
        $this->assertEquals(16, strlen($key16));
        $this->assertEquals(32, strlen($key32));
        $this->assertEquals(64, strlen($key64));
        
        // First 16 bytes should match
        $this->assertEquals($key16, substr($key32, 0, 16));
        $this->assertEquals($key32, substr($key64, 0, 32));
    }

    public function testKDFDeterministic(): void
    {
        $kdf = new KDF();
        $Z = "test_deterministic_input";
        
        $key1 = $kdf->deriveKey($Z, 32);
        $key2 = $kdf->deriveKey($Z, 32);
        
        $this->assertEquals($key1, $key2);
    }

    public function testIsZero(): void
    {
        $this->assertTrue(KDF::isZero(str_repeat("\x00", 10)));
        $this->assertFalse(KDF::isZero("\x00\x00\x01\x00"));
        $this->assertFalse(KDF::isZero("hello"));
        $this->assertTrue(KDF::isZero(""));
    }

    public function testKDFWithLongOutput(): void
    {
        $kdf = new KDF();
        $Z = "input_data";
        
        // Request more than one hash block (SM3 = 32 bytes)
        $key = $kdf->deriveKey($Z, 100);
        
        $this->assertEquals(100, strlen($key));
    }
}
