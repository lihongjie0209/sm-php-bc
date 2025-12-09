<?php

namespace SmBc\Tests\Unit\Asn1;

use PHPUnit\Framework\TestCase;
use SmBc\Asn1\ASN1Integer;
use SmBc\Asn1\ASN1OctetString;
use SmBc\Asn1\ASN1BitString;
use SmBc\Asn1\ASN1Boolean;
use SmBc\Asn1\ASN1Null;
use SmBc\Asn1\ASN1ObjectIdentifier;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;
use SmBc\Asn1\ASN1Set;
use SmBc\Asn1\ASN1TaggedObject;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Math\BigInteger;

class ASN1Test extends TestCase
{
    public function testASN1IntegerEncoding()
    {
        $int = new ASN1Integer(new BigInteger('12345'));
        $encoded = $int->getEncoded();
        
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
        
        // Decode and verify
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1Integer::class, $decoded);
        $this->assertTrue($decoded->getValue()->equals(new BigInteger('12345')));
    }

    public function testASN1IntegerNegative()
    {
        $int = new ASN1Integer(new BigInteger('-100'));
        $encoded = $int->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1Integer::class, $decoded);
        $this->assertTrue($decoded->getValue()->equals(new BigInteger('-100')));
    }

    public function testASN1IntegerZero()
    {
        $int = new ASN1Integer(new BigInteger('0'));
        $encoded = $int->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1Integer::class, $decoded);
        $this->assertTrue($decoded->getValue()->equals(new BigInteger('0')));
    }

    public function testASN1OctetString()
    {
        $data = 'Hello, ASN.1!';
        $octetString = new ASN1OctetString($data);
        $encoded = $octetString->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1OctetString::class, $decoded);
        $this->assertEquals($data, $decoded->getOctets());
    }

    public function testASN1OctetStringBinary()
    {
        $data = "\x00\x01\x02\x03\xff\xfe\xfd";
        $octetString = new ASN1OctetString($data);
        $encoded = $octetString->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1OctetString::class, $decoded);
        $this->assertEquals($data, $decoded->getOctets());
    }

    public function testASN1BitString()
    {
        $data = "\x80\x40\x20";
        $bitString = new ASN1BitString($data, 5); // 5 padding bits
        $encoded = $bitString->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1BitString::class, $decoded);
        $this->assertEquals($data, $decoded->getBytes());
        $this->assertEquals(5, $decoded->getPadBits());
    }

    public function testASN1BooleanTrue()
    {
        $bool = ASN1Boolean::getInstance(true);
        $encoded = $bool->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1Boolean::class, $decoded);
        $this->assertTrue($decoded->isTrue());
    }

    public function testASN1BooleanFalse()
    {
        $bool = ASN1Boolean::getInstance(false);
        $encoded = $bool->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1Boolean::class, $decoded);
        $this->assertFalse($decoded->isTrue());
    }

    public function testASN1Null()
    {
        $null = ASN1Null::getInstance();
        $encoded = $null->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1Null::class, $decoded);
    }

    public function testASN1ObjectIdentifier()
    {
        // SM2 OID: 1.2.156.10197.1.301
        $oid = new ASN1ObjectIdentifier('1.2.156.10197.1.301');
        $encoded = $oid->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1ObjectIdentifier::class, $decoded);
        $this->assertEquals('1.2.156.10197.1.301', $decoded->getId());
    }

    public function testASN1Sequence()
    {
        $elements = [
            new ASN1Integer(new BigInteger('123')),
            new ASN1OctetString('test'),
            ASN1Boolean::getInstance(true)
        ];
        
        $sequence = new DERSequence($elements);
        $encoded = $sequence->getEncoded();
        
        $decoded = ASN1Primitive::fromByteArray($encoded);
        $this->assertInstanceOf(ASN1Sequence::class, $decoded);
        $this->assertCount(3, $decoded->getObjects());
    }

    public function testASN1SequenceNested()
    {
        $inner = new DERSequence([
            new ASN1Integer(new BigInteger('456')),
            new ASN1OctetString('inner')
        ]);
        
        $outer = new DERSequence([
            new ASN1Integer(new BigInteger('123')),
            $inner
        ]);
        
        $encoded = $outer->getEncoded();
        $decoded = ASN1Primitive::fromByteArray($encoded);
        
        $this->assertInstanceOf(ASN1Sequence::class, $decoded);
        $this->assertCount(2, $decoded->getObjects());
    }

    public function testASN1TaggedObjectExplicit()
    {
        $obj = new ASN1Integer(new BigInteger('999'));
        $tagged = new ASN1TaggedObject(true, 0, $obj);
        $encoded = $tagged->getEncoded();
        
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }

    public function testASN1TaggedObjectImplicit()
    {
        $obj = new ASN1Integer(new BigInteger('888'));
        $tagged = new ASN1TaggedObject(false, 1, $obj);
        $encoded = $tagged->getEncoded();
        
        $this->assertIsString($encoded);
        $this->assertGreaterThan(0, strlen($encoded));
    }
}
