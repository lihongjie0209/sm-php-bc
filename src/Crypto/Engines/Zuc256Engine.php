<?php

namespace SmBc\Crypto\Engines;

use SmBc\Crypto\CipherParameters;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;
use SmBc\Crypto\StreamCipher;

/**
 * ZUC-256 stream cipher implementation (GM/T 0001-2012, 3GPP TS 35.221)
 * 
 * ZUC-256 is an enhanced version of ZUC-128 that supports:
 * - 256-bit keys (32 bytes) for enhanced security
 * - Flexible IV lengths: 128-bit (16 bytes) or 200-bit (25 bytes)
 * - Domain parameter (d) for key derivation
 */
class Zuc256Engine implements StreamCipher
{
    private const D_128 = 0; // For 128-bit IV
    private const D_200 = 1; // For 200-bit IV
    
    private ZUCEngine $engine;
    private ?int $d = null;
    
    public function __construct()
    {
        $this->engine = new ZUCEngine();
    }
    
    public function getAlgorithmName(): string
    {
        return 'ZUC-256';
    }
    
    public function init(bool $forEncryption, CipherParameters $params): void
    {
        if (!($params instanceof ParametersWithIV)) {
            throw new \InvalidArgumentException('ZUC-256 init parameters must include an IV (ParametersWithIV)');
        }
        
        $keyParam = $params->getParameters();
        if (!($keyParam instanceof KeyParameter)) {
            throw new \InvalidArgumentException('ZUC-256 init parameters must include a key (KeyParameter)');
        }
        
        $key = $keyParam->getKey();
        $iv = $params->getIV();
        
        if (strlen($key) !== 32) {
            throw new \InvalidArgumentException('ZUC-256 requires a 256-bit (32 byte) key');
        }
        
        $ivLen = strlen($iv);
        if ($ivLen !== 16 && $ivLen !== 25) {
            throw new \InvalidArgumentException('ZUC-256 IV must be either 128-bit (16 bytes) or 200-bit (25 bytes)');
        }
        
        // Determine d parameter based on IV length
        $this->d = ($ivLen == 16) ? self::D_128 : self::D_200;
        
        // Derive ZUC-128 key and IV from ZUC-256 key and IV
        $zucKey = $this->makeKey256($key, $iv);
        $zucIV = $this->makeIV256($key, $iv);
        
        // Initialize underlying ZUC-128 engine
        $this->engine->init(
            $forEncryption,
            new ParametersWithIV(new KeyParameter($zucKey), $zucIV)
        );
    }
    
    public function processBytes(string $in, int $inOff, int $len, string &$out, int $outOff): int
    {
        return $this->engine->processBytes($in, $inOff, $len, $out, $outOff);
    }
    
    public function returnByte(string $in): int
    {
        return $this->engine->returnByte($in);
    }
    
    public function reset(): void
    {
        $this->engine->reset();
    }
    
    /**
     * Derive 128-bit key for ZUC-128 from ZUC-256 key and IV
     */
    private function makeKey256(string $k, string $iv): string
    {
        $key = str_repeat("\x00", 16);
        $ivLen = strlen($iv);
        
        if ($ivLen == 16) {
            // 128-bit IV mode
            for ($i = 0; $i < 16; $i++) {
                $key[$i] = chr((ord($k[$i]) ^ ord($k[$i + 16])) & 0xFF);
            }
        } else {
            // 200-bit IV mode  
            for ($i = 0; $i < 16; $i++) {
                $key[$i] = chr((ord($k[$i]) ^ ord($k[$i + 16])) & 0xFF);
            }
        }
        
        return $key;
    }
    
    /**
     * Derive 128-bit IV for ZUC-128 from ZUC-256 key and IV
     */
    private function makeIV256(string $k, string $iv): string
    {
        $zucIV = str_repeat("\x00", 16);
        $ivLen = strlen($iv);
        
        if ($ivLen == 16) {
            // 128-bit IV mode: IV' = IV[0..15] || d || 0^7
            for ($i = 0; $i < 15; $i++) {
                $zucIV[$i] = $iv[$i];
            }
            $zucIV[15] = chr(($this->d << 7) & 0xFF);
        } else {
            // 200-bit IV mode: More complex derivation
            for ($i = 0; $i < 16; $i++) {
                if ($i < 9) {
                    $zucIV[$i] = $iv[$i];
                } else if ($i == 9) {
                    $zucIV[$i] = chr((ord($iv[$i]) ^ ($this->d << 6)) & 0xFF);
                } else {
                    $zucIV[$i] = chr((ord($iv[$i]) ^ ord($iv[$i + 9])) & 0xFF);
                }
            }
        }
        
        return $zucIV;
    }
}
