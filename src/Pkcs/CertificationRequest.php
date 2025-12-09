<?php

namespace SmBc\Pkcs;

use SmBc\Asn1\ASN1BitString;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;

/**
 * PKCS#10 CertificationRequest structure
 * 
 * <pre>
 * CertificationRequest ::= SEQUENCE {
 *   certificationRequestInfo CertificationRequestInfo,
 *   signatureAlgorithm AlgorithmIdentifier{{ SignatureAlgorithms }},
 *   signature          BIT STRING
 * }
 * </pre>
 */
class CertificationRequest extends ASN1Object
{
    private CertificationRequestInfo $reqInfo;
    private AlgorithmIdentifier $sigAlgId;
    private ASN1BitString $sigBits;
    
    /**
     * @param CertificationRequestInfo $requestInfo Request info
     * @param AlgorithmIdentifier $algorithmId Signature algorithm
     * @param ASN1BitString $signature Signature value
     */
    public function __construct(
        CertificationRequestInfo $requestInfo,
        AlgorithmIdentifier $algorithmId,
        ASN1BitString $signature
    ) {
        $this->reqInfo = $requestInfo;
        $this->sigAlgId = $algorithmId;
        $this->sigBits = $signature;
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
            throw new \InvalidArgumentException('Unknown object in CertificationRequest::getInstance');
        }
        
        $elements = $seq->getObjects();
        if (count($elements) !== 3) {
            throw new \InvalidArgumentException('Bad sequence size: ' . count($elements));
        }
        
        $reqInfo = CertificationRequestInfo::getInstance($elements[0]);
        $sigAlgId = AlgorithmIdentifier::getInstance($elements[1]);
        $sigBits = ASN1BitString::getInstance($elements[2]);
        
        return new self($reqInfo, $sigAlgId, $sigBits);
    }
    
    /**
     * Get certification request info
     */
    public function getCertificationRequestInfo(): CertificationRequestInfo
    {
        return $this->reqInfo;
    }
    
    /**
     * Get signature algorithm identifier
     */
    public function getSignatureAlgorithm(): AlgorithmIdentifier
    {
        return $this->sigAlgId;
    }
    
    /**
     * Get signature bits
     */
    public function getSignature(): ASN1BitString
    {
        return $this->sigBits;
    }
    
    /**
     * Get DER-encoded bytes suitable for signing
     */
    public function getTBSRequest(): string
    {
        return $this->reqInfo->getEncoded();
    }
    
    public function toASN1Primitive(): ASN1Primitive
    {
        $v = [];
        $v[] = $this->reqInfo->toASN1Primitive();
        $v[] = $this->sigAlgId->toASN1Primitive();
        $v[] = $this->sigBits;
        
        return new DERSequence($v);
    }
}
