<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 BOOLEAN type.
 * 
 * Represents a boolean value in ASN.1 encoding.
 */
class ASN1Boolean extends ASN1Primitive
{
    /** @var bool The boolean value */
    private bool $value;

    /** @var ASN1Boolean|null Cached TRUE instance */
    private static ?ASN1Boolean $trueInstance = null;
    
    /** @var ASN1Boolean|null Cached FALSE instance */
    private static ?ASN1Boolean $falseInstance = null;

    /**
     * Constructor.
     * 
     * @param bool $value The boolean value
     */
    private function __construct(bool $value)
    {
        $this->value = $value;
    }

    /**
     * Get an ASN1Boolean instance.
     * 
     * @param bool $value The boolean value
     * @return self The instance
     */
    public static function getInstance(bool $value): self
    {
        if ($value) {
            if (self::$trueInstance === null) {
                self::$trueInstance = new self(true);
            }
            return self::$trueInstance;
        } else {
            if (self::$falseInstance === null) {
                self::$falseInstance = new self(false);
            }
            return self::$falseInstance;
        }
    }

    /**
     * Get the boolean value.
     * 
     * @return bool The value
     */
    public function isTrue(): bool
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        $contents = $this->value ? "\xFF" : "\x00";
        $out->writeEncoded(ASN1Tags::BOOLEAN, ASN1Tags::BOOLEAN, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        if (!($other instanceof ASN1Boolean)) {
            return false;
        }
        return $this->value === $other->value;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        return $this->value ? 1 : 0;
    }

    /**
     * Create an ASN1Boolean from contents.
     * 
     * @param string $contents The encoded contents
     * @return self The boolean
     */
    public static function fromContents(string $contents): self
    {
        if (strlen($contents) !== 1) {
            throw new \InvalidArgumentException("BOOLEAN must be exactly 1 byte");
        }
        
        $byte = ord($contents[0]);
        return self::getInstance($byte !== 0);
    }
}
