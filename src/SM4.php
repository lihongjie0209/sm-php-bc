<?php

declare(strict_types=1);

namespace SmBc;

use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Modes\CTRBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\BufferedBlockCipher;
use SmBc\Crypto\Params;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

/**
 * SM4 High-Level API
 * 
 * Simplified interface for SM4 encryption/decryption operations.
 * Provides convenient methods for common use cases.
 * 
 * Example usage:
 * ```php
 * // CBC mode with PKCS7 padding
 * $key = random_bytes(16);
 * $iv = random_bytes(16);
 * $plaintext = "Secret message";
 * 
 * $ciphertext = SM4::encryptCBC($plaintext, $key, $iv);
 * $decrypted = SM4::decryptCBC($ciphertext, $key, $iv);
 * 
 * // CTR mode (no padding needed)
 * $ciphertext = SM4::encryptCTR($plaintext, $key, $nonce);
 * $decrypted = SM4::decryptCTR($ciphertext, $key, $nonce);
 * 
 * // ECB mode (not recommended for most use cases)
 * $ciphertext = SM4::encryptECB($plaintext, $key);
 * $decrypted = SM4::decryptECB($ciphertext, $key);
 * ```
 * 
 * @package SmBc
 */
class SM4
{
    /**
     * Encrypt data using SM4-ECB with PKCS7 padding (default mode for compatibility).
     *
     * @param string $plaintext Data to encrypt
     * @param string $key 16-byte key
     * @return string Encrypted data
     * @throws \RuntimeException
     */
    public static function encrypt(string $plaintext, string $key): string
    {
        return self::encryptECB($plaintext, $key);
    }

    /**
     * Decrypt data using SM4-ECB with PKCS7 padding (default mode for compatibility).
     *
     * @param string $ciphertext Data to decrypt
     * @param string $key 16-byte key
     * @return string Decrypted data
     * @throws \RuntimeException
     */
    public static function decrypt(string $ciphertext, string $key): string
    {
        return self::decryptECB($ciphertext, $key);
    }

    /**
     * Encrypt data using SM4-CBC with PKCS7 padding.
     *
     * @param string $plaintext Data to encrypt
     * @param string $key 16-byte key
     * @param string $iv 16-byte initialization vector
     * @return string Encrypted data
     * @throws \RuntimeException
     */
    public static function encryptCBC(string $plaintext, string $key, string $iv): string
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }
        if (strlen($iv) !== 16) {
            throw new \InvalidArgumentException('IV must be 16 bytes');
        }

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        $padding = new PKCS7Padding();
        $buffered = new BufferedBlockCipher($cipher, $padding);

        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $buffered->init(true, $params);

        // Calculate output size
        $outputLen = $buffered->getOutputSize(strlen($plaintext));
        $output = str_repeat("\x00", $outputLen);

        // Process data
        $len = $buffered->processBytes($plaintext, 0, strlen($plaintext), $output, 0);
        $len += $buffered->doFinal($output, $len);

        return substr($output, 0, $len);
    }

    /**
     * Decrypt data using SM4-CBC with PKCS7 padding.
     *
     * @param string $ciphertext Data to decrypt
     * @param string $key 16-byte key
     * @param string $iv 16-byte initialization vector
     * @return string Decrypted data
     * @throws \RuntimeException
     */
    public static function decryptCBC(string $ciphertext, string $key, string $iv): string
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }
        if (strlen($iv) !== 16) {
            throw new \InvalidArgumentException('IV must be 16 bytes');
        }

        $engine = new SM4Engine();
        $cipher = new CBCBlockCipher($engine);
        $padding = new PKCS7Padding();
        $buffered = new BufferedBlockCipher($cipher, $padding);

        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        $buffered->init(false, $params);

        // Calculate output size
        $outputLen = $buffered->getOutputSize(strlen($ciphertext));
        $output = str_repeat("\x00", $outputLen);

        // Process data
        $len = $buffered->processBytes($ciphertext, 0, strlen($ciphertext), $output, 0);
        $len += $buffered->doFinal($output, $len);

        return substr($output, 0, $len);
    }

    /**
     * Encrypt data using SM4-CTR (no padding needed).
     *
     * @param string $plaintext Data to encrypt
     * @param string $key 16-byte key
     * @param string $nonce 16-byte nonce/counter
     * @return string Encrypted data
     * @throws \RuntimeException
     */
    public static function encryptCTR(string $plaintext, string $key, string $nonce): string
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }
        if (strlen($nonce) !== 16) {
            throw new \InvalidArgumentException('Nonce must be 16 bytes');
        }

        $engine = new SM4Engine();
        $cipher = new CTRBlockCipher($engine);

        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $nonce);
        $cipher->init(true, $params);

        $output = str_repeat("\x00", strlen($plaintext));
        $cipher->processBytes($plaintext, 0, strlen($plaintext), $output, 0);

        return $output;
    }

    /**
     * Decrypt data using SM4-CTR (no padding needed).
     *
     * @param string $ciphertext Data to decrypt
     * @param string $key 16-byte key
     * @param string $nonce 16-byte nonce/counter
     * @return string Decrypted data
     * @throws \RuntimeException
     */
    public static function decryptCTR(string $ciphertext, string $key, string $nonce): string
    {
        // CTR mode: encryption and decryption are the same operation
        return self::encryptCTR($ciphertext, $key, $nonce);
    }

    /**
     * Encrypt data using SM4-ECB with PKCS7 padding.
     * 
     * WARNING: ECB mode is not semantically secure and should not be used
     * for most applications. Use CBC or CTR mode instead.
     *
     * @param string $plaintext Data to encrypt
     * @param string $key 16-byte key
     * @return string Encrypted data
     * @throws \RuntimeException
     */
    public static function encryptECB(string $plaintext, string $key): string
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }

        $engine = new SM4Engine();
        $padding = new PKCS7Padding();

        $keyParam = new KeyParameter($key);
        $engine->init(true, $keyParam);

        // Add padding
        $blockSize = 16;
        $paddedLen = strlen($plaintext);
        $remainder = $paddedLen % $blockSize;
        
        if ($remainder !== 0 || $paddedLen === 0) {
            $paddedLen = $paddedLen + ($blockSize - $remainder);
        } else {
            $paddedLen += $blockSize;
        }

        $padded = str_repeat("\x00", $paddedLen);
        
        // Copy plaintext
        for ($i = 0; $i < strlen($plaintext); $i++) {
            $padded[$i] = $plaintext[$i];
        }
        
        // Add padding
        $padding->addPadding($padded, strlen($plaintext));

        // Encrypt all blocks
        $output = str_repeat("\x00", $paddedLen);
        for ($i = 0; $i < $paddedLen; $i += $blockSize) {
            $engine->processBlock($padded, $i, $output, $i);
        }

        return $output;
    }

    /**
     * Decrypt data using SM4-ECB with PKCS7 padding.
     * 
     * WARNING: ECB mode is not semantically secure and should not be used
     * for most applications. Use CBC or CTR mode instead.
     *
     * @param string $ciphertext Data to decrypt
     * @param string $key 16-byte key
     * @return string Decrypted data
     * @throws \RuntimeException
     */
    public static function decryptECB(string $ciphertext, string $key): string
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }

        if (strlen($ciphertext) % 16 !== 0) {
            throw new \InvalidArgumentException('Ciphertext length must be multiple of 16');
        }

        $engine = new SM4Engine();
        $padding = new PKCS7Padding();

        $keyParam = new KeyParameter($key);
        $engine->init(false, $keyParam);

        // Decrypt all blocks
        $blockSize = 16;
        $output = str_repeat("\x00", strlen($ciphertext));
        
        for ($i = 0; $i < strlen($ciphertext); $i += $blockSize) {
            $engine->processBlock($ciphertext, $i, $output, $i);
        }

        // Remove padding
        $padCount = $padding->padCount($output, strlen($output) - $blockSize);
        
        return substr($output, 0, strlen($output) - $padCount);
    }

    /**
     * Generate a random 16-byte key.
     *
     * @return string 16-byte random key
     */
    public static function generateKey(): string
    {
        return random_bytes(16);
    }

    /**
     * Generate a random 16-byte IV/nonce.
     *
     * @return string 16-byte random IV
     */
    public static function generateIV(): string
    {
        return random_bytes(16);
    }

    /**
     * Encrypt data with a password using PBKDF2 key derivation.
     * 
     * This method:
     * 1. Derives a key from the password using PBKDF2
     * 2. Generates a random IV
     * 3. Encrypts the data using CBC mode
     * 4. Returns: salt + IV + ciphertext
     *
     * @param string $plaintext Data to encrypt
     * @param string $password User password
     * @param int $iterations PBKDF2 iterations (default: 10000)
     * @return string salt + IV + ciphertext
     */
    public static function encryptWithPassword(
        string $plaintext,
        string $password,
        int $iterations = 10000
    ): string {
        // Generate random salt
        $salt = random_bytes(16);
        
        // Derive key from password
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 16, true);
        
        // Generate random IV
        $iv = random_bytes(16);
        
        // Encrypt
        $ciphertext = self::encryptCBC($plaintext, $key, $iv);
        
        // Return: salt + IV + ciphertext
        return $salt . $iv . $ciphertext;
    }

    /**
     * Decrypt data encrypted with encryptWithPassword().
     *
     * @param string $encrypted salt + IV + ciphertext
     * @param string $password User password
     * @param int $iterations PBKDF2 iterations (default: 10000)
     * @return string Decrypted plaintext
     */
    public static function decryptWithPassword(
        string $encrypted,
        string $password,
        int $iterations = 10000
    ): string {
        if (strlen($encrypted) < 32) {
            throw new \InvalidArgumentException('Invalid encrypted data');
        }
        
        // Extract salt, IV, and ciphertext
        $salt = substr($encrypted, 0, 16);
        $iv = substr($encrypted, 16, 16);
        $ciphertext = substr($encrypted, 32);
        
        // Derive key from password
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 16, true);
        
        // Decrypt
        return self::decryptCBC($ciphertext, $key, $iv);
    }

    /**
     * Encrypt data using SM4-CFB mode.
     *
     * @param string $plaintext Data to encrypt
     * @param string $key 16-byte key
     * @param string $iv 16-byte initialization vector
     * @return string Encrypted data
     */
    public static function encryptCFB(string $plaintext, string $key, string $iv): string
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }
        if (strlen($iv) !== 16) {
            throw new \InvalidArgumentException('IV must be 16 bytes');
        }

        $engine = new SM4Engine();
        $cipher = new Modes\CFBBlockCipher($engine, 128);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        
        $cipher->init(true, $params);
        
        $len = strlen($plaintext);
        $output = str_repeat("\0", $len);
        $cipher->processBytes($plaintext, 0, $len, $output, 0);
        
        return $output;
    }

    /**
     * Decrypt data using SM4-CFB mode.
     *
     * @param string $ciphertext Data to decrypt
     * @param string $key 16-byte key
     * @param string $iv 16-byte initialization vector
     * @return string Decrypted data
     */
    public static function decryptCFB(string $ciphertext, string $key, string $iv): string
    {
        // CFB mode: encryption and decryption are the same operation
        return self::encryptCFB($ciphertext, $key, $iv);
    }

    /**
     * Encrypt data using SM4-OFB mode.
     *
     * @param string $plaintext Data to encrypt
     * @param string $key 16-byte key
     * @param string $iv 16-byte initialization vector
     * @return string Encrypted data
     */
    public static function encryptOFB(string $plaintext, string $key, string $iv): string
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }
        if (strlen($iv) !== 16) {
            throw new \InvalidArgumentException('IV must be 16 bytes');
        }

        $engine = new SM4Engine();
        $cipher = new Modes\OFBBlockCipher($engine, 128);
        
        $keyParam = new KeyParameter($key);
        $params = new ParametersWithIV($keyParam, $iv);
        
        $cipher->init(true, $params);
        
        $len = strlen($plaintext);
        $output = str_repeat("\0", $len);
        $cipher->processBytes($plaintext, 0, $len, $output, 0);
        
        return $output;
    }

    /**
     * Decrypt data using SM4-OFB mode.
     *
     * @param string $ciphertext Data to decrypt
     * @param string $key 16-byte key
     * @param string $iv 16-byte initialization vector
     * @return string Decrypted data
     */
    public static function decryptOFB(string $ciphertext, string $key, string $iv): string
    {
        // OFB mode: encryption and decryption are the same operation
        return self::encryptOFB($ciphertext, $key, $iv);
    }

    /**
     * Encrypt data using SM4-GCM (authenticated encryption).
     *
     * @param string $plaintext Data to encrypt
     * @param string $key 16-byte key
     * @param string $iv Initialization vector (12 bytes recommended)
     * @param string $aad Additional authenticated data (optional)
     * @return array ['ciphertext' => string, 'tag' => string]
     */
    public static function encryptGCM(string $plaintext, string $key, string $iv, string $aad = ''): array
    {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }

        $engine = new SM4Engine();
        $cipher = new Modes\GCMBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new Params\AEADParameters($keyParam, 128, $iv, $aad);
        
        $cipher->init(true, $params);
        
        $outputLen = $cipher->getOutputSize(strlen($plaintext));
        $output = str_repeat("\0", $outputLen);
        
        $len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        $output = substr($output, 0, $len);
        
        // Last 16 bytes are the tag
        $ciphertext = substr($output, 0, -16);
        $tag = substr($output, -16);
        
        return ['ciphertext' => $ciphertext, 'tag' => $tag];
    }

    /**
     * Decrypt data using SM4-GCM (authenticated decryption).
     *
     * @param string $ciphertext Data to decrypt
     * @param string $key 16-byte key
     * @param string $iv Initialization vector
     * @param string $aad Additional authenticated data (must match encryption)
     * @param string $tag Authentication tag
     * @return string Decrypted data
     * @throws \RuntimeException If authentication fails
     */
    public static function decryptGCM(
        string $ciphertext,
        string $key,
        string $iv,
        string $aad,
        string $tag
    ): string {
        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('Key must be 16 bytes');
        }

        $engine = new SM4Engine();
        $cipher = new Modes\GCMBlockCipher($engine);
        
        $keyParam = new KeyParameter($key);
        $params = new Params\AEADParameters($keyParam, 128, $iv, $aad);
        
        $cipher->init(false, $params);
        
        // Combine ciphertext and tag
        $input = $ciphertext . $tag;
        
        $outputLen = $cipher->getOutputSize(strlen($input));
        $output = str_repeat("\0", $outputLen);
        
        $len = $cipher->processBytes($input, 0, strlen($input), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
}
