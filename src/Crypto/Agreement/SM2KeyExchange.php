<?php

declare(strict_types=1);

namespace SmBc\Crypto\Agreement;

use SmBc\Crypto\Digest;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ParametersWithID;
use SmBc\Crypto\Params\SM2KeyExchangePrivateParameters;
use SmBc\Crypto\Params\SM2KeyExchangePublicParameters;
use SmBc\Math\EC\ECAlgorithms;
use SmBc\Math\EC\ECFieldElement;
use SmBc\Math\EC\ECPoint;
use SmBc\Util\Arrays;
use SmBc\Util\Pack;

/**
 * SM2 Key Exchange protocol implementation.
 * Based on https://tools.ietf.org/html/draft-shen-sm2-ecdsa-02
 * 
 * Implements the SM2 key agreement protocol allowing two parties to establish
 * a shared secret key over an insecure communication channel.
 */
class SM2KeyExchange
{
    private Digest $digest;
    private string $userID = '';
    private ?ECPrivateKeyParameters $staticKey = null;
    private ?ECPoint $staticPubPoint = null;
    private ?ECPoint $ephemeralPubPoint = null;
    private ?ECDomainParameters $ecParams = null;
    private ?int $w = null;
    private ?ECPrivateKeyParameters $ephemeralKey = null;
    private ?bool $initiator = null;

    /**
     * Constructor with optional custom digest (defaults to SM3).
     * 
     * @param Digest|null $digest Custom digest algorithm
     */
    public function __construct(?Digest $digest = null)
    {
        $this->digest = $digest ?? new SM3Digest();
    }

    /**
     * Initialize the key exchange with private parameters.
     * 
     * @param CipherParameters $privParam Private parameters for key exchange
     */
    public function init(CipherParameters $privParam): void
    {
        if ($privParam instanceof ParametersWithID) {
            $baseParam = $privParam->getParameters();
            $this->userID = $privParam->getID();
        } else {
            $baseParam = $privParam;
            $this->userID = '';
        }

        if (!$baseParam instanceof SM2KeyExchangePrivateParameters) {
            throw new \InvalidArgumentException('Expected SM2KeyExchangePrivateParameters');
        }

        $this->initiator = $baseParam->isInitiator();
        $this->staticKey = $baseParam->getStaticPrivateKey();
        $this->ephemeralKey = $baseParam->getEphemeralPrivateKey();
        $this->ecParams = $this->staticKey->getParameters();
        $this->staticPubPoint = $baseParam->getStaticPublicPoint();
        $this->ephemeralPubPoint = $baseParam->getEphemeralPublicPoint();

        // Calculate w = floor((field_size - 1) / 2)
        $this->w = (int) floor(($this->ecParams->getCurve()->getFieldSize() - 1) / 2);
    }

    /**
     * Calculate the shared key.
     * 
     * @param int $kLen Length of the key to generate (in bits)
     * @param CipherParameters $pubParam Public parameters from the other party
     * @return string The generated shared key (binary string)
     * @throws \Exception
     */
    public function calculateKey(int $kLen, CipherParameters $pubParam): string
    {
        if ($kLen <= 0) {
            throw new \InvalidArgumentException('Key length must be positive');
        }

        if ($pubParam instanceof ParametersWithID) {
            $otherPub = $pubParam->getParameters();
            $otherUserID = $pubParam->getID();
        } else {
            $otherPub = $pubParam;
            $otherUserID = '';
        }

        if (!$otherPub instanceof SM2KeyExchangePublicParameters) {
            throw new \InvalidArgumentException('Expected SM2KeyExchangePublicParameters');
        }

        $za = $this->getZ($this->digest, $this->userID, $this->staticPubPoint);
        $zb = $this->getZ($this->digest, $otherUserID, $otherPub->getStaticPublicKey()->getQ());

        $U = $this->calculateU($otherPub);

        if ($this->initiator) {
            return $this->kdf($U, $za, $zb, $kLen);
        } else {
            return $this->kdf($U, $zb, $za, $kLen);
        }
    }

    /**
     * Calculate key with confirmation tags.
     * 
     * @param int $kLen Length of the key to generate (in bits)
     * @param string|null $confirmationTag Confirmation tag from the other party (for initiator)
     * @param CipherParameters $pubParam Public parameters from the other party
     * @return array{0: string, 1: string, 2?: string} Array containing [key, confirmationTag1, confirmationTag2?]
     * @throws \Exception
     */
    public function calculateKeyWithConfirmation(
        int $kLen,
        ?string $confirmationTag,
        CipherParameters $pubParam
    ): array {
        if ($kLen <= 0) {
            throw new \InvalidArgumentException('Key length must be positive');
        }

        if ($pubParam instanceof ParametersWithID) {
            $otherPub = $pubParam->getParameters();
            $otherUserID = $pubParam->getID();
        } else {
            $otherPub = $pubParam;
            $otherUserID = '';
        }

        if (!$otherPub instanceof SM2KeyExchangePublicParameters) {
            throw new \InvalidArgumentException('Expected SM2KeyExchangePublicParameters');
        }

        if ($this->initiator && $confirmationTag === null) {
            throw new \InvalidArgumentException('If initiating, confirmationTag must be set');
        }

        $za = $this->getZ($this->digest, $this->userID, $this->staticPubPoint);
        $zb = $this->getZ($this->digest, $otherUserID, $otherPub->getStaticPublicKey()->getQ());

        $U = $this->calculateU($otherPub);

        if ($this->initiator) {
            $rv = $this->kdf($U, $za, $zb, $kLen);

            $inner = $this->calculateInnerHash(
                $this->digest,
                $U,
                $za,
                $zb,
                $this->ephemeralPubPoint,
                $otherPub->getEphemeralPublicKey()->getQ()
            );

            $s1 = $this->S1($this->digest, $U, $inner);

            if (!Arrays::constantTimeAreEqual($s1, $confirmationTag)) {
                throw new \RuntimeException('Confirmation tag mismatch');
            }

            return [$rv, $this->S2($this->digest, $U, $inner)];
        } else {
            $rv = $this->kdf($U, $zb, $za, $kLen);

            $inner = $this->calculateInnerHash(
                $this->digest,
                $U,
                $zb,
                $za,
                $otherPub->getEphemeralPublicKey()->getQ(),
                $this->ephemeralPubPoint
            );

            return [$rv, $this->S1($this->digest, $U, $inner), $this->S2($this->digest, $U, $inner)];
        }
    }

    /**
     * Calculate the U point for key derivation.
     */
    private function calculateU(SM2KeyExchangePublicParameters $otherPub): ECPoint
    {
        $params = $this->staticKey->getParameters();

        $p1 = $otherPub->getStaticPublicKey()->getQ()->normalize();
        $p2 = $otherPub->getEphemeralPublicKey()->getQ()->normalize();

        $x1Bi = $this->ephemeralPubPoint->normalize()->getAffineXCoord()->toBigInteger();
        $x2Bi = $p2->getAffineXCoord()->toBigInteger();
        $x1 = $this->reduce(gmp_init($x1Bi->toString()));
        $x2 = $this->reduce(gmp_init($x2Bi->toString()));
        
        $staticD = gmp_init($this->staticKey->getD()->toString());
        $ephemeralD = gmp_init($this->ephemeralKey->getD()->toString());
        $h = gmp_init($this->ecParams->getH()->toString());
        $n = gmp_init($this->ecParams->getN()->toString());
        
        $tA = gmp_add($staticD, gmp_mul($x1, $ephemeralD));
        $k1 = gmp_mod(gmp_mul($h, $tA), $n);
        $k2 = gmp_mod(gmp_mul($k1, $x2), $n);

        // U = [k1]P1 + [k2]P2
        $k1Bi = new \SmBc\Math\BigInteger(gmp_strval($k1));
        $k2Bi = new \SmBc\Math\BigInteger(gmp_strval($k2));
        $u1 = $p1->multiply($k1Bi);
        $u2 = $p2->multiply($k2Bi);
        return $u1->add($u2)->normalize();
    }

    /**
     * Key Derivation Function (KDF) implementation.
     */
    private function kdf(ECPoint $u, string $za, string $zb, int $klen): string
    {
        $digestSize = $this->digest->getDigestSize();
        $buf = str_repeat("\0", max(4, $digestSize));
        $rv = '';
        $ct = 0;

        $normalizedU = $u->normalize();
        $baseData = $this->fieldElementToBytes($normalizedU->getAffineXCoord())
                  . $this->fieldElementToBytes($normalizedU->getAffineYCoord())
                  . $za
                  . $zb;

        $bytesNeeded = (int) ceil($klen / 8);

        while (strlen($rv) < $bytesNeeded) {
            $this->digest->reset();
            $this->digest->updateBytes($baseData, 0, strlen($baseData));
            $ct++;
            $ctBytes = str_repeat("\0", 4);
            Pack::intToBigEndian($ct, $ctBytes, 0);
            $this->digest->updateBytes($ctBytes, 0, 4);
            $hashResult = str_repeat("\0", $digestSize);
            $this->digest->doFinal($hashResult, 0);
            
            $copyLen = min($digestSize, $bytesNeeded - strlen($rv));
            $rv .= substr($hashResult, 0, $copyLen);
        }

        return $rv;
    }

    /**
     * Reduce function: x1~ = 2^w + (x1 AND (2^w - 1))
     */
    private function reduce(\GMP $x): \GMP
    {
        $mask = gmp_sub(gmp_pow(2, $this->w), 1);
        return gmp_or(gmp_and($x, $mask), gmp_pow(2, $this->w));
    }

    /**
     * Calculate S1 confirmation tag.
     */
    private function S1(Digest $digest, ECPoint $u, string $inner): string
    {
        $digest->reset();
        $digest->update(0x02);
        $normalizedU = $u->normalize();
        $this->addFieldElement($digest, $normalizedU->getAffineYCoord());
        $digest->updateBytes($inner, 0, strlen($inner));
        $result = str_repeat("\0", $digest->getDigestSize());
        $digest->doFinal($result, 0);
        return $result;
    }

    /**
     * Calculate S2 confirmation tag.
     */
    private function S2(Digest $digest, ECPoint $u, string $inner): string
    {
        $digest->reset();
        $digest->update(0x03);
        $normalizedU = $u->normalize();
        $this->addFieldElement($digest, $normalizedU->getAffineYCoord());
        $digest->updateBytes($inner, 0, strlen($inner));
        $result = str_repeat("\0", $digest->getDigestSize());
        $digest->doFinal($result, 0);
        return $result;
    }

    /**
     * Calculate inner hash for confirmation.
     */
    private function calculateInnerHash(
        Digest $digest,
        ECPoint $u,
        string $za,
        string $zb,
        ECPoint $p1,
        ECPoint $p2
    ): string {
        $digest->reset();
        $normalizedU = $u->normalize();
        $normalizedP1 = $p1->normalize();
        $normalizedP2 = $p2->normalize();
        
        $this->addFieldElement($digest, $normalizedU->getAffineXCoord());
        $digest->updateBytes($za, 0, strlen($za));
        $digest->updateBytes($zb, 0, strlen($zb));
        $this->addFieldElement($digest, $normalizedP1->getAffineXCoord());
        $this->addFieldElement($digest, $normalizedP1->getAffineYCoord());
        $this->addFieldElement($digest, $normalizedP2->getAffineXCoord());
        $this->addFieldElement($digest, $normalizedP2->getAffineYCoord());
        
        $result = str_repeat("\0", $digest->getDigestSize());
        $digest->doFinal($result, 0);
        return $result;
    }

    /**
     * Calculate Z value (user identification hash).
     */
    private function getZ(Digest $digest, string $userID, ECPoint $pubPoint): string
    {
        $digest->reset();
        $this->addUserID($digest, $userID);

        $this->addFieldElement($digest, $this->ecParams->getCurve()->getA());
        $this->addFieldElement($digest, $this->ecParams->getCurve()->getB());
        $this->addFieldElement($digest, $this->ecParams->getG()->getAffineXCoord());
        $this->addFieldElement($digest, $this->ecParams->getG()->getAffineYCoord());
        
        $normalizedPubPoint = $pubPoint->normalize();
        $this->addFieldElement($digest, $normalizedPubPoint->getAffineXCoord());
        $this->addFieldElement($digest, $normalizedPubPoint->getAffineYCoord());

        $result = str_repeat("\0", $digest->getDigestSize());
        $digest->doFinal($result, 0);
        return $result;
    }

    /**
     * Add user ID to digest with length prefix.
     */
    private function addUserID(Digest $digest, string $userID): void
    {
        $len = strlen($userID) * 8; // Length in bits
        $digest->update($len >> 8);
        $digest->update($len & 0xFF);
        if (strlen($userID) > 0) {
            $digest->updateBytes($userID, 0, strlen($userID));
        }
    }

    /**
     * Add field element to digest.
     */
    private function addFieldElement(Digest $digest, ECFieldElement $v): void
    {
        $p = $v->getEncoded();
        $digest->updateBytes($p, 0, strlen($p));
    }

    /**
     * Convert field element to bytes.
     */
    private function fieldElementToBytes(ECFieldElement $v): string
    {
        return $v->getEncoded();
    }
}
