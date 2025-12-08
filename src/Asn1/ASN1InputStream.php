<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 input stream for reading DER encoded data.
 * 
 * This class provides methods for reading ASN.1 objects from DER
 * (Distinguished Encoding Rules) format.
 */
class ASN1InputStream
{
    /** @var string The input buffer */
    private string $buffer;
    
    /** @var int Current position in buffer */
    private int $position = 0;
    
    /** @var int Length of buffer */
    private int $length;

    /**
     * Constructor.
     * 
     * @param string $data The encoded data
     */
    public function __construct(string $data)
    {
        $this->buffer = $data;
        $this->length = strlen($data);
    }

    /**
     * Read an ASN.1 object from the stream.
     * 
     * @return ASN1Primitive The decoded object
     */
    public function readObject(): ASN1Primitive
    {
        $tag = $this->readByte();
        $length = $this->readLength();
        $contents = $this->readBytes($length);

        return $this->buildObject($tag, $contents);
    }

    /**
     * Read a single byte.
     * 
     * @return int The byte value
     */
    private function readByte(): int
    {
        if ($this->position >= $this->length) {
            throw new \RuntimeException("Unexpected end of stream");
        }
        return ord($this->buffer[$this->position++]);
    }

    /**
     * Read multiple bytes.
     * 
     * @param int $count The number of bytes to read
     * @return string The bytes
     */
    private function readBytes(int $count): string
    {
        if ($this->position + $count > $this->length) {
            throw new \RuntimeException("Unexpected end of stream");
        }
        $bytes = substr($this->buffer, $this->position, $count);
        $this->position += $count;
        return $bytes;
    }

    /**
     * Read a DER encoded length.
     * 
     * @return int The length value
     */
    private function readLength(): int
    {
        $byte = $this->readByte();
        
        if ($byte < 128) {
            // Short form
            return $byte;
        }
        
        // Long form
        $numBytes = $byte & 0x7F;
        $length = 0;
        
        for ($i = 0; $i < $numBytes; $i++) {
            $length = ($length << 8) | $this->readByte();
        }
        
        return $length;
    }

    /**
     * Build an ASN.1 object from tag and contents.
     * 
     * @param int $tag The tag byte
     * @param string $contents The contents
     * @return ASN1Primitive The built object
     */
    private function buildObject(int $tag, string $contents): ASN1Primitive
    {
        // For now, return a generic primitive
        // This will be expanded as we implement specific types
        throw new \RuntimeException("ASN.1 decoding not yet fully implemented. Tag: " . $tag);
    }
}
