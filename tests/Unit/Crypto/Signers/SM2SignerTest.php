<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Signers;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Signers\SM2Signer;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Crypto\Params\ParametersWithID;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;

/**
 * SM2Signer Test
 * 
 * Ported from sm-js-bc/test/unit/crypto/SM2Signer.test.ts
 */
class SM2SignerTest extends TestCase
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
    private ECPrivateKeyParameters $privateKey;
    private ECPublicKeyParameters $publicKey;

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
        
        // Generate test key pair
        $d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263');
        $this->privateKey = new ECPrivateKeyParameters($d, $this->domainParams);
        
        // Calculate public key Q = [d]G
        $Q = $g->multiply($d)->normalize();
        $this->publicKey = new ECPublicKeyParameters($Q, $this->domainParams);
    }

    public function testAlgorithmName(): void
    {
        $signer = new SM2Signer();
        $this->assertEquals('SM2', $signer->getAlgorithmName());
    }

    public function testInitializeForSigning(): void
    {
        $signer = new SM2Signer();
        $signer->init(true, $this->privateKey);
        
        // Should not throw
        $this->assertTrue(true);
    }

    public function testInitializeForVerification(): void
    {
        $signer = new SM2Signer();
        $signer->init(false, $this->publicKey);
        
        // Should not throw
        $this->assertTrue(true);
    }

    public function testInitializeWithRandom(): void
    {
        $signer = new SM2Signer();
        $random = new SecureRandom();
        $paramsWithRandom = new ParametersWithRandom($this->privateKey, $random);
        
        $signer->init(true, $paramsWithRandom);
        
        // Should not throw
        $this->assertTrue(true);
    }

    public function testInitializeWithUserID(): void
    {
        $signer = new SM2Signer();
        $userID = 'testuser@example.com';
        $paramsWithID = new ParametersWithID($this->privateKey, $userID);
        
        $signer->init(true, $paramsWithID);
        
        // Should not throw
        $this->assertTrue(true);
    }

    public function testSigningWithPublicKeyThrows(): void
    {
        $signer = new SM2Signer();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Signing requires ECPrivateKeyParameters');
        
        $signer->init(true, $this->publicKey);
    }

    public function testVerificationWithPrivateKeyThrows(): void
    {
        $signer = new SM2Signer();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Verification requires ECPublicKeyParameters');
        
        $signer->init(false, $this->privateKey);
    }

    public function testSignAndVerifyBasic(): void
    {
        $message = 'Hello, SM2 Signature!';
        
        // Sign
        $signer = new SM2Signer();
        $signer->init(true, $this->privateKey);
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        $this->assertNotEmpty($signature);
        
        // Verify
        $verifier = new SM2Signer();
        $verifier->init(false, $this->publicKey);
        $verifier->updateBytes($message, 0, strlen($message));
        $result = $verifier->verifySignature($signature);
        
        $this->assertTrue($result);
    }

    public function testSignAndVerifyWithUserID(): void
    {
        $message = 'Test message with user ID';
        $userID = 'alice@example.com';
        
        // Sign
        $signer = new SM2Signer();
        $signParams = new ParametersWithID($this->privateKey, $userID);
        $signer->init(true, $signParams);
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        // Verify with same user ID
        $verifier = new SM2Signer();
        $verifyParams = new ParametersWithID($this->publicKey, $userID);
        $verifier->init(false, $verifyParams);
        $verifier->updateBytes($message, 0, strlen($message));
        $result = $verifier->verifySignature($signature);
        
        $this->assertTrue($result);
    }

    public function testVerificationFailsWithDifferentUserID(): void
    {
        $message = 'Test message';
        $userID1 = 'alice@example.com';
        $userID2 = 'bob@example.com';
        
        // Sign with userID1
        $signer = new SM2Signer();
        $signParams = new ParametersWithID($this->privateKey, $userID1);
        $signer->init(true, $signParams);
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        // Verify with different userID2
        $verifier = new SM2Signer();
        $verifyParams = new ParametersWithID($this->publicKey, $userID2);
        $verifier->init(false, $verifyParams);
        $verifier->updateBytes($message, 0, strlen($message));
        $result = $verifier->verifySignature($signature);
        
        $this->assertFalse($result);
    }

    public function testVerificationFailsWithModifiedMessage(): void
    {
        $message = 'Original message';
        $modifiedMessage = 'Modified message';
        
        // Sign original
        $signer = new SM2Signer();
        $signer->init(true, $this->privateKey);
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        // Verify modified
        $verifier = new SM2Signer();
        $verifier->init(false, $this->publicKey);
        $verifier->updateBytes($modifiedMessage, 0, strlen($modifiedMessage));
        $result = $verifier->verifySignature($signature);
        
        $this->assertFalse($result);
    }

    public function testVerificationFailsWithCorruptedSignature(): void
    {
        $message = 'Test message';
        
        // Sign
        $signer = new SM2Signer();
        $signer->init(true, $this->privateKey);
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        // Corrupt signature
        $signature[5] = chr(ord($signature[5]) ^ 0xFF);
        
        // Verify
        $verifier = new SM2Signer();
        $verifier->init(false, $this->publicKey);
        $verifier->updateBytes($message, 0, strlen($message));
        $result = $verifier->verifySignature($signature);
        
        $this->assertFalse($result);
    }

    public function testMultipleSignaturesAreDifferent(): void
    {
        $message = 'Same message';
        
        $signatures = [];
        for ($i = 0; $i < 3; $i++) {
            $signer = new SM2Signer();
            $params = new ParametersWithRandom($this->privateKey, new SecureRandom());
            $signer->init(true, $params);
            $signer->updateBytes($message, 0, strlen($message));
            $signatures[] = bin2hex($signer->generateSignature());
        }
        
        // All signatures should be different (due to random k)
        $this->assertNotEquals($signatures[0], $signatures[1]);
        $this->assertNotEquals($signatures[1], $signatures[2]);
        $this->assertNotEquals($signatures[0], $signatures[2]);
    }

    public function testAllRandomSignaturesVerify(): void
    {
        $message = 'Test message';
        
        for ($i = 0; $i < 5; $i++) {
            // Sign
            $signer = new SM2Signer();
            $signer->init(true, $this->privateKey);
            $signer->updateBytes($message, 0, strlen($message));
            $signature = $signer->generateSignature();
            
            // Verify
            $verifier = new SM2Signer();
            $verifier->init(false, $this->publicKey);
            $verifier->updateBytes($message, 0, strlen($message));
            $result = $verifier->verifySignature($signature);
            
            $this->assertTrue($result, "Signature $i should verify");
        }
    }

    public function testSignerReset(): void
    {
        $message = 'Test message';
        
        $signer = new SM2Signer();
        $signer->init(true, $this->privateKey);
        
        // Add some data
        $signer->updateBytes('garbage', 0, 7);
        
        // Reset
        $signer->reset();
        
        // Sign after reset
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        // Verify
        $verifier = new SM2Signer();
        $verifier->init(false, $this->publicKey);
        $verifier->updateBytes($message, 0, strlen($message));
        $result = $verifier->verifySignature($signature);
        
        $this->assertTrue($result);
    }

    public function testEmptyMessage(): void
    {
        $message = '';
        
        // Sign
        $signer = new SM2Signer();
        $signer->init(true, $this->privateKey);
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        // Verify
        $verifier = new SM2Signer();
        $verifier->init(false, $this->publicKey);
        $verifier->updateBytes($message, 0, strlen($message));
        $result = $verifier->verifySignature($signature);
        
        $this->assertTrue($result);
    }

    public function testLongMessage(): void
    {
        $message = str_repeat('A', 1000);  // Reduced from 10000 for faster tests
        
        // Sign
        $signer = new SM2Signer();
        $signer->init(true, $this->privateKey);
        $signer->updateBytes($message, 0, strlen($message));
        $signature = $signer->generateSignature();
        
        // Verify
        $verifier = new SM2Signer();
        $verifier->init(false, $this->publicKey);
        $verifier->updateBytes($message, 0, strlen($message));
        $result = $verifier->verifySignature($signature);
        
        $this->assertTrue($result);
    }

    public function testUpdateWithSingleByte(): void
    {
        $message = 'Test';
        
        // Sign using updateBytes
        $signer1 = new SM2Signer();
        $signer1->init(true, $this->privateKey);
        $signer1->updateBytes($message, 0, strlen($message));
        $signature1 = $signer1->generateSignature();
        
        // Sign using update (byte by byte)
        $signer2 = new SM2Signer();
        $signer2->init(true, $this->privateKey);
        for ($i = 0; $i < strlen($message); $i++) {
            $signer2->update(ord($message[$i]));
        }
        $signature2 = $signer2->generateSignature();
        
        // Both should verify
        $verifier = new SM2Signer();
        $verifier->init(false, $this->publicKey);
        $verifier->updateBytes($message, 0, strlen($message));
        
        $this->assertTrue($verifier->verifySignature($signature1));
        
        $verifier->reset();
        $verifier->updateBytes($message, 0, strlen($message));
        $this->assertTrue($verifier->verifySignature($signature2));
    }
}
