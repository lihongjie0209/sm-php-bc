<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Modes;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\OFBBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

class OFBBlockCipherTest extends TestCase
{
    public function testAlgorithmName(): void
    {
        $engine = new SM4Engine();
        $cipher = new OFBBlockCipher($engine, 128);
        
        $this->assertSame('SM4/OFB128', $cipher->getAlgorithmName());
    }
    
    public function testBlockSize(): void
    {
        $engine = new SM4Engine();
        $cipher = new OFBBlockCipher($engine, 128);
        
        $this->assertSame(16, $cipher->getBlockSize());
    }
    
    public function testEncryptDecryptSingleBlock(): void
    {
        $key = str_repeat("\x01", 16);
        $iv = str_repeat("\x02", 16);
        $plaintext = "Hello OFB Mode!";
        $plaintext .= str_repeat("\x00", 16 - strlen($plaintext));
        
        // Encrypt
        $engine = new SM4Engine();
        $cipher = new OFBBlockCipher($engine, 128);
        $cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $ciphertext = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext, 0);
        
        $this->assertNotEquals($plaintext, $ciphertext);
        
        // Decrypt
        $cipher2 = new OFBBlockCipher(new SM4Engine(), 128);
        $cipher2->init(false, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $decrypted = str_repeat("\x00", 16);
        $cipher2->processBlock($ciphertext, 0, $decrypted, 0);
        
        $this->assertEquals($plaintext, $decrypted);
    }
    
    public function testStreamCipherBehavior(): void
    {
        $key = str_repeat("\x01", 16);
        $iv = str_repeat("\x02", 16);
        $plaintext = "ABCDEFGHIJKLMNOP";
        
        // Encrypt
        $engine = new SM4Engine();
        $cipher = new OFBBlockCipher($engine, 128);
        $cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $ciphertext = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext, 0);
        
        // Decrypt (same as encrypt in OFB)
        $cipher2 = new OFBBlockCipher(new SM4Engine(), 128);
        $cipher2->init(false, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $decrypted = str_repeat("\x00", 16);
        $cipher2->processBlock($ciphertext, 0, $decrypted, 0);
        
        $this->assertEquals($plaintext, $decrypted);
    }
    
    public function testDifferentIVsProduceDifferentCiphertext(): void
    {
        $key = str_repeat("\x01", 16);
        $iv1 = str_repeat("\x02", 16);
        $iv2 = str_repeat("\x03", 16);
        $plaintext = "Test plaintext!!";
        
        $engine1 = new SM4Engine();
        $cipher1 = new OFBBlockCipher($engine1, 128);
        $cipher1->init(true, new ParametersWithIV(new KeyParameter($key), $iv1));
        $ciphertext1 = str_repeat("\x00", 16);
        $cipher1->processBlock($plaintext, 0, $ciphertext1, 0);
        
        $engine2 = new SM4Engine();
        $cipher2 = new OFBBlockCipher($engine2, 128);
        $cipher2->init(true, new ParametersWithIV(new KeyParameter($key), $iv2));
        $ciphertext2 = str_repeat("\x00", 16);
        $cipher2->processBlock($plaintext, 0, $ciphertext2, 0);
        
        $this->assertNotEquals($ciphertext1, $ciphertext2);
    }
    
    public function testReset(): void
    {
        $key = str_repeat("\x01", 16);
        $iv = str_repeat("\x02", 16);
        $plaintext = "Test plaintext!!";
        
        $engine = new SM4Engine();
        $cipher = new OFBBlockCipher($engine, 128);
        $cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $ciphertext1 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext1, 0);
        
        $cipher->reset();
        
        $ciphertext2 = str_repeat("\x00", 16);
        $cipher->processBlock($plaintext, 0, $ciphertext2, 0);
        
        $this->assertEquals($ciphertext1, $ciphertext2);
    }
    
    public function testGetCurrentIV(): void
    {
        $key = str_repeat("\x01", 16);
        $iv = str_repeat("\x02", 16);
        
        $engine = new SM4Engine();
        $cipher = new OFBBlockCipher($engine, 128);
        $cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $currentIV = $cipher->getCurrentIV();
        $this->assertEquals($iv, $currentIV);
    }
}
