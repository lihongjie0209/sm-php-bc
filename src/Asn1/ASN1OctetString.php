<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 OCTET STRING type.
 * 
 * Represents a sequence of octets (bytes) in ASN.1 encoding.
 */
class ASN1OctetString extends ASN1Primitive
{
    /** @var string The octet string value */
    private string $value;

    /**
     * Constructor.
     * 
     * @param string $value The octet string value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Get the octet string value.
     * 
     * @return string The value
     */
    public function getOctets(): string
    {
        return $this->value;
    }

    /**
     * Get the length of the octet string.
     * 
     * @return int The length
     */
    public function getOctetsLength(): int
    {
        return strlen($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        $out->writeEncoded(ASN1Tags::OCTET_STRING, ASN1Tags::OCTET_STRING, $this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1OctetString)) {
            return false;
        }
        return $this->value === $other->value;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        return crc32($this->value);
    }

    /**
     * Create an ASN1OctetString from contents.
     * 
     * @param string $contents The contents
     * @return self The octet string
     */
    public static function fromContents(string $contents): self
    {
        return new self($contents);
    }
}
