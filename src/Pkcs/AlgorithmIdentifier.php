<?php

declare(strict_types=1);

namespace SmBc\Pkcs;

use SmBc\Asn1\ASN1Encodable;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1ObjectIdentifier;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;

/**
 * AlgorithmIdentifier structure from PKCS#8.
 * 
 * ```
 * AlgorithmIdentifier ::= SEQUENCE {
 *     algorithm  OBJECT IDENTIFIER,
 *     parameters ANY DEFINED BY algorithm OPTIONAL
 * }
 * ```
 */
class AlgorithmIdentifier extends ASN1Object
{
    /** @var ASN1ObjectIdentifier The algorithm OID */
    private ASN1ObjectIdentifier $algorithm;
    
    /** @var ASN1Encodable|null The algorithm parameters */
    private ?ASN1Encodable $parameters;

    /** SM2 algorithm OID (1.2.156.10197.1.301) */
    public const OID_SM2 = '1.2.156.10197.1.301';
    
    /** SM3 algorithm OID (1.2.156.10197.1.401) */
    public const OID_SM3 = '1.2.156.10197.1.401';
    
    /** SM4 algorithm OID (1.2.156.10197.1.104) */
    public const OID_SM4 = '1.2.156.10197.1.104';
    
    /** EC Public Key OID (1.2.840.10045.2.1) */
    public const OID_EC_PUBLIC_KEY = '1.2.840.10045.2.1';

    /**
     * Constructor.
     * 
     * @param ASN1ObjectIdentifier $algorithm The algorithm OID
     * @param ASN1Encodable|null $parameters The parameters (optional)
     */
    public function __construct(ASN1ObjectIdentifier $algorithm, ?ASN1Encodable $parameters = null)
    {
        $this->algorithm = $algorithm;
        $this->parameters = $parameters;
    }

    /**
     * Get the algorithm OID.
     * 
     * @return ASN1ObjectIdentifier The algorithm
     */
    public function getAlgorithm(): ASN1ObjectIdentifier
    {
        return $this->algorithm;
    }

    /**
     * Get the parameters.
     * 
     * @return ASN1Encodable|null The parameters
     */
    public function getParameters(): ?ASN1Encodable
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        $elements = [$this->algorithm];
        
        if ($this->parameters !== null) {
            $elements[] = $this->parameters;
        }
        
        return new DERSequence($elements);
    }

    /**
     * Create an AlgorithmIdentifier from an ASN1Sequence.
     * 
     * @param ASN1Sequence $seq The sequence
     * @return self The algorithm identifier
     */
    public static function fromSequence(ASN1Sequence $seq): self
    {
        if ($seq->size() < 1 || $seq->size() > 2) {
            throw new \InvalidArgumentException("Invalid AlgorithmIdentifier sequence");
        }
        
        $algorithm = $seq->getObjectAt(0);
        if (!($algorithm instanceof ASN1ObjectIdentifier)) {
            throw new \InvalidArgumentException("First element must be an OID");
        }
        
        $parameters = null;
        if ($seq->size() == 2) {
            $parameters = $seq->getObjectAt(1);
        }
        
        return new self($algorithm, $parameters);
    }

    /**
     * Create an AlgorithmIdentifier for SM2.
     * 
     * @param ASN1Encodable|null $parameters The parameters (typically the curve OID)
     * @return self The algorithm identifier
     */
    public static function forSM2(?ASN1Encodable $parameters = null): self
    {
        return new self(
            new ASN1ObjectIdentifier(self::OID_SM2),
            $parameters
        );
    }

    /**
     * Create an AlgorithmIdentifier for EC public key.
     * 
     * @param ASN1Encodable $parameters The curve parameters (required for EC)
     * @return self The algorithm identifier
     */
    public static function forECPublicKey(ASN1Encodable $parameters): self
    {
        return new self(
            new ASN1ObjectIdentifier(self::OID_EC_PUBLIC_KEY),
            $parameters
        );
    }
}
