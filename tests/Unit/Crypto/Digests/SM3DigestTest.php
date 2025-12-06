<?php

namespace SmBc\Tests\Unit\Crypto\Digests;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Digests\SM3Digest;

class SM3DigestTest extends TestCase
{
    public function testEmptyString(): void
    {
        $digest = new SM3Digest();
        $output = str_repeat("\x00", 32);
        $digest->doFinal($output, 0);
        
        $expected = "1ab21d8355cfa17f8e61194831e81a8f22bec8c728fefb747ed035eb5082aa2b";
        $this->assertEquals($expected, bin2hex($output));
    }

    public function testABC(): void
    {
        $digest = new SM3Digest();
        $input = "abc";
        $digest->updateBytes($input, 0, strlen($input));
        $output = str_repeat("\x00", 32);
        $digest->doFinal($output, 0);
        
        $expected = "66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0";
        $this->assertEquals($expected, bin2hex($output));
    }

    public function testLongString(): void
    {
        // 64 chars of "abcd..."
        $input = str_repeat("abcd", 16);
        $digest = new SM3Digest();
        $digest->updateBytes($input, 0, strlen($input));
        $output = str_repeat("\x00", 32);
        $digest->doFinal($output, 0);
        
        $expected = "debe9ff92275b8a138604889c18e5a4d6fdb70e5387e5765293dcba39c0c5732";
        $this->assertEquals($expected, bin2hex($output));
    }

    public function testReset(): void
    {
        $digest = new SM3Digest();
        $input = "abc";
        $digest->updateBytes($input, 0, strlen($input));
        $output1 = str_repeat("\x00", 32);
        $digest->doFinal($output1, 0);

        $digest->updateBytes($input, 0, strlen($input));
        $output2 = str_repeat("\x00", 32);
        $digest->doFinal($output2, 0);

        $this->assertEquals(bin2hex($output1), bin2hex($output2));
    }
}
