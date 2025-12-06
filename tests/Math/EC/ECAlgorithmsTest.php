<?php

declare(strict_types=1);

namespace SmBc\Tests\Math\EC;

use PHPUnit\Framework\TestCase;
use SmBc\Math\EC\ECAlgorithms;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Crypto\Params\ECDomainParameters;

class ECAlgorithmsTest extends TestCase
{
    private ECDomainParameters $sm2Params;

    protected function setUp(): void
    {
        // SM2 recommended parameters
        $p = new BigInteger('FFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF', 16);
        $a = new BigInteger('FFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC', 16);
        $b = new BigInteger('28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93', 16);
        $gx = new BigInteger('32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7', 16);
        $gy = new BigInteger('BC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0', 16);
        $n = new BigInteger('FFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123', 16);
        $h = BigInteger::ONE();

        $curve = new ECCurveFp($p, $a, $b);
        $G = $curve->createPoint($gx, $gy);

        $this->sm2Params = new ECDomainParameters($curve, $G, $n, $h);
    }

    public function testCleanPoint(): void
    {
        $curve = $this->sm2Params->getCurve();
        $G = $this->sm2Params->getG();
        
        $cleaned = ECAlgorithms::cleanPoint($curve, $G);
        
        $this->assertNotNull($cleaned);
        $this->assertTrue($cleaned->isValid());
    }

    public function testSumOfTwoMultiplies(): void
    {
        $G = $this->sm2Params->getG();
        
        $k1 = new BigInteger('5');
        $k2 = new BigInteger('7');
        
        // Calculate k1*G + k2*G using sumOfTwoMultiplies
        $result = ECAlgorithms::sumOfTwoMultiplies($G, $k1, $G, $k2);
        
        // Should equal (k1 + k2)*G = 12*G
        $expected = $G->multiply(new BigInteger('12'));
        
        $this->assertTrue($result->equals($expected));
    }

    public function testSumOfTwoMultipliesDifferentPoints(): void
    {
        $G = $this->sm2Params->getG();
        
        $k1 = new BigInteger('3');
        $P1 = $G->multiply(new BigInteger('2')); // P1 = 2*G
        
        $k2 = new BigInteger('5');
        $P2 = $G->multiply(new BigInteger('7')); // P2 = 7*G
        
        // Calculate 3*(2*G) + 5*(7*G) = 6*G + 35*G = 41*G
        $result = ECAlgorithms::sumOfTwoMultiplies($P1, $k1, $P2, $k2);
        
        $expected = $G->multiply(new BigInteger('41'));
        
        $this->assertTrue($result->equals($expected));
    }

    public function testIsValidScalar(): void
    {
        $curve = $this->sm2Params->getCurve();
        
        $this->assertTrue(ECAlgorithms::isValidScalar($curve, BigInteger::ONE()));
        $this->assertTrue(ECAlgorithms::isValidScalar($curve, new BigInteger('12345')));
        $this->assertFalse(ECAlgorithms::isValidScalar($curve, BigInteger::ZERO()));
        $this->assertFalse(ECAlgorithms::isValidScalar($curve, new BigInteger('-1')));
    }

    public function testIsPointAtInfinity(): void
    {
        $curve = $this->sm2Params->getCurve();
        $G = $this->sm2Params->getG();
        
        $infinity = $curve->getInfinity();
        
        $this->assertTrue(ECAlgorithms::isPointAtInfinity($infinity));
        $this->assertFalse(ECAlgorithms::isPointAtInfinity($G));
    }

    public function testAreOnSameCurve(): void
    {
        $G = $this->sm2Params->getG();
        $P = $G->multiply(new BigInteger('5'));
        
        $this->assertTrue(ECAlgorithms::areOnSameCurve($G, $P));
        $this->assertTrue(ECAlgorithms::areOnSameCurve($G, $G));
    }
}
