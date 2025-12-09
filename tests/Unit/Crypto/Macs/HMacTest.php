<?php

declare(strict_types=1);

namespace SmBc\Tests\Unit\Crypto\Macs;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Macs\HMac;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Params\KeyParameter;

/**
 * HMAC Test
 * 
 * Based on: sm-js-bc/test/unit/crypto/macs/HMac.test.ts
 */
class HMacTest extends TestCase
{
    /**
     * Helper function: compute HMAC
     */
    private function computeHMac(string $key, string $message): string
    {
        $hmac = new HMac(new SM3Digest());
        $hmac->init(new KeyParameter($key));
        $hmac->updateBytes($message, 0, strlen($message));
        $out = str_repeat("\x00", $hmac->getMacSize());
        $hmac->doFinal($out, 0);
        return $out;
    }

    public function testReturnCorrectAlgorithmName(): void
    {
        $hmac = new HMac(new SM3Digest());
        $this->assertSame('HMac/SM3', $hmac->getAlgorithmName());
    }

    public function testReturnCorrectMacSize(): void
    {
        $hmac = new HMac(new SM3Digest());
        $this->assertSame(32, $hmac->getMacSize()); // SM3 output size
    }

    public function testInitializeWithKey(): void
    {
        $hmac = new HMac(new SM3Digest());
        $key = str_repeat("\x00", 32);
        $this->expectNotToPerformAssertions();
        $hmac->init(new KeyParameter($key));
    }

    public function testThrowErrorIfInitializedWithoutKeyParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('HMac requires KeyParameter');
        
        $hmac = new HMac(new SM3Digest());
        $hmac->init($this->createMock(\SmBc\Crypto\Params\CipherParameters::class));
    }

    public function testComputeHMacForEmptyMessage(): void
    {
        $key = str_repeat("\x00", 32);
        $message = '';
        $result = $this->computeHMac($key, $message);
        $this->assertSame(32, strlen($result));
    }

    public function testComputeHMacForShortMessage(): void
    {
        $key = 'key';
        $message = 'The quick brown fox jumps over the lazy dog';
        $result = $this->computeHMac($key, $message);
        $this->assertSame(32, strlen($result));
        // Result should be deterministic
        $this->assertSame('bd4a34077888162b210645b8ebf74b9af357303789357a27c7fc457244ebd398', bin2hex($result));
    }

    public function testComputeHMacForMessageShorterThanBlockSize(): void
    {
        $key = 'key';
        $message = 'hello';
        $result = $this->computeHMac($key, $message);
        $this->assertSame(32, strlen($result));
    }

    public function testComputeHMacForMessageLongerThanBlockSize(): void
    {
        $key = 'key';
        $message = 'This is a longer message that exceeds the block size of 64 bytes for SM3...';
        $result = $this->computeHMac($key, $message);
        $this->assertSame(32, strlen($result));
    }

    public function testHandleShortKeys(): void
    {
        $key = str_repeat("\x00", 16); // 16 bytes < 64 bytes
        $message = 'test';
        $result = $this->computeHMac($key, $message);
        $this->assertSame(32, strlen($result));
    }

    public function testHandleKeysEqualToBlockSize(): void
    {
        $key = str_repeat("\x00", 64); // Exactly block size
        $message = 'test';
        $result = $this->computeHMac($key, $message);
        $this->assertSame(32, strlen($result));
    }

    public function testHandleLongKeys(): void
    {
        $key = str_repeat("\x00", 128); // 128 bytes > 64 bytes
        $message = 'test';
        $result = $this->computeHMac($key, $message);
        $this->assertSame(32, strlen($result));
    }

    public function testProduceDifferentResultsForDifferentKeyLengths(): void
    {
        $shortKey = str_repeat("\x01", 16);
        $longKey = str_repeat("\x01", 32);
        
        $message = 'test';
        $result1 = $this->computeHMac($shortKey, $message);
        $result2 = $this->computeHMac($longKey, $message);
        
        $this->assertNotSame(bin2hex($result1), bin2hex($result2));
    }

    public function testSupportMultipleUpdateCalls(): void
    {
        $key = 'key';
        $message = 'hello world';
        
        // Compute in one go
        $result1 = $this->computeHMac($key, $message);
        
        // Compute incrementally
        $hmac = new HMac(new SM3Digest());
        $hmac->init(new KeyParameter($key));
        $hmac->updateBytes($message, 0, 5); // "hello"
        $hmac->updateBytes($message, 5, 1); // " "
        $hmac->updateBytes($message, 6, 5); // "world"
        $result2 = str_repeat("\x00", $hmac->getMacSize());
        $hmac->doFinal($result2, 0);
        
        $this->assertSame(bin2hex($result1), bin2hex($result2));
    }

    public function testSupportUpdateWithSingleByte(): void
    {
        $key = 'key';
        $message = 'abc';
        
        // Compute in one go
        $result1 = $this->computeHMac($key, $message);
        
        // Compute byte by byte
        $hmac = new HMac(new SM3Digest());
        $hmac->init(new KeyParameter($key));
        $hmac->update(ord('a'));
        $hmac->update(ord('b'));
        $hmac->update(ord('c'));
        $result2 = str_repeat("\x00", $hmac->getMacSize());
        $hmac->doFinal($result2, 0);
        
        $this->assertSame(bin2hex($result1), bin2hex($result2));
    }

    public function testResetBetweenOperations(): void
    {
        $key = 'key';
        $message1 = 'first';
        $message2 = 'second';
        
        $hmac = new HMac(new SM3Digest());
        $hmac->init(new KeyParameter($key));
        
        // First computation
        $hmac->updateBytes($message1, 0, strlen($message1));
        $result1 = str_repeat("\x00", $hmac->getMacSize());
        $hmac->doFinal($result1, 0);
        
        // Reset and second computation
        $hmac->reset();
        $hmac->updateBytes($message2, 0, strlen($message2));
        $result2 = str_repeat("\x00", $hmac->getMacSize());
        $hmac->doFinal($result2, 0);
        
        // Results should be different
        $this->assertNotSame(bin2hex($result1), bin2hex($result2));
        
        // Verify second result is correct
        $expected = $this->computeHMac($key, $message2);
        $this->assertSame(bin2hex($expected), bin2hex($result2));
    }

    public function testReuseAfterDoFinal(): void
    {
        $key = 'key';
        $message = 'test';
        
        $hmac = new HMac(new SM3Digest());
        $hmac->init(new KeyParameter($key));
        
        // First computation
        $hmac->updateBytes($message, 0, strlen($message));
        $result1 = str_repeat("\x00", $hmac->getMacSize());
        $hmac->doFinal($result1, 0);
        
        // Reuse without explicit reset (doFinal should reset internally)
        $hmac->updateBytes($message, 0, strlen($message));
        $result2 = str_repeat("\x00", $hmac->getMacSize());
        $hmac->doFinal($result2, 0);
        
        // Results should be the same
        $this->assertSame(bin2hex($result1), bin2hex($result2));
    }

    public function testThrowExceptionForSmallOutputBuffer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Output buffer too small');
        
        $hmac = new HMac(new SM3Digest());
        $hmac->init(new KeyParameter('key'));
        $hmac->updateBytes('test', 0, 4);
        
        // Output buffer too small (only 10 bytes, need 32)
        $out = str_repeat("\x00", 10);
        $hmac->doFinal($out, 0);
    }

    public function testProduceDifferentResultsForDifferentMessages(): void
    {
        $key = 'key';
        $message1 = 'message1';
        $message2 = 'message2';
        
        $result1 = $this->computeHMac($key, $message1);
        $result2 = $this->computeHMac($key, $message2);
        
        $this->assertNotSame(bin2hex($result1), bin2hex($result2));
    }

    public function testProduceDifferentResultsForDifferentKeys(): void
    {
        $key1 = 'key1';
        $key2 = 'key2';
        $message = 'same message';
        
        $result1 = $this->computeHMac($key1, $message);
        $result2 = $this->computeHMac($key2, $message);
        
        $this->assertNotSame(bin2hex($result1), bin2hex($result2));
    }
}
