<?php

namespace SmBc\Crypto\Digests;

use SmBc\Crypto\ExtendedDigest;
use SmBc\Crypto\Memoable;

abstract class GeneralDigest implements ExtendedDigest, Memoable
{
    private const BYTE_LENGTH = 64;

    /** @var string 4 bytes buffer */
    protected string $xBuf;
    protected int $xBufOff;
    protected int $byteCount;

    protected function __construct(?GeneralDigest $digest = null)
    {
        if ($digest) {
            $this->xBuf = $digest->xBuf;
            $this->xBufOff = $digest->xBufOff;
            $this->byteCount = $digest->byteCount;
        } else {
            $this->xBuf = str_repeat("\x00", 4);
            $this->xBufOff = 0;
            $this->byteCount = 0;
        }
    }

    public function update(int $in): void
    {
        $this->xBuf[$this->xBufOff++] = chr($in & 0xff);

        if ($this->xBufOff === 4) {
            $this->processWord($this->xBuf, 0);
            $this->xBufOff = 0;
        }

        $this->byteCount++;
    }

    public function updateBytes(string $input, int $inOff, int $len): void
    {
        $len = max(0, $len);
        $i = 0;

        // First fill internal buffer
        if ($this->xBufOff !== 0) {
            while ($i < $len) {
                $this->xBuf[$this->xBufOff++] = $input[$inOff + $i++];
                if ($this->xBufOff === 4) {
                    $this->processWord($this->xBuf, 0);
                    $this->xBufOff = 0;
                    break;
                }
            }
        }

        // Process complete words
        $limit = (($len - $i) & ~3) + $i;
        for (; $i < $limit; $i += 4) {
            $this->processWord($input, $inOff + $i);
        }

        // Buffer remaining
        while ($i < $len) {
            $this->xBuf[$this->xBufOff++] = $input[$inOff + $i++];
        }

        $this->byteCount += $len;
    }

    public function finish(): void
    {
        $bitLength = $this->byteCount << 3;

        // Add 0x80
        $this->update(0x80);

        // Pad with zeros until xBufOff is 0
        while ($this->xBufOff !== 0) {
            $this->update(0);
        }
        
        $this->processLength($bitLength);
        $this->processBlock();
    }

    public function reset(?Memoable $other = null): void
    {
        if ($other !== null) {
            if ($other instanceof GeneralDigest) {
                $this->copyIn($other);
            } else {
                // Should not happen if type hint works, but Memoable could be anything
                $this->copyIn($other); // This might fail if copyIn expects GeneralDigest
            }
        } else {
            $this->byteCount = 0;
            $this->xBufOff = 0;
            $this->xBuf = str_repeat("\x00", 4);
            $this->resetState();
        }
    }
    
    public function getByteLength(): int
    {
        return self::BYTE_LENGTH;
    }

    protected function copyIn(GeneralDigest $digest): void
    {
        $this->xBuf = $digest->xBuf;
        $this->xBufOff = $digest->xBufOff;
        $this->byteCount = $digest->byteCount;
    }

    abstract protected function processWord(string $input, int $offset): void;
    abstract protected function processLength(int $bitLength): void;
    abstract protected function processBlock(): void;
    abstract protected function resetState(): void;
}