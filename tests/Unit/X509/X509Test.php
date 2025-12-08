<?php

namespace SmBc\Tests\Unit\X509;

use PHPUnit\Framework\TestCase;
use SmBc\X509\X509Name;
use SmBc\X509\Time;
use SmBc\X509\Validity;
use SmBc\X509\TBSCertificate;
use SmBc\X509\X509Certificate;
use SmBc\Pkcs\AlgorithmIdentifier;
use SmBc\Pkcs\SubjectPublicKeyInfo;
use SmBc\Asn1\ASN1ObjectIdentifier;
use SmBc\Asn1\ASN1Integer;
use SmBc\Asn1\ASN1BitString;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Math\BigInteger;
use DateTime;

class X509Test extends TestCase
{
    public function testX509NameBasic()
    {
        $name = new X509Name([
            'CN' => 'Test User',
            'O' => 'Test Organization',
            'C' => 'CN'
        ]);
        
        $encoded = $name->toASN1Primitive()->getEncoded();
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testX509NameRoundTrip()
    {
        $original = new X509Name([
            'CN' => 'John Doe',
            'O' => 'Acme Corp',
            'OU' => 'Engineering',
            'C' => 'US',
            'ST' => 'California',
            'L' => 'San Francisco'
        ]);
        
        $encoded = $original->toASN1Primitive()->getEncoded();
        $decoded = X509Name::getInstance(ASN1Primitive::fromByteArray($encoded));
        
        $this->assertInstanceOf(X509Name::class, $decoded);
    }

    public function testTimeUTC()
    {
        $date = new DateTime('2024-06-15 10:30:00');
        $time = Time::getInstance($date);
        
        $encoded = $time->toASN1Primitive()->getEncoded();
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testTimeGeneralized()
    {
        $date = new DateTime('2055-12-31 23:59:59');
        $time = Time::getInstance($date);
        
        $encoded = $time->toASN1Primitive()->getEncoded();
        $this->assertIsString($encoded);
    }

    public function testValidityBasic()
    {
        $notBefore = Time::getInstance(new DateTime('2024-01-01'));
        $notAfter = Time::getInstance(new DateTime('2025-01-01'));
        
        $validity = new Validity($notBefore, $notAfter);
        $encoded = $validity->toASN1Primitive()->getEncoded();
        
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testValidityRoundTrip()
    {
        $notBefore = Time::getInstance(new DateTime('2024-01-01 00:00:00'));
        $notAfter = Time::getInstance(new DateTime('2025-12-31 23:59:59'));
        
        $validity = new Validity($notBefore, $notAfter);
        $encoded = $validity->toASN1Primitive()->getEncoded();
        
        $decoded = Validity::getInstance(ASN1Primitive::fromByteArray($encoded));
        $this->assertInstanceOf(Validity::class, $decoded);
    }

    public function testTBSCertificateBasic()
    {
        $version = new ASN1Integer(new BigInteger('2')); // v3
        $serialNumber = new ASN1Integer(new BigInteger('12345'));
        
        $signatureAlg = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.501'), // SM2 with SM3
            null
        );
        
        $issuer = new X509Name(['CN' => 'Test CA', 'O' => 'Test Org', 'C' => 'CN']);
        $subject = new X509Name(['CN' => 'Test User', 'O' => 'Test Org', 'C' => 'CN']);
        
        $validity = new Validity(
            Time::getInstance(new DateTime('2024-01-01')),
            Time::getInstance(new DateTime('2025-01-01'))
        );
        
        $pubKeyAlg = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        $pubKey = new ASN1BitString(random_bytes(65), 0);
        $subjectPublicKeyInfo = new SubjectPublicKeyInfo($pubKeyAlg, $pubKey);
        
        $tbsCert = new TBSCertificate(
            $version,
            $serialNumber,
            $signatureAlg,
            $issuer,
            $validity,
            $subject,
            $subjectPublicKeyInfo
        );
        
        $encoded = $tbsCert->toASN1Primitive()->getEncoded();
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testX509CertificateBasic()
    {
        $version = new ASN1Integer(new BigInteger('2'));
        $serialNumber = new ASN1Integer(new BigInteger('99999'));
        
        $signatureAlg = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.501'),
            null
        );
        
        $issuer = new X509Name(['CN' => 'CA', 'C' => 'CN']);
        $subject = new X509Name(['CN' => 'User', 'C' => 'CN']);
        
        $validity = new Validity(
            Time::getInstance(new DateTime('2024-01-01')),
            Time::getInstance(new DateTime('2025-01-01'))
        );
        
        $pubKeyAlg = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        $pubKey = new ASN1BitString(str_repeat("\x04", 65), 0);
        $subjectPublicKeyInfo = new SubjectPublicKeyInfo($pubKeyAlg, $pubKey);
        
        $tbsCert = new TBSCertificate(
            $version,
            $serialNumber,
            $signatureAlg,
            $issuer,
            $validity,
            $subject,
            $subjectPublicKeyInfo
        );
        
        // Create signature value (mock)
        $signatureValue = new ASN1BitString(random_bytes(64), 0);
        
        $cert = new X509Certificate($tbsCert, $signatureAlg, $signatureValue);
        $encoded = $cert->getEncoded();
        
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testX509CertificateRoundTrip()
    {
        $version = new ASN1Integer(new BigInteger('2'));
        $serialNumber = new ASN1Integer(new BigInteger('54321'));
        
        $signatureAlg = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.501'),
            null
        );
        
        $issuer = new X509Name(['CN' => 'Root CA', 'O' => 'Test', 'C' => 'CN']);
        $subject = new X509Name(['CN' => 'End User', 'O' => 'Test', 'C' => 'CN']);
        
        $validity = new Validity(
            Time::getInstance(new DateTime('2024-06-01')),
            Time::getInstance(new DateTime('2024-12-31'))
        );
        
        $pubKeyAlg = new AlgorithmIdentifier(
            new ASN1ObjectIdentifier('1.2.156.10197.1.301'),
            null
        );
        $pubKey = new ASN1BitString(str_repeat("\x05", 65), 0);
        $subjectPublicKeyInfo = new SubjectPublicKeyInfo($pubKeyAlg, $pubKey);
        
        $tbsCert = new TBSCertificate(
            $version,
            $serialNumber,
            $signatureAlg,
            $issuer,
            $validity,
            $subject,
            $subjectPublicKeyInfo
        );
        
        $signatureValue = new ASN1BitString(str_repeat("\xaa", 64), 0);
        $cert = new X509Certificate($tbsCert, $signatureAlg, $signatureValue);
        
        $encoded = $cert->getEncoded();
        $decoded = X509Certificate::getInstance(ASN1Primitive::fromByteArray($encoded));
        
        $this->assertInstanceOf(X509Certificate::class, $decoded);
    }
}
