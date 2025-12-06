#!/usr/bin/env php
<?php
/**
 * SM2 密钥对生成示例
 * 演示如何生成 SM2 密钥对
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\SM2;

echo "=== SM2 密钥对生成示例 ===\n\n";

// 生成密钥对
$keyPair = SM2::generateKeyPair();

echo "私钥 (Private Key):\n";
echo gmp_strval($keyPair['privateKey'], 16) . "\n";
echo "\n私钥长度: " . strlen(gmp_strval($keyPair['privateKey'], 16)) . " 个十六进制字符\n";

echo "\n公钥 (Public Key):\n";
echo "X 坐标: " . gmp_strval($keyPair['publicKey']['x'], 16) . "\n";
echo "Y 坐标: " . gmp_strval($keyPair['publicKey']['y'], 16) . "\n";
echo "\n公钥坐标长度: " . strlen(gmp_strval($keyPair['publicKey']['x'], 16)) . " 个十六进制字符\n";

// 生成多个密钥对
echo "\n--- 生成多个密钥对 ---\n";
for ($i = 1; $i <= 3; $i++) {
    $kp = SM2::generateKeyPair();
    echo "\n密钥对 {$i}:\n";
    echo "私钥: " . substr(gmp_strval($kp['privateKey'], 16), 0, 32) . "...\n";
    echo "公钥 X: " . substr(gmp_strval($kp['publicKey']['x'], 16), 0, 32) . "...\n";
    echo "公钥 Y: " . substr(gmp_strval($kp['publicKey']['y'], 16), 0, 32) . "...\n";
}

echo "\n✅ SM2 密钥对生成示例运行完成\n";
