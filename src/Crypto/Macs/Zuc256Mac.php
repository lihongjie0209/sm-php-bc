<?php

namespace SmBc\Crypto\Macs;

use SmBc\Crypto\CipherParameters;
use SmBc\Crypto\Engines\Zuc256Engine;
use SmBc\Crypto\Mac;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

/**
 * ZUC-256 MAC implementation (3GPP TS 35.221)
 * 
 * Supports configurable MAC tag sizes:
 * - 32-bit (4 bytes)
 * - 64-bit (8 bytes)  
 * - 128-bit (16 bytes) - default
 */
class Zuc256Mac implements Mac
{
    private Zuc256Engine $engine;
    private int $macSizeBits;
    private int $wordIndex = 0;
    private int $word = 0;
    
    /**
     * @param int $macSizeBits MAC size in bits (32, 64, or 128)
     */
    public function __construct(int $macSizeBits = 128)
    {
        if (!in_array($macSizeBits, [32, 64, 128])) {
            throw new \InvalidArgumentException('ZUC-256 MAC size must be 32, 64, or 128 bits');
        }
        
        $this->engine = new Zuc256Engine();
        $this->macSizeBits = $macSizeBits;
    }
    
    public function getAlgorithmName(): string
    {
        return 'ZUC-256-MAC-' . $this->macSizeBits;
    }
    
    public function getMacSize(): int
    {
        return $this->macSizeBits / 8;
    }
    
    public function init(CipherParameters $params): void
    {
        if (!($params instanceof ParametersWithIV)) {
            throw new \InvalidArgumentException('ZUC-256-MAC requires ParametersWithIV');
        }
        
        $this->engine->init(true, $params);
        $this->wordIndex = 0;
        $this->word = 0;
    }
    
    public function update(string $in): void
    {
        $byte = ord($in[0]);
        $this->word = ($this->word << 8) | ($byte & 0xFF);
        $this->wordIndex++;
        
        if ($this->wordIndex == 4) {
            $this->processWord();
        }
    }
    
    public function updateBytes(string $in, int $inOff, int $len): void
    {
        for ($i = 0; $i < $len; $i++) {
            $this->update($in[$inOff + $i]);
        }
    }
    
    public function doFinal(string &$out, int $outOff): int
    {
        // Pad remaining bytes if necessary
        if ($this->wordIndex > 0) {
            while ($this->wordIndex < 4) {
                $this->word = $this->word << 8;
                $this->wordIndex++;
            }
            $this->processWord();
        }
        
        // Generate MAC tag based on configured size
        $macBytes = $this->getMacSize();
        $keystream = str_repeat("\x00", $macBytes);
        $this->engine->processBytes($keystream, 0, $macBytes, $keystream, 0);
        
        // Copy to output
        for ($i = 0; $i < $macBytes; $i++) {
            $out[$outOff + $i] = $keystream[$i];
        }
        
        $this->reset();
        return $macBytes;
    }
    
    public function reset(): void
    {
        $this->engine->reset();
        $this->wordIndex = 0;
        $this->word = 0;
    }
    
    private function processWord(): void
    {
        // Process 32-bit word with keystream
        $keystream = str_repeat("\x00", 4);
        $this->engine->processBytes($keystream, 0, 4, $keystream, 0);
        
        // XOR word with keystream
        for ($i = 0; $i < 4; $i++) {
            $byte = ($this->word >> (24 - 8 * $i)) & 0xFF;
            $keyByte = ord($keystream[$i]);
            $keystream[$i] = chr(($byte ^ $keyByte) & 0xFF);
        }
        
        $this->word = 0;
        $this->wordIndex = 0;
    }
}
