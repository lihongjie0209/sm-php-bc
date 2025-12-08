<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Engines;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Engines\ZUCEngine;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

class ZUCEngineTest extends TestCase
{
    public function testAlgorithmName(): void
    {
        $engine = new ZUCEngine();
        $this->assertEquals('ZUC-128', $engine->getAlgorithmName());
    }

    public function testInitRequiresIV(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ZUC init parameters must include an IV');

        $engine = new ZUCEngine();
        $key = str_repeat("\x00", 16);
        $engine->init(true, new KeyParameter($key));
    }

    public function testInitRequires128BitKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ZUC requires a 128-bit key');

        $engine = new ZUCEngine();
        $key = str_repeat("\x00", 15); // Wrong length
        $iv = str_repeat("\x00", 16);
        $engine->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
    }

    public function testInitRequires128BitIV(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ZUC requires a 128-bit IV');

        $engine = new ZUCEngine();
        $key = str_repeat("\x00", 16);
        $iv = str_repeat("\x00", 15); // Wrong length
        $engine->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
    }

    public function testBasicEncryption(): void
    {
        $engine = new ZUCEngine();
        
        // Test vector from specification
        $key = str_repeat("\x00", 16);
        $iv = str_repeat("\x00", 16);
        
        $engine->init(true, new ParametersWithIV(new KeyParameter($key), $iv));

        // Encrypt some bytes
        $plaintext = "Hello, ZUC!";
        $ciphertext = str_repeat("\x00", strlen($plaintext));
        
        $engine->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);
        
        // Ciphertext should be different from plaintext
        $this->assertNotEquals($plaintext, $ciphertext);
        
        // Reset and decrypt
        $engine->reset();
        $decrypted = str_repeat("\x00", strlen($ciphertext));
        $engine->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testReturnByte(): void
    {
        $engine = new ZUCEngine();
        
        $key = str_repeat("\x00", 16);
        $iv = str_repeat("\x00", 16);
        
        $engine->init(true, new ParametersWithIV(new KeyParameter($key), $iv));

        $input = 0x42;
        $output = $engine->returnByte($input);
        
        // Output should be different (XORed with keystream)
        $this->assertIsInt($output);
        $this->assertGreaterThanOrEqual(0, $output);
        $this->assertLessThanOrEqual(255, $output);
    }

    public function testReset(): void
    {
        $engine = new ZUCEngine();
        
        $key = str_repeat("\x00", 16);
        $iv = str_repeat("\x00", 16);
        
        $engine->init(true, new ParametersWithIV(new KeyParameter($key), $iv));

        // Get some keystream bytes
        $byte1 = $engine->returnByte(0);
        $byte2 = $engine->returnByte(0);
        
        // Reset
        $engine->reset();
        
        // Should get same keystream after reset
        $byte1Reset = $engine->returnByte(0);
        $byte2Reset = $engine->returnByte(0);
        
        $this->assertEquals($byte1, $byte1Reset);
        $this->assertEquals($byte2, $byte2Reset);
    }

    public function testProcessBytesThrowsOnShortInput(): void
    {
        $this->expectException(\LengthException::class);
        $this->expectExceptionMessage('Input buffer too short');

        $engine = new ZUCEngine();
        $key = str_repeat("\x00", 16);
        $iv = str_repeat("\x00", 16);
        $engine->init(true, new ParametersWithIV(new KeyParameter($key), $iv));

        $input = "short";
        $output = str_repeat("\x00", 10);
        $engine->processBytes($input, 0, 10, $output, 0); // Try to process more than available
    }

    public function testProcessBytesThrowsOnShortOutput(): void
    {
        $this->expectException(\LengthException::class);
        $this->expectExceptionMessage('Output buffer too short');

        $engine = new ZUCEngine();
        $key = str_repeat("\x00", 16);
        $iv = str_repeat("\x00", 16);
        $engine->init(true, new ParametersWithIV(new KeyParameter($key), $iv));

        $input = str_repeat("x", 10);
        $output = "short";
        $engine->processBytes($input, 0, 10, $output, 0); // Output too short
    }
}
