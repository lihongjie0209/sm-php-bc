<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Engines;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Engines\SM2Engine;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;

/**
 * SM2Engine Test
 * 
 * Ported from sm-js-bc/test/unit/crypto/SM2Engine.test.ts
 */
class SM2EngineTest extends TestCase
{
    // SM2 Standard Parameters
    private const P = '0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF';
    private const A = '0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC';
    private const B = '0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93';
    private const N = '0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123';
    private const GX = '0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7';
    private const GY = '0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0';

    private ECCurveFp $curve;
    private ECDomainParameters $domainParams;

    protected function setUp(): void
    {
        $p = new BigInteger(self::P);
        $a = new BigInteger(self::A);
        $b = new BigInteger(self::B);
        $n = new BigInteger(self::N);
        
        $this->curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());
        
        $gx = new BigInteger(self::GX);
        $gy = new BigInteger(self::GY);
        $g = $this->curve->createPoint($gx, $gy);
        
        $this->domainParams = new ECDomainParameters($this->curve, $g, $n, BigInteger::ONE());
    }

    private function generateKeyPair(): array
    {
        // Use fixed private key for testing
        $d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263');
        
        $q = $this->domainParams->getG()->multiply($d)->normalize();
        
        return [
            'private' => new ECPrivateKeyParameters($d, $this->domainParams),
            'public' => new ECPublicKeyParameters($q, $this->domainParams)
        ];
    }

    public function testEncryptDecryptC1C2C3(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Hello SM2!';
        
        // Encrypt
        $encryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C2C3);
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        // Decrypt
        $decryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C2C3);
        $decryptEngine->init(false, $keyPair['private']);
        $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
        
        $this->assertEquals($message, $decrypted);
    }

    public function testEncryptDecryptC1C3C2(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Hello SM2!';
        
        // Encrypt
        $encryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C3C2);
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        // Decrypt
        $decryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C3C2);
        $decryptEngine->init(false, $keyPair['private']);
        $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
        
        $this->assertEquals($message, $decrypted);
    }

    public function testHandleSingleByte(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'A';
        
        $encryptEngine = new SM2Engine();
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        $decryptEngine = new SM2Engine();
        $decryptEngine->init(false, $keyPair['private']);
        $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
        
        $this->assertEquals($message, $decrypted);
    }

    /**
     * @dataProvider messageLengthProvider
     */
    public function testDifferentMessageLengths(int $length): void
    {
        $keyPair = $this->generateKeyPair();
        $message = '';
        for ($i = 0; $i < $length; $i++) {
            $message .= chr($i & 0xff);
        }
        
        $encryptEngine = new SM2Engine();
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        $decryptEngine = new SM2Engine();
        $decryptEngine->init(false, $keyPair['private']);
        $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
        
        $this->assertEquals(bin2hex($message), bin2hex($decrypted));
    }

    public function messageLengthProvider(): array
    {
        return [
            [1],
            [16],
            [32],
            [63],
            [64],
            [65],
            [100],
            [256]
        ];
    }

    public function testModeCompatibility(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Test message';
        
        // Encrypt with C1C2C3
        $encryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C2C3);
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        // Try to decrypt with C1C3C2 (should fail)
        $decryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C3C2);
        $decryptEngine->init(false, $keyPair['private']);
        
        $this->expectException(\RuntimeException::class);
        $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
    }

    public function testOutputSize(): void
    {
        $keyPair = $this->generateKeyPair();
        $engine = new SM2Engine();
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $engine->init(true, $encryptParams);
        
        $inputLengths = [1, 16, 32, 64, 100, 256];
        
        foreach ($inputLengths as $inputLen) {
            // C1(65) + message + C3(32)
            $expectedSize = (1 + 2 * 32) + $inputLen + 32;
            $this->assertEquals($expectedSize, $engine->getOutputSize($inputLen));
        }
    }

    public function testCiphertextStructureC1C2C3(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Test';
        
        $encryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C2C3);
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        // C1 (65 bytes: 0x04 + 32 bytes x + 32 bytes y) + C2 (message length) + C3 (32 bytes)
        $expectedLength = 65 + strlen($message) + 32;
        $this->assertEquals($expectedLength, strlen($ciphertext));
        
        // C1 should start with 0x04 (uncompressed point)
        $this->assertEquals(0x04, ord($ciphertext[0]));
    }

    public function testRandomness(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Same message';
        
        $ciphertexts = [];
        
        for ($i = 0; $i < 3; $i++) {
            $engine = new SM2Engine();
            $params = new ParametersWithRandom($keyPair['public'], new SecureRandom());
            $engine->init(true, $params);
            $ciphertext = $engine->processBlock($message, 0, strlen($message));
            $ciphertexts[] = bin2hex($ciphertext);
        }
        
        // All ciphertexts should be different (due to random k)
        $this->assertNotEquals($ciphertexts[0], $ciphertexts[1]);
        $this->assertNotEquals($ciphertexts[1], $ciphertexts[2]);
        $this->assertNotEquals($ciphertexts[0], $ciphertexts[2]);
    }

    public function testDecryptAllRandomCiphertexts(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Same message';
        
        for ($i = 0; $i < 5; $i++) {
            $encryptEngine = new SM2Engine();
            $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
            $encryptEngine->init(true, $encryptParams);
            $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
            
            $decryptEngine = new SM2Engine();
            $decryptEngine->init(false, $keyPair['private']);
            $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
            
            $this->assertEquals($message, $decrypted);
        }
    }

    public function testCorruptedCiphertext(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Test message';
        
        $encryptEngine = new SM2Engine();
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        // Corrupt one byte
        $ciphertext[strlen($ciphertext) - 1] = chr(ord($ciphertext[strlen($ciphertext) - 1]) ^ 0x01);
        
        $decryptEngine = new SM2Engine();
        $decryptEngine->init(false, $keyPair['private']);
        
        $this->expectException(\RuntimeException::class);
        $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
    }

    public function testTruncatedCiphertext(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = 'Test message';
        
        $encryptEngine = new SM2Engine();
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        // Truncate ciphertext
        $truncated = substr($ciphertext, 0, strlen($ciphertext) - 10);
        
        $decryptEngine = new SM2Engine();
        $decryptEngine->init(false, $keyPair['private']);
        
        $this->expectException(\RuntimeException::class);
        $decryptEngine->processBlock($truncated, 0, strlen($truncated));
    }

    public function testInvalidBufferLength(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = str_repeat('x', 10);
        
        $engine = new SM2Engine();
        $params = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $engine->init(true, $params);
        
        $this->expectException(\InvalidArgumentException::class);
        $engine->processBlock($message, 0, 20); // inLen > buffer
    }

    public function testMessageWithAllZeros(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = str_repeat("\x00", 32);
        
        $encryptEngine = new SM2Engine();
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        $decryptEngine = new SM2Engine();
        $decryptEngine->init(false, $keyPair['private']);
        $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
        
        $this->assertEquals(bin2hex($message), bin2hex($decrypted));
    }

    public function testMessageWithAllFF(): void
    {
        $keyPair = $this->generateKeyPair();
        $message = str_repeat("\xFF", 32);
        
        $encryptEngine = new SM2Engine();
        $encryptParams = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $encryptEngine->init(true, $encryptParams);
        $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
        
        $decryptEngine = new SM2Engine();
        $decryptEngine->init(false, $keyPair['private']);
        $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
        
        $this->assertEquals(bin2hex($message), bin2hex($decrypted));
    }

    public function testReusabilityMultipleEncryptions(): void
    {
        $keyPair = $this->generateKeyPair();
        $engine = new SM2Engine();
        $params = new ParametersWithRandom($keyPair['public'], new SecureRandom());
        $engine->init(true, $params);
        
        $messages = ['First', 'Second', 'Third'];
        
        foreach ($messages as $message) {
            $ciphertext = $engine->processBlock($message, 0, strlen($message));
            
            $decryptEngine = new SM2Engine();
            $decryptEngine->init(false, $keyPair['private']);
            $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
            
            $this->assertEquals($message, $decrypted);
        }
    }

    public function testReusabilityMultipleDecryptions(): void
    {
        $keyPair = $this->generateKeyPair();
        $messages = ['First', 'Second', 'Third'];
        
        $ciphertexts = [];
        foreach ($messages as $message) {
            $engine = new SM2Engine();
            $params = new ParametersWithRandom($keyPair['public'], new SecureRandom());
            $engine->init(true, $params);
            $ciphertexts[] = $engine->processBlock($message, 0, strlen($message));
        }
        
        $decryptEngine = new SM2Engine();
        $decryptEngine->init(false, $keyPair['private']);
        
        foreach ($ciphertexts as $i => $ciphertext) {
            $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
            $this->assertEquals($messages[$i], $decrypted);
        }
    }
}
