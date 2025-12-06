<?php

declare(strict_types=1);

namespace SmBc\Crypto\Signers;

use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;

/**
 * Interface for calculating k values used in DSA-style signatures.
 * 
 * The k value is a critical component in DSA signatures that must be
 * cryptographically random and unique for each signature to maintain
 * security. This interface abstracts the k generation process.
 * 
 * Based on: org.bouncycastle.crypto.signers.DSAKCalculator
 *           sm-js-bc/src/crypto/signers/DSAKCalculator.ts
 */
interface DSAKCalculator
{
    /**
     * Initialize the calculator with the domain parameters.
     * 
     * @param BigInteger $n The order of the base point (curve order)
     * @param SecureRandom $random Secure random number generator
     */
    public function init(BigInteger $n, SecureRandom $random): void;

    /**
     * Calculate the next k value for signature generation.
     * 
     * @return BigInteger A cryptographically secure random k value where 1 <= k < n
     */
    public function nextK(): BigInteger;

    /**
     * Check if the calculator has been properly initialized.
     * 
     * @return bool True if this is a deterministic calculator, false for random
     */
    public function isDeterministic(): bool;
}
