<?php

declare(strict_types=1);

namespace SmBc\Crypto\Engines;

use SmBc\Crypto\StreamCipher;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

/**
 * ZUC-128 Stream Cipher Engine
 * 
 * ZUC is a stream cipher algorithm designed for use in 3GPP confidentiality 
 * and integrity algorithms 128-EEA3 and 128-EIA3.
 * 
 * Standards: GM/T 0001-2012, 3GPP TS 35.221
 * Reference: org.bouncycastle.crypto.engines.ZucEngine
 */
class ZUCEngine implements StreamCipher
{
    // S-Box S0
    private static array $S0 = [
        0x3e, 0x72, 0x5b, 0x47, 0xca, 0xe0, 0x00, 0x33, 0x04, 0xd1, 0x54, 0x98, 0x09, 0xb9, 0x6d, 0xcb,
        0x7b, 0x1b, 0xf9, 0x32, 0xaf, 0x9d, 0x6a, 0xa5, 0xb8, 0x2d, 0xfc, 0x1d, 0x08, 0x53, 0x03, 0x90,
        0x4d, 0x4e, 0x84, 0x99, 0xe4, 0xce, 0xd9, 0x91, 0xdd, 0xb6, 0x85, 0x48, 0x8b, 0x29, 0x6e, 0xac,
        0xcd, 0xc1, 0xf8, 0x1e, 0x73, 0x43, 0x69, 0xc6, 0xb5, 0xbd, 0xfd, 0x39, 0x63, 0x20, 0xd4, 0x38,
        0x76, 0x7d, 0xb2, 0xa7, 0xcf, 0xed, 0x57, 0xc5, 0xf3, 0x2c, 0xbb, 0x14, 0x21, 0x06, 0x55, 0x9b,
        0xe3, 0xef, 0x5e, 0x31, 0x4f, 0x7f, 0x5a, 0xa4, 0x0d, 0x82, 0x51, 0x49, 0x5f, 0xba, 0x58, 0x1c,
        0x4a, 0x16, 0xd5, 0x17, 0xa8, 0x92, 0x24, 0x1f, 0x8c, 0xff, 0xd8, 0xae, 0x2e, 0x01, 0xd3, 0xad,
        0x3b, 0x4b, 0xda, 0x46, 0xeb, 0xc9, 0xde, 0x9a, 0x8f, 0x87, 0xd7, 0x3a, 0x80, 0x6f, 0x2f, 0xc8,
        0xb1, 0xb4, 0x37, 0xf7, 0x0a, 0x22, 0x13, 0x28, 0x7c, 0xcc, 0x3c, 0x89, 0xc7, 0xc3, 0x96, 0x56,
        0x07, 0xbf, 0x7e, 0xf0, 0x0b, 0x2b, 0x97, 0x52, 0x35, 0x41, 0x79, 0x61, 0xa6, 0x4c, 0x10, 0xfe,
        0xbc, 0x26, 0x95, 0x88, 0x8a, 0xb0, 0xa3, 0xfb, 0xc0, 0x18, 0x94, 0xf2, 0xe1, 0xe5, 0xe9, 0x5d,
        0xd0, 0xdc, 0x11, 0x66, 0x64, 0x5c, 0xec, 0x59, 0x42, 0x75, 0x12, 0xf5, 0x74, 0x9c, 0xaa, 0x23,
        0x0e, 0x86, 0xab, 0xbe, 0x2a, 0x02, 0xe7, 0x67, 0xe6, 0x44, 0xa2, 0x6c, 0xc2, 0x93, 0x9f, 0xf1,
        0xf6, 0xfa, 0x36, 0xd2, 0x50, 0x68, 0x9e, 0x62, 0x71, 0x15, 0x3d, 0xd6, 0x40, 0xc4, 0xe2, 0x0f,
        0x8e, 0x83, 0x77, 0x6b, 0x25, 0x05, 0x3f, 0x0c, 0x30, 0xea, 0x70, 0xb7, 0xa1, 0xe8, 0xa9, 0x65,
        0x8d, 0x27, 0x1a, 0xdb, 0x81, 0xb3, 0xa0, 0xf4, 0x45, 0x7a, 0x19, 0xdf, 0xee, 0x78, 0x34, 0x60
    ];

    // S-Box S1
    private static array $S1 = [
        0x55, 0xc2, 0x63, 0x71, 0x3b, 0xc8, 0x47, 0x86, 0x9f, 0x3c, 0xda, 0x5b, 0x29, 0xaa, 0xfd, 0x77,
        0x8c, 0xc5, 0x94, 0x0c, 0xa6, 0x1a, 0x13, 0x00, 0xe3, 0xa8, 0x16, 0x72, 0x40, 0xf9, 0xf8, 0x42,
        0x44, 0x26, 0x68, 0x96, 0x81, 0xd9, 0x45, 0x3e, 0x10, 0x76, 0xc6, 0xa7, 0x8b, 0x39, 0x43, 0xe1,
        0x3a, 0xb5, 0x56, 0x2a, 0xc0, 0x6d, 0xb3, 0x05, 0x22, 0x66, 0xbf, 0xdc, 0x0b, 0xfa, 0x62, 0x48,
        0xdd, 0x20, 0x11, 0x06, 0x36, 0xc9, 0xc1, 0xcf, 0xf6, 0x27, 0x52, 0xbb, 0x69, 0xf5, 0xd4, 0x87,
        0x7f, 0x84, 0x4c, 0xd2, 0x9c, 0x57, 0xa4, 0xbc, 0x4f, 0x9a, 0xdf, 0xfe, 0xd6, 0x8d, 0x7a, 0xeb,
        0x2b, 0x53, 0xd8, 0x5c, 0xa1, 0x14, 0x17, 0xfb, 0x23, 0xd5, 0x7d, 0x30, 0x67, 0x73, 0x08, 0x09,
        0xee, 0xb7, 0x70, 0x3f, 0x61, 0xb2, 0x19, 0x8e, 0x4e, 0xe5, 0x4b, 0x93, 0x8f, 0x5d, 0xdb, 0xa9,
        0xad, 0xf1, 0xae, 0x2e, 0xcb, 0x0d, 0xfc, 0xf4, 0x2d, 0x46, 0x6e, 0x1d, 0x97, 0xe8, 0xd1, 0xe9,
        0x4d, 0x37, 0xa5, 0x75, 0x5e, 0x83, 0x9e, 0xab, 0x82, 0x9d, 0xb9, 0x1c, 0xe0, 0xcd, 0x49, 0x89,
        0x01, 0xb6, 0xbd, 0x58, 0x24, 0xa2, 0x5f, 0x38, 0x78, 0x99, 0x15, 0x90, 0x50, 0xb8, 0x95, 0xe4,
        0xd0, 0x91, 0xc7, 0xce, 0xed, 0x0f, 0xb4, 0x6f, 0xa0, 0xcc, 0xf0, 0x02, 0x4a, 0x79, 0xc3, 0xde,
        0xa3, 0xef, 0xea, 0x51, 0xe6, 0x6b, 0x18, 0xec, 0x1b, 0x2c, 0x80, 0xf7, 0x74, 0xe7, 0xff, 0x21,
        0x5a, 0x6a, 0x54, 0x1e, 0x41, 0x31, 0x92, 0x35, 0xc4, 0x33, 0x07, 0x0a, 0xba, 0x7e, 0x0e, 0x34,
        0x88, 0xb1, 0x98, 0x7c, 0xf3, 0x3d, 0x60, 0x6c, 0x7b, 0xca, 0xd3, 0x1f, 0x32, 0x65, 0x04, 0x28,
        0x64, 0xbe, 0x85, 0x9b, 0x2f, 0x59, 0x8a, 0xd7, 0xb0, 0x25, 0xac, 0xaf, 0x12, 0x03, 0xe2, 0xf2
    ];

    // LFSR - 16 cells of 31 bits each
    private array $LFSR = [];

    // Registers R1 and R2
    private int $R1 = 0;
    private int $R2 = 0;

    // Key stream buffer
    private array $keyStream = [];
    private int $keyStreamIndex = 0;

    // Initialization parameters
    private bool $initialized = false;
    private ?string $workingKey = null;
    private ?string $workingIV = null;

    public function __construct()
    {
        $this->LFSR = array_fill(0, 16, 0);
        $this->keyStream = [0, 0];
    }

    /**
     * Initialize the cipher.
     * 
     * @param bool $forEncryption Ignored (stream ciphers are symmetric)
     * @param CipherParameters $params Must be ParametersWithIV containing a KeyParameter
     */
    public function init(bool $forEncryption, CipherParameters $params): void
    {
        if (!($params instanceof ParametersWithIV)) {
            throw new \InvalidArgumentException('ZUC init parameters must include an IV (use ParametersWithIV)');
        }

        $iv = $params->getIV();
        $keyParam = $params->getParameters();

        if (!($keyParam instanceof KeyParameter)) {
            throw new \InvalidArgumentException('ZUC init parameters must include a KeyParameter');
        }

        $key = $keyParam->getKey();

        if (strlen($key) !== 16) {
            throw new \InvalidArgumentException('ZUC requires a 128-bit key');
        }

        if (strlen($iv) !== 16) {
            throw new \InvalidArgumentException('ZUC requires a 128-bit IV');
        }

        $this->workingKey = $key;
        $this->workingIV = $iv;

        $this->setKeyAndIV($this->workingKey, $this->workingIV);
        $this->initialized = true;
    }

    /**
     * Return the algorithm name.
     */
    public function getAlgorithmName(): string
    {
        return 'ZUC-128';
    }

    /**
     * Encrypt/decrypt a single byte.
     * 
     * @param int $input The byte to process
     * @return int The processed byte
     */
    public function returnByte(int $input): int
    {
        if (!$this->initialized) {
            throw new \RuntimeException('ZUC not initialized');
        }

        if ($this->keyStreamIndex === 0) {
            $this->generateKeyStream();
        }

        return ($input ^ $this->getKeyStreamByte()) & 0xFF;
    }

    /**
     * Process a block of bytes.
     * 
     * @param string $input The input bytes
     * @param int $inOff The offset in input to start
     * @param int $length The length of data to process
     * @param string &$output The output buffer
     * @param int $outOff The offset in output to start
     * @return int The number of bytes processed
     */
    public function processBytes(
        string $input,
        int $inOff,
        int $length,
        string &$output,
        int $outOff
    ): int {
        if (!$this->initialized) {
            throw new \RuntimeException('ZUC not initialized');
        }

        if ($inOff + $length > strlen($input)) {
            throw new \LengthException('Input buffer too short');
        }

        if ($outOff + $length > strlen($output)) {
            throw new \LengthException('Output buffer too short');
        }

        for ($i = 0; $i < $length; $i++) {
            if ($this->keyStreamIndex === 0) {
                $this->generateKeyStream();
            }

            $output[$outOff + $i] = chr((ord($input[$inOff + $i]) ^ $this->getKeyStreamByte()) & 0xFF);
        }

        return $length;
    }

    /**
     * Reset the cipher.
     */
    public function reset(): void
    {
        if ($this->workingKey !== null && $this->workingIV !== null) {
            $this->setKeyAndIV($this->workingKey, $this->workingIV);
        }
        $this->initialized = ($this->workingKey !== null);
    }

    /**
     * Set key and IV, initialize LFSR and discard first 32 words.
     */
    private function setKeyAndIV(string $key, string $iv): void
    {
        // Constants for key loading
        $d = [
            0x44, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
            0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x3f
        ];

        // Load key and IV into LFSR
        for ($i = 0; $i < 16; $i++) {
            $this->LFSR[$i] = $this->makeU31(
                (ord($key[$i]) << 23) | ($d[$i] << 8) | ord($iv[$i])
            );
        }

        // Run initialization mode (32 iterations)
        for ($i = 0; $i < 32; $i++) {
            $W = $this->F();
            $this->LFSRWithInitMode($W >> 1);
        }

        // Reset key stream
        $this->keyStreamIndex = 0;
    }

    /**
     * Make a 31-bit unsigned value.
     */
    private function makeU31(int $value): int
    {
        return $value & 0x7FFFFFFF;
    }

    /**
     * LFSR with initialization mode.
     */
    private function LFSRWithInitMode(int $u): void
    {
        $s16 = $this->LFSR[0];
        $s16 = $this->mulByPow2($s16, 8);
        $s16 = $this->add31($s16, $this->LFSR[4]);
        $s16 = $this->mulByPow2($s16, 20);
        $s16 = $this->add31($s16, $this->LFSR[10]);
        $s16 = $this->mulByPow2($s16, 21);
        $s16 = $this->add31($s16, $this->LFSR[13]);
        $s16 = $this->mulByPow2($s16, 17);
        $s16 = $this->add31($s16, $this->LFSR[15]);
        $s16 = $this->add31($s16, $u);

        // Shift register
        for ($i = 0; $i < 15; $i++) {
            $this->LFSR[$i] = $this->LFSR[$i + 1];
        }
        $this->LFSR[15] = $this->makeU31($s16);
    }

    /**
     * LFSR with work mode.
     */
    private function LFSRWithWorkMode(): void
    {
        $s16 = $this->LFSR[0];
        $s16 = $this->mulByPow2($s16, 8);
        $s16 = $this->add31($s16, $this->LFSR[4]);
        $s16 = $this->mulByPow2($s16, 20);
        $s16 = $this->add31($s16, $this->LFSR[10]);
        $s16 = $this->mulByPow2($s16, 21);
        $s16 = $this->add31($s16, $this->LFSR[13]);
        $s16 = $this->mulByPow2($s16, 17);
        $s16 = $this->add31($s16, $this->LFSR[15]);

        // Shift register
        for ($i = 0; $i < 15; $i++) {
            $this->LFSR[$i] = $this->LFSR[$i + 1];
        }
        $this->LFSR[15] = $this->makeU31($s16);
    }

    /**
     * Addition modulo 2^31-1.
     */
    private function add31(int $a, int $b): int
    {
        $c = $a + $b;
        return $this->makeU31($c) + ($c >> 31);
    }

    /**
     * Multiplication by 2^k modulo 2^31-1.
     */
    private function mulByPow2(int $x, int $k): int
    {
        return $this->makeU31(($x << $k) | ($x >> (31 - $k)));
    }

    /**
     * Bit reorganization.
     * 
     * @return array [X0, X1, X2, X3]
     */
    private function BitReorganization(): array
    {
        $X0 = (($this->LFSR[15] & 0x7FFF8000) << 1) | ($this->LFSR[14] & 0xFFFF);
        $X1 = (($this->LFSR[11] & 0xFFFF) << 16) | ($this->LFSR[9] >> 15);
        $X2 = (($this->LFSR[7] & 0xFFFF) << 16) | ($this->LFSR[5] >> 15);
        $X3 = (($this->LFSR[2] & 0xFFFF) << 16) | ($this->LFSR[0] >> 15);

        return [
            $X0 & 0xFFFFFFFF,
            $X1 & 0xFFFFFFFF,
            $X2 & 0xFFFFFFFF,
            $X3 & 0xFFFFFFFF
        ];
    }

    /**
     * S-box substitution.
     */
    private function S(int $x): int
    {
        return (self::$S0[($x >> 24) & 0xFF] << 24) |
               (self::$S1[($x >> 16) & 0xFF] << 16) |
               (self::$S0[($x >> 8) & 0xFF] << 8) |
               (self::$S1[$x & 0xFF]);
    }

    /**
     * Linear transformation L1.
     */
    private function L1(int $X): int
    {
        return ($X ^ $this->ROT($X, 2) ^ $this->ROT($X, 10) ^
                $this->ROT($X, 18) ^ $this->ROT($X, 24)) & 0xFFFFFFFF;
    }

    /**
     * Linear transformation L2.
     */
    private function L2(int $X): int
    {
        return ($X ^ $this->ROT($X, 8) ^ $this->ROT($X, 14) ^
                $this->ROT($X, 22) ^ $this->ROT($X, 30)) & 0xFFFFFFFF;
    }

    /**
     * Rotate left.
     */
    private function ROT(int $x, int $n): int
    {
        $x = $x & 0xFFFFFFFF;
        return ((($x << $n) | ($x >> (32 - $n))) & 0xFFFFFFFF);
    }

    /**
     * Non-linear function F.
     * 
     * @return int The output word W
     */
    private function F(): int
    {
        [$X0, $X1, $X2, $X3] = $this->BitReorganization();

        $W = ($X0 ^ $this->R1) & 0xFFFFFFFF;
        $W1 = ($this->R1 + $X1) & 0xFFFFFFFF;
        $W2 = ($this->R2 ^ $X2) & 0xFFFFFFFF;

        $u = $this->L1((($W1 << 16) | ($W2 >> 16)) & 0xFFFFFFFF);
        $v = $this->L2((($W2 << 16) | ($W1 >> 16)) & 0xFFFFFFFF);

        $this->R1 = $this->S($this->L1((($u << 16) | ($v >> 16)) & 0xFFFFFFFF));
        $this->R2 = $this->S($this->L2((($v << 16) | ($u >> 16)) & 0xFFFFFFFF));

        return $W;
    }

    /**
     * Generate key stream.
     */
    private function generateKeyStream(): void
    {
        $this->LFSRWithWorkMode();
        $W = $this->F();
        $this->keyStream[0] = $W ^ $this->BitReorganization()[3];

        $this->LFSRWithWorkMode();
        $W = $this->F();
        $this->keyStream[1] = $W ^ $this->BitReorganization()[3];

        $this->keyStreamIndex = 0;
    }

    /**
     * Get next key stream byte.
     */
    private function getKeyStreamByte(): int
    {
        $wordIndex = $this->keyStreamIndex >> 2; // div 4
        $byteIndex = 3 - ($this->keyStreamIndex & 3); // mod 4
        $this->keyStreamIndex = ($this->keyStreamIndex + 1) & 7; // mod 8

        return ($this->keyStream[$wordIndex] >> ($byteIndex * 8)) & 0xFF;
    }
}
