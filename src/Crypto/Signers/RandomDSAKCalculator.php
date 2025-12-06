<?php

declare(strict_types=1);

namespace SmBc\Crypto\Signers;

use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;
use RuntimeException;

/**
 * Random k calculator for DSA-style signatures.
 * 
 * This implementation generates cryptographically secure random k values
 * for each signature operation. Each k value is uniformly distributed
 * in the range [1, n-1] where n is the curve order.
 * 
 * Based on: org.bouncycastle.crypto.signers.RandomDSAKCalculator
 *           sm-js-bc/src/crypto/signers/RandomDSAKCalculator.ts
 */
class RandomDSAKCalculator implements DSAKCalculator
{
    private ?BigInteger $q = null;
    private ?SecureRandom $random = null;

    /**
     * Initialize with curve order and random source.
     */
    public function init(BigInteger $n, SecureRandom $random): void
    {
        if ($n->compareTo(BigInteger::ONE()) <= 0) {
            throw new RuntimeException('Order must be greater than 1');
        }
        
        $this->q = $n;
        $this->random = $random;
    }

    /**
     * Generate next random k value.
     */
    public function nextK(): BigInteger
    {
        if ($this->q === null || $this->random === null) {
            throw new RuntimeException('Calculator not initialized');
        }

        $bitLength = $this->q->bitLength();
        
        // Generate random k in range [1, q-1]
        do {
            $k = $this->createRandomBigInteger($bitLength - 1, $this->random);
            
            // Ensure k is at least 1
            if ($k->compareTo(BigInteger::ZERO()) === 0) {
                $k = BigInteger::ONE();
            }
        } while ($k->compareTo($this->q) >= 0);

        return $k;
    }

    /**
     * This is a non-deterministic (random) calculator.
     */
    public function isDeterministic(): bool
    {
        return false;
    }

    /**
     * Create random BigInteger with specified bit length.
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
}
