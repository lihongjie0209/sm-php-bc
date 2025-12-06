<?php

declare(strict_types=1);

namespace SmBc\Crypto\Signers;

use SmBc\Math\BigInteger;

/**
 * DSA signature encoding interface.
 * 
 * This interface defines how DSA-style signatures (r, s) are encoded
 * and decoded to/from byte arrays. Different encodings may be used
 * (e.g., ASN.1 DER, raw concatenation, etc.).
 * 
 * Based on: org.bouncycastle.crypto.signers.DSAEncoding
 *           sm-js-bc/src/crypto/signers/DSAEncoding.ts
 */
interface DSAEncoding
{
    /**
     * Encode the (r, s) signature components into a byte array.
     * 
     * @param BigInteger $n The order of the base point of the curve
     * @param BigInteger $r The r component of the signature
     * @param BigInteger $s The s component of the signature
     * @return string Encoded signature bytes
     * @throws \RuntimeException if encoding fails
     */
    public function encode(BigInteger $n, BigInteger $r, BigInteger $s): string;

    /**
     * Decode signature bytes into (r, s) components.
     * 
     * @param BigInteger $n The order of the base point of the curve
     * @param string $encoding Encoded signature bytes
     * @return array{BigInteger, BigInteger} Array containing [r, s] components
     * @throws \RuntimeException if decoding fails or signature is invalid
     */
    public function decode(BigInteger $n, string $encoding): array;
}
