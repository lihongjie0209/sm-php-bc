<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 NULL type.
 * 
 * Represents a null value in ASN.1 encoding.
 */
class ASN1Null extends ASN1Primitive
{
    /** @var ASN1Null|null Singleton instance */
    private static ?ASN1Null $instance = null;

    /**
     * Private constructor.
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton NULL instance.
     * 
     * @return self The instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ASN1OutputStream $out): void
    {
        $out->writeEncoded(ASN1Tags::NULL, ASN1Tags::NULL, '');
    }

    /**
     * {@inheritdoc}
     */
    public function asn1Equals(ASN1Primitive $other): bool
    {
        return $other instanceof ASN1Null;
    }

    /**
     * {@inheritdoc}
     */
    public function asn1HashCode(): int
    {
        return 0;
    }
}
