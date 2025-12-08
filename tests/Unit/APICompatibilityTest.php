<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Engines\SM2Engine;
use SmBc\Crypto\Signers\SM2Signer;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;

/**
 * API Compatibility Tests
 * 
 * Tests to verify the API consistency improvements match Bouncy Castle Java API.
 * Based on sm-js-bc/test/unit/crypto/APICompatibility.test.ts
 */
class APICompatibilityTest extends TestCase
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

    /**
     * Test SM3Digest.reset() with no parameters
     */
    public function testSM3DigestResetNoParameters(): void
    {
        $digest = new SM3Digest();
        $data = 'test data';
        
        $digest->updateBytes($data, 0, strlen($data));
        
        // Call reset with no parameters
        $digest->reset();
        
        // Verify digest is reset to initial state
        $hash1 = str_repeat("\x00", $digest->getDigestSize());
        $digest->doFinal($hash1, 0);
        
        // Create a fresh digest for comparison
        $digest2 = new SM3Digest();
        $hash2 = str_repeat("\x00", $digest2->getDigestSize());
        $digest2->doFinal($hash2, 0);
        
        $this->assertSame(bin2hex($hash1), bin2hex($hash2));
    }

    /**
     * Test SM3Digest.reset(Memoable) to restore state
     */
    public function testSM3DigestResetWithMemoable(): void
    {
        $digest1 = new SM3Digest();
        $data1 = 'first part';
        $digest1->updateBytes($data1, 0, strlen($data1));
        
        // Save state
        $savedState = $digest1->copy();
        
        // Continue with digest1
        $data2 = 'second part';
        $digest1->updateBytes($data2, 0, strlen($data2));
        $hash1 = str_repeat("\x00", $digest1->getDigestSize());
        $digest1->doFinal($hash1, 0);
        
        // Create new digest and restore to saved state
        $digest2 = new SM3Digest();
        $digest2->reset($savedState);
        $digest2->updateBytes($data2, 0, strlen($data2));
        $hash2 = str_repeat("\x00", $digest2->getDigestSize());
        $digest2->doFinal($hash2, 0);
        
        // Both should produce same result
        $this->assertSame(bin2hex($hash1), bin2hex($hash2));
    }

    /**
     * Test SM2Engine::Mode constant array access (Java-style)
     */
    public function testSM2EngineModeStaticAccess(): void
    {
        // Test that we can access Mode as a static constant
        $this->assertIsArray(SM2Engine::Mode);
        $this->assertSame(SM2Engine::MODE_C1C2C3, SM2Engine::Mode['C1C2C3']);
        $this->assertSame(SM2Engine::MODE_C1C3C2, SM2Engine::Mode['C1C3C2']);
    }

    /**
     * Test creating SM2Engine with Mode enum
     */
    public function testSM2EngineWithModeEnum(): void
    {
        // Create engine using Java-style API
        $engine1 = new SM2Engine(null, SM2Engine::Mode['C1C2C3']);
        $this->assertInstanceOf(SM2Engine::class, $engine1);
        
        $engine2 = new SM2Engine(null, SM2Engine::Mode['C1C3C2']);
        $this->assertInstanceOf(SM2Engine::class, $engine2);
        
        // Also verify backward compatibility with MODE_ constants
        $engine3 = new SM2Engine(null, SM2Engine::MODE_C1C2C3);
        $this->assertInstanceOf(SM2Engine::class, $engine3);
    }

    /**
     * Test encrypt/decrypt with different modes using static enum
     */
    public function testSM2EngineEncryptDecryptWithModeEnum(): void
    {
        // Generate key pair
        $keyPair = $this->generateKeyPair();
        $publicKey = $keyPair['public'];
        $privateKey = $keyPair['private'];
        
        // Test with C1C2C3 mode using static enum
        $engine1 = new SM2Engine(null, SM2Engine::Mode['C1C2C3']);
        $engine1->init(true, new ParametersWithRandom($publicKey, new SecureRandom()));
        
        $plaintext = 'Test message';
        $ciphertext = $engine1->processBlock($plaintext, 0, strlen($plaintext));
        
        // Decrypt
        $engine2 = new SM2Engine(null, SM2Engine::Mode['C1C2C3']);
        $engine2->init(false, $privateKey);
        $decrypted = $engine2->processBlock($ciphertext, 0, strlen($ciphertext));
        
        $this->assertSame('Test message', $decrypted);
    }

    /**
     * Test SM2Signer has createBasePointMultiplier method available for subclassing
     */
    public function testSM2SignerHasCreateBasePointMultiplier(): void
    {
        $signer = new SM2Signer();
        
        // Use reflection to verify the protected method exists
        $reflection = new \ReflectionClass($signer);
        $this->assertTrue($reflection->hasMethod('createBasePointMultiplier'));
        
        $method = $reflection->getMethod('createBasePointMultiplier');
        $this->assertTrue($method->isProtected());
        
        // Make method accessible and verify it's callable
        $method->setAccessible(true);
        $multiplier = $method->invoke($signer);
        
        // In PHP implementation, this returns null as we use ECPoint's built-in multiply
        // But the method exists for API compatibility
        $this->assertNull($multiplier);
    }

    /**
     * Test SM2Signer has calculateE method available for subclassing
     */
    public function testSM2SignerHasCalculateE(): void
    {
        $signer = new SM2Signer();
        
        // Use reflection to verify the protected method exists
        $reflection = new \ReflectionClass($signer);
        $this->assertTrue($reflection->hasMethod('calculateE'));
        
        $method = $reflection->getMethod('calculateE');
        $this->assertTrue($method->isProtected());
        
        // Make method accessible and test it
        $method->setAccessible(true);
        
        $n = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123');
        $message = pack('C*', 1, 2, 3, 4);
        
        $e = $method->invoke($signer, $n, $message);
        
        $this->assertInstanceOf(BigInteger::class, $e);
        $this->assertTrue($e->compareTo(BigInteger::ZERO()) >= 0);
        $this->assertTrue($e->compareTo($n) < 0);
    }

    /**
     * Test SM2Signer works with standard signing flow (integration test)
     */
    public function testSM2SignerStandardFlow(): void
    {
        // Generate key pair
        $keyPair = $this->generateKeyPair();
        
        $message = 'Test message for signing';
        
        // Sign
        $signer = new SM2Signer();
        $signer->init(true, new ParametersWithRandom($keyPair['private'], new SecureRandom()));
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        $this->assertIsString($signature);
        $this->assertGreaterThan(0, strlen($signature));
        
        // Verify
        $verifier = new SM2Signer();
        $verifier->init(false, $keyPair['public']);
        $verifier->updateBytes($message, 0, strlen($message));
        $isValid = $verifier->verifySignature($signature);
        
        $this->assertTrue($isValid);
    }

    /**
     * Test API method naming consistency - getAlgorithmName() not algorithmName property
     */
    public function testSM3DigestMethodNamingConsistency(): void
    {
        $digest = new SM3Digest();
        
        // Java-style method call
        $name = $digest->getAlgorithmName();
        $this->assertSame('SM3', $name);
        
        // Verify it's a method, not a property
        $reflection = new \ReflectionClass($digest);
        $this->assertTrue($reflection->hasMethod('getAlgorithmName'));
        $this->assertFalse($reflection->hasProperty('algorithmName'));
    }

    /**
     * Test API method naming consistency - getDigestSize() not digestSize property
     */
    public function testSM3DigestSizeMethodNaming(): void
    {
        $digest = new SM3Digest();
        
        // Java-style method call
        $size = $digest->getDigestSize();
        $this->assertSame(32, $size);
        
        // Verify it's a method, not a property
        $reflection = new \ReflectionClass($digest);
        $this->assertTrue($reflection->hasMethod('getDigestSize'));
        $this->assertFalse($reflection->hasProperty('digestSize'));
    }

    /**
     * Test API method naming consistency - getByteLength()
     */
    public function testSM3DigestByteLengthMethodNaming(): void
    {
        $digest = new SM3Digest();
        
        // Java-style method call
        $length = $digest->getByteLength();
        $this->assertSame(64, $length);
        
        // Verify it's a method, not a property
        $reflection = new \ReflectionClass($digest);
        $this->assertTrue($reflection->hasMethod('getByteLength'));
    }
}
