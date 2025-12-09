<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 BIT STRING type.
 * 
 * Represents a sequence of bits in ASN.1 encoding.
 */
class ASN1BitString extends ASN1Primitive
{
    /** @var string The bit string data */
    private string $data;
    
    /** @var int Number of unused bits in the last byte */
    private int $padBits;

    /**
     * Constructor.
     * 
     * @param string $data The bit string data
     * @param int $padBits Number of unused bits in last byte (0-7)
     */
    public function __construct(string $data, int $padBits = 0)
    {
        if ($padBits < 0 || $padBits > 7) {
            throw new \InvalidArgumentException("Pad bits must be between 0 and 7");
        }
        
        $this->data = $data;
        $this->padBits = $padBits;
    }

    /**
     * Get the bit string data.
     * 
     * @return string The data
     */
    public function getBytes(): string
    {
        return $this->data;
    }

    /**
     * Get the number of padding bits.
     * 
     * @return int The padding bits
     */
    public function getPadBits(): int
    {
        return $this->padBits;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        $contents = chr($this->padBits) . $this->data;
        $out->writeEncoded(ASN1Tags::BIT_STRING, ASN1Tags::BIT_STRING, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1BitString)) {
            return false;
        }
        return $this->data === $other->data && $this->padBits === $other->padBits;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        return crc32($this->data) ^ $this->padBits;
    }

    /**
     * Create an ASN1BitString from contents.
     * 
     * @param string $contents The encoded contents (includes pad byte)
     * @return self The bit string
     */
    public static function fromContents(string $contents): self
    {
        if (strlen($contents) < 1) {
            throw new \InvalidArgumentException("BIT STRING contents too short");
        }
        
        $padBits = ord($contents[0]);
        $data = substr($contents, 1);
        
        return new self($data, $padBits);
    }
}
