<?php

declare(strict_types=1);

namespace SmBc\Crypto\Params;

use SmBc\Math\EC\ECPoint;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\CipherParameters;

/**
 * Private parameters for SM2 key exchange.
 * 
 * Contains both static and ephemeral private keys along with their
 * corresponding public points for the key agreement protocol.
 */
class SM2KeyExchangePrivateParameters extends CipherParameters
{
    private bool $initiator;
    private ECPrivateKeyParameters $staticPrivateKey;
    private ECPoint $staticPublicPoint;
    private ECPrivateKeyParameters $ephemeralPrivateKey;
    private ECPoint $ephemeralPublicPoint;

    /**
     * Create SM2 key exchange private parameters.
     * 
     * @param bool $initiator Whether this party is the initiator of the key exchange
     * @param ECPrivateKeyParameters $staticPrivateKey The static private key
     * @param ECPrivateKeyParameters $ephemeralPrivateKey The ephemeral private key
     * @throws \InvalidArgumentException
     */
    public function __construct(
        bool $initiator,
        ECPrivateKeyParameters $staticPrivateKey,
        ECPrivateKeyParameters $ephemeralPrivateKey
    ) {
        $parameters = $staticPrivateKey->getParameters();

        $this->initiator = $initiator;
        $this->staticPrivateKey = $staticPrivateKey;
        $this->staticPublicPoint = $parameters->getG()->multiply($staticPrivateKey->getD())->normalize();
        $this->ephemeralPrivateKey = $ephemeralPrivateKey;
        $this->ephemeralPublicPoint = $parameters->getG()->multiply($ephemeralPrivateKey->getD())->normalize();
    }

    /**
     * @return bool true if this party is the initiator
     */
    public function isInitiator(): bool
    {
        return $this->initiator;
    }

    /**
     * @return ECPrivateKeyParameters the static private key
     */
    public function getStaticPrivateKey(): ECPrivateKeyParameters
    {
        return $this->staticPrivateKey;
    }

    /**
     * @return ECPoint the computed static public point
     */
    public function getStaticPublicPoint(): ECPoint
    {
        return $this->staticPublicPoint;
    }

    /**
     * @return ECPrivateKeyParameters the ephemeral private key
     */
    public function getEphemeralPrivateKey(): ECPrivateKeyParameters
    {
        return $this->ephemeralPrivateKey;
    }

    /**
     * @return ECPoint the computed ephemeral public point
     */
    public function getEphemeralPublicPoint(): ECPoint
    {
        return $this->ephemeralPublicPoint;
    }
}
