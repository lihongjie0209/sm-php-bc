<?php

declare(strict_types=1);

namespace SmBc\Crypto\KDF;

use SmBc\Crypto\Digests\SM3Digest;

/**
 * Key Derivation Function based on SM3.
 * 
 * Based on: org.bouncycastle.crypto.agreement.kdf.ECDHKEKGenerator
 */
class KDF
{
    private SM3Digest $digest;

    public function __construct()
    {
        $this->digest = new SM3Digest();
    }

    /**
     * Derive key material from shared secret.
     * 
     * @param string $Z Shared secret (x || y coordinates)
     * @param int $klen Required key length in bytes
     * @return string Derived key material
     */
    public function deriveKey(string $Z, int $klen): string
    {
        $v = $this->digest->getDigestSize();
        $K = '';
        $ct = 1;
        $offset = 0;

        while ($offset < $klen) {
            $this->digest->reset();
            $this->digest->updateBytes($Z, 0, strlen($Z));
            
            // Add counter as 4 bytes big-endian
            $ctBytes = pack('N', $ct);
            $this->digest->updateBytes($ctBytes, 0, 4);

            $hashBuf = str_repeat("\0", $v);
            $this->digest->doFinal($hashBuf, 0);

            $copyLen = min($klen - $offset, $v);
            $K .= substr($hashBuf, 0, $copyLen);

            $offset += $copyLen;
            $ct++;
        }

        return $K;
    }

    /**
     * Check if all bytes are zero (used in decryption).
     * 
     * @param string $data Binary data
     * @return bool True if all zeros
     */
    public static function isZero(string $data): bool
    {
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            if ($data[$i] !== "\0") {
                return false;
            }
        }
        return true;
    }
}
