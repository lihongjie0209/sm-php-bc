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
        switch ($tag) {
            case ASN1Tags::INTEGER:
                return ASN1Integer::fromContents($contents);
            
            case ASN1Tags::OCTET_STRING:
                return new ASN1OctetString($contents);
            
            case ASN1Tags::BIT_STRING:
                if (strlen($contents) < 1) {
                    throw new \RuntimeException("Invalid BIT STRING");
                }
                $padBits = ord($contents[0]);
                $bytes = substr($contents, 1);
                return new ASN1BitString($bytes, $padBits);
            
            case ASN1Tags::BOOLEAN:
                $value = strlen($contents) > 0 && ord($contents[0]) !== 0;
                return ASN1Boolean::getInstance($value);
            
            case ASN1Tags::NULL:
                return ASN1Null::getInstance();
            
            case ASN1Tags::OBJECT_IDENTIFIER:
                return ASN1ObjectIdentifier::fromContents($contents);
            
            case ASN1Tags::SEQUENCE:
            case ASN1Tags::SEQUENCE | ASN1Tags::CONSTRUCTED:
                return $this->parseSequence($contents);
            
            case ASN1Tags::SET:
            case ASN1Tags::SET | ASN1Tags::CONSTRUCTED:
                return $this->parseSet($contents);
            
            default:
                // Check if it's a tagged object (context-specific)
                if (($tag & 0xC0) == 0x80) {
                    $tagNo = $tag & 0x1F;
                    $explicit = ($tag & 0x20) != 0;
                    return new ASN1TaggedObject($explicit, $tagNo, $this->parseContents($contents));
                }
                throw new \RuntimeException("Unsupported ASN.1 tag: 0x" . dechex($tag));
        }
    }

    /**
     * Parse SEQUENCE contents into elements.
     * 
     * @param string $contents The SEQUENCE contents
     * @return ASN1Sequence The decoded sequence
     */
    private function parseSequence(string $contents): ASN1Sequence
    {
        $elements = $this->parseContents($contents);
        return new DERSequence($elements);
    }

    /**
     * Parse SET contents into elements.
     * 
     * @param string $contents The SET contents
     * @return ASN1Set The decoded set
     */
    private function parseSet(string $contents): ASN1Set
    {
        $elements = $this->parseContents($contents);
        return new ASN1Set($elements);
    }

    /**
     * Parse constructed contents into an array of elements.
     * 
     * @param string $contents The contents
     * @return array|ASN1Encodable The parsed elements or single element
     */
    private function parseContents(string $contents)
    {
        $elements = [];
        $stream = new ASN1InputStream($contents);
        
        while ($stream->position < $stream->length) {
            $elements[] = $stream->readObject();
        }
        
        // If parsing for a tagged object and there's only one element, return it directly
        if (count($elements) === 1) {
            return $elements[0];
        }
        
        return $elements;
    }
}
