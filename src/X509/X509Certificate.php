<?php

declare(strict_types=1);

namespace SmBc\X509;

use SmBc\Asn1\ASN1BitString;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;
use SmBc\Pkcs\AlgorithmIdentifier;

/**
 * X.509 Certificate.
 * 
 * ```
 * Certificate ::= SEQUENCE {
 *     tbsCertificate       TBSCertificate,
 *     signatureAlgorithm   AlgorithmIdentifier,
 *     signatureValue       BIT STRING
 * }
 * ```
 */
class X509Certificate extends ASN1Object
{
    /** @var TBSCertificate The TBS certificate */
    private TBSCertificate $tbsCertificate;
    
    /** @var AlgorithmIdentifier The signature algorithm */
    private AlgorithmIdentifier $signatureAlgorithm;
    
    /** @var ASN1BitString The signature value */
    private ASN1BitString $signatureValue;

    /**
     * Constructor.
     * 
     * @param TBSCertificate $tbsCertificate The TBS certificate
     * @param AlgorithmIdentifier $signatureAlgorithm The signature algorithm
     * @param ASN1BitString $signatureValue The signature
     */
    public function __construct(
        TBSCertificate $tbsCertificate,
        AlgorithmIdentifier $signatureAlgorithm,
        ASN1BitString $signatureValue
    ) {
        $this->tbsCertificate = $tbsCertificate;
        $this->signatureAlgorithm = $signatureAlgorithm;
        $this->signatureValue = $signatureValue;
    }

    /**
     * Get the TBS certificate.
     * 
     * @return TBSCertificate The TBS certificate
     */
    public function getTBSCertificate(): TBSCertificate
    {
        return $this->tbsCertificate;
    }

    /**
     * Get the signature algorithm.
     * 
     * @return AlgorithmIdentifier The signature algorithm
     */
    public function getSignatureAlgorithm(): AlgorithmIdentifier
    {
        return $this->signatureAlgorithm;
    }

    /**
     * Get the signature value.
     * 
     * @return ASN1BitString The signature
     */
    public function getSignatureValue(): ASN1BitString
    {
        return $this->signatureValue;
    }

    /**
     * Get the subject name.
     * 
     * @return X509Name The subject
     */
    public function getSubject(): X509Name
    {
        return $this->tbsCertificate->getSubject();
    }

    /**
     * Get the issuer name.
     * 
     * @return X509Name The issuer
     */
    public function getIssuer(): X509Name
    {
        return $this->tbsCertificate->getIssuer();
    }

    /**
     * Get the serial number.
     * 
     * @return string The serial number
     */
    public function getSerialNumber(): string
    {
        return $this->tbsCertificate->getSerialNumber()->getValue()->toString();
    }

    /**
     * Get the validity period.
     * 
     * @return Validity The validity
     */
    public function getValidity(): Validity
    {
        return $this->tbsCertificate->getValidity();
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        return new DERSequence([
            $this->tbsCertificate->toASN1Primitive(),
            $this->signatureAlgorithm->toASN1Primitive(),
            $this->signatureValue
        ]);
    }

    /**
     * Parse a certificate from DER encoded bytes.
     * 
     * @param string $der The DER encoded data
     * @return self The certificate
     */
    public static function fromDER(string $der): self
    {
        $obj = ASN1Object::fromByteArray($der);
        if (!($obj instanceof ASN1Sequence)) {
            throw new \InvalidArgumentException("Invalid certificate DER");
        }
        return self::fromSequence($obj);
    }

    /**
     * Parse a certificate from ASN1Sequence.
     * 
     * @param ASN1Sequence $seq The sequence
     * @return self The certificate
     */
    public static function fromSequence(ASN1Sequence $seq): self
    {
        if ($seq->size() !== 3) {
            throw new \InvalidArgumentException("Invalid certificate sequence");
        }
        
        // TBS Certificate
        $tbsSeq = $seq->getObjectAt(0);
        if (!($tbsSeq instanceof ASN1Sequence)) {
            throw new \InvalidArgumentException("Invalid TBS certificate");
        }
        $tbsCert = TBSCertificate::fromSequence($tbsSeq);
        
        // Signature algorithm
        $sigAlgSeq = $seq->getObjectAt(1);
        if (!($sigAlgSeq instanceof ASN1Sequence)) {
            throw new \InvalidArgumentException("Invalid signature algorithm");
        }
        $sigAlg = AlgorithmIdentifier::fromSequence($sigAlgSeq);
        
        // Signature value
        $sigValue = $seq->getObjectAt(2);
        if (!($sigValue instanceof ASN1BitString)) {
            throw new \InvalidArgumentException("Invalid signature value");
        }
        
        return new self($tbsCert, $sigAlg, $sigValue);
    }
}
