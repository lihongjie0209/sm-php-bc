<?php

declare(strict_types=1);

namespace SmBc\Crypto\Signers;

use SmBc\Crypto\Digest;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ECKeyParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Crypto\Params\ParametersWithID;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Math\EC\ECPoint;
use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;
use RuntimeException;

/**
 * SM2 Digital Signature Algorithm implementation.
 * 
 * This class implements the SM2 digital signature algorithm as specified
 * in GM/T 0003.2-2012. SM2 is an elliptic curve digital signature algorithm
 * based on the SM2 elliptic curve parameters.
 * 
 * The algorithm supports:
 * - Message signing with private key
 * - Signature verification with public key  
 * - User ID-based message preprocessing (Z_A calculation)
 * - SM3 digest algorithm by default
 * 
 * Based on: org.bouncycastle.crypto.signers.SM2Signer
 *           sm-js-bc/src/crypto/signers/SM2Signer.ts
 */
class SM2Signer implements Signer
{
    private const DEFAULT_USER_ID = '1234567812345678';

    private Digest $digest;
    private DSAKCalculator $dsaKCalculator;
    private DSAEncoding $encoding;
    private ?ECKeyParameters $keyParameters = null;
    private ?ECPublicKeyParameters $publicKey = null;
    private ?ECDomainParameters $ecParams = null;
    private ?DSAKCalculator $kCalculator = null;
    private bool $forSigning = false;
    private string $userID;
    private ?string $z = null;

    /**
     * Create SM2Signer with default SM3 digest and random k calculator.
     */
    public function __construct(
        ?Digest $digest = null,
        ?DSAKCalculator $dsaKCalculator = null,
        ?DSAEncoding $encoding = null
    ) {
        $this->digest = $digest ?? new SM3Digest();
        $this->dsaKCalculator = $dsaKCalculator ?? new RandomDSAKCalculator();
        $this->encoding = $encoding ?? StandardDSAEncoding::getInstance();
        $this->userID = self::DEFAULT_USER_ID;
    }

    /**
     * Get algorithm name.
     */
    public function getAlgorithmName(): string
    {
        return 'SM2';
    }

    /**
     * Initialize the signer.
     */
    public function init(bool $forSigning, CipherParameters $parameters): void
    {
        $this->forSigning = $forSigning;
        
        $ecKey = null;
        $providedRandom = null;
        
        // Extract parameters
        if ($parameters instanceof ParametersWithID) {
            $this->userID = $parameters->getID();
            $innerParams = $parameters->getParameters();
            
            if ($innerParams instanceof ParametersWithRandom) {
                $providedRandom = $innerParams->getRandom();
                $ecKey = $innerParams->getParameters();
            } else {
                $ecKey = $innerParams;
            }
        } elseif ($parameters instanceof ParametersWithRandom) {
            $providedRandom = $parameters->getRandom();
            $ecKey = $parameters->getParameters();
            $this->userID = self::DEFAULT_USER_ID;
        } else {
            $ecKey = $parameters;
            $this->userID = self::DEFAULT_USER_ID;
        }

        if (!($ecKey instanceof ECKeyParameters)) {
            throw new RuntimeException('ECKeyParameters required');
        }

        $this->keyParameters = $ecKey;
        $this->ecParams = $ecKey->getParameters();

        if ($forSigning) {
            // Signing - need private key
            if (!($ecKey instanceof ECPrivateKeyParameters)) {
                throw new RuntimeException('Signing requires ECPrivateKeyParameters');
            }
            
            // Initialize k calculator
            $this->kCalculator = $this->dsaKCalculator;
            $random = $providedRandom ?? new SecureRandom();
            $this->kCalculator->init($this->ecParams->getN(), $random);
            
            // Calculate public key from private key
            $this->publicKey = $this->calculatePublicKey($ecKey);
            
        } else {
            // Verification - need public key
            if (!($ecKey instanceof ECPublicKeyParameters)) {
                throw new RuntimeException('Verification requires ECPublicKeyParameters');
            }
            
            $this->publicKey = $ecKey;
        }

        // Calculate Z_A value
        $this->z = $this->calculateZ($this->userID, $this->publicKey);

        // Reset digest and add Z_A
        $this->digest->reset();
        $this->digest->updateBytes($this->z, 0, strlen($this->z));
    }

    /**
     * Update with single byte.
     */
    public function update(int $b): void
    {
        $this->digest->update($b);
    }

    /**
     * Update with byte array.
     */
    public function updateBytes(string $input, int $offset, int $length): void
    {
        $this->digest->updateBytes($input, $offset, $length);
    }

    /**
     * Generate signature.
     */
    public function generateSignature(): string
    {
        if (!$this->forSigning) {
            throw new RuntimeException('Signer not initialized for signing');
        }
        
        if (!$this->kCalculator || !$this->ecParams || !$this->keyParameters) {
            throw new RuntimeException('Signer not properly initialized');
        }

        $privateKey = $this->keyParameters;
        $n = $this->ecParams->getN();
        $G = $this->ecParams->getG();
        $d = $privateKey->getD();

        // Calculate message hash e = H(Z_A || M)
        $eBytes = str_repeat("\x00", $this->digest->getDigestSize());
        $this->digest->doFinal($eBytes, 0);
        $e = $this->hashToInteger($eBytes, $n);

        $r = null;
        $s = null;
        
        do {
            // Generate random k
            $k = $this->kCalculator->nextK();
            
            // Calculate point (x1, y1) = [k]G
            $kG = $G->multiply($k)->normalize();
            
            // Calculate r = (e + x1) mod n
            $x1 = $kG->getAffineXCoord()->toBigInteger();
            $r = $e->add($x1)->mod($n);
            
            // Check r ≠ 0 and r + k ≠ n
            if ($r->compareTo(BigInteger::ZERO()) === 0 || $r->add($k)->mod($n)->compareTo(BigInteger::ZERO()) === 0) {
                continue;
            }

            // Calculate s = (1 + d)^(-1) * (k - r*d) mod n
            $dPlusOne = BigInteger::ONE()->add($d);
            $dInv = $dPlusOne->modInverse($n);
            
            $rd = $r->multiply($d)->mod($n);
            $kMinusRd = $k->subtract($rd)->mod($n);
            if ($kMinusRd->compareTo(BigInteger::ZERO()) < 0) {
                $kMinusRd = $kMinusRd->add($n);
            }
            
            $s = $dInv->multiply($kMinusRd)->mod($n);
            
        } while ($s->compareTo(BigInteger::ZERO()) === 0);

        // Reset for next signature
        $this->digest->reset();
        if ($this->z) {
            $this->digest->updateBytes($this->z, 0, strlen($this->z));
        }

        // Encode signature
        return $this->encoding->encode($n, $r, $s);
    }

    /**
     * Verify signature.
     */
    public function verifySignature(string $signature): bool
    {
        if ($this->forSigning) {
            throw new RuntimeException('Signer not initialized for verification');
        }
        
        if (!$this->ecParams || !$this->publicKey) {
            throw new RuntimeException('Signer not properly initialized');
        }

        try {
            $n = $this->ecParams->getN();
            $G = $this->ecParams->getG();
            $P_A = $this->publicKey->getQ();

            // Decode signature
            [$r, $s] = $this->encoding->decode($n, $signature);

            // Verify 1 ≤ r < n and 1 ≤ s < n
            if ($r->compareTo(BigInteger::ZERO()) <= 0 || $r->compareTo($n) >= 0 ||
                $s->compareTo(BigInteger::ZERO()) <= 0 || $s->compareTo($n) >= 0) {
                return false;
            }

            // Calculate message hash e = H(Z_A || M)
            $eBytes = str_repeat("\x00", $this->digest->getDigestSize());
            $this->digest->doFinal($eBytes, 0);
            $e = $this->hashToInteger($eBytes, $n);

            // Calculate t = (r + s) mod n
            $t = $r->add($s)->mod($n);
            if ($t->compareTo(BigInteger::ZERO()) === 0) {
                return false;
            }

            // Calculate point (x1, y1) = [s]G + [t]P_A
            $sG = $G->multiply($s);
            $tP = $P_A->multiply($t);
            $point = $sG->add($tP)->normalize();

            // Calculate R = (e + x1) mod n
            $x1 = $point->getAffineXCoord()->toBigInteger();
            $R = $e->add($x1)->mod($n);

            // Verification passes if R = r
            $result = ($R->compareTo($r) === 0);

            // Reset for next verification
            $this->digest->reset();
            if ($this->z) {
                $this->digest->updateBytes($this->z, 0, strlen($this->z));
            }

            return $result;

        } catch (\Exception $error) {
            // Reset on error
            $this->digest->reset();
            if ($this->z) {
                $this->digest->updateBytes($this->z, 0, strlen($this->z));
            }
            return false;
        }
    }

    /**
     * Reset the signer.
     */
    public function reset(): void
    {
        $this->digest->reset();
        if ($this->z) {
            $this->digest->updateBytes($this->z, 0, strlen($this->z));
        }
    }

    /**
     * Calculate public key from private key.
     */
    private function calculatePublicKey(ECPrivateKeyParameters $privateKey): ECPublicKeyParameters
    {
        $d = $privateKey->getD();
        $params = $privateKey->getParameters();
        $Q = $params->getG()->multiply($d);
        
        return new ECPublicKeyParameters($Q, $params);
    }

    /**
     * Calculate Z_A value for user ID and public key.
     */
    private function calculateZ(string $userID, ECPublicKeyParameters $publicKey): string
    {
        $digest = new SM3Digest();
        
        // ENTL_A: two-byte length of user ID in bits
        $userIDBitLength = strlen($userID) * 8;
        $digest->update(($userIDBitLength >> 8) & 0xFF);
        $digest->update($userIDBitLength & 0xFF);
        
        // ID_A: user ID
        $digest->updateBytes($userID, 0, strlen($userID));
        
        $curve = $this->ecParams->getCurve();
        $fieldSize = $curve->getFieldSize();
        $fieldBytes = (int)ceil($fieldSize / 8);
        
        // a, b: curve parameters
        $a = $curve->getA()->toBigInteger();
        $b = $curve->getB()->toBigInteger();
        
        $aBytes = $this->asUnsignedByteArray($fieldBytes, $a);
        $bBytes = $this->asUnsignedByteArray($fieldBytes, $b);
        
        $digest->updateBytes($aBytes, 0, strlen($aBytes));
        $digest->updateBytes($bBytes, 0, strlen($bBytes));
        
        // x_G, y_G: base point coordinates
        $G = $this->ecParams->getG()->normalize();
        $xGBytes = $this->asUnsignedByteArray($fieldBytes, $G->getAffineXCoord()->toBigInteger());
        $yGBytes = $this->asUnsignedByteArray($fieldBytes, $G->getAffineYCoord()->toBigInteger());
        
        $digest->updateBytes($xGBytes, 0, strlen($xGBytes));
        $digest->updateBytes($yGBytes, 0, strlen($yGBytes));
        
        // x_A, y_A: public key coordinates
        $P_A = $publicKey->getQ()->normalize();
        $xABytes = $this->asUnsignedByteArray($fieldBytes, $P_A->getAffineXCoord()->toBigInteger());
        $yABytes = $this->asUnsignedByteArray($fieldBytes, $P_A->getAffineYCoord()->toBigInteger());
        
        $digest->updateBytes($xABytes, 0, strlen($xABytes));
        $digest->updateBytes($yABytes, 0, strlen($yABytes));
        
        // Calculate final hash
        $z = str_repeat("\x00", $digest->getDigestSize());
        $digest->doFinal($z, 0);
        
        return $z;
    }

    /**
     * Calculate e value from message hash.
     * This method is protected to allow subclasses to customize the calculation.
     * 
     * @param BigInteger $n The order of the curve
     * @param string $message The message hash bytes
     * @return BigInteger The calculated e value
     * @since 0.2.0
     */
    protected function calculateE(BigInteger $n, string $message): BigInteger
    {
        $e = BigInteger::fromByteArray($message, false);
        return $e->mod($n);
    }

    /**
     * Convert hash bytes to integer in range [1, n-1].
     * 
     * @deprecated 0.2.0 Use calculateE() instead for compatibility with Bouncy Castle Java API.
     *             This method is kept for internal backward compatibility and will be removed in 1.0.0.
     * @internal
     * @see calculateE()
     */
    private function hashToInteger(string $hash, BigInteger $n): BigInteger
    {
        return $this->calculateE($n, $hash);
    }
    
    /**
     * Create base point multiplier for signature generation.
     * This method is protected to allow subclasses to customize the multiplier.
     * 
     * In the current implementation, we use ECPoint's built-in multiply method directly.
     * Subclasses can override this method to provide custom multiplication strategies.
     * 
     * @return null This implementation doesn't use a separate multiplier object
     * @since 0.2.0
     */
    protected function createBasePointMultiplier(): ?object
    {
        // PHP implementation uses ECPoint's built-in multiply method
        // This method is provided for API compatibility with Bouncy Castle Java
        return null;
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
