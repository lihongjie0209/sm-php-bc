<?php

declare(strict_types=1);

namespace SmBc\Util\Pem;

use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Math\BigInteger;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\EC\ECPoint;

/**
 * SM2 Key PEM Encoder
 * 
 * Provides simple PEM encoding/decoding for SM2 keys.
 * This is a minimal implementation using a custom format for quick key export/import.
 * 
 * Format for private key PEM:
 * - Type: "SM2 PRIVATE KEY"
 * - Content: JSON encoded array with key data
 * 
 * Format for public key PEM:
 * - Type: "SM2 PUBLIC KEY"
 * - Content: JSON encoded array with key data
 * 
 * Note: This uses a simplified format. For full PKCS#8/SEC1 support, 
 * see the PKI module in future versions.
 */
class SM2KeyPemEncoder
{
    // SM2 Standard curve parameters
    private const P = '0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF';
    private const A = '0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC';
    private const B = '0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93';
    private const N = '0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123';
    private const GX = '0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7';
    private const GY = '0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0';

    /**
     * Encode SM2 private key to PEM format
     * 
     * @param ECPrivateKeyParameters $privateKey The private key to encode
     * @return string PEM formatted private key
     */
    public static function encodePrivateKey(ECPrivateKeyParameters $privateKey): string
    {
        $d = $privateKey->getD();
        
        // Encode as JSON for simplicity
        $keyData = [
            'version' => 1,
            'curve' => 'sm2p256v1',
            'd' => gmp_strval($d->val, 16)
        ];
        
        $jsonData = json_encode($keyData, JSON_UNESCAPED_SLASHES);
        
        return PemEncoder::encode($jsonData, 'SM2 PRIVATE KEY');
    }

    /**
     * Encode SM2 public key to PEM format
     * 
     * @param ECPublicKeyParameters $publicKey The public key to encode
     * @return string PEM formatted public key
     */
    public static function encodePublicKey(ECPublicKeyParameters $publicKey): string
    {
        $q = $publicKey->getQ()->normalize();
        $x = $q->getAffineXCoord()->toBigInteger();
        $y = $q->getAffineYCoord()->toBigInteger();
        
        // Encode as JSON for simplicity
        $keyData = [
            'version' => 1,
            'curve' => 'sm2p256v1',
            'x' => gmp_strval($x->val, 16),
            'y' => gmp_strval($y->val, 16)
        ];
        
        $jsonData = json_encode($keyData, JSON_UNESCAPED_SLASHES);
        
        return PemEncoder::encode($jsonData, 'SM2 PUBLIC KEY');
    }

    /**
     * Decode SM2 private key from PEM format
     * 
     * @param string $pem PEM formatted private key
     * @return ECPrivateKeyParameters The decoded private key
     * @throws \InvalidArgumentException If PEM format is invalid or not an SM2 private key
     */
    public static function decodePrivateKey(string $pem): ECPrivateKeyParameters
    {
        $decoded = PemEncoder::decode($pem);
        
        if ($decoded['type'] !== 'SM2 PRIVATE KEY') {
            throw new \InvalidArgumentException('Not an SM2 private key PEM');
        }
        
        $keyData = json_decode($decoded['data'], true);
        
        if (!$keyData || !isset($keyData['d'])) {
            throw new \InvalidArgumentException('Invalid SM2 private key data');
        }
        
        $d = new BigInteger($keyData['d'], 16);
        $domainParams = self::getSM2DomainParameters();
        
        return new ECPrivateKeyParameters($d, $domainParams);
    }

    /**
     * Decode SM2 public key from PEM format
     * 
     * @param string $pem PEM formatted public key
     * @return ECPublicKeyParameters The decoded public key
     * @throws \InvalidArgumentException If PEM format is invalid or not an SM2 public key
     */
    public static function decodePublicKey(string $pem): ECPublicKeyParameters
    {
        $decoded = PemEncoder::decode($pem);
        
        if ($decoded['type'] !== 'SM2 PUBLIC KEY') {
            throw new \InvalidArgumentException('Not an SM2 public key PEM');
        }
        
        $keyData = json_decode($decoded['data'], true);
        
        if (!$keyData || !isset($keyData['x']) || !isset($keyData['y'])) {
            throw new \InvalidArgumentException('Invalid SM2 public key data');
        }
        
        $x = new BigInteger($keyData['x'], 16);
        $y = new BigInteger($keyData['y'], 16);
        
        $domainParams = self::getSM2DomainParameters();
        $curve = $domainParams->getCurve();
        $q = $curve->createPoint($x, $y);
        
        return new ECPublicKeyParameters($q, $domainParams);
    }

    /**
     * Get SM2 standard domain parameters
     * 
     * @return ECDomainParameters SM2 curve parameters
     */
    private static function getSM2DomainParameters(): ECDomainParameters
    {
        $p = new BigInteger(self::P);
        $a = new BigInteger(self::A);
        $b = new BigInteger(self::B);
        $n = new BigInteger(self::N);
        
        $curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());
        
        $gx = new BigInteger(self::GX);
        $gy = new BigInteger(self::GY);
        $g = $curve->createPoint($gx, $gy);
        
        return new ECDomainParameters($curve, $g, $n, BigInteger::ONE());
    }
}
