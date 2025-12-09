<?php

namespace SmBc\Pkcs;

use SmBc\Asn1\ASN1Encodable;
use SmBc\Asn1\ASN1Integer;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\ASN1Set;
use SmBc\Asn1\DERSequence;
use SmBc\X509\X509Name;

/**
 * PKCS#10 CertificationRequestInfo structure
 * 
 * <pre>
 * CertificationRequestInfo ::= SEQUENCE {
 *   version       INTEGER { v1(0) } (v1,...),
 *   subject       Name,
 *   subjectPKInfo SubjectPublicKeyInfo{{ PKInfoAlgorithms }},
 *   attributes    [0] Attributes {{ CRIAttributes }}
 * }
 * </pre>
 */
class CertificationRequestInfo extends ASN1Object
{
    private ASN1Integer $version;
    private X509Name $subject;
    private SubjectPublicKeyInfo $subjectPublicKeyInfo;
    private ASN1Set $attributes;
    
    /**
     * @param X509Name $subject Subject name
     * @param SubjectPublicKeyInfo $pkInfo Subject public key info
     * @param ASN1Set|null $attributes Optional attributes
     */
    public function __construct(
        X509Name $subject,
        SubjectPublicKeyInfo $pkInfo,
        ?ASN1Set $attributes = null
    ) {
        $this->version = new ASN1Integer(0); // v1
        $this->subject = $subject;
        $this->subjectPublicKeyInfo = $pkInfo;
        $this->attributes = $attributes ?? new ASN1Set([]);
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
            throw new \InvalidArgumentException('Unknown object in CertificationRequestInfo::getInstance');
        }
        
        $elements = $seq->getObjects();
        if (count($elements) < 3 || count($elements) > 4) {
            throw new \InvalidArgumentException('Bad sequence size: ' . count($elements));
        }
        
        $version = ASN1Integer::getInstance($elements[0]);
        $subject = X509Name::getInstance($elements[1]);
        $pkInfo = SubjectPublicKeyInfo::getInstance($elements[2]);
        
        $attributes = null;
        if (count($elements) == 4) {
            $attributes = ASN1Set::getInstance($elements[3]);
        }
        
        return new self($subject, $pkInfo, $attributes);
    }
    
    public function getVersion(): ASN1Integer
    {
        return $this->version;
    }
    
    public function getSubject(): X509Name
    {
        return $this->subject;
    }
    
    public function getSubjectPublicKeyInfo(): SubjectPublicKeyInfo
    {
        return $this->subjectPublicKeyInfo;
    }
    
    public function getAttributes(): ASN1Set
    {
        return $this->attributes;
    }
    
    public function toASN1Primitive(): ASN1Primitive
    {
        $v = [];
        $v[] = $this->version;
        $v[] = $this->subject->toASN1Primitive();
        $v[] = $this->subjectPublicKeyInfo->toASN1Primitive();
        
        if (count($this->attributes->getObjects()) > 0) {
            $v[] = $this->attributes;
        }
        
        return new DERSequence($v);
    }
}
