<?php

declare(strict_types=1);

namespace SmBc\Crypto\Engines;

use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ECKeyParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Math\EC\ECPoint;
use SmBc\Math\BigInteger;
use SmBc\Util\Arrays;
use SmBc\Util\SecureRandom;
use InvalidArgumentException;
use RuntimeException;

/**
 * SM2 public key encryption engine.
 * 
 * Based on: https://tools.ietf.org/html/draft-shen-sm2-ecdsa-02
 *           org.bouncycastle.crypto.engines.SM2Engine
 *           sm-js-bc/src/crypto/engines/SM2Engine.ts
 * 
 * Implements SM2 encryption/decryption with two modes:
 * - C1C2C3: Ciphertext format is C1||C2||C3 (default)
 * - C1C3C2: Ciphertext format is C1||C3||C2
 * 
 * Where:
 * - C1: Elliptic curve point (ephemeral public key)
 * - C2: Encrypted message
 * - C3: Hash value for integrity check
 */
class SM2Engine
{
    public const MODE_C1C2C3 = 'C1C2C3';
    public const MODE_C1C3C2 = 'C1C3C2';

    private SM3Digest $digest;
    private string $mode;
    
    private bool $forEncryption = false;
    private ?ECKeyParameters $ecKey = null;
    private ?ECDomainParameters $ecParams = null;
    private int $curveLength = 0;
    private ?SecureRandom $random = null;

    public function __construct(?SM3Digest $digest = null, string $mode = self::MODE_C1C2C3)
    {
        $this->digest = $digest ?? new SM3Digest();
        $this->mode = $mode;
    }

    /**
     * Initialize engine for encryption or decryption.
     * 
     * @param bool $forEncryption true for encryption, false for decryption
     * @param CipherParameters $param For encryption: ParametersWithRandom containing ECPublicKeyParameters
     *                                For decryption: ECPrivateKeyParameters
     */
    public function init(bool $forEncryption, CipherParameters $param): void
    {
        $this->forEncryption = $forEncryption;

        if ($forEncryption) {
            if (!($param instanceof ParametersWithRandom)) {
                throw new InvalidArgumentException('For encryption, ParametersWithRandom is required');
            }
            
            $this->ecKey = $param->getParameters();
            if (!($this->ecKey instanceof ECPublicKeyParameters)) {
                throw new InvalidArgumentException('Public key required for encryption');
            }
            
            $this->ecParams = $this->ecKey->getParameters();

            // Verify [h]Q is not at infinity
            $s = $this->ecKey->getQ()->multiply($this->ecParams->getH());
            if ($s->isInfinity()) {
                throw new RuntimeException('invalid key: [h]Q at infinity');
            }

            $this->random = $param->getRandom();
        } else {
            if (!($param instanceof ECPrivateKeyParameters)) {
                throw new InvalidArgumentException('Private key required for decryption');
            }
            
            $this->ecKey = $param;
            $this->ecParams = $this->ecKey->getParameters();
        }

        $this->curveLength = (int)floor(($this->ecParams->getCurve()->getFieldSize() + 7) / 8);
    }

    /**
     * Process a block of data (encrypt or decrypt).
     * 
     * @param string $input Input data
     * @param int $inOff Offset in input
     * @param int $inLen Length of data to process
     * @return string Processed data
     */
    public function processBlock(string $input, int $inOff, int $inLen): string
    {
        if ($inOff + $inLen > strlen($input) || $inLen === 0) {
            throw new InvalidArgumentException('input buffer too short');
        }

        if ($this->forEncryption) {
            return $this->encrypt($input, $inOff, $inLen);
        } else {
            return $this->decrypt($input, $inOff, $inLen);
        }
    }

    /**
     * Get output size for given input length.
     */
    public function getOutputSize(int $inputLen): int
    {
        return (1 + 2 * $this->curveLength) + $inputLen + $this->digest->getDigestSize();
    }

    /**
     * Encrypt plaintext.
     */
    private function encrypt(string $input, int $inOff, int $inLen): string
    {
        $c2 = substr($input, $inOff, $inLen);

        do {
            // Generate random k
            $k = $this->nextK();

            // C1 = [k]G
            $c1P = $this->ecParams->getG()->multiply($k)->normalize();
            $c1 = $c1P->getEncoded(false);

            // [k]PB
            /** @var ECPublicKeyParameters $pubKey */
            $pubKey = $this->ecKey;
            $kPB = $pubKey->getQ()->multiply($k)->normalize();

            // C2 = M ⊕ KDF(x2||y2, klen)
            $this->kdf($this->digest, $kPB, $c2);
        } while ($this->notEncrypted($c2, $input, $inOff));

        // C3 = Hash(x2||M||y2)
        $c3 = str_repeat("\x00", $this->digest->getDigestSize());
        $this->addFieldElement($this->digest, $kPB->getAffineXCoord());
        $this->digest->updateBytes(substr($input, $inOff, $inLen), 0, $inLen);
        $this->addFieldElement($this->digest, $kPB->getAffineYCoord());
        $this->digest->doFinal($c3, 0);

        // Return C1||C2||C3 or C1||C3||C2
        if ($this->mode === self::MODE_C1C3C2) {
            return $c1 . $c3 . $c2;
        } else {
            return $c1 . $c2 . $c3;
        }
    }

    /**
     * Decrypt ciphertext.
     */
    private function decrypt(string $input, int $inOff, int $inLen): string
    {
        // Extract C1
        $c1Length = $this->curveLength * 2 + 1;
        $c1 = substr($input, $inOff, $c1Length);

        $c1P_initial = $this->ecParams->getCurve()->decodePoint($c1);

        // Verify [h]C1 is not at infinity
        $s = $c1P_initial->multiply($this->ecParams->getH());
        if ($s->isInfinity()) {
            throw new RuntimeException('[h]C1 at infinity');
        }

        // Compute [d]C1
        /** @var ECPrivateKeyParameters $privKey */
        $privKey = $this->ecKey;
        $c1P = $c1P_initial->multiply($privKey->getD())->normalize();

        $digestSize = $this->digest->getDigestSize();
        
        // Extract C2 based on mode
        if ($this->mode === self::MODE_C1C3C2) {
            $c2 = substr($input, $inOff + $c1Length + $digestSize, $inLen - $c1Length - $digestSize);
        } else {
            $c2 = substr($input, $inOff + $c1Length, $inLen - $c1Length - $digestSize);
        }

        // M = C2 ⊕ KDF(x2||y2, klen)
        $this->kdf($this->digest, $c1P, $c2);

        // Compute C3' = Hash(x2||M||y2)
        $c3 = str_repeat("\x00", $digestSize);
        $this->addFieldElement($this->digest, $c1P->getAffineXCoord());
        $this->digest->updateBytes($c2, 0, strlen($c2));
        $this->addFieldElement($this->digest, $c1P->getAffineYCoord());
        $this->digest->doFinal($c3, 0);

        // Verify C3' === C3 (constant-time comparison)
        $check = 0;
        if ($this->mode === self::MODE_C1C3C2) {
            $c3Input = substr($input, $inOff + $c1Length, $digestSize);
        } else {
            $c3Input = substr($input, $inOff + $c1Length + strlen($c2), $digestSize);
        }
        
        for ($i = 0; $i < $digestSize; $i++) {
            $check |= ord($c3[$i]) ^ ord($c3Input[$i]);
        }

        if ($check !== 0) {
            throw new RuntimeException('invalid cipher text');
        }

        return $c2;
    }

    /**
     * Check if encryption failed (KDF returned all zeros).
     */
    private function notEncrypted(string $encData, string $input, int $inOff): bool
    {
        $len = strlen($encData);
        for ($i = 0; $i < $len; $i++) {
            if ($encData[$i] !== $input[$inOff + $i]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Key Derivation Function using SM3.
     * 
     * Derives key material and XORs it with encData in place.
     */
    private function kdf(SM3Digest $digest, ECPoint $c1, string &$encData): void
    {
        $digestSize = $digest->getDigestSize();
        $buf = str_repeat("\x00", max(4, $digestSize));
        $off = 0;
        $encLen = strlen($encData);

        // Optimize with Memoable if available
        $memo = null;
        $copy = null;

        if ($digest instanceof \SmBc\Crypto\Memoable) {
            $this->addFieldElement($digest, $c1->getAffineXCoord());
            $this->addFieldElement($digest, $c1->getAffineYCoord());
            $memo = $digest;
            $copy = $memo->copy();
        }

        $ct = 0;

        while ($off < $encLen) {
            if ($memo && $copy) {
                $memo->reset($copy);
            } else {
                $this->addFieldElement($digest, $c1->getAffineXCoord());
                $this->addFieldElement($digest, $c1->getAffineYCoord());
            }

            // Add counter (big-endian)
            $ct++;
            $buf[0] = chr(($ct >> 24) & 0xff);
            $buf[1] = chr(($ct >> 16) & 0xff);
            $buf[2] = chr(($ct >> 8) & 0xff);
            $buf[3] = chr($ct & 0xff);

            $digest->updateBytes($buf, 0, 4);
            $digest->doFinal($buf, 0);

            $xorLen = min($digestSize, $encLen - $off);
            for ($i = 0; $i < $xorLen; $i++) {
                $encData[$off + $i] = chr(ord($encData[$off + $i]) ^ ord($buf[$i]));
            }
            $off += $xorLen;
        }
    }

    /**
     * Generate random k in range [1, n-1].
     */
    private function nextK(): BigInteger
    {
        $n = $this->ecParams->getN();
        $bitLength = $n->bitLength();

        do {
            $k = $this->createRandomBigInteger($bitLength, $this->random);
        } while ($k->compareTo(BigInteger::ZERO()) === 0 || $k->compareTo($n) >= 0);

        return $k;
    }

    /**
     * Create random BigInteger.
     */
    private function createRandomBigInteger(int $bitLength, SecureRandom $random): BigInteger
    {
        $numBytes = (int)ceil($bitLength / 8);
        $bytes = $random->nextBytes($numBytes);
        
        // Clear excess bits
        $excessBits = $numBytes * 8 - $bitLength;
        if ($excessBits > 0) {
            $bytes[0] = chr(ord($bytes[0]) & ((1 << (8 - $excessBits)) - 1));
        }
        
        return BigInteger::fromByteArray($bytes, false);
    }

    /**
     * Add field element to digest.
     */
    private function addFieldElement(SM3Digest $digest, $v): void
    {
        $p = $this->asUnsignedByteArray($this->curveLength, $v->toBigInteger());
        $digest->updateBytes($p, 0, strlen($p));
    }

    /**
     * Convert BigInteger to unsigned byte array with specific length.
     */
    private function asUnsignedByteArray(int $length, BigInteger $value): string
    {
        $bytes = $value->toByteArray(false);
        
        if (strlen($bytes) === $length) {
            return $bytes;
        }
        
        if (strlen($bytes) > $length) {
            // Trim leading bytes
            return substr($bytes, strlen($bytes) - $length);
        }
        
        // Pad with leading zeros
        return str_repeat("\x00", $length - strlen($bytes)) . $bytes;
    }
}
