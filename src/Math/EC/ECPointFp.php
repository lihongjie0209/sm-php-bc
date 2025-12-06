<?php

namespace SmBc\Math\EC;

use SmBc\Math\BigInteger;

class ECPointFp extends ECPoint
{
    public function __construct(?ECCurve $curve, ?ECFieldElement $x, ?ECFieldElement $y, array $zs = [])
    {
        parent::__construct($curve, $x, $y, $zs);
    }

    protected function satisfiesCurveEquation(): bool
    {
        $x = $this->x;
        $y = $this->y;
        $a = $this->curve->getA();
        $b = $this->curve->getB();
        $q = $this->curve->getQ();

        // y^2 = x^3 + ax + b
        // Jacobian: Y^2 = X^3 + a*X*Z^4 + b*Z^6
        
        $lhs = $y->square();
        
        if (!empty($this->zs)) {
            $z = $this->zs[0];
            if (!$z->isOne()) {
                 $z2 = $z->square();
                 $z4 = $z2->square();
                 $z6 = $z4->multiply($z2);
                 $a = $a->multiply($z4);
                 $b = $b->multiply($z6);
            }
        }
        
        $rhs = $x->square()->add($a)->multiply($x)->add($b);
        
        return $lhs->equals($rhs);
    }

    public function add(ECPoint $b): ECPoint
    {
        if ($this->isInfinity()) return $b;
        if ($b->isInfinity()) return $this;
        if ($this === $b) return $this->twice();

        $curve = $this->getCurve();
        
        $X1 = $this->x;
        $Y1 = $this->y;
        $X2 = $b->x;
        $Y2 = $b->y;
        
        $Z1 = empty($this->zs) ? $curve->fromBigInteger(ECConstants::$ONE) : $this->zs[0];
        $Z2 = empty($b->zs) ? $curve->fromBigInteger(ECConstants::$ONE) : $b->zs[0];
        
        // Jacobian Addition
        // U1 = X1 * Z2^2
        // U2 = X2 * Z1^2
        // S1 = Y1 * Z2^3
        // S2 = Y2 * Z1^3
        
        $Z1Sq = $Z1->square();
        $Z2Sq = $Z2->square();
        
        $U1 = $X1->multiply($Z2Sq);
        $U2 = $X2->multiply($Z1Sq);
        
        $S1 = $Y1->multiply($Z2Sq)->multiply($Z2);
        $S2 = $Y2->multiply($Z1Sq)->multiply($Z1);
        
        $H = $U2->subtract($U1);
        $R = $S2->subtract($S1);
        
        if ($H->isZero()) {
            if ($R->isZero()) {
                return $this->twice();
            }
            return $curve->getInfinity();
        }
        
        $HSq = $H->square();
        $HCub = $HSq->multiply($H);
        $V = $U1->multiply($HSq);
        
        $X3 = $R->square()->subtract($HCub)->subtract($V->add($V)); // R^2 - H^3 - 2*V
        $Y3 = $R->multiply($V->subtract($X3))->subtract($S1->multiply($HCub));
        $Z3 = $Z1->multiply($Z2)->multiply($H);
        
        return new ECPointFp($curve, $X3, $Y3, [$Z3]);
    }

    public function twice(): ECPoint
    {
        if ($this->isInfinity()) return $this;
        if ($this->y->isZero()) return $this->getCurve()->getInfinity();
        
        $curve = $this->getCurve();
        $X1 = $this->x;
        $Y1 = $this->y;
        $Z1 = empty($this->zs) ? $curve->fromBigInteger(ECConstants::$ONE) : $this->zs[0];
        
        // Jacobian Doubling
        // S = 4 * X * Y^2
        // M = 3 * X^2 + a * Z^4
        // X3 = M^2 - 2 * S
        // Y3 = M * (S - X3) - 8 * Y^4
        // Z3 = 2 * Y * Z
        
        $Y1Sq = $Y1->square();
        $T = $Y1Sq->square();
        
        $S = $X1->multiply($Y1Sq)->multiply($curve->fromBigInteger(ECConstants::$FOUR)); // 4*X*Y^2
        
        $M = $X1->square()->multiply($curve->fromBigInteger(ECConstants::$THREE));
        if (!$curve->getA()->isZero()) {
             $Z1Pow4 = $Z1->square()->square();
             $M = $M->add($curve->getA()->multiply($Z1Pow4));
        }
        
        $X3 = $M->square()->subtract($S->add($S));
        $Y3 = $M->multiply($S->subtract($X3))->subtract($T->multiply($curve->fromBigInteger(ECConstants::$EIGHT)));
        $Z3 = $Y1->multiply($Z1)->add($Y1->multiply($Z1)); // 2*Y*Z
        
        return new ECPointFp($curve, $X3, $Y3, [$Z3]);
    }
    
    public function negate(): ECPoint
    {
        if ($this->isInfinity()) return $this;
        
        $curve = $this->getCurve();
        $yNeg = $this->y->negate();
        return new ECPointFp($curve, $this->x, $yNeg, $this->zs);
    }
}
