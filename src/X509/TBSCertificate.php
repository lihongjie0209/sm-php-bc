<?php

declare(strict_types=1);

namespace SmBc\X509;

use SmBc\Asn1\ASN1Integer;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\ASN1TaggedObject;
use SmBc\Asn1\DERSequence;
use SmBc\Pkcs\AlgorithmIdentifier;
use SmBc\Pkcs\SubjectPublicKeyInfo;

/**
 * TBSCertificate (To Be Signed Certificate).
 * 
 * ```
 * TBSCertificate ::= SEQUENCE {
 *     version         [0] EXPLICIT Version DEFAULT v1,
 *     serialNumber    CertificateSerialNumber,
 *     signature       AlgorithmIdentifier,
 *     issuer          Name,
 *     validity        Validity,
 *     subject         Name,
 *     subjectPublicKeyInfo SubjectPublicKeyInfo,
 *     issuerUniqueID  [1] IMPLICIT UniqueIdentifier OPTIONAL,
 *     subjectUniqueID [2] IMPLICIT UniqueIdentifier OPTIONAL,
 *     extensions      [3] EXPLICIT Extensions OPTIONAL
 * }
 * ```
 */
class TBSCertificate extends ASN1Object
{
    /** @var ASN1Integer The version (v1=0, v2=1, v3=2) */
    private ASN1Integer $version;
    
    /** @var ASN1Integer The serial number */
    private ASN1Integer $serialNumber;
    
    /** @var AlgorithmIdentifier The signature algorithm */
    private AlgorithmIdentifier $signature;
    
    /** @var X509Name The issuer name */
    private X509Name $issuer;
    
    /** @var Validity The validity period */
    private Validity $validity;
    
    /** @var X509Name The subject name */
    private X509Name $subject;
    
    /** @var SubjectPublicKeyInfo The subject public key */
    private SubjectPublicKeyInfo $subjectPublicKeyInfo;
    
    /** @var ASN1Sequence|null Optional extensions */
    private ?ASN1Sequence $extensions;

    /**
     * Constructor.
     * 
     * @param ASN1Integer $serialNumber The serial number
     * @param AlgorithmIdentifier $signature The signature algorithm
     * @param X509Name $issuer The issuer
     * @param Validity $validity The validity
     * @param X509Name $subject The subject
     * @param SubjectPublicKeyInfo $subjectPublicKeyInfo The public key
     * @param ASN1Sequence|null $extensions Optional extensions
     */
    public function __construct(
        ASN1Integer $serialNumber,
        AlgorithmIdentifier $signature,
        X509Name $issuer,
        Validity $validity,
        X509Name $subject,
        SubjectPublicKeyInfo $subjectPublicKeyInfo,
        ?ASN1Sequence $extensions = null
    ) {
        $this->version = new ASN1Integer($extensions !== null ? 2 : 0); // v3 if extensions, v1 otherwise
        $this->serialNumber = $serialNumber;
        $this->signature = $signature;
        $this->issuer = $issuer;
        $this->validity = $validity;
        $this->subject = $subject;
        $this->subjectPublicKeyInfo = $subjectPublicKeyInfo;
        $this->extensions = $extensions;
    }

    /**
     * Get the version.
     * 
     * @return ASN1Integer The version
     */
    public function getVersion(): ASN1Integer
    {
        return $this->version;
    }

    /**
     * Get the serial number.
     * 
     * @return ASN1Integer The serial number
     */
    public function getSerialNumber(): ASN1Integer
    {
        return $this->serialNumber;
    }

    /**
     * Get the signature algorithm.
     * 
     * @return AlgorithmIdentifier The signature
     */
    public function getSignature(): AlgorithmIdentifier
    {
        return $this->signature;
    }

    /**
     * Get the issuer.
     * 
     * @return X509Name The issuer
     */
    public function getIssuer(): X509Name
    {
        return $this->issuer;
    }

    /**
     * Get the validity period.
     * 
     * @return Validity The validity
     */
    public function getValidity(): Validity
    {
        return $this->validity;
    }

    /**
     * Get the subject.
     * 
     * @return X509Name The subject
     */
    public function getSubject(): X509Name
    {
        return $this->subject;
    }

    /**
     * Get the subject public key info.
     * 
     * @return SubjectPublicKeyInfo The public key info
     */
    public function getSubjectPublicKeyInfo(): SubjectPublicKeyInfo
    {
        return $this->subjectPublicKeyInfo;
    }

    /**
     * Get the extensions.
     * 
     * @return ASN1Sequence|null The extensions
     */
    public function getExtensions(): ?ASN1Sequence
    {
        return $this->extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        $elements = [];
        
        // Version (explicit tag [0] if not v1)
        if ($this->version->getIntValue() !== 0) {
            $elements[] = ASN1TaggedObject::createExplicit(0, $this->version);
        }
        
        $elements[] = $this->serialNumber;
        $elements[] = $this->signature->toASN1Primitive();
        $elements[] = $this->issuer->toASN1Primitive();
        $elements[] = $this->validity->toASN1Primitive();
        $elements[] = $this->subject->toASN1Primitive();
        $elements[] = $this->subjectPublicKeyInfo->toASN1Primitive();
        
        // Extensions (explicit tag [3])
        if ($this->extensions !== null) {
            $elements[] = ASN1TaggedObject::createExplicit(3, $this->extensions);
        }
        
        return new DERSequence($elements);
    }

    /**
     * Create from ASN1Sequence.
     * 
     * @param ASN1Sequence $seq The sequence
     * @return self The TBS certificate
     */
    public static function fromSequence(ASN1Sequence $seq): self
    {
        // This is a simplified parser - full implementation would handle all optional fields
        throw new \RuntimeException("TBSCertificate parsing not yet implemented");
    }
}
