<?php

declare(strict_types=1);

namespace SmBc\Crypto\Params;

use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;

/**
 * Public parameters for SM2 key exchange.
 * 
 * Contains both static and ephemeral public keys for the key agreement protocol.
 */
class SM2KeyExchangePublicParameters extends CipherParameters
{
    private ECPublicKeyParameters $staticPublicKey;
    private ECPublicKeyParameters $ephemeralPublicKey;

    /**
     * Create SM2 key exchange public parameters.
     * 
     * @param ECPublicKeyParameters $staticPublicKey The static public key
     * @param ECPublicKeyParameters $ephemeralPublicKey The ephemeral public key
     * @throws \InvalidArgumentException
     */
    public function __construct(
        ECPublicKeyParameters $staticPublicKey,
        ECPublicKeyParameters $ephemeralPublicKey
    ) {


        $this->staticPublicKey = $staticPublicKey;
        $this->ephemeralPublicKey = $ephemeralPublicKey;
    }

    /**
     * @return ECPublicKeyParameters the static public key
     */
    public function getStaticPublicKey(): ECPublicKeyParameters
    {
        return $this->staticPublicKey;
    }

    /**
     * @return ECPublicKeyParameters the ephemeral public key
     */
    public function getEphemeralPublicKey(): ECPublicKeyParameters
    {
        return $this->ephemeralPublicKey;
    }
}
