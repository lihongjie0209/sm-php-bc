<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * Base interface for ASN.1 encodable objects.
 * 
 * This interface defines the contract for objects that can be encoded
 * to ASN.1 format (typically DER encoding).
 */
interface ASN1Encodable
{
    /**
     * Return an ASN1Primitive representation of this object.
     * 
     * @return ASN1Primitive The primitive representation
     */
    public function toASN1Primitive(): ASN1Primitive;
}
