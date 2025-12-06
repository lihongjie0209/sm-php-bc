<?php

declare(strict_types=1);

namespace SmBc;

use SmBc\Crypto\Engines\SM2Engine;
use SmBc\Crypto\Signers\SM2Signer;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Crypto\Params\ParametersWithID;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Math\EC\SM2KeyPair;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;

/**
 * SM2 High-Level API
 * 
 * Simplified interface for SM2 public key cryptography operations.
 * Provides convenient methods for encryption, decryption, signing, and verification.
 * 
 * Example usage:
 * ```php
 * // Generate key pair
 * $keyPair = SM2::generateKeyPair();
 * 
 * // Encryption
 * $ciphertext = SM2::encrypt($plaintext, $keyPair->getPublic());
 * $decrypted = SM2::decrypt($ciphertext, $keyPair->getPrivate());
 * 
 * // Digital Signatures
 * $signature = SM2::sign($message, $keyPair->getPrivate());
 * $valid = SM2::verify($message, $signature, $keyPair->getPublic());
 * 
 * // With User ID
 * $signature = SM2::sign($message, $keyPair->getPrivate(), 'user@example.com');
 * $valid = SM2::verify($message, $signature, $keyPair->getPublic(), 'user@example.com');
 * ```
 * 
 * @package SmBc
 */
class SM2
{
    /**
     * Generate a new SM2 key pair.
     *
     * @return SM2KeyPair A new key pair
     */
    public static function generateKeyPair(): SM2KeyPair
    {
        // Get SM2 curve
        $domainParams = self::getDomainParameters();
        $n = $domainParams->getN();
        $G = $domainParams->getG();
        
        // Generate random private key (1 < d < n-1)
        $random = new SecureRandom();
        $dBytes = $random->nextBytes(32);
        $d = new BigInteger(bin2hex($dBytes), 16);
        
        // Ensure d is in valid range
        while ($d->compareTo(BigInteger::ONE()) <= 0 || $d->compareTo($n) >= 0) {
            $dBytes = $random->nextBytes(32);
            $d = new BigInteger(bin2hex($dBytes), 16);
        }
        
        // Calculate public key Q = d * G
        $Q = $G->multiply($d)->normalize();
        
        $privateKey = new ECPrivateKeyParameters($d, $domainParams);
        $publicKey = new ECPublicKeyParameters($Q, $domainParams);
        
        return new SM2KeyPair($publicKey, $privateKey);
    }

    /**
     * Encrypt data using SM2 public key.
     *
     * @param string $plaintext Data to encrypt
     * @param ECPublicKeyParameters $publicKey Public key
     * @param string $mode Cipher mode: SM2Engine::MODE_C1C2C3 (default) or SM2Engine::MODE_C1C3C2
     * @return string Encrypted data
     * @throws \RuntimeException
     */
    public static function encrypt(
        string $plaintext,
        ECPublicKeyParameters $publicKey,
        string $mode = SM2Engine::MODE_C1C2C3
    ): string {
        $engine = new SM2Engine(null, $mode);
        $params = new ParametersWithRandom($publicKey, new SecureRandom());
        $engine->init(true, $params);
        
        return $engine->processBlock($plaintext, 0, strlen($plaintext));
    }

    /**
     * Decrypt data using SM2 private key.
     *
     * @param string $ciphertext Data to decrypt
     * @param ECPrivateKeyParameters $privateKey Private key
     * @param string $mode Cipher mode: SM2Engine::MODE_C1C2C3 (default) or SM2Engine::MODE_C1C3C2
     * @return string Decrypted data
     * @throws \RuntimeException
     */
    public static function decrypt(
        string $ciphertext,
        ECPrivateKeyParameters $privateKey,
        string $mode = SM2Engine::MODE_C1C2C3
    ): string {
        $engine = new SM2Engine(null, $mode);
        $engine->init(false, $privateKey);
        
        return $engine->processBlock($ciphertext, 0, strlen($ciphertext));
    }

    /**
     * Sign a message using SM2 private key.
     *
     * @param string $message Message to sign
     * @param ECPrivateKeyParameters $privateKey Private key
     * @param string|null $userId Optional user ID (default: '1234567812345678')
     * @return string Signature (DER encoded)
     * @throws \RuntimeException
     */
    public static function sign(
        string $message,
        ECPrivateKeyParameters $privateKey,
        ?string $userId = null
    ): string {
        $signer = new SM2Signer();
        
        $randomParams = new ParametersWithRandom($privateKey, new SecureRandom());
        
        if ($userId !== null) {
            $params = new ParametersWithID($randomParams, $userId);
            $signer->init(true, $params);
        } else {
            $signer->init(true, $randomParams);
        }
        
        $signer->updateBytes($message, 0, strlen($message));
        return $signer->generateSignature();
    }

    /**
     * Verify a signature using SM2 public key.
     *
     * @param string $message Original message
     * @param string $signature Signature to verify (DER encoded)
     * @param ECPublicKeyParameters $publicKey Public key
     * @param string|null $userId Optional user ID (must match the one used for signing)
     * @return bool True if signature is valid, false otherwise
     */
    public static function verify(
        string $message,
        string $signature,
        ECPublicKeyParameters $publicKey,
        ?string $userId = null
    ): bool {
        $signer = new SM2Signer();
        
        if ($userId !== null) {
            $params = new ParametersWithID($publicKey, $userId);
            $signer->init(false, $params);
        } else {
            $signer->init(false, $publicKey);
        }
        
        $signer->updateBytes($message, 0, strlen($message));
        return $signer->verifySignature($signature);
    }

    /**
     * Encrypt data with C1C3C2 mode (old standard).
     *
     * @param string $plaintext Data to encrypt
     * @param SM2PublicKeyParameters $publicKey Public key
     * @return string Encrypted data
     */
    public static function encryptC1C3C2(string $plaintext, ECPublicKeyParameters $publicKey): string
    {
        return self::encrypt($plaintext, $publicKey, SM2Engine::MODE_C1C3C2);
    }

    /**
     * Decrypt data with C1C3C2 mode (old standard).
     *
     * @param string $ciphertext Data to decrypt
     * @param ECPrivateKeyParameters $privateKey Private key
     * @return string Decrypted data
     */
    public static function decryptC1C3C2(string $ciphertext, ECPrivateKeyParameters $privateKey): string
    {
        return self::decrypt($ciphertext, $privateKey, SM2Engine::MODE_C1C3C2);
    }

    /**
     * Export public key to hex string (uncompressed format).
     *
     * @param ECPublicKeyParameters $publicKey Public key
     * @return string Hex-encoded public key (04 + X + Y)
     */
    public static function exportPublicKey(ECPublicKeyParameters $publicKey): string
    {
        return bin2hex($publicKey->getQ()->getEncoded(false));
    }

    /**
     * Export private key to hex string.
     *
     * @param ECPrivateKeyParameters $privateKey Private key
     * @return string Hex-encoded private key
     */
    public static function exportPrivateKey(ECPrivateKeyParameters $privateKey): string
    {
        $d = $privateKey->getD();
        $hex = $d->toString(16);
        
        // Pad to 64 hex characters (32 bytes)
        return str_pad($hex, 64, '0', STR_PAD_LEFT);
    }

    /**
     * Import public key from hex string.
     *
     * @param string $hex Hex-encoded public key
     * @return ECPublicKeyParameters Public key
     * @throws \InvalidArgumentException
     */
    public static function importPublicKey(string $hex): ECPublicKeyParameters
    {
        $bytes = hex2bin($hex);
        if ($bytes === false) {
            throw new \InvalidArgumentException('Invalid hex string');
        }
        
        $domainParams = self::getDomainParameters();
        $point = $domainParams->getCurve()->decodePoint($bytes);
        
        return new ECPublicKeyParameters($point, $domainParams);
    }

    /**
     * Import private key from hex string.
     *
     * @param string $hex Hex-encoded private key
     * @return ECPrivateKeyParameters Private key
     * @throws \InvalidArgumentException
     */
    public static function importPrivateKey(string $hex): ECPrivateKeyParameters
    {
        if (!ctype_xdigit($hex)) {
            throw new \InvalidArgumentException('Invalid hex string');
        }
        
        $d = new BigInteger($hex, 16);
        $domainParams = self::getDomainParameters();
        
        return new ECPrivateKeyParameters($d, $domainParams);
    }

    /**
     * Get SM2 curve domain parameters.
     *
     * @return ECDomainParameters Domain parameters
     */
    public static function getDomainParameters(): ECDomainParameters
    {
        // SM2 Standard Parameters
        $p = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF');
        $a = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC');
        $b = new BigInteger('0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93');
        $n = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123');
        $gx = new BigInteger('0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7');
        $gy = new BigInteger('0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0');
        
        $curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());
        $g = $curve->createPoint($gx, $gy);
        
        return new ECDomainParameters($curve, $g, $n, BigInteger::ONE());
    }

    /**
     * Sign a message using hex-encoded private key (convenience method).
     *
     * @param string $message Message to sign
     * @param string $privateKeyHex Hex-encoded private key
     * @param string|null $userId Optional user ID (default: '1234567812345678')
     * @return string Signature (DER encoded, binary)
     */
    public static function signWithHex(
        string $message,
        string $privateKeyHex,
        ?string $userId = null
    ): string {
        $privateKey = self::importPrivateKey($privateKeyHex);
        return self::sign($message, $privateKey, $userId);
    }

    /**
     * Verify a signature using hex-encoded public key (convenience method).
     *
     * @param string $message Original message
     * @param string $signature Signature to verify (DER encoded, binary)
     * @param string $publicKeyHex Hex-encoded public key
     * @param string|null $userId Optional user ID (must match the one used for signing)
     * @return bool True if signature is valid, false otherwise
     */
    public static function verifyWithHex(
        string $message,
        string $signature,
        string $publicKeyHex,
        ?string $userId = null
    ): bool {
        $publicKey = self::importPublicKey($publicKeyHex);
        return self::verify($message, $signature, $publicKey, $userId);
    }

    /**
     * Encrypt data using hex-encoded public key (convenience method).
     *
     * @param string $plaintext Data to encrypt
     * @param string $publicKeyHex Hex-encoded public key
     * @param string $mode Encryption mode (MODE_C1C2C3 or MODE_C1C3C2)
     * @return string Encrypted data
     */
    public static function encryptWithHex(
        string $plaintext,
        string $publicKeyHex,
        string $mode = SM2Engine::MODE_C1C2C3
    ): string {
        $publicKey = self::importPublicKey($publicKeyHex);
        return self::encrypt($plaintext, $publicKey, $mode);
    }

    /**
     * Decrypt data using hex-encoded private key (convenience method).
     *
     * @param string $ciphertext Data to decrypt
     * @param string $privateKeyHex Hex-encoded private key
     * @param string $mode Encryption mode (MODE_C1C2C3 or MODE_C1C3C2)
     * @return string Decrypted data
     */
    public static function decryptWithHex(
        string $ciphertext,
        string $privateKeyHex,
        string $mode = SM2Engine::MODE_C1C2C3
    ): string {
        $privateKey = self::importPrivateKey($privateKeyHex);
        return self::decrypt($ciphertext, $privateKey, $mode);
    }
}
