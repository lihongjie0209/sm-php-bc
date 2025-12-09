<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 SET type.
 * 
 * Represents an unordered collection of ASN.1 objects.
 * In DER encoding, SET elements must be sorted.
 */
class ASN1Set extends ASN1Primitive
{
    /** @var ASN1Encodable[] The set elements */
    private array $elements;
    
    /** @var bool Whether elements are sorted for DER */
    private bool $isSorted;

    /**
     * Constructor.
     * 
     * @param ASN1Encodable[] $elements The set elements
     * @param bool $needsSorting Whether elements need to be sorted for DER
     */
    public function __construct(array $elements = [], bool $needsSorting = true)
    {
        $this->elements = array_values($elements);
        $this->isSorted = false;
        
        if ($needsSorting && count($this->elements) > 0) {
            $this->sortElements();
        }
    }

    /**
     * Sort elements for DER encoding.
     */
    private function sortElements(): void
    {
        // Sort by DER encoded representation
        usort($this->elements, function($a, $b) {
            $aEncoded = $a->toASN1Primitive()->getEncoded();
            $bEncoded = $b->toASN1Primitive()->getEncoded();
            return strcmp($aEncoded, $bEncoded);
        });
        $this->isSorted = true;
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
        // Ensure sorted for DER
        if (!$this->isSorted) {
            $this->sortElements();
        }
        
        // Encode all elements to a temporary stream
        $tempOut = new ASN1OutputStream();
        foreach ($this->elements as $element) {
            $tempOut->writeObject($element);
        }
        
        $contents = $tempOut->toByteArray();
        $out->writeEncoded(
            ASN1Tags::SET | ASN1Tags::CONSTRUCTED,
            ASN1Tags::SET,
            $contents
        );
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1Set)) {
            return false;
        }
        
        if (count($this->elements) !== count($other->elements)) {
            return false;
        }
        
        // For sets, order doesn't matter, but we compare sorted versions
        $thisEncoded = $this->getEncoded();
        $otherEncoded = $other->getEncoded();
        
        return $thisEncoded === $otherEncoded;
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
     * Create an ASN1Set from an array of encodables.
     * 
     * @param ASN1Encodable[] $elements The elements
     * @return self The set
     */
    public static function fromArray(array $elements): self
    {
        return new self($elements);
    }
}
