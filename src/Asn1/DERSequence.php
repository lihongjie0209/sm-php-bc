<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * DER SEQUENCE - Distinguished Encoding Rules variant.
 * 
 * This is the most commonly used variant of ASN1Sequence.
 * DER encoding ensures a unique encoding for each value.
 */
class DERSequence extends ASN1Sequence
{
    /**
     * Constructor.
     * 
     * @param ASN1Encodable[] $elements The sequence elements
     */
    public function __construct(array $elements = [])
    {
        parent::__construct($elements);
    }

    /**
     * Create a DERSequence from a single element.
     * 
     * @param ASN1Encodable $element The element
     * @return self The sequence
     */
    public static function fromElement(ASN1Encodable $element): self
    {
        return new self([$element]);
    }

    /**
     * Create a DERSequence from an array of encodables.
     * 
     * @param ASN1Encodable[] $elements The elements
     * @return self The sequence
     */
    public static function fromArray(array $elements): self
    {
        return new self($elements);
    }
}
