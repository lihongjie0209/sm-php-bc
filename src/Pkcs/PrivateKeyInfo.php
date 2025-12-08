<?php

declare(strict_types=1);

namespace SmBc\Pkcs;

use SmBc\Asn1\ASN1Encodable;
use SmBc\Asn1\ASN1Integer;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1OctetString;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\ASN1Set;
use SmBc\Asn1\ASN1TaggedObject;
use SmBc\Asn1\DERSequence;

/**
 * PrivateKeyInfo structure from PKCS#8.
 * 
 * ```
 * PrivateKeyInfo ::= SEQUENCE {
 *     version              Version,
 *     privateKeyAlgorithm  AlgorithmIdentifier,
 *     privateKey           OCTET STRING,
 *     attributes           [0] IMPLICIT Attributes OPTIONAL
 * }
 * 
 * Version ::= INTEGER { v1(0) }
 * ```
 */
class PrivateKeyInfo extends ASN1Object
{
    /** @var ASN1Integer The version (typically 0) */
    private ASN1Integer $version;
    
    /** @var AlgorithmIdentifier The algorithm identifier */
    private AlgorithmIdentifier $privateKeyAlgorithm;
    
    /** @var ASN1OctetString The private key bytes */
    private ASN1OctetString $privateKey;
    
    /** @var ASN1Set|null Optional attributes */
    private ?ASN1Set $attributes;

    /**
     * Constructor.
     * 
     * @param AlgorithmIdentifier $privateKeyAlgorithm The algorithm
     * @param ASN1OctetString $privateKey The private key
     * @param ASN1Set|null $attributes Optional attributes
     */
    public function __construct(
        AlgorithmIdentifier $privateKeyAlgorithm,
        ASN1OctetString $privateKey,
        ?ASN1Set $attributes = null
    ) {
        $this->version = new ASN1Integer(0);
        $this->privateKeyAlgorithm = $privateKeyAlgorithm;
        $this->privateKey = $privateKey;
        $this->attributes = $attributes;
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
     * Get the algorithm identifier.
     * 
     * @return AlgorithmIdentifier The algorithm
     */
    public function getPrivateKeyAlgorithm(): AlgorithmIdentifier
    {
        return $this->privateKeyAlgorithm;
    }

    /**
     * Get the private key bytes.
     * 
     * @return ASN1OctetString The private key
     */
    public function getPrivateKey(): ASN1OctetString
    {
        return $this->privateKey;
    }

    /**
     * Get the attributes.
     * 
     * @return ASN1Set|null The attributes
     */
    public function getAttributes(): ?ASN1Set
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        $elements = [
            $this->version,
            $this->privateKeyAlgorithm->toASN1Primitive(),
            $this->privateKey
        ];
        
        if ($this->attributes !== null) {
            $elements[] = ASN1TaggedObject::createImplicit(0, $this->attributes);
        }
        
        return new DERSequence($elements);
    }

    /**
     * Create a PrivateKeyInfo from an ASN1Sequence.
     * 
     * @param ASN1Sequence $seq The sequence
     * @return self The private key info
     */
    public static function fromSequence(ASN1Sequence $seq): self
    {
        if ($seq->size() < 3 || $seq->size() > 4) {
            throw new \InvalidArgumentException("Invalid PrivateKeyInfo sequence");
        }
        
        // Version (must be 0)
        $version = $seq->getObjectAt(0);
        if (!($version instanceof ASN1Integer) || $version->getIntValue() !== 0) {
            throw new \InvalidArgumentException("Invalid version");
        }
        
        // Algorithm identifier
        $algIdObj = $seq->getObjectAt(1);
        if (!($algIdObj instanceof ASN1Sequence)) {
            throw new \InvalidArgumentException("Invalid algorithm identifier");
        }
        $algId = AlgorithmIdentifier::fromSequence($algIdObj);
        
        // Private key
        $privateKey = $seq->getObjectAt(2);
        if (!($privateKey instanceof ASN1OctetString)) {
            throw new \InvalidArgumentException("Invalid private key");
        }
        
        // Optional attributes
        $attributes = null;
        if ($seq->size() == 4) {
            $attrObj = $seq->getObjectAt(3);
            if ($attrObj instanceof ASN1TaggedObject) {
                $attrSet = $attrObj->getObject();
                if ($attrSet instanceof ASN1Set) {
                    $attributes = $attrSet;
                }
            }
        }
        
        return new self($algId, $privateKey, $attributes);
    }

    /**
     * Parse PrivateKeyInfo from DER encoded bytes.
     * 
     * @param string $der The DER encoded data
     * @return self The private key info
     */
    public static function fromDER(string $der): self
    {
        $obj = ASN1Object::fromByteArray($der);
        if (!($obj instanceof ASN1Sequence)) {
            throw new \InvalidArgumentException("Invalid PrivateKeyInfo DER");
        }
        return self::fromSequence($obj);
    }
}
