<?php

namespace SmBc\Tests\Unit\Math\EC;

use PHPUnit\Framework\TestCase;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;

class ECPointFpTest extends TestCase
{
    private ECCurveFp $curve;
    private $P1;
    private $P2;

    protected function setUp(): void
    {
        // y^2 = x^3 + 4x + 20 over F_1063
        $p = new BigInteger(1063);
        $a = new BigInteger(4);
        $b = new BigInteger(20);
        $this->curve = new ECCurveFp($p, $a, $b);

        $this->P1 = $this->curve->validatePoint(new BigInteger(1), new BigInteger(5));
        $this->P2 = $this->curve->validatePoint(new BigInteger(4), new BigInteger(10));
    }

    public function testValidation(): void
    {
        $this->assertTrue($this->P1->isValid());
        $this->assertTrue($this->P2->isValid());
        
        $inf = $this->curve->getInfinity();
        $this->assertTrue($inf->isInfinity());
        $this->assertTrue($inf->isValid());
    }

    public function testDoubling(): void
    {
        $P = $this->P1;
        $twoP = $P->twice();
        
        $this->assertFalse($twoP->isInfinity());
        $this->assertTrue($twoP->isValid());
        
        // P + P
        $P_plus_P = $P->add($P);
        $this->assertTrue($twoP->normalize()->equals($P_plus_P->normalize()));
    }

    public function testDoublingInfinity(): void
    {
        $inf = $this->curve->getInfinity();
        $result = $inf->twice();
        $this->assertTrue($result->isInfinity());
    }

    public function testAddition(): void
    {
        $res = $this->P1->add($this->P2);
        $this->assertFalse($res->isInfinity());
        $this->assertTrue($res->isValid());
    }

    public function testCommutative(): void
    {
        $r1 = $this->P1->add($this->P2);
        $r2 = $this->P2->add($this->P1);
        $this->assertTrue($r1->normalize()->equals($r2->normalize()));
    }

    public function testIdentity(): void
    {
        $inf = $this->curve->getInfinity();
        $res = $this->P1->add($inf);
        $this->assertTrue($res->normalize()->equals($this->P1->normalize()));
        
        $res2 = $inf->add($this->P1);
        $this->assertTrue($res2->normalize()->equals($this->P1->normalize()));
    }

    public function testInverse(): void
    {
        $P = $this->P1;
        $negP = $P->negate();
        $res = $P->add($negP);
        $this->assertTrue($res->isInfinity());
    }

    public function testNegation(): void
    {
        $P = $this->P1;
        $negP = $P->negate();
        
        $this->assertFalse($negP->isInfinity());
        $this->assertTrue($negP->isValid());
        
        // Check coordinates
        $Pnorm = $P->normalize();
        $negPnorm = $negP->normalize();
        
        $this->assertEquals($Pnorm->getXCoord()->toBigInteger()->toString(), $negPnorm->getXCoord()->toBigInteger()->toString());
        
        $p = $this->curve->getQ();
        $y = $Pnorm->getYCoord()->toBigInteger();
        $negY = $negPnorm->getYCoord()->toBigInteger();
        
        // y + negY = p (or 0)
        $sum = $y->add($negY)->mod($p);
        $this->assertEquals(0, $sum->toString());
    }

    public function testEncoding(): void
    {
        $P = $this->P1;
        $encoded = $P->getEncoded(false);
        
        $this->assertEquals("\x04", $encoded[0]);
        
        $decoded = $this->curve->decodePoint($encoded);
        $this->assertTrue($decoded->normalize()->equals($P->normalize()));
    }

    public function testEncodingInfinity(): void
    {
        $inf = $this->curve->getInfinity();
        $encoded = $inf->getEncoded(false);
        
        $this->assertEquals(1, strlen($encoded));
        $this->assertEquals("\x00", $encoded);
        
        $decoded = $this->curve->decodePoint($encoded);
        $this->assertTrue($decoded->isInfinity());
    }
}
