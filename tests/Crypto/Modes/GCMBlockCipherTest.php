<?php

namespace SmBc\Tests\Crypto\Modes;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Modes\GCMBlockCipher;
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Params\AEADParameters;
use SmBc\Crypto\Params\KeyParameter;

class GCMBlockCipherTest extends TestCase
{
    /**
     * Test basic GCM encryption and decryption with SM4
     */
    public function testBasicEncryptionDecryption(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $nonce = hex2bin('000000000000000000000000');
        $plaintext = 'Hello, GCM mode!';
        
        // Encrypt
        $cipher = new GCMBlockCipher(new SM4Engine());
        $params = new AEADParameters(new KeyParameter($key), 128, $nonce);
        $cipher->init(true, $params);
        
        $plaintextBytes = $plaintext;
        $ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintextBytes)));
        
        $len = $cipher->processBytes($plaintextBytes, 0, strlen($plaintextBytes), $ciphertext, 0);
        $len += $cipher->doFinal($ciphertext, $len);
        
        $ciphertext = substr($ciphertext, 0, $len);
        
        // Decrypt
        $cipher2 = new GCMBlockCipher(new SM4Engine());
        $cipher2->init(false, $params);
        
        $decrypted = str_repeat("\x00", $cipher2->getOutputSize(strlen($ciphertext)));
        $len = $cipher2->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
        $len += $cipher2->doFinal($decrypted, $len);
        
        $decrypted = substr($decrypted, 0, $len);
        
        $this->assertEquals($plaintext, $decrypted);
    }
    
    /**
     * Test GCM with AAD (Additional Authenticated Data)
     */
    public function testWithAAD(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $nonce = hex2bin('000000000000000000000000');
        $aad = 'Additional data that is authenticated but not encrypted';
        $plaintext = 'Secret message';
        
        // Encrypt with AAD
        $cipher = new GCMBlockCipher(new SM4Engine());
        $params = new AEADParameters(new KeyParameter($key), 128, $nonce, $aad);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
        $len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);
        $len += $cipher->doFinal($ciphertext, $len);
        $ciphertext = substr($ciphertext, 0, $len);
        
        // Decrypt with AAD
        $cipher2 = new GCMBlockCipher(new SM4Engine());
        $cipher2->init(false, $params);
        
        $decrypted = str_repeat("\x00", $cipher2->getOutputSize(strlen($ciphertext)));
        $len = $cipher2->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
        $len += $cipher2->doFinal($decrypted, $len);
        $decrypted = substr($decrypted, 0, $len);
        
        $this->assertEquals($plaintext, $decrypted);
    }
    
    /**
     * Test MAC verification failure with tampered ciphertext
     */
    public function testMACVerificationFailure(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $nonce = hex2bin('000000000000000000000000');
        $plaintext = 'Test message';
        
        // Encrypt
        $cipher = new GCMBlockCipher(new SM4Engine());
        $params = new AEADParameters(new KeyParameter($key), 128, $nonce);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
        $len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);
        $len += $cipher->doFinal($ciphertext, $len);
        $ciphertext = substr($ciphertext, 0, $len);
        
        // Tamper with ciphertext
        $ciphertext[0] = chr(ord($ciphertext[0]) ^ 0x01);
        
        // Try to decrypt - should fail
        $cipher2 = new GCMBlockCipher(new SM4Engine());
        $cipher2->init(false, $params);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('mac check in GCM failed');
        
        $decrypted = str_repeat("\x00", $cipher2->getOutputSize(strlen($ciphertext)));
        $len = $cipher2->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
        $cipher2->doFinal($decrypted, $len);
    }
    
    /**
     * Test empty plaintext
     */
    public function testEmptyPlaintext(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $nonce = hex2bin('000000000000000000000000');
        $plaintext = '';
        
        // Encrypt
        $cipher = new GCMBlockCipher(new SM4Engine());
        $params = new AEADParameters(new KeyParameter($key), 128, $nonce);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
        $len = $cipher->doFinal($ciphertext, 0);
        $ciphertext = substr($ciphertext, 0, $len);
        
        // Should only contain MAC tag (16 bytes)
        $this->assertEquals(16, strlen($ciphertext));
        
        // Decrypt
        $cipher2 = new GCMBlockCipher(new SM4Engine());
        $cipher2->init(false, $params);
        
        $decrypted = str_repeat("\x00", $cipher2->getOutputSize(strlen($ciphertext)));
        $len = $cipher2->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
        $len += $cipher2->doFinal($decrypted, $len);
        $decrypted = substr($decrypted, 0, $len);
        
        $this->assertEquals($plaintext, $decrypted);
    }
    
    /**
     * Test variable MAC sizes
     */
    public function testVariableMACSize(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $nonce = hex2bin('000000000000000000000000');
        $plaintext = 'Test';
        
        // Test with 96-bit (12-byte) MAC
        $cipher = new GCMBlockCipher(new SM4Engine());
        $params = new AEADParameters(new KeyParameter($key), 96, $nonce);
        $cipher->init(true, $params);
        
        $ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
        $len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);
        $len += $cipher->doFinal($ciphertext, $len);
        $ciphertext = substr($ciphertext, 0, $len);
        
        // Ciphertext should be plaintext + 12 bytes MAC
        $this->assertEquals(strlen($plaintext) + 12, strlen($ciphertext));
        
        // Decrypt
        $cipher2 = new GCMBlockCipher(new SM4Engine());
        $cipher2->init(false, $params);
        
        $decrypted = str_repeat("\x00", $cipher2->getOutputSize(strlen($ciphertext)));
        $len = $cipher2->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
        $len += $cipher2->doFinal($decrypted, $len);
        $decrypted = substr($decrypted, 0, $len);
        
        $this->assertEquals($plaintext, $decrypted);
    }
}
