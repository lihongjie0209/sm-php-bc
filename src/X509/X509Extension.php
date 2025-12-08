<?php

namespace SmBc\X509;

use SmBc\Asn1\ASN1Boolean;
use SmBc\Asn1\ASN1Encodable;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1ObjectIdentifier;
use SmBc\Asn1\ASN1OctetString;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;

/**
 * X.509 Extension
 * 
 * <pre>
 * Extension  ::=  SEQUENCE  {
 *   extnID      OBJECT IDENTIFIER,
 *   critical    BOOLEAN DEFAULT FALSE,
 *   extnValue   OCTET STRING
 * }
 * </pre>
 */
class X509Extension extends ASN1Object
{
    private ASN1ObjectIdentifier $extnId;
    private bool $critical;
    private ASN1OctetString $value;
    
    /**
     * @param ASN1ObjectIdentifier $extnId Extension OID
     * @param bool $critical Critical flag
     * @param ASN1OctetString $value Extension value
     */
    public function __construct(
        ASN1ObjectIdentifier $extnId,
        bool $critical,
        ASN1OctetString $value
    ) {
        $this->extnId = $extnId;
        $this->critical = $critical;
        $this->value = $value;
    }
    
    /**
     * Create from ASN.1 sequence
     */
    public static function getInstance($obj): self
    {
        if ($obj instanceof self) {
            return $obj;
        }
        
        if ($obj instanceof ASN1Sequence) {
            $seq = $obj;
        } else if (is_string($obj)) {
            $seq = ASN1Sequence::getInstance(ASN1Primitive::fromByteArray($obj));
        } else {
            throw new \InvalidArgumentException('Unknown object in X509Extension::getInstance');
        }
        
        $elements = $seq->getObjects();
        if (count($elements) < 2 || count($elements) > 3) {
            throw new \InvalidArgumentException('Bad sequence size: ' . count($elements));
        }
        
        $extnId = ASN1ObjectIdentifier::getInstance($elements[0]);
        $critical = false;
        $valueIdx = 1;
        
        if (count($elements) == 3) {
            $critical = ASN1Boolean::getInstance($elements[1])->isTrue();
            $valueIdx = 2;
        }
        
        $value = ASN1OctetString::getInstance($elements[$valueIdx]);
        
        return new self($extnId, $critical, $value);
    }
    
    public function getExtnId(): ASN1ObjectIdentifier
    {
        return $this->extnId;
    }
    
    public function isCritical(): bool
    {
        return $this->critical;
    }
    
    public function getExtnValue(): ASN1OctetString
    {
        return $this->value;
    }
    
    /**
     * Get parsed extension value
     */
    public function getParsedValue(): ASN1Primitive
    {
        return ASN1Primitive::fromByteArray($this->value->getOctets());
    }
    
    public function toASN1Primitive(): ASN1Primitive
    {
        $v = [];
        $v[] = $this->extnId;
        
        if ($this->critical) {
            $v[] = ASN1Boolean::getInstance(true);
        }
        
        $v[] = $this->value;
        
        return new DERSequence($v);
    }
}
