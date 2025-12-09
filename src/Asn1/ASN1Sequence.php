<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 SEQUENCE type.
 * 
 * Represents an ordered collection of ASN.1 objects.
 */
class ASN1Sequence extends ASN1Primitive
{
    /** @var ASN1Encodable[] The sequence elements */
    private array $elements;

    /**
     * Constructor.
     * 
     * @param ASN1Encodable[] $elements The sequence elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = array_values($elements); // Re-index to ensure numeric keys
    }

    /**
     * Get the number of elements.
     * 
     * @return int The size
     */
    public function size(): int
    {
        return count($this->elements);
    }

    /**
     * Get an element at a specific index.
     * 
     * @param int $index The index
     * @return ASN1Encodable The element
     */
    public function getObjectAt(int $index): ASN1Encodable
    {
        if ($index < 0 || $index >= count($this->elements)) {
            throw new \OutOfBoundsException("Index out of bounds: $index");
        }
        return $this->elements[$index];
    }

    /**
     * Get all elements.
     * 
     * @return ASN1Encodable[] The elements
     */
    public function getObjects(): array
    {
        return $this->elements;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        // Encode all elements to a temporary stream
        $tempOut = new ASN1OutputStream();
        foreach ($this->elements as $element) {
            $tempOut->writeObject($element);
        }
        
        $contents = $tempOut->toByteArray();
        $out->writeEncoded(
            ASN1Tags::SEQUENCE | ASN1Tags::CONSTRUCTED,
            ASN1Tags::SEQUENCE,
            $contents
        );
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1Sequence)) {
            return false;
        }
        
        if (count($this->elements) !== count($other->elements)) {
            return false;
        }
        
        for ($i = 0; $i < count($this->elements); $i++) {
            $thisElem = $this->elements[$i]->toASN1Primitive();
            $otherElem = $other->elements[$i]->toASN1Primitive();
            
            if (!$thisElem->asn1Equals($otherElem)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        $hash = 0;
        foreach ($this->elements as $element) {
            $hash ^= $element->toASN1Primitive()->asn1HashCode();
        }
        return $hash;
    }

    /**
     * Create an ASN1Sequence from an array of encodables.
     * 
     * @param ASN1Encodable[] $elements The elements
     * @return self The sequence
     */
    public static function fromArray(array $elements): self
    {
        return new self($elements);
    }
}
