<?php

declare(strict_types=1);

namespace SmBc\Util\Pem;

/**
 * Simple PEM encoder for cryptographic keys
 * 
 * This is a minimal implementation focused on SM2 key encoding.
 * For full PKCS#8 support, see future versions.
 * 
 * Based on: RFC 7468 - Textual Encodings of PKIX, PKCS, and CMS Structures
 */
class PemEncoder
{
    /**
     * Encode data to PEM format
     * 
     * @param string $data Binary data to encode
     * @param string $type PEM type (e.g., "PUBLIC KEY", "PRIVATE KEY", "EC PRIVATE KEY")
     * @return string PEM formatted string
     */
    public static function encode(string $data, string $type): string
    {
        $base64 = base64_encode($data);
        
        // Split into 64-character lines as per RFC 7468
        $lines = str_split($base64, 64);
        
        $pem = "-----BEGIN {$type}-----\n";
        $pem .= implode("\n", $lines) . "\n";
        $pem .= "-----END {$type}-----\n";
        
        return $pem;
    }

    /**
     * Decode PEM formatted data
     * 
     * @param string $pem PEM formatted string
     * @return array{type: string, data: string} Array with 'type' and 'data' keys
     * @throws \InvalidArgumentException If PEM format is invalid
     */
    public static function decode(string $pem): array
    {
        // Extract PEM type
        if (!preg_match('/^-----BEGIN ([A-Z0-9 ]+)-----/', $pem, $matches)) {
            throw new \InvalidArgumentException('Invalid PEM format: missing BEGIN marker');
        }
        
        $type = $matches[1];
        
        // Extract base64 content
        $lines = explode("\n", $pem);
        $base64Content = '';
        $inContent = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (strpos($line, '-----BEGIN') === 0) {
                $inContent = true;
                continue;
            }
            
            if (strpos($line, '-----END') === 0) {
                break;
            }
            
            if ($inContent && !empty($line)) {
                $base64Content .= $line;
            }
        }
        
        if (empty($base64Content)) {
            throw new \InvalidArgumentException('Invalid PEM format: no content found');
        }
        
        $data = base64_decode($base64Content, true);
        
        if ($data === false) {
            throw new \InvalidArgumentException('Invalid PEM format: base64 decode failed');
        }
        
        return [
            'type' => $type,
            'data' => $data
        ];
    }

    /**
     * Check if a string is PEM formatted
     * 
     * @param string $data String to check
     * @return bool True if the string appears to be PEM formatted
     */
    public static function isPem(string $data): bool
    {
        return strpos($data, '-----BEGIN ') === 0;
    }
}
