<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Modes;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

/**
 * CBC Block Cipher Mode Test
 */
class CBCBlockCipherTest extends TestCase
{
    public function testAlgorithmName(): void
    {
        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        $this->assertEquals('SM4/CBC', $cipher->getAlgorithmName());
    }

    public function testBlockSize(): void
    {
        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        $this->assertEquals(16, $cipher->getBlockSize());
    }

    public function testEncryptDecryptSingleBlock(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        // Encrypt
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext, 0);

        // Decrypt
        $cipher->init(false, $params);
        $decrypted = str_repeat("\x00", 16);
        $cipher->processBlock($ciphertext, 0, $decrypted, 0);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testEncryptDecryptMultipleBlocks(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        
        $plaintext = 
            hex2bin('0123456789ABCDEFFEDCBA9876543210') .
            hex2bin('FEDCBA98765432100123456789ABCDEF') .
            hex2bin('00112233445566778899AABBCCDDEEFF');

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        // Encrypt
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", 48);
        for ($i = 0; $i < 3; $i++) {
            $cipher->processBlock($plaintext, $i * 16, $ciphertext, $i * 16);
        }

        // Decrypt
        $cipher->init(false, $params);
        $decrypted = str_repeat("\x00", 48);
        for ($i = 0; $i < 3; $i++) {
            $cipher->processBlock($ciphertext, $i * 16, $decrypted, $i * 16);
        }

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testCBCChaining(): void
    {
        // In CBC mode, each block affects the next
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        
        $plaintext1 = hex2bin('00000000000000000000000000000000');
        $plaintext2 = hex2bin('00000000000000000000000000000000');

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        // Encrypt two identical blocks
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        $ciphertext1 = str_repeat("\x00", 16);
        $ciphertext2 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext1, 0, $ciphertext1, 0);
        $cipher->processBlock($plaintext2, 0, $ciphertext2, 0);

        // Ciphertext should be different due to CBC chaining
        $this->assertNotEquals($ciphertext1, $ciphertext2);
    }

    public function testDifferentIVs(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv1 = hex2bin('00000000000000000000000000000000');
        $iv2 = hex2bin('11111111111111111111111111111111');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine1 = new SM4Engine();
        $cipher1 = new CBCBlockCipher($engine1);
        
        $engine2 = new SM4Engine();
        $cipher2 = new CBCBlockCipher($engine2);
        
        // Encrypt with IV1
        $keyParam = new KeyParameter($key);
        $params1 = new ParametersWithIV($keyParam, $iv1);
        $cipher1->init(true, $params1);
        $ciphertext1 = str_repeat("\x00", 16);
        $cipher1->processBlock($plaintext, 0, $ciphertext1, 0);

        // Encrypt with IV2
        $params2 = new ParametersWithIV($keyParam, $iv2);
        $cipher2->init(true, $params2);
        $ciphertext2 = str_repeat("\x00", 16);
        $cipher2->processBlock($plaintext, 0, $ciphertext2, 0);

        // Different IVs should produce different ciphertext
        $this->assertNotEquals($ciphertext1, $ciphertext2);
    }

    public function testReset(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        // First encryption
        $ciphertext1 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext1, 0);

        // Reset and encrypt again
        $cipher->reset();
        $ciphertext2 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext2, 0);

        // Should produce same ciphertext after reset
        $this->assertEquals($ciphertext1, $ciphertext2);
    }

    public function testInvalidIVLength(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Initialization vector must be the same length as block size');

        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000'); // Wrong length

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
    }

    public function testWithoutIV(): void
    {
        // When no IV is provided, it should default to all zeros
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        
        // Initialize without IV
        $keyParam = new KeyParameter($key);
        $cipher->init(true, $keyParam);
        
        $ciphertext = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext, 0);

        // Should work without throwing
        $this->assertNotEquals($plaintext, $ciphertext);
    }
}
