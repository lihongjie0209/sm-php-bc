<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 output stream for writing DER encoded data.
 * 
 * This class provides methods for writing ASN.1 objects in DER
 * (Distinguished Encoding Rules) format.
 */
class ASN1OutputStream
{
    /** @var string The output buffer */
    private string $buffer = '';

    /**
     * Write an ASN.1 encodable object.
     * 
     * @param ASN1Encodable $obj The object to write
     */
    public function writeObject(ASN1Encodable $obj): void
    {
        $primitive = $obj->toASN1Primitive();
        $primitive->encode($this);
    }

    /**
     * Write a byte to the stream.
     * 
     * @param int $byte The byte value (0-255)
     */
    public function writeByte(int $byte): void
    {
        $this->buffer .= chr($byte & 0xFF);
    }

    /**
     * Write multiple bytes to the stream.
     * 
     * @param string $bytes The bytes to write
     * @param int $offset The offset to start from
     * @param int $length The number of bytes to write
     */
    public function writeBytes(string $bytes, int $offset = 0, int $length = null): void
    {
        if ($length === null) {
            $length = strlen($bytes) - $offset;
        }
        $this->buffer .= substr($bytes, $offset, $length);
    }

    /**
     * Write a DER encoded tag.
     * 
     * @param int $flags The tag class flags
     * @param int $tagNo The tag number
     */
    public function writeTag(int $flags, int $tagNo): void
    {
        if ($tagNo < 31) {
            $this->writeByte($flags | $tagNo);
        } else {
            $this->writeByte($flags | 0x1F);
            if ($tagNo < 128) {
                $this->writeByte($tagNo);
            } else {
                $stack = [];
                $stack[] = $tagNo & 0x7F;
                
                while ($tagNo > 127) {
                    $tagNo >>= 7;
                    $stack[] = ($tagNo & 0x7F) | 0x80;
                }
                
                for ($i = count($stack) - 1; $i >= 0; $i--) {
                    $this->writeByte($stack[$i]);
                }
            }
        }
    }

    /**
     * Write a DER encoded length.
     * 
     * @param int $length The length value
     */
    public function writeLength(int $length): void
    {
        if ($length < 128) {
            // Short form
            $this->writeByte($length);
        } else {
            // Long form
            $bytes = [];
            $temp = $length;
            
            while ($temp > 0) {
                $bytes[] = $temp & 0xFF;
                $temp >>= 8;
            }
            
            $this->writeByte(0x80 | count($bytes));
            
            for ($i = count($bytes) - 1; $i >= 0; $i--) {
                $this->writeByte($bytes[$i]);
            }
        }
    }

    /**
     * Write an encoded object with tag and length.
     * 
     * @param int $flags The tag class flags
     * @param int $tagNo The tag number
     * @param string $contents The encoded contents
     */
    public function writeEncoded(int $flags, int $tagNo, string $contents): void
    {
        $this->writeTag($flags, $tagNo);
        $this->writeLength(strlen($contents));
        $this->writeBytes($contents);
    }

    /**
     * Get the output as a byte array.
     * 
     * @return string The encoded bytes
     */
    public function toByteArray(): string
    {
        return $this->buffer;
    }
}
