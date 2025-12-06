<?php

declare(strict_types=1);

namespace SmBc\Crypto\Signers;

use SmBc\Math\BigInteger;
use RuntimeException;

/**
 * Standard DSA encoding using ASN.1 DER format.
 * 
 * This implementation encodes DSA-style signatures (r, s) using the
 * standard ASN.1 DER format as defined in various standards including
 * PKCS#1, X9.62, and others.
 * 
 * The format is:
 *   SEQUENCE {
 *     r INTEGER,
 *     s INTEGER
 *   }
 * 
 * Based on: org.bouncycastle.crypto.signers.StandardDSAEncoding
 *           sm-js-bc/src/crypto/signers/StandardDSAEncoding.ts
 */
class StandardDSAEncoding implements DSAEncoding
{
    /**
     * Singleton instance.
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Encode (r, s) signature into ASN.1 DER format.
     */
    public function encode(BigInteger $n, BigInteger $r, BigInteger $s): string
    {
        // Validate inputs
        if ($r->compareTo(BigInteger::ZERO()) <= 0 || $r->compareTo($n) >= 0) {
            throw new RuntimeException('r component out of range');
        }
        if ($s->compareTo(BigInteger::ZERO()) <= 0 || $s->compareTo($n) >= 0) {
            throw new RuntimeException('s component out of range');
        }

        // Encode r as INTEGER
        $rBytes = $this->encodeInteger($r);
        
        // Encode s as INTEGER
        $sBytes = $this->encodeInteger($s);

        // Calculate total sequence length
        $sequenceLength = strlen($rBytes) + strlen($sBytes);
        
        // Build the complete DER encoding
        $lengthBytes = $this->lengthBytesCount($sequenceLength);
        $result = str_repeat("\x00", 1 + $lengthBytes + $sequenceLength);
        $offset = 0;

        // SEQUENCE tag
        $result[$offset++] = "\x30";
        
        // Encode sequence length
        $offset += $this->encodeLength($result, $offset, $sequenceLength);
        
        // Copy r bytes
        for ($i = 0; $i < strlen($rBytes); $i++) {
            $result[$offset++] = $rBytes[$i];
        }
        
        // Copy s bytes
        for ($i = 0; $i < strlen($sBytes); $i++) {
            $result[$offset++] = $sBytes[$i];
        }
        
        return $result;
    }

    /**
     * Decode ASN.1 DER signature into (r, s) components.
     */
    public function decode(BigInteger $n, string $encoding): array
    {
        $offset = 0;
        $length = strlen($encoding);

        // Check SEQUENCE tag
        if ($offset >= $length || ord($encoding[$offset]) !== 0x30) {
            throw new RuntimeException('Invalid DER encoding: expected SEQUENCE tag');
        }
        $offset++;

        // Parse sequence length
        [$sequenceLength, $lengthBytesUsed] = $this->parseLength($encoding, $offset);
        $offset += $lengthBytesUsed;

        // Check total length
        if ($offset + $sequenceLength !== $length) {
            throw new RuntimeException('Invalid DER encoding: incorrect sequence length');
        }

        // Parse r component
        [$r, $rLength] = $this->parseInteger($encoding, $offset);
        $offset += $rLength;

        // Parse s component
        [$s, $sLength] = $this->parseInteger($encoding, $offset);
        $offset += $sLength;

        // Check we consumed all bytes
        if ($offset !== $length) {
            throw new RuntimeException('Invalid DER encoding: extra bytes');
        }

        // Validate ranges
        if ($r->compareTo(BigInteger::ZERO()) <= 0 || $r->compareTo($n) >= 0) {
            throw new RuntimeException('Invalid signature: r component out of range');
        }
        if ($s->compareTo(BigInteger::ZERO()) <= 0 || $s->compareTo($n) >= 0) {
            throw new RuntimeException('Invalid signature: s component out of range');
        }

        return [$r, $s];
    }

    /**
     * Encode a positive integer as DER INTEGER.
     */
    private function encodeInteger(BigInteger $value): string
    {
        if ($value->compareTo(BigInteger::ZERO()) <= 0) {
            throw new RuntimeException('Integer must be positive');
        }

        // Convert to minimal byte representation
        $bytes = $value->toByteArray(false);
        
        // Add leading zero if MSB is set (to ensure positive interpretation)
        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }

        // Build DER INTEGER: tag (1 byte) + length + value bytes
        $lengthBytes = $this->lengthBytesCount(strlen($bytes));
        $result = str_repeat("\x00", 1 + $lengthBytes + strlen($bytes));
        $offset = 0;

        // INTEGER tag
        $result[$offset++] = "\x02";
        
        // Encode length
        $offset += $this->encodeLength($result, $offset, strlen($bytes));
        
        // Copy integer bytes
        for ($i = 0; $i < strlen($bytes); $i++) {
            $result[$offset++] = $bytes[$i];
        }
        
        return $result;
    }

    /**
     * Parse a DER INTEGER from the encoding.
     * 
     * @return array{BigInteger, int} Returns [value, bytesConsumed]
     */
    private function parseInteger(string $encoding, int $offset): array
    {
        if ($offset >= strlen($encoding)) {
            throw new RuntimeException('Unexpected end of DER encoding');
        }

        // Check INTEGER tag
        if (ord($encoding[$offset]) !== 0x02) {
            throw new RuntimeException('Invalid DER encoding: expected INTEGER tag');
        }
        $offset++;

        // Parse length
        [$intLength, $lengthBytesUsed] = $this->parseLength($encoding, $offset);
        $offset += $lengthBytesUsed;

        if ($offset + $intLength > strlen($encoding)) {
            throw new RuntimeException('Invalid DER encoding: integer extends beyond available data');
        }

        // Check for minimal encoding (no unnecessary leading zeros)
        if ($intLength > 1 && ord($encoding[$offset]) === 0x00 && (ord($encoding[$offset + 1]) & 0x80) === 0) {
            throw new RuntimeException('Invalid DER encoding: non-minimal integer');
        }

        // Extract integer bytes
        $integerBytes = substr($encoding, $offset, $intLength);
        $value = BigInteger::fromByteArray($integerBytes, false);

        if ($value->compareTo(BigInteger::ZERO()) <= 0) {
            throw new RuntimeException('Invalid DER encoding: non-positive integer');
        }

        return [$value, 1 + $lengthBytesUsed + $intLength];
    }

    /**
     * Calculate number of bytes needed to encode a length.
     */
    private function lengthBytesCount(int $length): int
    {
        if ($length < 0x80) {
            return 1;
        } else {
            $count = 1;
            $temp = $length;
            while ($temp > 0) {
                $count++;
                $temp = $temp >> 8;
            }
            return $count;
        }
    }

    /**
     * Encode length in DER format.
     * 
     * @return int Number of bytes written
     */
    private function encodeLength(string &$buffer, int $offset, int $length): int
    {
        if ($length < 0x80) {
            // Short form
            $buffer[$offset] = chr($length);
            return 1;
        } else {
            // Long form
            $lengthBytes = 0;
            $temp = $length;
            while ($temp > 0) {
                $lengthBytes++;
                $temp = $temp >> 8;
            }

            $buffer[$offset] = chr(0x80 | $lengthBytes);
            $writeOffset = $offset + 1;

            for ($i = $lengthBytes - 1; $i >= 0; $i--) {
                $buffer[$writeOffset++] = chr(($length >> ($i * 8)) & 0xFF);
            }

            return 1 + $lengthBytes;
        }
    }

    /**
     * Parse DER length encoding.
     * 
     * @return array{int, int} Returns [length, bytesConsumed]
     */
    private function parseLength(string $encoding, int $offset): array
    {
        if ($offset >= strlen($encoding)) {
            throw new RuntimeException('Unexpected end of DER encoding');
        }

        $firstByte = ord($encoding[$offset]);
        
        if (($firstByte & 0x80) === 0) {
            // Short form
            return [$firstByte, 1];
        } else {
            // Long form
            $lengthBytes = $firstByte & 0x7F;
            
            if ($lengthBytes === 0) {
                throw new RuntimeException('Invalid DER encoding: indefinite length not allowed');
            }
            if ($lengthBytes > 4) {
                throw new RuntimeException('Invalid DER encoding: length too large');
            }
            if ($offset + 1 + $lengthBytes > strlen($encoding)) {
                throw new RuntimeException('Invalid DER encoding: length extends beyond available data');
            }

            $length = 0;
            for ($i = 0; $i < $lengthBytes; $i++) {
                $length = ($length << 8) | ord($encoding[$offset + 1 + $i]);
            }

            // Check for minimal encoding
            if ($length < 0x80) {
                throw new RuntimeException('Invalid DER encoding: non-minimal length');
            }

            return [$length, 1 + $lengthBytes];
        }
    }
}
