<?php

namespace SmBc\Tests\Unit\Math\EC;

use PHPUnit\Framework\TestCase;
use SmBc\Math\EC\ECFieldElementFp;
use SmBc\Math\BigInteger;

class ECFieldElementFpTest extends TestCase
{
    private BigInteger $p;

    protected function setUp(): void
    {
        $this->p = new BigInteger(1063);
    }

    public function testAdd(): void
    {
        $a = new ECFieldElementFp($this->p, new BigInteger(5));
        $b = new ECFieldElementFp($this->p, new BigInteger(7));
        $this->assertEquals(12, $a->add($b)->toBigInteger()->toString());
    }

    public function testSubtract(): void
    {
        $a = new ECFieldElementFp($this->p, new BigInteger(7));
        $b = new ECFieldElementFp($this->p, new BigInteger(5));
        $this->assertEquals(2, $a->subtract($b)->toBigInteger()->toString());
    }

    public function testSubtractWrap(): void
    {
        $a = new ECFieldElementFp($this->p, new BigInteger(5));
        $b = new ECFieldElementFp($this->p, new BigInteger(7));
        $expected = 1061; // 5 - 7 + 1063
        $this->assertEquals($expected, $a->subtract($b)->toBigInteger()->toString());
    }

    public function testMultiply(): void
    {
        $a = new ECFieldElementFp($this->p, new BigInteger(5));
        $b = new ECFieldElementFp($this->p, new BigInteger(7));
        $this->assertEquals(35, $a->multiply($b)->toBigInteger()->toString());
    }

    public function testMultiplyWrap(): void
    {
        $a = new ECFieldElementFp($this->p, new BigInteger(500));
        $b = new ECFieldElementFp($this->p, new BigInteger(500));
        // 250000 % 1063 = 195
        $this->assertEquals(195, $a->multiply($b)->toBigInteger()->toString());
    }

    public function testSquare(): void
    {
        $a = new ECFieldElementFp($this->p, new BigInteger(5));
        $this->assertEquals(25, $a->square()->toBigInteger()->toString());
    }

    public function testNegate(): void
    {
        $a = new ECFieldElementFp($this->p, new BigInteger(5));
        // 1063 - 5 = 1058
        $this->assertEquals(1058, $a->negate()->toBigInteger()->toString());
    }

    public function testInvert(): void
    {
        $two = new ECFieldElementFp($this->p, new BigInteger(2));
        $invTwo = $two->invert();
        $res = $two->multiply($invTwo);
        $this->assertEquals(1, $res->toBigInteger()->toString());
    }

    public function testDivide(): void
    {
        $ten = new ECFieldElementFp($this->p, new BigInteger(10));
        $two = new ECFieldElementFp($this->p, new BigInteger(2));
        $res = $ten->divide($two);
        $this->assertEquals(5, $res->toBigInteger()->toString());
    }

    public function testOnCurve(): void
    {
        // y^2 = x^3 + 4x + 20 mod 1063
        // point (1, 5)
        $x = new ECFieldElementFp($this->p, new BigInteger(1));
        $y = new ECFieldElementFp($this->p, new BigInteger(5));
        $a = new ECFieldElementFp($this->p, new BigInteger(4));
        $b = new ECFieldElementFp($this->p, new BigInteger(20));

        $lhs = $y->square();
        $x3 = $x->square()->multiply($x);
        $ax = $a->multiply($x);
        $rhs = $x3->add($ax)->add($b);

        $this->assertEquals($lhs->toBigInteger()->toString(), $rhs->toBigInteger()->toString());
    }
}