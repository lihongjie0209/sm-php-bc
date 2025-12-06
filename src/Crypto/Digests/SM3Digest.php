<?php

namespace SmBc\Crypto\Digests;

use SmBc\Crypto\Memoable;
use SmBc\Util\Pack;
use SmBc\Util\Integers;

class SM3Digest extends GeneralDigest
{
    private const DIGEST_LENGTH = 32;
    private const BLOCK_SIZE = 64;

    private static array $IV = [
        0x7380166f, 0x4914b2b9, 0x172442d7, 0xda8a0600,
        0xa96f30bc, 0x163138aa, 0xe38dee4d, 0xb0fb0e4e
    ];

    private static ?array $T = null;

    /** @var int[] */
    private array $V;
    /** @var int[] */
    private array $X;
    private int $xOff;

    public function __construct(?SM3Digest $digest = null)
    {
        self::initT();
        parent::__construct($digest);

        $this->V = array_fill(0, 8, 0);
        $this->X = array_fill(0, 68, 0);
        $this->xOff = 0;

        if ($digest) {
            $this->copyInSM3($digest);
        } else {
            $this->resetState();
        }
    }

    private static function initT(): void
    {
        if (self::$T !== null) {
            return;
        }

        self::$T = [];
        for ($i = 0; $i < 16; $i++) {
            $t = 0x79cc4519;
            self::$T[$i] = Integers::rotateLeft($t, $i);
        }
        for ($i = 16; $i < 64; $i++) {
            $n = $i % 32;
            $t = 0x7a879d8a;
            self::$T[$i] = Integers::rotateLeft($t, $n);
        }
    }

    public function getAlgorithmName(): string
    {
        return 'SM3';
    }

    public function getDigestSize(): int
    {
        return self::DIGEST_LENGTH;
    }

    public function doFinal(string &$output, int $outOff): int
    {
        $this->finish();

        for ($i = 0; $i < 8; $i++) {
            Pack::intToBigEndian($this->V[$i], $output, $outOff + $i * 4);
        }

        $this->reset();

        return self::DIGEST_LENGTH;
    }

    public function reset(Memoable $other = null): void
    {
        if ($other !== null) {
            if (!($other instanceof SM3Digest)) {
                throw new \InvalidArgumentException("Cannot reset from different digest type");
            }
            parent::copyIn($other);
            $this->copyInSM3($other);
        } else {
            parent::reset();
            $this->resetState();
        }
    }

    protected function resetState(): void
    {
        $this->V = self::$IV;
        $this->xOff = 0;
        // X will be overwritten, no need to clear
        for($i=0; $i<68; $i++) $this->X[$i] = 0;
    }

    protected function processWord(string $input, int $offset): void
    {
        $this->X[$this->xOff] = Pack::bigEndianToInt($input, $offset);
        $this->xOff++;

        if ($this->xOff >= 16) {
            $this->processBlock();
        }
    }

    protected function processLength(int $bitLength): void
    {
        if ($this->xOff > 14) {
            $this->X[$this->xOff] = 0;
            $this->xOff++;
            $this->processBlock();
        }

        while ($this->xOff < 14) {
            $this->X[$this->xOff] = 0;
            $this->xOff++;
        }

        $this->X[$this->xOff++] = ($bitLength >> 32) & 0xFFFFFFFF; // High 32 bits
        $this->X[$this->xOff++] = $bitLength & 0xFFFFFFFF; // Low 32 bits
    }

    protected function processBlock(): void
    {
        // Message Expansion
        for ($j = 16; $j < 68; $j++) {
            $wj3 = $this->X[$j - 3];
            $r15 = Integers::rotateLeft($wj3, 15);
            
            $wj13 = $this->X[$j - 13];
            $r7 = Integers::rotateLeft($wj13, 7);
            
            $xor = $this->X[$j - 16] ^ $this->X[$j - 9] ^ $r15;
            $p1 = $this->P1($xor);
            
            $this->X[$j] = $p1 ^ $r7 ^ $this->X[$j - 6];
        }

        $A = $this->V[0];
        $B = $this->V[1];
        $C = $this->V[2];
        $D = $this->V[3];
        $E = $this->V[4];
        $F = $this->V[5];
        $G = $this->V[6];
        $H = $this->V[7];

        // Rounds 0-15
        for ($j = 0; $j < 16; $j++) {
            $a12 = Integers::rotateLeft($A, 12);
            $s1 = ($a12 + $E + self::$T[$j]) & 0xFFFFFFFF;
            $SS1 = Integers::rotateLeft($s1, 7);
            $SS2 = $SS1 ^ $a12;
            
            $Wj = $this->X[$j];
            $W1j = $Wj ^ $this->X[$j + 4];
            
            $ff0 = $this->FF0($A, $B, $C);
            $gg0 = $this->GG0($E, $F, $G);
            
            $TT1 = ($ff0 + $D + $SS2 + $W1j) & 0xFFFFFFFF;
            $TT2 = ($gg0 + $H + $SS1 + $Wj) & 0xFFFFFFFF;
            
            $D = $C;
            $C = Integers::rotateLeft($B, 9);
            $B = $A;
            $A = $TT1;
            $H = $G;
            $G = Integers::rotateLeft($F, 19);
            $F = $E;
            $E = $this->P0($TT2);
        }

        // Rounds 16-63
        for ($j = 16; $j < 64; $j++) {
            $a12 = Integers::rotateLeft($A, 12);
            $s1 = ($a12 + $E + self::$T[$j]) & 0xFFFFFFFF;
            $SS1 = Integers::rotateLeft($s1, 7);
            $SS2 = $SS1 ^ $a12;
            
            $Wj = $this->X[$j];
            $W1j = $Wj ^ $this->X[$j + 4];
            
            $ff1 = $this->FF1($A, $B, $C);
            $gg1 = $this->GG1($E, $F, $G);
            
            $TT1 = ($ff1 + $D + $SS2 + $W1j) & 0xFFFFFFFF;
            $TT2 = ($gg1 + $H + $SS1 + $Wj) & 0xFFFFFFFF;
            
            $D = $C;
            $C = Integers::rotateLeft($B, 9);
            $B = $A;
            $A = $TT1;
            $H = $G;
            $G = Integers::rotateLeft($F, 19);
            $F = $E;
            $E = $this->P0($TT2);
        }

        $this->V[0] ^= $A;
        $this->V[1] ^= $B;
        $this->V[2] ^= $C;
        $this->V[3] ^= $D;
        $this->V[4] ^= $E;
        $this->V[5] ^= $F;
        $this->V[6] ^= $G;
        $this->V[7] ^= $H;

        $this->xOff = 0;
    }

    private function FF0(int $x, int $y, int $z): int
    {
        return $x ^ $y ^ $z;
    }

    private function FF1(int $x, int $y, int $z): int
    {
        return ($x & $y) | ($x & $z) | ($y & $z);
    }

    private function GG0(int $x, int $y, int $z): int
    {
        return $x ^ $y ^ $z;
    }

    private function GG1(int $x, int $y, int $z): int
    {
        // (~$x) needs mask in PHP
        return ($x & $y) | ((~$x & 0xFFFFFFFF) & $z);
    }

    private function P0(int $x): int
    {
        return $x ^ Integers::rotateLeft($x, 9) ^ Integers::rotateLeft($x, 17);
    }

    private function P1(int $x): int
    {
        return $x ^ Integers::rotateLeft($x, 15) ^ Integers::rotateLeft($x, 23);
    }

    public function copy(): Memoable
    {
        return new SM3Digest($this);
    }

    private function copyInSM3(SM3Digest $digest): void
    {
        $this->V = $digest->V;
        $this->X = $digest->X;
        $this->xOff = $digest->xOff;
    }
}
