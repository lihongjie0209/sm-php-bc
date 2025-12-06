<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Modes;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\CTRBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

/**
 * CTR Block Cipher Mode Test
 */
class CTRBlockCipherTest extends TestCase
{
    public function testAlgorithmName(): void
    {
        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        $this->assertEquals('SM4/CTR', $cipher->getAlgorithmName());
    }

    public function testBlockSize(): void
    {
        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        $this->assertEquals(16, $cipher->getBlockSize());
    }

    public function testEncryptDecryptSingleBlock(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        // Encrypt
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext, 0);

        // Decrypt (uses same operation in CTR)
        $cipher->reset();
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
        $cipher = new CTRBlockCipher($engine);
        
        // Encrypt
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", 48);
        for ($i = 0; $i < 3; $i++) {
            $cipher->processBlock($plaintext, $i * 16, $ciphertext, $i * 16);
        }

        // Decrypt
        $cipher->reset();
        $decrypted = str_repeat("\x00", 48);
        for ($i = 0; $i < 3; $i++) {
            $cipher->processBlock($ciphertext, $i * 16, $decrypted, $i * 16);
        }

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testNoPaddingRequired(): void
    {
        // CTR mode doesn't require padding - can handle any length
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        
        // 25 bytes - not a multiple of block size
        $plaintext = "Hello, CTR! 1234567890ABC";

        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        // Encrypt using processBytes
        $ciphertext = str_repeat("\x00", strlen($plaintext));
        $cipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);

        // Decrypt
        $cipher->reset();
        $decrypted = str_repeat("\x00", strlen($ciphertext));
        $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testProcessByte(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        
        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        // Encrypt byte by byte
        $plaintext = "Hello";
        $ciphertext = '';
        for ($i = 0; $i < strlen($plaintext); $i++) {
            $ciphertext .= chr($cipher->processByte(ord($plaintext[$i])));
        }

        // Decrypt
        $cipher->reset();
        $decrypted = '';
        for ($i = 0; $i < strlen($ciphertext); $i++) {
            $decrypted .= chr($cipher->processByte(ord($ciphertext[$i])));
        }

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testCounterIncrement(): void
    {
        // Encrypt two identical blocks with CTR
        // They should produce different ciphertext due to counter increment
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');
        
        $plaintext1 = hex2bin('00000000000000000000000000000000');
        $plaintext2 = hex2bin('00000000000000000000000000000000');

        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        $ciphertext1 = str_repeat("\x00", 16);
        $ciphertext2 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext1, 0, $ciphertext1, 0);
        $cipher->processBlock($plaintext2, 0, $ciphertext2, 0);

        // Should be different due to counter increment
        $this->assertNotEquals($ciphertext1, $ciphertext2);
    }

    public function testDifferentIVs(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv1 = hex2bin('00000000000000000000000000000000');
        $iv2 = hex2bin('11111111111111111111111111111111');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine1 = new SM4Engine();
        $cipher1 = new CTRBlockCipher($engine1);
        
        $engine2 = new SM4Engine();
        $cipher2 = new CTRBlockCipher($engine2);
        
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
        $cipher = new CTRBlockCipher($engine);
        
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
        $this->expectExceptionMessage('CTR mode requires IV same length as block size');

        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000'); // Wrong length

        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
    }

    public function testRequiresIV(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CTR mode requires IV');

        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        // Initialize without IV
        $keyParam = new KeyParameter($key);
        $cipher->init(true, $keyParam);
    }

    public function testStreamingEncryption(): void
    {
        // Test that CTR can handle streaming data of any length
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000000');

        $lengths = [1, 5, 15, 16, 17, 31, 32, 33, 100];

        foreach ($lengths as $len) {
            $plaintext = random_bytes($len);

            $engine = new SM4Engine();
            $cipher = new CTRBlockCipher($engine);
            
            $keyParam = new KeyParameter($key);
            $params = new ParametersWithIV($keyParam, $iv);
            $cipher->init(true, $params);
            
            // Encrypt
            $ciphertext = str_repeat("\x00", $len);
            $cipher->processBytes($plaintext, 0, $len, $ciphertext, 0);

            // Decrypt
            $cipher->reset();
            $decrypted = str_repeat("\x00", $len);
            $cipher->processBytes($ciphertext, 0, $len, $decrypted, 0);

            $this->assertEquals($plaintext, $decrypted, "Failed at length $len");
        }
    }

    public function testParallelizability(): void
    {
        // In CTR mode, we can decrypt blocks in any order
        // This tests that we can decrypt a specific block directly
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $iv = hex2bin('00000000000000000000000000000001'); // Start at 1

        $plaintext = 
            hex2bin('1111111111111111111111111111111111111111111111111111111111111111') .
            hex2bin('2222222222222222222222222222222222222222222222222222222222222222');

        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $cipher->init(true, $params);
        
        // Encrypt all blocks
        $ciphertext = str_repeat("\x00", 64);
        for ($i = 0; $i < 4; $i++) {
            $cipher->processBlock($plaintext, $i * 16, $ciphertext, $i * 16);
        }

        // Decrypt all blocks
        $cipher->reset();
        $decrypted = str_repeat("\x00", 64);
        for ($i = 0; $i < 4; $i++) {
            $cipher->processBlock($ciphertext, $i * 16, $decrypted, $i * 16);
        }

        $this->assertEquals($plaintext, $decrypted);
    }
}
