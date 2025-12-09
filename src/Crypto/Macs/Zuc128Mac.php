<?php

declare(strict_types=1);

namespace SmBc\Crypto\Macs;

use SmBc\Crypto\Mac;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ParametersWithIV;
use SmBc\Crypto\Engines\ZUCEngine;
use SmBc\Util\Pack;

/**
 * ZUC-128 MAC (128-EIA3)
 * 
 * Implementation of the 3GPP Integrity Algorithm 128-EIA3 based on ZUC-128.
 * This MAC produces a 32-bit tag used for integrity protection in LTE/5G.
 * 
 * Standards: 3GPP TS 35.221
 * Reference: org.bouncycastle.crypto.macs.Zuc128Mac
 */
class Zuc128Mac implements Mac
{
    private ZUCEngine $zuc;
    private string $buf;
    private int $bufOff;
    private int $mac;
    private int $wordCount;

    public function __construct()
    {
        $this->zuc = new ZUCEngine();
        $this->buf = str_repeat("\x00", 4);
        $this->bufOff = 0;
        $this->mac = 0;
        $this->wordCount = 0;
    }

    /**
     * Initialize the MAC.
     * 
     * @param CipherParameters $params Must be ParametersWithIV containing a KeyParameter
     */
    public function init(CipherParameters $params): void
    {
        if (!($params instanceof ParametersWithIV)) {
            throw new \InvalidArgumentException('Zuc128Mac requires ParametersWithIV');
        }

        $this->zuc->init(true, $params);
        $this->bufOff = 0;
        $this->mac = 0;
        $this->wordCount = 0;
    }

    /**
     * Return the algorithm name.
     */
    public function getAlgorithmName(): string
    {
        return 'ZUC-128-MAC';
    }

    /**
     * Return the MAC size (4 bytes = 32 bits).
     */
    public function getMacSize(): int
    {
        return 4;
    }

    /**
     * Add a single byte to the MAC calculation.
     * 
     * @param int $input The byte to add
     */
    public function update(int $input): void
    {
        $this->buf[$this->bufOff++] = chr($input & 0xFF);

        if ($this->bufOff === 4) {
            $this->processWord();
        }
    }

    /**
     * Add multiple bytes to the MAC calculation.
     * 
     * @param string $input The bytes to add
     * @param int $inOff The offset into the input
     * @param int $length The length of data to add
     */
    public function updateBytes(string $input, int $inOff, int $length): void
    {
        $remaining = $length;
        $offset = $inOff;

        // Fill buffer first
        if ($this->bufOff > 0) {
            while ($remaining > 0 && $this->bufOff < 4) {
                $this->buf[$this->bufOff++] = $input[$offset++];
                $remaining--;
            }

            if ($this->bufOff === 4) {
                $this->processWord();
            }
        }

        // Process complete words
        while ($remaining >= 4) {
            $this->buf[0] = $input[$offset++];
            $this->buf[1] = $input[$offset++];
            $this->buf[2] = $input[$offset++];
            $this->buf[3] = $input[$offset++];
            $remaining -= 4;
            $this->processWord();
        }

        // Store remaining bytes
        while ($remaining > 0) {
            $this->buf[$this->bufOff++] = $input[$offset++];
            $remaining--;
        }
    }

    /**
     * Complete the MAC calculation and write the result to the output array.
     * 
     * @param string &$output The output buffer
     * @param int $outOff The offset into the output buffer
     * @return int The number of bytes written (4)
     */
    public function doFinal(string &$output, int $outOff): int
    {
        // Process any remaining bytes
        if ($this->bufOff > 0) {
            // Pad with zeros
            while ($this->bufOff < 4) {
                $this->buf[$this->bufOff++] = "\x00";
            }
            $this->processWord();
        }

        // Final MAC value
        Pack::intToBigEndian($this->mac, $output, $outOff);

        $this->reset();

        return 4;
    }

    /**
     * Reset the MAC to its initial state.
     */
    public function reset(): void
    {
        $this->zuc->reset();
        $this->bufOff = 0;
        $this->mac = 0;
        $this->wordCount = 0;
        $this->buf = str_repeat("\x00", 4);
    }

    /**
     * Process a complete 32-bit word.
     */
    private function processWord(): void
    {
        $w = Pack::bigEndianToInt($this->buf, 0);

        // Generate keystream word
        $keyStreamWord = $this->getKeyStreamWord();

        // XOR with keystream and accumulate
        $this->mac ^= ($w ^ $keyStreamWord);

        $this->bufOff = 0;
        $this->wordCount++;
    }

    /**
     * Generate a keystream word from ZUC.
     */
    private function getKeyStreamWord(): int
    {
        $temp = str_repeat("\x00", 4);
        $input = str_repeat("\x00", 4);

        $this->zuc->processBytes($input, 0, 4, $temp, 0);

        return Pack::bigEndianToInt($temp, 0);
    }
}
