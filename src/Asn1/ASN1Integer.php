<?php

declare(strict_types=1);

namespace SmBc\Asn1;

use SmBc\Math\BigInteger;

/**
 * ASN.1 INTEGER type.
 * 
 * Represents an integer value in ASN.1 encoding.
 */
class ASN1Integer extends ASN1Primitive
{
    /** @var BigInteger The integer value */
    private BigInteger $value;

    /**
     * Constructor.
     * 
     * @param BigInteger|int $value The integer value
     */
    public function __construct($value)
    {
        if ($value instanceof BigInteger) {
            $this->value = $value;
        } else {
            $this->value = new BigInteger($value);
        }
    }

    /**
     * Get the integer value.
     * 
     * @return BigInteger The value
     */
    public function getValue(): BigInteger
    {
        return $this->value;
    }

    /**
     * Get the value as a PHP integer (if it fits).
     * 
     * @return int The value
     */
    public function getIntValue(): int
    {
        return (int)$this->value->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        $bytes = $this->value->toByteArray(true); // unsigned big-endian
        $out->writeEncoded(ASN1Tags::INTEGER, ASN1Tags::INTEGER, $bytes);
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1Integer)) {
            return false;
        }
        return $this->value->equals($other->value);
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        return (int)$this->value->toString();
    }

    /**
     * Create an ASN1Integer from a byte array.
     * 
     * @param string $bytes The encoded bytes
     * @return self The decoded integer
     */
    public static function fromBytes(string $bytes): self
    {
        return new self(new BigInteger($bytes, 256));
    }
}
