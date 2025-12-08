<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 OBJECT IDENTIFIER type.
 * 
 * Represents an object identifier (OID) in ASN.1 encoding.
 * OIDs are sequences of integers like "1.2.840.10045.3.1.7".
 */
class ASN1ObjectIdentifier extends ASN1Primitive
{
    /** @var string The OID string (e.g., "1.2.840.113549.1.1.1") */
    private string $identifier;
    
    /** @var string|null Cached encoded bytes */
    private ?string $encodedBytes = null;

    /**
     * Constructor.
     * 
     * @param string $identifier The OID string
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
        $this->validate();
    }

    /**
     * Validate the OID format.
     */
    private function validate(): void
    {
        if (!preg_match('/^[0-2](\.\d+)*$/', $this->identifier)) {
            throw new \InvalidArgumentException("Invalid OID format: {$this->identifier}");
        }
        
        $parts = explode('.', $this->identifier);
        if (count($parts) < 2) {
            throw new \InvalidArgumentException("OID must have at least 2 components");
        }
        
        $first = (int)$parts[0];
        $second = (int)$parts[1];
        
        if ($first > 2 || ($first < 2 && $second > 39)) {
            throw new \InvalidArgumentException("Invalid OID arc values");
        }
    }

    /**
     * Get the OID string.
     * 
     * @return string The identifier
     */
    public function getId(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        if ($this->encodedBytes === null) {
            $this->encodedBytes = $this->encodeContents();
        }
        
        $out->writeEncoded(ASN1Tags::OBJECT_IDENTIFIER, ASN1Tags::OBJECT_IDENTIFIER, $this->encodedBytes);
    }

    /**
     * Encode the OID contents.
     * 
     * @return string The encoded contents
     */
    private function encodeContents(): string
    {
        $parts = array_map('intval', explode('.', $this->identifier));
        
        // First two components are encoded as: first * 40 + second
        $result = chr($parts[0] * 40 + $parts[1]);
        
        // Encode remaining components
        for ($i = 2; $i < count($parts); $i++) {
            $result .= $this->encodeSubIdentifier($parts[$i]);
        }
        
        return $result;
    }

    /**
     * Encode a sub-identifier using base-128 encoding.
     * 
     * @param int $value The value to encode
     * @return string The encoded bytes
     */
    private function encodeSubIdentifier(int $value): string
    {
        if ($value < 128) {
            return chr($value);
        }
        
        $bytes = [];
        $bytes[] = $value & 0x7F;
        
        while ($value > 127) {
            $value >>= 7;
            $bytes[] = ($value & 0x7F) | 0x80;
        }
        
        $result = '';
        for ($i = count($bytes) - 1; $i >= 0; $i--) {
            $result .= chr($bytes[$i]);
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1ObjectIdentifier)) {
            return false;
        }
        return $this->identifier === $other->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        return crc32($this->identifier);
    }

    /**
     * Create an ASN1ObjectIdentifier from contents.
     * 
     * @param string $contents The encoded contents
     * @return self The OID
     */
    public static function fromContents(string $contents): self
    {
        if (strlen($contents) < 1) {
            throw new \InvalidArgumentException("OID contents too short");
        }
        
        // Decode first byte into first two components
        $first = ord($contents[0]);
        $arc1 = min(2, intdiv($first, 40));
        $arc2 = $first - ($arc1 * 40);
        
        $parts = [$arc1, $arc2];
        
        // Decode remaining sub-identifiers
        $i = 1;
        while ($i < strlen($contents)) {
            $value = 0;
            do {
                $byte = ord($contents[$i++]);
                $value = ($value << 7) | ($byte & 0x7F);
            } while (($byte & 0x80) !== 0 && $i < strlen($contents));
            
            $parts[] = $value;
        }
        
        return new self(implode('.', $parts));
    }
}
