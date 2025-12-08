<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Util\Pem;

use PHPUnit\Framework\TestCase;
use SmBc\Util\Pem\SM2KeyPemEncoder;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;

class SM2KeyPemEncoderTest extends TestCase
{
    private ECDomainParameters $domainParams;

    protected function setUp(): void
    {
        // SM2 parameters
        $p = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF');
        $a = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC');
        $b = new BigInteger('0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93');
        $n = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123');
        
        $curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());
        
        $gx = new BigInteger('0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7');
        $gy = new BigInteger('0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0');
        $g = $curve->createPoint($gx, $gy);
        
        $this->domainParams = new ECDomainParameters($curve, $g, $n, BigInteger::ONE());
    }

    public function testEncodeDecodePrivateKey(): void
    {
        // Create a test private key
        $d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263');
        $privateKey = new ECPrivateKeyParameters($d, $this->domainParams);
        
        // Encode to PEM
        $pem = SM2KeyPemEncoder::encodePrivateKey($privateKey);
        
        // Check PEM format
        $this->assertStringContainsString('-----BEGIN SM2 PRIVATE KEY-----', $pem);
        $this->assertStringContainsString('-----END SM2 PRIVATE KEY-----', $pem);
        
        // Decode back
        $decodedKey = SM2KeyPemEncoder::decodePrivateKey($pem);
        
        // Verify the private key value matches
        $this->assertSame(
            gmp_strval($d->val, 16),
            gmp_strval($decodedKey->getD()->val, 16)
        );
    }

    public function testEncodeDecodePublicKey(): void
    {
        // Create a test private key and derive public key
        $d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263');
        $q = $this->domainParams->getG()->multiply($d)->normalize();
        $publicKey = new ECPublicKeyParameters($q, $this->domainParams);
        
        // Encode to PEM
        $pem = SM2KeyPemEncoder::encodePublicKey($publicKey);
        
        // Check PEM format
        $this->assertStringContainsString('-----BEGIN SM2 PUBLIC KEY-----', $pem);
        $this->assertStringContainsString('-----END SM2 PUBLIC KEY-----', $pem);
        
        // Decode back
        $decodedKey = SM2KeyPemEncoder::decodePublicKey($pem);
        
        // Verify the public key coordinates match
        $originalQ = $publicKey->getQ()->normalize();
        $decodedQ = $decodedKey->getQ()->normalize();
        
        $this->assertSame(
            gmp_strval($originalQ->getAffineXCoord()->toBigInteger()->val, 16),
            gmp_strval($decodedQ->getAffineXCoord()->toBigInteger()->val, 16)
        );
        
        $this->assertSame(
            gmp_strval($originalQ->getAffineYCoord()->toBigInteger()->val, 16),
            gmp_strval($decodedQ->getAffineYCoord()->toBigInteger()->val, 16)
        );
    }

    public function testDecodeInvalidPrivateKeyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not an SM2 private key PEM');
        
        $pem = "-----BEGIN WRONG KEY-----\n" . base64_encode('{}') . "\n-----END WRONG KEY-----\n";
        SM2KeyPemEncoder::decodePrivateKey($pem);
    }

    public function testDecodeInvalidPublicKeyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not an SM2 public key PEM');
        
        $pem = "-----BEGIN WRONG KEY-----\n" . base64_encode('{}') . "\n-----END WRONG KEY-----\n";
        SM2KeyPemEncoder::decodePublicKey($pem);
    }

    public function testDecodeInvalidPrivateKeyData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SM2 private key data');
        
        $invalidJson = json_encode(['version' => 1]); // Missing 'd' field
        $pem = "-----BEGIN SM2 PRIVATE KEY-----\n" . base64_encode($invalidJson) . "\n-----END SM2 PRIVATE KEY-----\n";
        SM2KeyPemEncoder::decodePrivateKey($pem);
    }

    public function testDecodeInvalidPublicKeyData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SM2 public key data');
        
        $invalidJson = json_encode(['version' => 1]); // Missing 'x' and 'y' fields
        $pem = "-----BEGIN SM2 PUBLIC KEY-----\n" . base64_encode($invalidJson) . "\n-----END SM2 PUBLIC KEY-----\n";
        SM2KeyPemEncoder::decodePublicKey($pem);
    }

    public function testRoundTripPrivateKey(): void
    {
        // Test multiple round trips
        $d = new BigInteger('0xAABBCCDD11223344556677889900AABBCCDDEEFF00112233445566778899AABB');
        $privateKey = new ECPrivateKeyParameters($d, $this->domainParams);
        
        $pem1 = SM2KeyPemEncoder::encodePrivateKey($privateKey);
        $decoded1 = SM2KeyPemEncoder::decodePrivateKey($pem1);
        
        $pem2 = SM2KeyPemEncoder::encodePrivateKey($decoded1);
        $decoded2 = SM2KeyPemEncoder::decodePrivateKey($pem2);
        
        // Should be identical
        $this->assertSame(
            gmp_strval($privateKey->getD()->val, 16),
            gmp_strval($decoded2->getD()->val, 16)
        );
    }
}
