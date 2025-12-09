<?php

declare(strict_types=1);

/**
 * HMAC-SM3 示例
 * 
 * 演示如何使用 HMAC-SM3 进行消息认证码计算
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Macs\HMac;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Params\KeyParameter;

echo "====================================\n";
echo "HMAC-SM3 使用示例\n";
echo "====================================\n\n";

// ============================================
// 1. 基础 HMAC 计算
// ============================================
echo "1. 基础 HMAC 计算\n";
echo "-----------------------------------\n";

$key = 'my-secret-key';
$message = 'Hello, HMAC-SM3!';

$hmac = new HMac(new SM3Digest());
$hmac->init(new KeyParameter($key));
$hmac->updateBytes($message, 0, strlen($message));

$mac = str_repeat("\x00", $hmac->getMacSize());
$hmac->doFinal($mac, 0);

echo "密钥: $key\n";
echo "消息: $message\n";
echo "HMAC: " . bin2hex($mac) . "\n\n";

// ============================================
// 2. 增量更新
// ============================================
echo "2. 增量更新示例\n";
echo "-----------------------------------\n";

$hmac2 = new HMac(new SM3Digest());
$hmac2->init(new KeyParameter($key));

// 分段更新消息
$parts = ['Hello, ', 'HMAC', '-', 'SM3', '!'];
foreach ($parts as $part) {
    $hmac2->updateBytes($part, 0, strlen($part));
    echo "添加: '$part'\n";
}

$mac2 = str_repeat("\x00", $hmac2->getMacSize());
$hmac2->doFinal($mac2, 0);

echo "增量计算的 HMAC: " . bin2hex($mac2) . "\n";
echo "结果一致: " . ($mac === $mac2 ? '是 ✓' : '否 ✗') . "\n\n";

// ============================================
// 3. 不同密钥长度
// ============================================
echo "3. 不同密钥长度的处理\n";
echo "-----------------------------------\n";

$testMessage = 'test message';

// 短密钥（< 64 字节）
$shortKey = 'short';
$hmacShort = new HMac(new SM3Digest());
$hmacShort->init(new KeyParameter($shortKey));
$hmacShort->updateBytes($testMessage, 0, strlen($testMessage));
$macShort = str_repeat("\x00", $hmacShort->getMacSize());
$hmacShort->doFinal($macShort, 0);

echo "短密钥 (5 bytes): " . substr(bin2hex($macShort), 0, 32) . "...\n";

// 长密钥（> 64 字节，会被哈希）
$longKey = str_repeat('long-key-', 10); // 90 bytes
$hmacLong = new HMac(new SM3Digest());
$hmacLong->init(new KeyParameter($longKey));
$hmacLong->updateBytes($testMessage, 0, strlen($testMessage));
$macLong = str_repeat("\x00", $hmacLong->getMacSize());
$hmacLong->doFinal($macLong, 0);

echo "长密钥 (90 bytes): " . substr(bin2hex($macLong), 0, 32) . "...\n\n";

// ============================================
// 4. 重用 HMAC 实例
// ============================================
echo "4. 重用 HMAC 实例\n";
echo "-----------------------------------\n";

$hmacReuse = new HMac(new SM3Digest());
$hmacReuse->init(new KeyParameter('reuse-key'));

// 第一次计算
$msg1 = 'first message';
$hmacReuse->updateBytes($msg1, 0, strlen($msg1));
$mac1 = str_repeat("\x00", $hmacReuse->getMacSize());
$hmacReuse->doFinal($mac1, 0);
echo "消息 1: '$msg1'\n";
echo "MAC 1:  " . substr(bin2hex($mac1), 0, 32) . "...\n";

// 重用（doFinal 后自动重置）
$msg2 = 'second message';
$hmacReuse->updateBytes($msg2, 0, strlen($msg2));
$mac2 = str_repeat("\x00", $hmacReuse->getMacSize());
$hmacReuse->doFinal($mac2, 0);
echo "消息 2: '$msg2'\n";
echo "MAC 2:  " . substr(bin2hex($mac2), 0, 32) . "...\n\n";

// ============================================
// 5. 标准测试向量
// ============================================
echo "5. 标准测试向量验证\n";
echo "-----------------------------------\n";

$testKey = 'key';
$testMsg = 'The quick brown fox jumps over the lazy dog';

$hmacTest = new HMac(new SM3Digest());
$hmacTest->init(new KeyParameter($testKey));
$hmacTest->updateBytes($testMsg, 0, strlen($testMsg));
$macTest = str_repeat("\x00", $hmacTest->getMacSize());
$hmacTest->doFinal($macTest, 0);

$expected = 'bd4a34077888162b210645b8ebf74b9af357303789357a27c7fc457244ebd398';
$actual = bin2hex($macTest);

echo "密钥: '$testKey'\n";
echo "消息: '$testMsg'\n";
echo "期望: $expected\n";
echo "实际: $actual\n";
echo "验证: " . ($expected === $actual ? '通过 ✓' : '失败 ✗') . "\n\n";

// ============================================
// 6. 实用场景：API 签名
// ============================================
echo "6. 实用场景：API 请求签名\n";
echo "-----------------------------------\n";

function signApiRequest(string $apiKey, array $params): string
{
    // 按键名排序参数
    ksort($params);
    
    // 构建签名字符串
    $signString = http_build_query($params);
    
    // 计算 HMAC 签名
    $hmac = new HMac(new SM3Digest());
    $hmac->init(new KeyParameter($apiKey));
    $hmac->updateBytes($signString, 0, strlen($signString));
    
    $signature = str_repeat("\x00", $hmac->getMacSize());
    $hmac->doFinal($signature, 0);
    
    return bin2hex($signature);
}

function verifyApiRequest(string $apiKey, array $params, string $signature): bool
{
    $expectedSignature = signApiRequest($apiKey, $params);
    return hash_equals($expectedSignature, $signature);
}

$apiKey = 'my-api-secret-key';
$apiParams = [
    'user_id' => '12345',
    'action' => 'transfer',
    'amount' => '100.00',
    'timestamp' => '1638360000'
];

$signature = signApiRequest($apiKey, $apiParams);

echo "API 密钥: $apiKey\n";
echo "请求参数: " . json_encode($apiParams) . "\n";
echo "签名: $signature\n";
echo "验证: " . (verifyApiRequest($apiKey, $apiParams, $signature) ? '通过 ✓' : '失败 ✗') . "\n\n";

// 篡改检测
$apiParams['amount'] = '999.99';
echo "篡改后验证: " . (verifyApiRequest($apiKey, $apiParams, $signature) ? '通过 ✓' : '失败 ✗ (正确检测到篡改)') . "\n\n";

echo "====================================\n";
echo "示例完成\n";
echo "====================================\n";
