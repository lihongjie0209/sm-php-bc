<?php

declare(strict_types=1);

namespace SmBc\Tests\CrossLanguage;

use SmBc\Crypto\Digests\SM3Digest;

/**
 * SM3 Digest cross-language interoperability tests
 * Tests PHP SM3 implementation against JavaScript sm-js-bc
 */
class SM3DigestInteropTest extends BaseInteropTest
{
    /**
     * @dataProvider sm3TestVectorsProvider
     */
    public function testSM3CrossImplementation(string $message, string $expectedHash): void
    {
        if (!$this->isNodeJsAvailable()) {
            $this->markTestSkipped('Node.js is not available');
        }
        
        echo "\nTesting message: \"{$message}\"\n";
        
        // Compute hash with PHP
        $phpHash = $this->computePhpSM3($message);
        
        // Compute hash with JavaScript
        $jsHash = $this->computeJavaScriptSM3($message);
        
        // Verify against expected
        $this->assertEquals($expectedHash, $phpHash, "PHP SM3 doesn't match expected");
        $this->assertEquals($expectedHash, $jsHash, "JavaScript SM3 doesn't match expected");
        
        // Verify PHP and JS match each other
        $this->assertEquals($phpHash, $jsHash, "PHP and JavaScript SM3 results don't match");
        
        echo "  ✓ Both implementations agree: {$phpHash}\n";
    }
    
    public function sm3TestVectorsProvider(): array
    {
        return [
            'empty string' => [
                '',
                '1ab21d8355cfa17f8e61194831e81a8f22bec8c728fefb747ed035eb5082aa2b'
            ],
            'single char' => [
                'a',
                '623476ac18f65a2909e43c7fec61b49c7e764a91a18ccb82f1917a29c86c5e88'
            ],
            'abc' => [
                'abc',
                '66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0'
            ],
        ];
    }
    
    /**
     * Compute SM3 hash using PHP implementation
     */
    private function computePhpSM3(string $message): string
    {
        $digest = new SM3Digest();
        $bytes = $message;
        $digest->updateBytes($bytes, 0, strlen($bytes));
        
        $result = str_repeat("\0", $digest->getDigestSize());
        $digest->doFinal($result, 0);
        
        return $this->bytesToHex($result);
    }
    
    /**
     * Compute SM3 hash using JavaScript implementation via Node.js
     */
    private function computeJavaScriptSM3(string $message): string
    {
        $escapedMessage = addcslashes($message, "\\\"\n\r\t");
        
        $script = $this->createJsScript(<<<JS
const message = new TextEncoder().encode("$escapedMessage");
const digest = new smBc.SM3Digest();
digest.updateArray(message, 0, message.length);

const result = new Uint8Array(digest.getDigestSize());
digest.doFinal(result, 0);

const hash = Array.from(result)
    .map(b => b.toString(16).padStart(2, '0'))
    .join('');

console.log(JSON.stringify({ hash: hash }));
JS
        );
        
        $result = $this->executeNodeJs($script);
        return $result['hash'];
    }
    
    /**
     * Test with random data
     */
    public function testRandomData(): void
    {
        if (!$this->isNodeJsAvailable()) {
            $this->markTestSkipped('Node.js is not available');
        }
        
        echo "\n=== Testing SM3 with random data ===\n";
        
        for ($i = 0; $i < 10; $i++) {
            $length = random_int(1, 1000); // At least 1 byte
            $randomData = random_bytes($length);
            
            $phpHash = $this->computePhpSM3($randomData);
            
            // For binary data, send as hex to JavaScript
            $hexData = $this->bytesToHex($randomData);
            $script = $this->createJsScript(<<<JS
const hexToBytes = (hex) => {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substr(i, 2), 16);
    }
    return bytes;
};

const message = hexToBytes("$hexData");
const digest = new smBc.SM3Digest();
digest.updateArray(message, 0, message.length);

const result = new Uint8Array(digest.getDigestSize());
digest.doFinal(result, 0);

const hash = Array.from(result)
    .map(b => b.toString(16).padStart(2, '0'))
    .join('');

console.log(JSON.stringify({ hash: hash }));
JS
            );
            
            $jsHash = $this->executeNodeJs($script)['hash'];
            
            $this->assertEquals($phpHash, $jsHash, "PHP and JS don't match for random data iteration {$i}");
            echo "  ✓ Test {$i}: length={$length} bytes\n";
        }
        
        echo "✓ All random data tests passed\n";
    }
}
