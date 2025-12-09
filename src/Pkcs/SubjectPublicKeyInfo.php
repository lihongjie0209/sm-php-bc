<?php

declare(strict_types=1);

namespace SmBc\Pkcs;

use SmBc\Asn1\ASN1BitString;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;

/**
 * SubjectPublicKeyInfo structure from X.509.
 * 
 * ```
 * SubjectPublicKeyInfo ::= SEQUENCE {
 *     algorithm        AlgorithmIdentifier,
 *     subjectPublicKey BIT STRING
 * }
 * ```
 */
class SubjectPublicKeyInfo extends ASN1Object
{
    /** @var AlgorithmIdentifier The algorithm identifier */
    private AlgorithmIdentifier $algorithm;
    
    /** @var ASN1BitString The public key bits */
    private ASN1BitString $subjectPublicKey;

    /**
     * Constructor.
     * 
     * @param AlgorithmIdentifier $algorithm The algorithm
     * @param ASN1BitString $subjectPublicKey The public key
     */
    public function __construct(AlgorithmIdentifier $algorithm, ASN1BitString $subjectPublicKey)
    {
        $this->algorithm = $algorithm;
        $this->subjectPublicKey = $subjectPublicKey;
    }

    /**
     * Get the algorithm identifier.
     * 
     * @return AlgorithmIdentifier The algorithm
     */
    public function getAlgorithm(): AlgorithmIdentifier
    {
        return $this->algorithm;
    }

    /**
     * Get the subject public key.
     * 
     * @return ASN1BitString The public key
     */
    public function getSubjectPublicKey(): ASN1BitString
    {
        return $this->subjectPublicKey;
    }

    /**
     * Get the public key bytes.
     * 
     * @return string The public key bytes
     */
    public function getPublicKeyData(): string
    {
        return $this->subjectPublicKey->getBytes();
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        return new DERSequence([
            $this->algorithm->toASN1Primitive(),
            $this->subjectPublicKey
        ]);
    }

    /**
     * Create a SubjectPublicKeyInfo from an ASN1Sequence.
     * 
     * @param ASN1Sequence $seq The sequence
     * @return self The public key info
     */
    public static function fromSequence(ASN1Sequence $seq): self
    {
        if ($seq->size() !== 2) {
            throw new \InvalidArgumentException("Invalid SubjectPublicKeyInfo sequence");
        }
        
        // Algorithm identifier
        $algIdObj = $seq->getObjectAt(0);
        if (!($algIdObj instanceof ASN1Sequence)) {
            throw new \InvalidArgumentException("Invalid algorithm identifier");
        }
        $algId = AlgorithmIdentifier::fromSequence($algIdObj);
        
        // Public key
        $publicKey = $seq->getObjectAt(1);
        if (!($publicKey instanceof ASN1BitString)) {
            throw new \InvalidArgumentException("Invalid public key");
        }
        
        return new self($algId, $publicKey);
    }

    /**
     * Parse SubjectPublicKeyInfo from DER encoded bytes.
     * 
     * @param string $der The DER encoded data
     * @return self The public key info
     */
    public static function fromDER(string $der): self
    {
        $obj = ASN1Object::fromByteArray($der);
        if (!($obj instanceof ASN1Sequence)) {
            throw new \InvalidArgumentException("Invalid SubjectPublicKeyInfo DER");
        }
        return self::fromSequence($obj);
    }
}
