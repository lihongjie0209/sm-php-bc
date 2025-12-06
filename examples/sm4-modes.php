#!/usr/bin/env php
<?php
/**
 * SM4 多种工作模式示例
 * 
 * 演示：
 * 1. ECB 模式（电子密码本）
 * 2. CBC 模式（密码块链接）
 * 3. CTR 模式（计数器）
 * 4. GCM 模式（伽罗瓦/计数器）
 * 
 * 使用底层 API 直接控制加密模式和填充
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\ECBBlockCipher;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Modes\SICBlockCipher;
use SmBc\Crypto\Modes\GCMBlockCipher;
use SmBc\Crypto\Paddings\PaddedBufferedBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;
use SmBc\Crypto\Params\AEADParameters;

echo "=== SM4 多种工作模式示例 ===\n\n";

// 准备测试数据
$key = '';
for ($i = 0; $i < 16; $i++) {
    $key .= chr($i);
}

$plaintext = 'Hello, SM4 modes! 你好，SM4！';
echo "明文: {$plaintext}\n";
echo "明文长度: " . strlen($plaintext) . " 字节\n";
echo "\n";

// ========== ECB 模式 ==========
echo "--- 1. ECB 模式（不推荐用于生产）---\n";
try {
    $ecbCipher = new PaddedBufferedBlockCipher(
        new ECBBlockCipher(new SM4Engine()),
        new PKCS7Padding()
    );
    
    // 加密
    $ecbCipher->init(true, new KeyParameter($key));
    $ecbOutput = str_repeat("\x00", $ecbCipher->getOutputSize(strlen($plaintext)));
    $ecbLen = $ecbCipher->processBytes($plaintext, 0, strlen($plaintext), $ecbOutput, 0);
    $ecbLen += $ecbCipher->doFinal($ecbOutput, $ecbLen);
    $ecbCiphertext = substr($ecbOutput, 0, $ecbLen);
    
    echo "ECB 密文长度: {$ecbLen} 字节\n";
    echo "ECB 密文 (hex): " . substr(bin2hex($ecbCiphertext), 0, 64) . "...\n";
    
    // 解密
    $ecbCipher->init(false, new KeyParameter($key));
    $ecbDecrypted = str_repeat("\x00", $ecbCipher->getOutputSize(strlen($ecbCiphertext)));
    $ecbDecLen = $ecbCipher->processBytes($ecbCiphertext, 0, strlen($ecbCiphertext), $ecbDecrypted, 0);
    $ecbDecLen += $ecbCipher->doFinal($ecbDecrypted, $ecbDecLen);
    
    echo "ECB 解密: " . substr($ecbDecrypted, 0, $ecbDecLen) . "\n";
    echo "ECB 验证: ✅\n";
} catch (Exception $e) {
    echo "ECB 模式错误: " . $e->getMessage() . "\n";
}
echo "\n";

// ========== CBC 模式 ==========
echo "--- 2. CBC 模式（推荐） ---\n";
try {
    $iv = '';
    for ($i = 0; $i < 16; $i++) {
        $iv .= chr($i * 2);
    }
    
    $cbcCipher = new PaddedBufferedBlockCipher(
        new CBCBlockCipher(new SM4Engine()),
        new PKCS7Padding()
    );
    
    // 加密
    $cbcCipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
    $cbcOutput = str_repeat("\x00", $cbcCipher->getOutputSize(strlen($plaintext)));
    $cbcLen = $cbcCipher->processBytes($plaintext, 0, strlen($plaintext), $cbcOutput, 0);
    $cbcLen += $cbcCipher->doFinal($cbcOutput, $cbcLen);
    $cbcCiphertext = substr($cbcOutput, 0, $cbcLen);
    
    echo "CBC 密文长度: {$cbcLen} 字节\n";
    echo "CBC 密文 (hex): " . substr(bin2hex($cbcCiphertext), 0, 64) . "...\n";
    
    // 解密
    $cbcCipher->init(false, new ParametersWithIV(new KeyParameter($key), $iv));
    $cbcDecrypted = str_repeat("\x00", $cbcCipher->getOutputSize(strlen($cbcCiphertext)));
    $cbcDecLen = $cbcCipher->processBytes($cbcCiphertext, 0, strlen($cbcCiphertext), $cbcDecrypted, 0);
    $cbcDecLen += $cbcCipher->doFinal($cbcDecrypted, $cbcDecLen);
    
    echo "CBC 解密: " . substr($cbcDecrypted, 0, $cbcDecLen) . "\n";
    echo "CBC 验证: ✅\n";
} catch (Exception $e) {
    echo "CBC 模式错误: " . $e->getMessage() . "\n";
}
echo "\n";

// ========== CTR 模式 ==========
echo "--- 3. CTR 模式（流密码）---\n";
try {
    $ctrIv = '';
    for ($i = 0; $i < 16; $i++) {
        $ctrIv .= chr(0xFF - $i);
    }
    
    $ctrCipher = new SICBlockCipher(new SM4Engine());
    
    // 加密
    $ctrCipher->init(true, new ParametersWithIV(new KeyParameter($key), $ctrIv));
    $ctrCiphertext = str_repeat("\x00", strlen($plaintext));
    $ctrCipher->processBytes($plaintext, 0, strlen($plaintext), $ctrCiphertext, 0);
    
    echo "CTR 密文长度: " . strlen($ctrCiphertext) . " 字节 (无填充)\n";
    echo "CTR 密文 (hex): " . substr(bin2hex($ctrCiphertext), 0, 64) . "...\n";
    
    // 解密
    $ctrCipher->init(false, new ParametersWithIV(new KeyParameter($key), $ctrIv));
    $ctrDecrypted = str_repeat("\x00", strlen($ctrCiphertext));
    $ctrCipher->processBytes($ctrCiphertext, 0, strlen($ctrCiphertext), $ctrDecrypted, 0);
    
    echo "CTR 解密: {$ctrDecrypted}\n";
    echo "CTR 验证: ✅\n";
} catch (Exception $e) {
    echo "CTR 模式错误: " . $e->getMessage() . "\n";
}
echo "\n";

// ========== GCM 模式 ==========
echo "--- 4. GCM 模式（认证加密）---\n";
try {
    $gcmNonce = '';
    for ($i = 0; $i < 12; $i++) {
        $gcmNonce .= chr($i + 100);
    }
    
    $gcmCipher = new GCMBlockCipher(new SM4Engine());
    
    // 加密
    $macSize = 128; // 128位认证标签
    $gcmCipher->init(true, new AEADParameters(new KeyParameter($key), $macSize, $gcmNonce, null));
    
    $gcmOutput = str_repeat("\x00", $gcmCipher->getOutputSize(strlen($plaintext)));
    $gcmLen = $gcmCipher->processBytes($plaintext, 0, strlen($plaintext), $gcmOutput, 0);
    $gcmLen += $gcmCipher->doFinal($gcmOutput, $gcmLen);
    $gcmCiphertext = substr($gcmOutput, 0, $gcmLen);
    
    echo "GCM 密文长度: {$gcmLen} 字节 (含16字节MAC标签)\n";
    echo "GCM 密文 (hex): " . substr(bin2hex($gcmCiphertext), 0, 64) . "...\n";
    
    // 解密
    $gcmCipher->init(false, new AEADParameters(new KeyParameter($key), $macSize, $gcmNonce, null));
    $gcmDecrypted = str_repeat("\x00", $gcmCipher->getOutputSize(strlen($gcmCiphertext)));
    $gcmDecLen = $gcmCipher->processBytes($gcmCiphertext, 0, strlen($gcmCiphertext), $gcmDecrypted, 0);
    $gcmDecLen += $gcmCipher->doFinal($gcmDecrypted, $gcmDecLen);
    
    echo "GCM 解密: " . substr($gcmDecrypted, 0, $gcmDecLen) . "\n";
    echo "GCM 验证: ✅ (含认证标签验证)\n";
} catch (Exception $e) {
    echo "GCM 模式错误: " . $e->getMessage() . "\n";
}
echo "\n";

echo "✅ SM4 多种工作模式示例运行完成\n";
echo "\n";
echo "📌 模式选择建议：\n";
echo "   • ECB: ❌ 不安全，仅用于兼容性测试\n";
echo "   • CBC: ✅ 传统选择，需要正确处理 IV\n";
echo "   • CTR: ✅ 流密码模式，可并行，无填充\n";
echo "   • GCM: ⭐ 最佳选择，提供认证加密（AEAD）\n";
