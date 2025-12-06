<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Modes;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\ECBBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use RuntimeException;

/**
 * Test ECB (Electronic Codebook) mode
 * 
 * Note: ECB mode is insecure and these tests are only for verification
 * that the implementation correctly follows the ECB specification.
 */
class ECBBlockCipherTest extends TestCase
{
    /**
     * Test algorithm name
     */
    public function testAlgorithmName(): void
    {
        $cipher = new ECBBlockCipher(new SM4Engine());
        $this->assertSame('SM4/ECB', $cipher->getAlgorithmName());
    }

    /**
     * Test block size
     */
    public function testBlockSize(): void
    {
        $cipher = new ECBBlockCipher(new SM4Engine());
        $this->assertSame(16, $cipher->getBlockSize());
    }

    /**
     * Test encrypt/decrypt single block
     */
    public function testEncryptDecryptSingleBlock(): void
    {
        // Key and plaintext
        $key = str_repeat("\x01", 16);
        $plaintext = "\x01\x23\x45\x67\x89\xAB\xCD\xEF\xFE\xDC\xBA\x98\x76\x54\x32\x10";

        // Encrypt
        $cipher = new ECBBlockCipher(new SM4Engine());
        $cipher->init(true, new KeyParameter($key));
        
        $ciphertext = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext, 0);

        // Verify ciphertext is different from plaintext
        $this->assertNotEquals($plaintext, $ciphertext);

        // Decrypt
        $cipher->init(false, new KeyParameter($key));
        $decrypted = str_repeat("\x00", 16);
        $cipher->processBlock($ciphertext, 0, $decrypted, 0);

        // Verify round-trip
        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test that identical blocks produce identical ciphertext (ECB property)
     * This is a WEAKNESS, not a feature!
     */
    public function testIdenticalBlocksProduceIdenticalCiphertext(): void
    {
        $key = str_repeat("\x01", 16);
        $block1 = "\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10";
        $block2 = $block1; // Identical block

        $cipher = new ECBBlockCipher(new SM4Engine());
        $cipher->init(true, new KeyParameter($key));

        $ciphertext1 = str_repeat("\x00", 16);
        $ciphertext2 = str_repeat("\x00", 16);

        $cipher->processBlock($block1, 0, $ciphertext1, 0);
        $cipher->processBlock($block2, 0, $ciphertext2, 0);

        // In ECB mode, identical plaintext blocks produce identical ciphertext
        $this->assertSame($ciphertext1, $ciphertext2);
    }

    /**
     * Test multiple blocks encryption/decryption
     */
    public function testEncryptDecryptMultipleBlocks(): void
    {
        $key = str_repeat("\x01", 16);
        $plaintext = '';
        for ($i = 0; $i < 48; $i++) {
            $plaintext .= chr($i);
        }

        // Encrypt
        $cipher = new ECBBlockCipher(new SM4Engine());
        $cipher->init(true, new KeyParameter($key));

        $ciphertext = str_repeat("\x00", 48);
        for ($i = 0; $i < 3; $i++) {
            $cipher->processBlock($plaintext, $i * 16, $ciphertext, $i * 16);
        }

        // Decrypt
        $cipher->init(false, new KeyParameter($key));
        $decrypted = str_repeat("\x00", 48);
        for ($i = 0; $i < 3; $i++) {
            $cipher->processBlock($ciphertext, $i * 16, $decrypted, $i * 16);
        }

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test reset functionality
     */
    public function testReset(): void
    {
        $key = str_repeat("\x01", 16);
        $plaintext = str_repeat("\x42", 16);

        $cipher = new ECBBlockCipher(new SM4Engine());
        $cipher->init(true, new KeyParameter($key));

        $ciphertext1 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext1, 0);

        // Reset and encrypt again
        $cipher->reset();
        $ciphertext2 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext2, 0);

        // Should produce same result
        $this->assertSame($ciphertext1, $ciphertext2);
    }

    /**
     * Test input buffer too short
     */
    public function testInputBufferTooShort(): void
    {
        $cipher = new ECBBlockCipher(new SM4Engine());
        $cipher->init(true, new KeyParameter(str_repeat("\x00", 16)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Input buffer too short');

        $shortInput = str_repeat("\x00", 10); // Only 10 bytes
        $output = str_repeat("\x00", 16);
        $cipher->processBlock($shortInput, 0, $output, 0);
    }

    /**
     * Test output buffer too short
     */
    public function testOutputBufferTooShort(): void
    {
        $cipher = new ECBBlockCipher(new SM4Engine());
        $cipher->init(true, new KeyParameter(str_repeat("\x00", 16)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Output buffer too short');

        $input = str_repeat("\x00", 16);
        $shortOutput = str_repeat("\x00", 10); // Only 10 bytes
        $cipher->processBlock($input, 0, $shortOutput, 0);
    }

    /**
     * Test getting underlying cipher
     */
    public function testGetUnderlyingCipher(): void
    {
        $sm4 = new SM4Engine();
        $cipher = new ECBBlockCipher($sm4);
        
        $this->assertSame($sm4, $cipher->getUnderlyingCipher());
    }
}
