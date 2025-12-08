<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * Base class for ASN.1 primitive objects.
 * 
 * Primitive objects are the basic building blocks of ASN.1 structures.
 */
abstract class ASN1Primitive extends ASN1Object
{
    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        return $this;
    }

    /**
     * Encode this primitive to DER format.
     * 
     * @param ASN1OutputStream $out The output stream
     */
    abstract public function encode(ASN1OutputStream $out): void;

    /**
     * Return whether two ASN.1 primitives are equal.
     * 
     * @param ASN1Primitive $other The other primitive
     * @return bool True if equal
     */
    abstract public function asn1Equals(ASN1Primitive $other): bool;

    /**
     * Return a hash code for this primitive.
     * 
     * @return int The hash code
     */
    abstract public function asn1HashCode(): int;

    /**
     * Create an ASN.1 primitive from a byte array.
     * 
     * @param string $data The DER encoded data
     * @return ASN1Primitive The decoded primitive
     */
    public static function fromByteArray(string $data): ASN1Primitive
    {
        $stream = new ASN1InputStream($data);
        return $stream->readObject();
    }
}
