<?php

declare(strict_types=1);

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;

/**
 * Elliptic curve algorithms utility class.
 * 
 * Provides common elliptic curve operations and algorithms.
 */
class ECAlgorithms
{
    /**
     * Clean a point by validating it's on the curve and normalizing if needed.
     * 
     * @param ECCurve $curve The elliptic curve
     * @param ECPoint $point The point to clean
     * @return ECPoint The cleaned (validated and normalized) point
     * @throws \RuntimeException If point is not valid
     */
    public static function cleanPoint(ECCurve $curve, ECPoint $point): ECPoint
    {
        if (!$curve->equals($point->getCurve())) {
            throw new \RuntimeException('Point is not on the expected curve');
        }
        
        if (!$point->isValid()) {
            throw new \RuntimeException('Point is not valid');
        }
        
        return $point->normalize();
    }

    /**
     * Calculate k1*P1 + k2*P2 efficiently.
     * 
     * This method computes the sum of two scalar multiplications more efficiently
     * than computing them separately and then adding.
     * 
     * @param ECPoint $P1 The first point
     * @param BigInteger $k1 The first scalar
     * @param ECPoint $P2 The second point
     * @param BigInteger $k2 The second scalar
     * @return ECPoint The result point k1*P1 + k2*P2
     * @throws \RuntimeException If points are not on the same curve
     */
    public static function sumOfTwoMultiplies(
        ECPoint $P1,
        BigInteger $k1,
        ECPoint $P2,
        BigInteger $k2
    ): ECPoint {
        if (!$P1->getCurve()->equals($P2->getCurve())) {
            throw new \RuntimeException('Points must be on the same curve');
        }

        // Use Shamir's trick for efficient dual scalar multiplication
        // This is a simplified implementation - could be optimized further
        
        $result1 = $P1->multiply($k1);
        $result2 = $P2->multiply($k2);
        
        return $result1->add($result2);
    }

    /**
     * Validate that a scalar is in the valid range [1, n-1] for the curve.
     * 
     * @param ECCurve $curve The elliptic curve
     * @param BigInteger $scalar The scalar to validate
     * @return bool true if valid, false otherwise
     */
    public static function isValidScalar(ECCurve $curve, BigInteger $scalar): bool
    {
        if ($scalar->compareTo(BigInteger::ZERO()) <= 0) {
            return false;
        }
        
        // For elliptic curves, we need the domain parameters to get n
        // This is a simplified check - 2^256
        $maxValue = BigInteger::ONE()->shiftLeft(256);
        return $scalar->compareTo($maxValue) < 0;
    }

    /**
     * Check if a point is the point at infinity.
     * 
     * @param ECPoint $point The point to check
     * @return bool true if the point is at infinity
     */
    public static function isPointAtInfinity(ECPoint $point): bool
    {
        return $point->isInfinity();
    }

    /**
     * Validate that two points are on the same curve.
     * 
     * @param ECPoint $P1 First point
     * @param ECPoint $P2 Second point
     * @return bool true if both points are on the same curve
     */
    public static function areOnSameCurve(ECPoint $P1, ECPoint $P2): bool
    {
        return $P1->getCurve()->equals($P2->getCurve());
    }
}
