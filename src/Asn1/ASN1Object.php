<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * Base class for ASN.1 objects.
 * 
 * ASN.1 (Abstract Syntax Notation One) is a standard interface description
 * language for defining data structures that can be serialized.
 */
abstract class ASN1Object implements ASN1Encodable
{
    /**
     * Return the DER encoding of this object.
     * 
     * @return string The DER encoded bytes
     */
    public function getEncoded(): string
    {
        $stream = new ASN1OutputStream();
        $stream->writeObject($this);
        return $stream->toByteArray();
    }

    /**
     * Return the DER encoding of this object using a specific encoding.
     * 
     * @param string $encoding The encoding to use (currently only "DER" supported)
     * @return string The encoded bytes
     */
    public function getEncodedAs(string $encoding): string
    {
        if ($encoding !== 'DER') {
            throw new \InvalidArgumentException("Only DER encoding is supported");
        }
        return $this->getEncoded();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function toASN1Primitive(): ASN1Primitive;

    /**
     * Create an ASN1Object from a byte array.
     * 
     * @param string $data The encoded data
     * @return ASN1Primitive The decoded object
     */
    public static function fromByteArray(string $data): ASN1Primitive
    {
        $stream = new ASN1InputStream($data);
        return $stream->readObject();
    }
}
