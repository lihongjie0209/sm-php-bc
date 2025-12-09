<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 Tagged Object.
 * 
 * Represents an object with a context-specific tag for disambiguation.
 * Used in CHOICE types and optional fields.
 */
class ASN1TaggedObject extends ASN1Primitive
{
    /** @var int The tag number */
    private int $tagNo;
    
    /** @var bool Whether the tagging is explicit */
    private bool $explicit;
    
    /** @var ASN1Encodable The wrapped object */
    private ASN1Encodable $obj;

    /**
     * Constructor.
     * 
     * @param bool $explicit Whether tagging is explicit (true) or implicit (false)
     * @param int $tagNo The tag number
     * @param ASN1Encodable $obj The wrapped object
     */
    public function __construct(bool $explicit, int $tagNo, ASN1Encodable $obj)
    {
        $this->explicit = $explicit;
        $this->tagNo = $tagNo;
        $this->obj = $obj;
    }

    /**
     * Get the tag number.
     * 
     * @return int The tag number
     */
    public function getTagNo(): int
    {
        return $this->tagNo;
    }

    /**
     * Check if tagging is explicit.
     * 
     * @return bool True if explicit
     */
    public function isExplicit(): bool
    {
        return $this->explicit;
    }

    /**
     * Get the wrapped object.
     * 
     * @return ASN1Encodable The object
     */
    public function getObject(): ASN1Encodable
    {
        return $this->obj;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        $primitive = $this->obj->toASN1Primitive();
        
        if ($this->explicit) {
            // Explicit tagging: wrap the encoded object
            $tempOut = new ASN1OutputStream();
            $tempOut->writeObject($primitive);
            $contents = $tempOut->toByteArray();
            
            $out->writeEncoded(
                ASN1Tags::CONTEXT_SPECIFIC | ASN1Tags::CONSTRUCTED,
                $this->tagNo,
                $contents
            );
        } else {
            // Implicit tagging: replace the object's tag
            $tempOut = new ASN1OutputStream();
            $tempOut->writeObject($primitive);
            $encoded = $tempOut->toByteArray();
            
            // Skip the original tag and length, write new tag with original contents
            // This is a simplified implementation
            $out->writeEncoded(
                ASN1Tags::CONTEXT_SPECIFIC,
                $this->tagNo,
                substr($encoded, $this->skipTagAndLength($encoded))
            );
        }
    }

    /**
     * Skip tag and length bytes to get to contents.
     * 
     * @param string $encoded The encoded data
     * @return int The offset to contents
     */
    private function skipTagAndLength(string $encoded): int
    {
        $offset = 1; // Skip tag
        
        $lengthByte = ord($encoded[$offset++]);
        if ($lengthByte >= 128) {
            // Long form
            $numBytes = $lengthByte & 0x7F;
            $offset += $numBytes;
        }
        
        return $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1TaggedObject)) {
            return false;
        }
        
        return $this->tagNo === $other->tagNo
            && $this->explicit === $other->explicit
            && $this->obj->toASN1Primitive()->asn1Equals($other->obj->toASN1Primitive());
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        return $this->tagNo ^ ($this->explicit ? 1 : 0) ^ $this->obj->toASN1Primitive()->asn1HashCode();
    }

    /**
     * Create an explicit tagged object.
     * 
     * @param int $tagNo The tag number
     * @param ASN1Encodable $obj The object
     * @return self The tagged object
     */
    public static function createExplicit(int $tagNo, ASN1Encodable $obj): self
    {
        return new self(true, $tagNo, $obj);
    }

    /**
     * Create an implicit tagged object.
     * 
     * @param int $tagNo The tag number
     * @param ASN1Encodable $obj The object
     * @return self The tagged object
     */
    public static function createImplicit(int $tagNo, ASN1Encodable $obj): self
    {
        return new self(false, $tagNo, $obj);
    }
}
