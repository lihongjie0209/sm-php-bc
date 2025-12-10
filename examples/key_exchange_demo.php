<?php
/**
 * SM2 密钥交换演示
 * 展示如何使用 SM2 协议进行密钥协商
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\SM2;
use SmBc\Crypto\Agreement\SM2KeyExchange;
use SmBc\Crypto\Params\SM2KeyExchangePrivateParameters;
use SmBc\Crypto\Params\SM2KeyExchangePublicParameters;
use SmBc\Crypto\Params\ParametersWithID;

echo "===== SM2 密钥交换演示 =====\n\n";

echo "场景: Alice 和 Bob 通过 SM2 密钥交换协议协商共享密钥\n\n";

// 步骤 1: Alice 生成密钥对
echo "1. Alice 生成密钥对\n";
$aliceKeyPair = SM2::generateKeyPair();
$aliceEphemeralKeyPair = SM2::generateKeyPair();
$aliceID = "alice@example.com";
echo "Alice 静态私钥: " . substr($aliceKeyPair->getPrivateKeyHex(), 0, 32) . "...\n";
echo "Alice 临时私钥: " . substr($aliceEphemeralKeyPair->getPrivateKeyHex(), 0, 32) . "...\n";

// 步骤 2: Bob 生成密钥对
echo "\n2. Bob 生成密钥对\n";
$bobKeyPair = SM2::generateKeyPair();
$bobEphemeralKeyPair = SM2::generateKeyPair();
$bobID = "bob@example.com";
echo "Bob 静态私钥: " . substr($bobKeyPair->getPrivateKeyHex(), 0, 32) . "...\n";
echo "Bob 临时私钥: " . substr($bobEphemeralKeyPair->getPrivateKeyHex(), 0, 32) . "...\n";

// 步骤 3: Alice 发起密钥交换
echo "\n3. Alice 发起密钥交换\n";
$aliceExchange = new SM2KeyExchange();
$alicePrivateParams = new SM2KeyExchangePrivateParameters(
    true, // initiator
    $aliceKeyPair->getPrivate(),
    $aliceEphemeralKeyPair->getPrivate()
);
$alicePrivateParams = new ParametersWithID($alicePrivateParams, $aliceID);
$aliceExchange->init($alicePrivateParams);

// 步骤 4: Bob 响应密钥交换
echo "4. Bob 响应密钥交换\n";
$bobExchange = new SM2KeyExchange();
$bobPrivateParams = new SM2KeyExchangePrivateParameters(
    false, // responder
    $bobKeyPair->getPrivate(),
    $bobEphemeralKeyPair->getPrivate()
);
$bobPrivateParams = new ParametersWithID($bobPrivateParams, $bobID);
$bobExchange->init($bobPrivateParams);

// 步骤 5: 双方计算共享密钥
echo "\n5. 双方计算共享密钥\n";

// Alice 计算
$alicePublicParams = new SM2KeyExchangePublicParameters(
    $bobKeyPair->getPublic(),
    $bobEphemeralKeyPair->getPublic()
);
$alicePublicParams = new ParametersWithID($alicePublicParams, $bobID);
$aliceSharedKey = $aliceExchange->calculateKey(128, $alicePublicParams);

// Bob 计算
$bobPublicParams = new SM2KeyExchangePublicParameters(
    $aliceKeyPair->getPublic(),
    $aliceEphemeralKeyPair->getPublic()
);
$bobPublicParams = new ParametersWithID($bobPublicParams, $aliceID);
$bobSharedKey = $bobExchange->calculateKey(128, $bobPublicParams);

// 步骤 6: 验证共享密钥
echo "\n6. 验证共享密钥\n";
echo "Alice 的共享密钥: " . bin2hex($aliceSharedKey) . "\n";
echo "Bob 的共享密钥:   " . bin2hex($bobSharedKey) . "\n";
echo "密钥匹配: " . ($aliceSharedKey === $bobSharedKey ? "✓ 成功" : "✗ 失败") . "\n";

echo "\n===== 演示完成 =====\n";
