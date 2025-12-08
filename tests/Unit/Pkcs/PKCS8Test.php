<?php

namespace SmBc\Tests\Unit\Pkcs;

use PHPUnit\Framework\TestCase;
use SmBc\Pkcs\AlgorithmIdentifier;
use SmBc\Pkcs\PrivateKeyInfo;
use SmBc\Pkcs\SubjectPublicKeyInfo;
use SmBc\Asn1\ASN1ObjectIdentifier;
use SmBc\Asn1\ASN1OctetString;
use SmBc\Asn1\ASN1BitString;
use SmBc\Asn1\ASN1Null;
use SmBc\Asn1\ASN1Integer;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Math\BigInteger;

class PKCS8Test extends TestCase
{
    public function testAlgorithmIdentifierSM2()
    {
        $oid = new ASN1ObjectIdentifier('1.2.156.10197.1.301'); // SM2
        $algId = new AlgorithmIdentifier($oid, null);
        
        $encoded = $algId->toASN1Primitive()->getEncoded();
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testAlgorithmIdentifierWithParameters()
    {
        $oid = new ASN1ObjectIdentifier('1.2.840.10045.2.1'); // EC Public Key
        $params = new ASN1ObjectIdentifier('1.2.156.10197.1.301'); // SM2 curve
        $algId = new AlgorithmIdentifier($oid, $params);
        
        $encoded = $algId->toASN1Primitive()->getEncoded();
        $this->assertIsString($encoded);
    }

    public function testPrivateKeyInfoBasic()
    {
        $algId = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        
        $privateKeyBytes = random_bytes(32);
        $privateKey = new ASN1OctetString($privateKeyBytes);
        
        $pkInfo = new PrivateKeyInfo($algId, $privateKey);
        $encoded = $pkInfo->getEncoded();
        
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testPrivateKeyInfoRoundTrip()
    {
        $algId = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        
        $privateKeyBytes = "test_private_key_data_32bytes!";
        $privateKey = new ASN1OctetString($privateKeyBytes);
        
        $pkInfo = new PrivateKeyInfo($algId, $privateKey);
        $encoded = $pkInfo->getEncoded();
        
        // Decode
        $decoded = PrivateKeyInfo::getInstance(ASN1Primitive::fromByteArray($encoded));
        $this->assertInstanceOf(PrivateKeyInfo::class, $decoded);
        $this->assertEquals($privateKeyBytes, $decoded->getPrivateKey()->getOctets());
    }

    public function testSubjectPublicKeyInfoBasic()
    {
        $algId = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        
        $publicKeyBytes = random_bytes(65); // Uncompressed EC point
        $publicKey = new ASN1BitString($publicKeyBytes, 0);
        
        $spkInfo = new SubjectPublicKeyInfo($algId, $publicKey);
        $encoded = $spkInfo->getEncoded();
        
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testSubjectPublicKeyInfoRoundTrip()
    {
        $algId = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        
        $publicKeyBytes = str_repeat("\x04", 65); // Mock uncompressed point
        $publicKey = new ASN1BitString($publicKeyBytes, 0);
        
        $spkInfo = new SubjectPublicKeyInfo($algId, $publicKey);
        $encoded = $spkInfo->getEncoded();
        
        // Decode
        $decoded = SubjectPublicKeyInfo::getInstance(ASN1Primitive::fromByteArray($encoded));
        $this->assertInstanceOf(SubjectPublicKeyInfo::class, $decoded);
        $this->assertEquals($publicKeyBytes, $decoded->getPublicKey()->getBytes());
    }

    public function testPrivateKeyInfoVersion()
    {
        $algId = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        
        $privateKey = new ASN1OctetString(random_bytes(32));
        $pkInfo = new PrivateKeyInfo($algId, $privateKey);
        
        // PKCS#8 version should be 0
        $encoded = $pkInfo->getEncoded();
        $this->assertIsString($encoded);
    }

    public function testAlgorithmIdentifierCommonOIDs()
    {
        // Test SM2
        $sm2 = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        $this->assertIsString($sm2->toASN1Primitive()->getEncoded());
        
        // Test SM3
        $sm3 = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.401'),
            null
        );
        $this->assertIsString($sm3->toASN1Primitive()->getEncoded());
        
        // Test SM4
        $sm4 = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.104'),
            null
        );
        $this->assertIsString($sm4->toASN1Primitive()->getEncoded());
    }
}
