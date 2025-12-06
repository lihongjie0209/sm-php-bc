<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Engines;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Params\KeyParameter;

/**
 * SM4Engine Test
 * 
 * Test vectors from GB/T 32907-2016 Appendix A
 */
class SM4EngineTest extends TestCase
{
    public function testAlgorithmName(): void
    {
        $engine = new SM4Engine();
        $this->assertEquals('SM4', $engine->getAlgorithmName());
    }

    public function testBlockSize(): void
    {
        $engine = new SM4Engine();
        $this->assertEquals(16, $engine->getBlockSize());
    }

    public function testStandardTestVector1(): void
    {
        // Test vector from GB/T 32907-2016 Appendix A.1
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $expectedCiphertext = hex2bin('681EDF34D206965E86B3E94F536E4246');

        $engine = new SM4Engine();
        $keyParam = new KeyParameter($key);
        
        // Encrypt
        $engine->init(true, $keyParam);
        $output = str_repeat("\x00", 16);
        $engine->processBlock($plaintext, 0, $output, 0);
        
        $this->assertEquals($expectedCiphertext, $output, 'Encryption failed');

        // Decrypt
        $engine->init(false, $keyParam);
        $decrypted = str_repeat("\x00", 16);
        $engine->processBlock($output, 0, $decrypted, 0);
        
        $this->assertEquals($plaintext, $decrypted, 'Decryption failed');
    }

    public function testStandardTestVector2(): void
    {
        // Test vector from GB/T 32907-2016 Appendix A.2
        // Multiple rounds of encryption
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        
        $engine = new SM4Engine();
        $keyParam = new KeyParameter($key);
        $engine->init(true, $keyParam);

        // First encryption
        $data = $plaintext;
        $output = str_repeat("\x00", 16);
        
        for ($i = 0; $i < 1000000; $i++) {
            $engine->processBlock($data, 0, $output, 0);
            $data = $output;
        }

        $expectedResult = hex2bin('595298C7C6FD271F0402F804C33D3F66');
        $this->assertEquals($expectedResult, $output, '1 million rounds encryption failed');
    }

    public function testEncryptDecryptWithDifferentKeys(): void
    {
        $plaintext = 'Hello SM4 World!';
        
        // Pad to 16 bytes
        $plaintext = str_pad($plaintext, 16, "\x00");

        $key1 = random_bytes(16);
        $key2 = random_bytes(16);

        $engine = new SM4Engine();
        
        // Encrypt with key1
        $engine->init(true, new KeyParameter($key1));
        $ciphertext1 = str_repeat("\x00", 16);
        $engine->processBlock($plaintext, 0, $ciphertext1, 0);

        // Encrypt with key2
        $engine->init(true, new KeyParameter($key2));
        $ciphertext2 = str_repeat("\x00", 16);
        $engine->processBlock($plaintext, 0, $ciphertext2, 0);

        // Ciphertexts should be different
        $this->assertNotEquals($ciphertext1, $ciphertext2);

        // Decrypt with correct keys
        $engine->init(false, new KeyParameter($key1));
        $decrypted1 = str_repeat("\x00", 16);
        $engine->processBlock($ciphertext1, 0, $decrypted1, 0);
        $this->assertEquals($plaintext, $decrypted1);

        $engine->init(false, new KeyParameter($key2));
        $decrypted2 = str_repeat("\x00", 16);
        $engine->processBlock($ciphertext2, 0, $decrypted2, 0);
        $this->assertEquals($plaintext, $decrypted2);
    }

    public function testAllZeroKey(): void
    {
        $key = str_repeat("\x00", 16);
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $engine->init(true, new KeyParameter($key));
        
        $output = str_repeat("\x00", 16);
        $engine->processBlock($plaintext, 0, $output, 0);

        // Decrypt to verify
        $engine->init(false, new KeyParameter($key));
        $decrypted = str_repeat("\x00", 16);
        $engine->processBlock($output, 0, $decrypted, 0);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testAllFFKey(): void
    {
        $key = str_repeat("\xFF", 16);
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $engine->init(true, new KeyParameter($key));
        
        $output = str_repeat("\x00", 16);
        $engine->processBlock($plaintext, 0, $output, 0);

        // Decrypt to verify
        $engine->init(false, new KeyParameter($key));
        $decrypted = str_repeat("\x00", 16);
        $engine->processBlock($output, 0, $decrypted, 0);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testInvalidKeyLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SM4 requires a 128 bit key');

        $engine = new SM4Engine();
        $engine->init(true, new KeyParameter('short'));
    }

    public function testUninitializedEngine(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SM4 not initialised');

        $engine = new SM4Engine();
        $input = str_repeat("\x00", 16);
        $output = str_repeat("\x00", 16);
        $engine->processBlock($input, 0, $output, 0);
    }

    public function testMultipleBlocks(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $engine = new SM4Engine();
        $keyParam = new KeyParameter($key);

        // Encrypt multiple blocks
        $blocks = [
            hex2bin('0123456789ABCDEFFEDCBA9876543210'),
            hex2bin('FEDCBA98765432100123456789ABCDEF'),
            hex2bin('00112233445566778899AABBCCDDEEFF'),
        ];

        $ciphertexts = [];
        $engine->init(true, $keyParam);
        
        foreach ($blocks as $block) {
            $output = str_repeat("\x00", 16);
            $engine->processBlock($block, 0, $output, 0);
            $ciphertexts[] = $output;
        }

        // Decrypt and verify
        $engine->init(false, $keyParam);
        
        foreach ($ciphertexts as $i => $ciphertext) {
            $decrypted = str_repeat("\x00", 16);
            $engine->processBlock($ciphertext, 0, $decrypted, 0);
            $this->assertEquals($blocks[$i], $decrypted, "Block $i mismatch");
        }
    }

    public function testReusability(): void
    {
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');

        $engine = new SM4Engine();
        $keyParam = new KeyParameter($key);

        // First encryption
        $engine->init(true, $keyParam);
        $output1 = str_repeat("\x00", 16);
        $engine->processBlock($plaintext, 0, $output1, 0);

        // Second encryption (reuse engine)
        $output2 = str_repeat("\x00", 16);
        $engine->processBlock($plaintext, 0, $output2, 0);

        // Results should be identical
        $this->assertEquals($output1, $output2);
    }
}
