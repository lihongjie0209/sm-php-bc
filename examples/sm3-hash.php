#!/usr/bin/env php
<?php
/**
 * SM3 哈希示例
 * 演示如何使用 SM3Digest 计算数据的哈希值
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Digests\SM3Digest;

echo "=== SM3 哈希示例 ===\n\n";

// 创建 SM3 摘要实例
$digest = new SM3Digest();

// 更新数据
$data = 'Hello, SM3!';
$digest->updateBytes($data, 0, strlen($data));

// 获取哈希值
$hash = str_repeat("\x00", $digest->getDigestSize());
$digest->doFinal($hash, 0);

echo "输入数据: Hello, SM3!\n";
echo "SM3 Hash: " . bin2hex($hash) . "\n";
echo "哈希长度: " . strlen($hash) . " 字节\n";

// 多次更新示例
echo "\n--- 分段更新示例 ---\n";
$digest2 = new SM3Digest();
$part1 = 'Hello, ';
$part2 = 'SM3!';

$digest2->updateBytes($part1, 0, strlen($part1));
$digest2->updateBytes($part2, 0, strlen($part2));

$hash2 = str_repeat("\x00", $digest2->getDigestSize());
$digest2->doFinal($hash2, 0);

echo "分段输入: \"Hello, \" + \"SM3!\"\n";
echo "SM3 Hash: " . bin2hex($hash2) . "\n";
echo "结果一致: " . ($hash === $hash2 ? 'true' : 'false') . "\n";

// 空数据哈希
echo "\n--- 空数据哈希 ---\n";
$digest3 = new SM3Digest();
$hash3 = str_repeat("\x00", $digest3->getDigestSize());
$digest3->doFinal($hash3, 0);

echo "空数据 Hash: " . bin2hex($hash3) . "\n";

echo "\n✅ SM3 哈希示例运行完成\n";
