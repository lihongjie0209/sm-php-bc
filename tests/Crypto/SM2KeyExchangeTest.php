<?php

declare(strict_types=1);

namespace Rtgm\SmPhpBc\Tests\Crypto;

use PHPUnit\Framework\TestCase;
use Rtgm\SmPhpBc\Crypto\Agreement\SM2KeyExchange;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ParametersWithID;
use Rtgm\SmPhpBc\Crypto\Params\SM2KeyExchangePrivateParameters;
use Rtgm\SmPhpBc\Crypto\Params\SM2KeyExchangePublicParameters;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\EC\ECPoint;
use SmBc\Math\BigInteger;

class SM2KeyExchangeTest extends TestCase
{
    // Standard SM2 Curve Parameters
    private const SM2_ECC_P = '0x8542D69E4C044F18E8B92435BF6FF7DE457283915C45517D722EDB8B08F1DFC3';
    private const SM2_ECC_A = '0x787968B4FA32C3FD2417842E73BBFEFF2F3C848B6831D7E0EC65228B3937E498';
    private const SM2_ECC_B = '0x63E4C6D3B23B0C849CF84241484BFE48F61D59A5B16BA06E6E12D1DA27C5249A';
    private const SM2_ECC_N = '0x8542D69E4C044F18E8B92435BF6FF7DD297720630485628D5AE74EE7C32E79B7';
    private const SM2_ECC_GX = '0x421DEBD61B62EAB6746434EBC3CC315E32220B3BADD50BDC4C4E6C147FEDD43D';
    private const SM2_ECC_GY = '0x0680512BCBB42C07D47349D2153B70C4E5D7FDFCBFA36EA1A85841B9E46E09A2';

    private ECCurveFp $curve;
    private ECPoint $g;
    private ECDomainParameters $domainParams;

    // Test key pairs from BC Java reference
    private ECPrivateKeyParameters $aPriv;
    private ECPublicKeyParameters $aPub;
    private ECPrivateKeyParameters $aePriv;
    private ECPublicKeyParameters $aePub;
    private ECPrivateKeyParameters $bPriv;
    private ECPublicKeyParameters $bPub;
    private ECPrivateKeyParameters $bePriv;
    private ECPublicKeyParameters $bePub;

    protected function setUp(): void
    {
        // Create the SM2 curve
        $this->curve = new ECCurveFp(
            new BigInteger(self::SM2_ECC_P),
            new BigInteger(self::SM2_ECC_A),
            new BigInteger(self::SM2_ECC_B)
        );
        $this->g = $this->curve->createPoint(new BigInteger(self::SM2_ECC_GX), new BigInteger(self::SM2_ECC_GY));
        $this->domainParams = new ECDomainParameters(
            $this->curve,
            $this->g,
            new BigInteger(self::SM2_ECC_N),
            new BigInteger('1')
        );

        // Alice's static key pair
        $aPrivateKey = new BigInteger('0x6FCBA2EF9AE0AB902BC3BDE3FF915D44BA4CC78F88E2F8E7F8996D3B8CCEEDEE');
        $this->aPriv = new ECPrivateKeyParameters($aPrivateKey, $this->domainParams);
        $this->aPub = new ECPublicKeyParameters($this->g->multiply($aPrivateKey), $this->domainParams);

        // Alice's ephemeral key pair
        $aePrivateKey = new BigInteger('0x83A2C9C8B96E5AF70BD480B472409A9A327257F1EBB73F5B073354B248668563');
        $this->aePriv = new ECPrivateKeyParameters($aePrivateKey, $this->domainParams);
        $this->aePub = new ECPublicKeyParameters($this->g->multiply($aePrivateKey), $this->domainParams);

        // Bob's static key pair
        $bPrivateKey = new BigInteger('0x5E35D7D3F3C54DBAC72E61819E730B019A84208CA3A35E4C2E353DFCCB2A3B53');
        $this->bPriv = new ECPrivateKeyParameters($bPrivateKey, $this->domainParams);
        $this->bPub = new ECPublicKeyParameters($this->g->multiply($bPrivateKey), $this->domainParams);

        // Bob's ephemeral key pair
        $bePrivateKey = new BigInteger('0x33FE21940342161C55619C4A0C060293D543C80AF19748CE176D83477DE71C80');
        $this->bePriv = new ECPrivateKeyParameters($bePrivateKey, $this->domainParams);
        $this->bePub = new ECPublicKeyParameters($this->g->multiply($bePrivateKey), $this->domainParams);
    }

    public function testBasicKeyExchangeFromAliceToBob(): void
    {
        $exchange = new SM2KeyExchange();
        $aliceUserID = 'ALICE123@YAHOO.COM';
        $bobUserID = 'BILL456@YAHOO.COM';

        // Alice initiates
        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);
        $exchange->init(new ParametersWithID($alicePrivParams, $aliceUserID));

        // Alice calculates key using Bob's public parameters
        $bobPubParams = new SM2KeyExchangePublicParameters($this->bPub, $this->bePub);
        $key1 = $exchange->calculateKey(128, new ParametersWithID($bobPubParams, $bobUserID));

        $this->assertSame(16, strlen($key1)); // 128 bits = 16 bytes
    }

    public function testBasicKeyExchangeFromBobToAlice(): void
    {
        $exchange = new SM2KeyExchange();
        $aliceUserID = 'ALICE123@YAHOO.COM';
        $bobUserID = 'BILL456@YAHOO.COM';

        // Bob responds
        $bobPrivParams = new SM2KeyExchangePrivateParameters(false, $this->bPriv, $this->bePriv);
        $exchange->init(new ParametersWithID($bobPrivParams, $bobUserID));

        // Bob calculates key using Alice's public parameters
        $alicePubParams = new SM2KeyExchangePublicParameters($this->aPub, $this->aePub);
        $key2 = $exchange->calculateKey(128, new ParametersWithID($alicePubParams, $aliceUserID));

        $this->assertSame(16, strlen($key2)); // 128 bits = 16 bytes
    }

    public function testBothPartiesProduceSameKey(): void
    {
        $aliceExchange = new SM2KeyExchange();
        $bobExchange = new SM2KeyExchange();

        // Alice initiates key exchange
        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);
        $aliceExchange->init(new ParametersWithID($alicePrivParams, 'ALICE123@YAHOO.COM'));
        $bobPubParams = new SM2KeyExchangePublicParameters($this->bPub, $this->bePub);
        $aliceKey = $aliceExchange->calculateKey(128, new ParametersWithID($bobPubParams, 'BILL456@YAHOO.COM'));

        // Bob's exchange
        $bobPrivParams = new SM2KeyExchangePrivateParameters(false, $this->bPriv, $this->bePriv);
        $bobExchange->init(new ParametersWithID($bobPrivParams, 'BILL456@YAHOO.COM'));
        $alicePubParams = new SM2KeyExchangePublicParameters($this->aPub, $this->aePub);
        $bobKey = $bobExchange->calculateKey(128, new ParametersWithID($alicePubParams, 'ALICE123@YAHOO.COM'));

        // Keys should be identical
        $this->assertSame($aliceKey, $bobKey);
    }

    public function testKeyExchangeWithConfirmationFromBobSide(): void
    {
        $exchange = new SM2KeyExchange();
        $bobPrivParams = new SM2KeyExchangePrivateParameters(false, $this->bPriv, $this->bePriv);
        $exchange->init(new ParametersWithID($bobPrivParams, 'BILL456@YAHOO.COM'));

        $alicePubParams = new SM2KeyExchangePublicParameters($this->aPub, $this->aePub);
        $result = $exchange->calculateKeyWithConfirmation(128, null, new ParametersWithID($alicePubParams, 'ALICE123@YAHOO.COM'));

        // Bob is responder, so returns [key, s1, s2]
        $this->assertCount(3, $result);
        $this->assertSame(16, strlen($result[0])); // key should be 16 bytes
        $this->assertSame(32, strlen($result[1])); // s1 should be 32 bytes (SM3 digest)
        $this->assertSame(32, strlen($result[2])); // s2 should be 32 bytes (SM3 digest)
    }

    public function testKeyExchangeWithConfirmationFromAliceSide(): void
    {
        $aliceExchange = new SM2KeyExchange();
        $bobExchange = new SM2KeyExchange();

        // Bob creates confirmation tag first (as responder)
        $bobPrivParams = new SM2KeyExchangePrivateParameters(false, $this->bPriv, $this->bePriv);
        $bobExchange->init(new ParametersWithID($bobPrivParams, 'BILL456@YAHOO.COM'));
        $alicePubParams = new SM2KeyExchangePublicParameters($this->aPub, $this->aePub);
        $bobResult = $bobExchange->calculateKeyWithConfirmation(128, null, new ParametersWithID($alicePubParams, 'ALICE123@YAHOO.COM'));

        // Alice responds with confirmation (as initiator)
        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);
        $aliceExchange->init(new ParametersWithID($alicePrivParams, 'ALICE123@YAHOO.COM'));
        $bobPubParams = new SM2KeyExchangePublicParameters($this->bPub, $this->bePub);
        $aliceResult = $aliceExchange->calculateKeyWithConfirmation(128, $bobResult[1], new ParametersWithID($bobPubParams, 'BILL456@YAHOO.COM'));

        // Bob is responder: returns [key, s1, s2]
        // Alice is initiator: returns [key, s2]
        $this->assertCount(3, $bobResult);
        $this->assertCount(2, $aliceResult);

        // Keys should be the same
        $this->assertSame($bobResult[0], $aliceResult[0]);
    }

    public function testValidateConfirmationTagsMatch(): void
    {
        $aliceExchange = new SM2KeyExchange();
        $bobExchange = new SM2KeyExchange();

        // Bob exchange (responder)
        $bobPrivParams = new SM2KeyExchangePrivateParameters(false, $this->bPriv, $this->bePriv);
        $bobExchange->init(new ParametersWithID($bobPrivParams, 'BILL456@YAHOO.COM'));
        $alicePubParams = new SM2KeyExchangePublicParameters($this->aPub, $this->aePub);
        $bobResult = $bobExchange->calculateKeyWithConfirmation(128, null, new ParametersWithID($alicePubParams, 'ALICE123@YAHOO.COM'));

        // Alice exchange (initiator with Bob's confirmation)
        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);
        $aliceExchange->init(new ParametersWithID($alicePrivParams, 'ALICE123@YAHOO.COM'));
        $bobPubParams = new SM2KeyExchangePublicParameters($this->bPub, $this->bePub);
        $aliceResult = $aliceExchange->calculateKeyWithConfirmation(128, $bobResult[1], new ParametersWithID($bobPubParams, 'BILL456@YAHOO.COM'));

        // Validate key agreement
        $this->assertSame($bobResult[0], $aliceResult[0]);
        // Validate confirmation protocol - both should produce same S2 tag
        $this->assertSame($bobResult[2], $aliceResult[1]); // Bob's S2 should match Alice's S2
    }

    public function testDifferentKeyLengths(): void
    {
        $exchange = new SM2KeyExchange();
        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);
        $exchange->init(new ParametersWithID($alicePrivParams, 'ALICE123@YAHOO.COM'));

        $bobPubParams = new SM2KeyExchangePublicParameters($this->bPub, $this->bePub);

        $key64 = $exchange->calculateKey(64, new ParametersWithID($bobPubParams, 'BILL456@YAHOO.COM'));
        $key256 = $exchange->calculateKey(256, new ParametersWithID($bobPubParams, 'BILL456@YAHOO.COM'));

        $this->assertSame(8, strlen($key64));   // 64 bits = 8 bytes
        $this->assertSame(32, strlen($key256)); // 256 bits = 32 bytes
    }

    public function testThrowsErrorForInvalidKeyLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $exchange = new SM2KeyExchange();
        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);
        $exchange->init(new ParametersWithID($alicePrivParams, 'ALICE123@YAHOO.COM'));

        $bobPubParams = new SM2KeyExchangePublicParameters($this->bPub, $this->bePub);

        $exchange->calculateKey(0, new ParametersWithID($bobPubParams, 'BILL456@YAHOO.COM'));
    }

    public function testHandlesEmptyUserIDs(): void
    {
        $exchange = new SM2KeyExchange();
        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);

        // Should not throw for empty user ID
        $exchange->init(new ParametersWithID($alicePrivParams, ''));
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testDifferentUserIDsProduceDifferentKeys(): void
    {
        $exchange1 = new SM2KeyExchange();
        $exchange2 = new SM2KeyExchange();

        $alicePrivParams = new SM2KeyExchangePrivateParameters(true, $this->aPriv, $this->aePriv);
        $bobPubParams = new SM2KeyExchangePublicParameters($this->bPub, $this->bePub);

        $exchange1->init(new ParametersWithID($alicePrivParams, 'ALICE123@YAHOO.COM'));
        $key1 = $exchange1->calculateKey(128, new ParametersWithID($bobPubParams, 'BILL456@YAHOO.COM'));

        $exchange2->init(new ParametersWithID($alicePrivParams, 'ALICE456@YAHOO.COM'));
        $key2 = $exchange2->calculateKey(128, new ParametersWithID($bobPubParams, 'BILL789@YAHOO.COM'));

        $this->assertNotSame($key1, $key2); // Different user IDs should produce different keys
    }
}
