<?php

namespace SmBc\X509;

use SmBc\Asn1\ASN1BitString;
use SmBc\Asn1\ASN1Integer;
use SmBc\Math\BigInteger;
use SmBc\Pkcs\AlgorithmIdentifier;
use SmBc\Pkcs\SubjectPublicKeyInfo;

/**
 * X.509 Certificate Builder
 * 
 * Utility class for building X.509 certificates.
 */
class X509CertificateBuilder
{
    private ?ASN1Integer $version = null;
    private ?ASN1Integer $serialNumber = null;
    private ?AlgorithmIdentifier $signature = null;
    private ?X509Name $issuer = null;
    private ?Validity $validity = null;
    private ?X509Name $subject = null;
    private ?SubjectPublicKeyInfo $subjectPublicKeyInfo = null;
    private ?X509Extensions $extensions = null;
    
    /**
     * Set certificate version (0 = v1, 1 = v2, 2 = v3)
     */
    public function setVersion(int $version): self
    {
        $this->version = new ASN1Integer($version);
        return $this;
    }
    
    /**
     * Set serial number
     */
    public function setSerialNumber($serialNumber): self
    {
        if ($serialNumber instanceof BigInteger) {
            $this->serialNumber = new ASN1Integer($serialNumber);
        } else if ($serialNumber instanceof ASN1Integer) {
            $this->serialNumber = $serialNumber;
        } else if (is_int($serialNumber) || is_string($serialNumber)) {
            $this->serialNumber = new ASN1Integer(new BigInteger($serialNumber));
        } else {
            throw new \InvalidArgumentException('Invalid serial number type');
        }
        return $this;
    }
    
    /**
     * Set signature algorithm
     */
    public function setSignatureAlgorithm(AlgorithmIdentifier $signature): self
    {
        $this->signature = $signature;
        return $this;
    }
    
    /**
     * Set issuer name
     */
    public function setIssuer(X509Name $issuer): self
    {
        $this->issuer = $issuer;
        return $this;
    }
    
    /**
     * Set validity period
     */
    public function setValidity(Validity $validity): self
    {
        $this->validity = $validity;
        return $this;
    }
    
    /**
     * Set subject name
     */
    public function setSubject(X509Name $subject): self
    {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * Set subject public key info
     */
    public function setSubjectPublicKeyInfo(SubjectPublicKeyInfo $publicKeyInfo): self
    {
        $this->subjectPublicKeyInfo = $publicKeyInfo;
        return $this;
    }
    
    /**
     * Set extensions
     */
    public function setExtensions(X509Extensions $extensions): self
    {
        $this->extensions = $extensions;
        return $this;
    }
    
    /**
     * Build TBSCertificate (To-Be-Signed Certificate)
     */
    public function buildTBSCertificate(): TBSCertificate
    {
        if ($this->serialNumber === null) {
            throw new \RuntimeException('Serial number is required');
        }
        if ($this->signature === null) {
            throw new \RuntimeException('Signature algorithm is required');
        }
        if ($this->issuer === null) {
            throw new \RuntimeException('Issuer is required');
        }
        if ($this->validity === null) {
            throw new \RuntimeException('Validity is required');
        }
        if ($this->subject === null) {
            throw new \RuntimeException('Subject is required');
        }
        if ($this->subjectPublicKeyInfo === null) {
            throw new \RuntimeException('Subject public key info is required');
        }
        
        $version = $this->version ?? new ASN1Integer(2); // Default to v3
        
        return new TBSCertificate(
            $version,
            $this->serialNumber,
            $this->signature,
            $this->issuer,
            $this->validity,
            $this->subject,
            $this->subjectPublicKeyInfo,
            $this->extensions
        );
    }
    
    /**
     * Build complete X509Certificate with signature
     */
    public function build(AlgorithmIdentifier $signatureAlgorithm, ASN1BitString $signatureValue): X509Certificate
    {
        $tbsCert = $this->buildTBSCertificate();
        return new X509Certificate($tbsCert, $signatureAlgorithm, $signatureValue);
    }
}
