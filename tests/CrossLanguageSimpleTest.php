<?php

namespace SmBc\Tests;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Digests\SM3Digest;

/**
 * 简化的跨语言兼容性测试
 * 首先测试 SM3 标准测试向量
 */
class CrossLanguageSimpleTest extends TestCase
{
    /**
     * 测试 SM3 标准测试向量
     */
    public function testSM3StandardVectors(): void
    {
        $vectors = [
            ['input' => '', 'expected' => '1ab21d8355cfa17f8e61194831e81a8f22bec8c728fefb747ed035eb5082aa2b'],
            ['input' => 'a', 'expected' => '623476ac18f65a2909e43c7fec61b49c7e764a91a18ccb82f1917a29c86c5e88'],
            ['input' => 'abc', 'expected' => '66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0']
        ];
        
        foreach ($vectors as $i => $tv) {
            $vectorNum = $i + 1;
            echo "\nTest vector $vectorNum: \"{$tv['input']}\"\n";
            
            // 测试 PHP
            $phpResult = $this->computePhpSM3($tv['input']);
            $this->assertEquals($tv['expected'], $phpResult, 
                "PHP SM3 failed for test vector $vectorNum");
            
            echo "  ✓ PHP matches expected: {$tv['expected']}\n";
        }
        
        echo "✓ All standard test vectors passed\n";
    }
    
    private function computePhpSM3(string $input): string
    {
        $digest = new SM3Digest();
        $digest->updateBytes($input, 0, strlen($input));
        
        $result = str_repeat("\0", $digest->getDigestSize());
        $digest->doFinal($result, 0);
        
        return bin2hex($result);
    }
}
