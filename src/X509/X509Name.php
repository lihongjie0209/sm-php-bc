<?php

declare(strict_types=1);

namespace SmBc\X509;

use SmBc\Asn1\ASN1Encodable;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1ObjectIdentifier;
use SmBc\Asn1\ASN1OctetString;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\ASN1Set;
use SmBc\Asn1\DERSequence;

/**
 * X.509 Name (Distinguished Name).
 * 
 * ```
 * Name ::= CHOICE {
 *     rdnSequence RDNSequence
 * }
 * 
 * RDNSequence ::= SEQUENCE OF RelativeDistinguishedName
 * RelativeDistinguishedName ::= SET OF AttributeTypeAndValue
 * ```
 */
class X509Name extends ASN1Object
{
    /** @var array RDN sequence */
    private array $rdns = [];

    /** Common Name OID */
    public const OID_CN = '2.5.4.3';
    /** Country OID */
    public const OID_C = '2.5.4.6';
    /** Organization OID */
    public const OID_O = '2.5.4.10';
    /** Organizational Unit OID */
    public const OID_OU = '2.5.4.11';
    /** State/Province OID */
    public const OID_ST = '2.5.4.8';
    /** Locality OID */
    public const OID_L = '2.5.4.7';

    /**
     * Constructor.
     * 
     * @param array $attributes Associative array of OID => value
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $oid => $value) {
            $this->addRDN($oid, $value);
        }
    }

    /**
     * Add an RDN (Relative Distinguished Name).
     * 
     * @param string $oid The attribute OID
     * @param string $value The attribute value
     */
    public function addRDN(string $oid, string $value): void
    {
        $this->rdns[] = [
            'oid' => new ASN1ObjectIdentifier($oid),
            'value' => new ASN1OctetString($value) // Simplified - should be appropriate string type
        ];
    }

    /**
     * Get the RDNs.
     * 
     * @return array The RDNs
     */
    public function getRDNs(): array
    {
        return $this->rdns;
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        $sequence = [];
        
        foreach ($this->rdns as $rdn) {
            // Each RDN is a SET containing a SEQUENCE of oid and value
            $attrSeq = new DERSequence([$rdn['oid'], $rdn['value']]);
            $attrSet = ASN1Set::fromArray([$attrSeq]);
            $sequence[] = $attrSet;
        }
        
        return new DERSequence($sequence);
    }

    /**
     * Create an X509Name from a DN string.
     * 
     * @param string $dn The DN string (e.g., "CN=Test,O=Org,C=CN")
     * @return self The X509Name
     */
    public static function fromString(string $dn): self
    {
        $name = new self();
        $parts = explode(',', $dn);
        
        $oidMap = [
            'CN' => self::OID_CN,
            'C' => self::OID_C,
            'O' => self::OID_O,
            'OU' => self::OID_OU,
            'ST' => self::OID_ST,
            'L' => self::OID_L,
        ];
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (isset($oidMap[$key])) {
                    $name->addRDN($oidMap[$key], $value);
                }
            }
        }
        
        return $name;
    }

    /**
     * Create an X509Name from an ASN1Sequence.
     * 
     * @param ASN1Sequence $seq The sequence
     * @return self The name
     */
    public static function fromSequence(ASN1Sequence $seq): self
    {
        $name = new self();
        
        for ($i = 0; $i < $seq->size(); $i++) {
            $rdnSet = $seq->getObjectAt($i);
            if ($rdnSet instanceof ASN1Set) {
                $attrSeq = $rdnSet->getObjectAt(0);
                if ($attrSeq instanceof ASN1Sequence && $attrSeq->size() >= 2) {
                    $oid = $attrSeq->getObjectAt(0);
                    $value = $attrSeq->getObjectAt(1);
                    
                    if ($oid instanceof ASN1ObjectIdentifier && $value instanceof ASN1OctetString) {
                        $name->addRDN($oid->getId(), $value->getOctets());
                    }
                }
            }
        }
        
        return $name;
    }

    /**
     * Convert to string representation.
     * 
     * @return string The DN string
     */
    public function toString(): string
    {
        $parts = [];
        $oidNames = [
            self::OID_CN => 'CN',
            self::OID_C => 'C',
            self::OID_O => 'O',
            self::OID_OU => 'OU',
            self::OID_ST => 'ST',
            self::OID_L => 'L',
        ];
        
        foreach ($this->rdns as $rdn) {
            $oid = $rdn['oid']->getId();
            $value = $rdn['value']->getOctets();
            $key = $oidNames[$oid] ?? $oid;
            $parts[] = "$key=$value";
        }
        
        return implode(', ', $parts);
    }
}
