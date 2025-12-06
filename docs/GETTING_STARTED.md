# SM-PHP-BC 快速入门指南

欢迎使用 SM-PHP-BC！本指南将帮助你快速上手中国国密算法（SM2/SM3/SM4）的 PHP 实现。

## 📋 目录

- [环境要求](#环境要求)
- [安装](#安装)
- [5 分钟快速入门](#5-分钟快速入门)
- [核心概念](#核心概念)
- [常用场景](#常用场景)
- [进阶使用](#进阶使用)
- [最佳实践](#最佳实践)
- [常见问题](#常见问题)
- [获取帮助](#获取帮助)

## 🔧 环境要求

### 必需条件
- **PHP**: >= 8.1
- **ext-gmp**: GMP 扩展（大整数运算）
- **Composer**: 包管理工具

### 检查环境

```bash
# 检查 PHP 版本
php --version  # 应该显示 8.1 或更高

# 检查 GMP 扩展
php -m | grep gmp  # 应该显示 gmp

# 检查 Composer
composer --version
```

### 安装 GMP 扩展

如果缺少 GMP 扩展：

```bash
# Ubuntu/Debian
sudo apt-get install php-gmp

# CentOS/RHEL
sudo yum install php-gmp

# macOS (Homebrew)
brew install gmp

# Windows
# 在 php.ini 中启用: extension=gmp
```

## 📦 安装

### 方式 1: Composer（推荐）

```bash
composer require sm-php-bc/sm-php-bc
```

### 方式 2: 克隆仓库

```bash
git clone <repository-url>
cd sm-php-bc
composer install
```

### 验证安装

```bash
# 运行测试
vendor/bin/phpunit

# 运行示例
php examples/sm3-hash.php
```

## ⚡ 5 分钟快速入门

### 1. SM3 哈希（最简单）

```php
<?php
require_once 'vendor/autoload.php';

use SmBc\Crypto\Digests\SM3Digest;

// 创建摘要实例
$digest = new SM3Digest();

// 计算哈希
$data = 'Hello, World!';
$digest->updateBytes($data, 0, strlen($data));

$hash = str_repeat("\x00", 32);
$digest->doFinal($hash, 0);

echo "SM3 哈希: " . bin2hex($hash) . "\n";
```

**运行结果**:
```
SM3 哈希: 44f0061e69fa6fdfc290c494654a05dc0c053da7e5c52b84ef93a9d67d3fff88
```

### 2. SM2 加密/解密

```php
<?php
require_once 'vendor/autoload.php';

use SmBc\Crypto\SM2;

// 生成密钥对
$keyPair = SM2::generateKeyPair();

// 加密
$plaintext = 'Secret message';
$ciphertext = SM2::encrypt($plaintext, $keyPair['publicKey']);
echo "密文: " . bin2hex($ciphertext) . "\n";

// 解密
$decrypted = SM2::decrypt($ciphertext, $keyPair['privateKey']);
echo "解密: $decrypted\n";
```

**运行结果**:
```
密文: 04... (很长的十六进制)
解密: Secret message
```

### 3. SM2 数字签名

```php
<?php
require_once 'vendor/autoload.php';

use SmBc\Crypto\SM2;

// 生成密钥对
$keyPair = SM2::generateKeyPair();

// 签名
$message = 'Important message';
$signature = SM2::sign($message, $keyPair['privateKey']);
echo "签名: " . bin2hex($signature) . "\n";

// 验签
$isValid = SM2::verify($message, $signature, $keyPair['publicKey']);
echo "验证: " . ($isValid ? '✅ 有效' : '❌ 无效') . "\n";
```

**运行结果**:
```
签名: 3045... (ASN.1 DER 编码)
验证: ✅ 有效
```

### 4. SM4 对称加密

```php
<?php
require_once 'vendor/autoload.php';

use SmBc\Crypto\SM4;

// 生成密钥和 IV
$key = SM4::generateKey();
$iv = SM4::generateIV();

// CBC 模式加密
$plaintext = 'Hello, SM4!';
$ciphertext = SM4::encryptCBC($plaintext, $key, $iv);
echo "密文: " . bin2hex($ciphertext) . "\n";

// CBC 模式解密
$decrypted = SM4::decryptCBC($ciphertext, $key, $iv);
echo "解密: $decrypted\n";
```

**运行结果**:
```
密文: a1b2c3d4e5f6...
解密: Hello, SM4!
```

### 5. SM4 认证加密（GCM）

```php
<?php
require_once 'vendor/autoload.php';

use SmBc\Crypto\SM4;

// 生成密钥和 nonce
$key = SM4::generateKey();
$nonce = random_bytes(12); // GCM 使用 12 字节 nonce

// GCM 加密（带认证）
$plaintext = 'Secret data';
$ciphertext = SM4::encryptGCM($plaintext, $key, $nonce);
echo "密文+MAC: " . bin2hex($ciphertext) . "\n";

// GCM 解密（自动验证 MAC）
$decrypted = SM4::decryptGCM($ciphertext, $key, $nonce);
echo "解密: $decrypted\n";
```

**运行结果**:
```
密文+MAC: a1b2c3d4... (包含 16 字节 MAC 标签)
解密: Secret data
```

## 💡 核心概念

### SM3 - 密码哈希算法

**用途**: 数据完整性验证、数字签名

**特点**:
- 输出 256 位（32 字节）哈希值
- 单向函数，不可逆
- 抗碰撞、抗原像攻击

**使用场景**:
- 密码存储（加盐哈希）
- 文件完整性校验
- 数字签名的消息摘要

### SM2 - 椭圆曲线公钥算法

**用途**: 非对称加密、数字签名、密钥交换

**特点**:
- 基于椭圆曲线
- 公钥加密，私钥解密
- 安全性高，密钥短

**使用场景**:
- 数字证书
- 安全通信
- 身份认证
- 密钥协商

### SM4 - 分组密码算法

**用途**: 对称加密

**特点**:
- 128 位密钥
- 128 位块大小
- 快速、高效

**使用场景**:
- 数据加密
- 文件加密
- 通信加密

### 工作模式

| 模式 | 特点 | 推荐度 | 使用场景 |
|------|------|--------|---------|
| **ECB** | 简单但不安全 | ❌ 不推荐 | 仅兼容性测试 |
| **CBC** | 需要 IV，串行 | ✅ 传统选择 | 文件加密 |
| **CTR** | 流密码，可并行 | ✅ 推荐 | 大数据加密 |
| **GCM** | 认证加密 (AEAD) | ⭐ 最推荐 | 网络通信 |

## 🎯 常用场景

### 场景 1: 用户密码存储

```php
use SmBc\Crypto\Digests\SM3Digest;

function hashPassword($password, $salt = null) {
    // 生成或使用提供的盐值
    $salt = $salt ?? random_bytes(16);
    
    // SM3 哈希
    $digest = new SM3Digest();
    $digest->updateBytes($salt, 0, strlen($salt));
    $digest->updateBytes($password, 0, strlen($password));
    
    $hash = str_repeat("\x00", 32);
    $digest->doFinal($hash, 0);
    
    // 返回 salt + hash
    return bin2hex($salt) . ':' . bin2hex($hash);
}

function verifyPassword($password, $stored) {
    [$saltHex, $hashHex] = explode(':', $stored);
    $salt = hex2bin($saltHex);
    
    $computed = hashPassword($password, $salt);
    return hash_equals($stored, $computed);
}

// 使用
$hashed = hashPassword('myPassword123');
$isValid = verifyPassword('myPassword123', $hashed); // true
```

### 场景 2: API 请求签名

```php
use SmBc\Crypto\SM2;

// 服务端：生成密钥对
$keyPair = SM2::generateKeyPair();

// 客户端：签名请求
$request = json_encode(['api' => 'getData', 'timestamp' => time()]);
$signature = SM2::sign($request, $keyPair['privateKey']);

// 发送: request + signature

// 服务端：验证签名
$isValid = SM2::verify($request, $signature, $keyPair['publicKey']);
if ($isValid) {
    // 处理请求
} else {
    // 拒绝请求
}
```

### 场景 3: 文件加密

```php
use SmBc\Crypto\SM4;

// 加密文件
function encryptFile($inputPath, $outputPath, $password) {
    $plaintext = file_get_contents($inputPath);
    
    // 使用密码派生密钥
    $key = SM4::generateKey(); // 实际应该从密码派生
    $iv = SM4::generateIV();
    
    $ciphertext = SM4::encryptCBC($plaintext, $key, $iv);
    
    // 保存 IV + 密文
    file_put_contents($outputPath, $iv . $ciphertext);
    
    return $key; // 应该安全存储
}

// 解密文件
function decryptFile($inputPath, $outputPath, $key) {
    $data = file_get_contents($inputPath);
    
    // 提取 IV 和密文
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    
    $plaintext = SM4::decryptCBC($ciphertext, $key, $iv);
    
    file_put_contents($outputPath, $plaintext);
}
```

### 场景 4: HTTPS API 加密

```php
use SmBc\Crypto\SM2;
use SmBc\Crypto\SM4;

// 混合加密（公钥 + 对称）
function encryptForAPI($data, $recipientPublicKey) {
    // 1. 生成随机对称密钥
    $sessionKey = SM4::generateKey();
    
    // 2. 用对称密钥加密数据
    $iv = SM4::generateIV();
    $encryptedData = SM4::encryptGCM($data, $sessionKey, $iv);
    
    // 3. 用公钥加密对称密钥
    $encryptedKey = SM2::encrypt($sessionKey, $recipientPublicKey);
    
    return [
        'encryptedKey' => bin2hex($encryptedKey),
        'iv' => bin2hex($iv),
        'data' => bin2hex($encryptedData)
    ];
}

function decryptFromAPI($encrypted, $privateKey) {
    // 1. 用私钥解密对称密钥
    $sessionKey = SM2::decrypt(hex2bin($encrypted['encryptedKey']), $privateKey);
    
    // 2. 用对称密钥解密数据
    $data = SM4::decryptGCM(
        hex2bin($encrypted['data']),
        $sessionKey,
        hex2bin($encrypted['iv'])
    );
    
    return $data;
}
```

## 🚀 进阶使用

### 使用底层 API

```php
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Paddings\PaddedBufferedBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

// 创建 CBC 密码器
$cipher = new PaddedBufferedBlockCipher(
    new CBCBlockCipher(new SM4Engine()),
    new PKCS7Padding()
);

// 初始化加密
$key = random_bytes(16);
$iv = random_bytes(16);
$cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));

// 分段加密
$plaintext = 'Long message...';
$output = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
$len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $output, 0);
$len += $cipher->doFinal($output, $len);

$ciphertext = substr($output, 0, $len);
```

### 自定义填充

```php
use SmBc\Crypto\Paddings\ISO7816d4Padding;
use SmBc\Crypto\Paddings\ISO10126d2Padding;

// 使用 ISO7816-4 填充（智能卡标准）
$cipher = new PaddedBufferedBlockCipher(
    new CBCBlockCipher(new SM4Engine()),
    new ISO7816d4Padding()
);

// 使用 ISO10126 填充（随机填充）
$cipher = new PaddedBufferedBlockCipher(
    new CBCBlockCipher(new SM4Engine()),
    new ISO10126d2Padding()
);
```

### SM2 密钥交换（ECDH）

完整示例请查看 `examples/sm2-keyexchange.php`。

## ✅ 最佳实践

### 安全性

1. **使用强随机数**
   ```php
   // ✅ 好
   $key = random_bytes(16);
   
   // ❌ 不好
   $key = str_repeat('a', 16);
   ```

2. **选择正确的模式**
   ```php
   // ✅ 推荐：GCM（认证加密）
   SM4::encryptGCM($data, $key, $nonce);
   
   // ✅ 可以：CBC（需要 IV）
   SM4::encryptCBC($data, $key, $iv);
   
   // ❌ 不推荐：ECB（不安全）
   SM4::encrypt($data, $key);
   ```

3. **保护私钥**
   ```php
   // 导出私钥时加密存储
   $privateKeyHex = SM2::exportPrivateKey($privateKey);
   $encrypted = encryptWithPassword($privateKeyHex, $userPassword);
   file_put_contents('key.enc', $encrypted);
   ```

4. **使用盐值和 KDF**
   ```php
   // 从密码派生密钥
   function deriveKey($password, $salt) {
       $digest = new SM3Digest();
       $digest->updateBytes($salt, 0, strlen($salt));
       $digest->updateBytes($password, 0, strlen($password));
       
       $key = str_repeat("\x00", 32);
       $digest->doFinal($key, 0);
       
       return substr($key, 0, 16); // SM4 需要 16 字节
   }
   ```

### 性能

1. **重用对象**
   ```php
   // ✅ 好：重用摘要对象
   $digest = new SM3Digest();
   foreach ($messages as $msg) {
       $digest->reset();
       $digest->updateBytes($msg, 0, strlen($msg));
       $hash = str_repeat("\x00", 32);
       $digest->doFinal($hash, 0);
   }
   ```

2. **选择合适的模式**
   ```php
   // 大文件：使用 CTR（可并行）
   SM4::encryptCTR($largeData, $key, $iv);
   
   // 小数据：使用 GCM（安全性）
   SM4::encryptGCM($smallData, $key, $nonce);
   ```

### 错误处理

```php
try {
    $decrypted = SM2::decrypt($ciphertext, $privateKey);
} catch (\Exception $e) {
    // 解密失败（密钥错误或数据损坏）
    error_log("解密失败: " . $e->getMessage());
    // 返回错误响应
}
```

## ❓ 常见问题

### Q1: GMP 扩展安装失败怎么办？

**A**: 确保已安装 GMP 开发库，然后重新编译 PHP 或安装扩展。

```bash
# Ubuntu
sudo apt-get install libgmp-dev
sudo apt-get install php-gmp

# 检查
php -m | grep gmp
```

### Q2: 如何与 Java/JavaScript 版本互操作？

**A**: 使用相同的参数和模式。例如，使用 C1C3C2 模式：

```php
// PHP
$engine = new SM2Engine(SM2Engine::C1C3C2);

// 或使用高层 API（默认 C1C3C2）
$ciphertext = SM2::encrypt($data, $publicKey);
```

### Q3: ECB 模式为什么不安全？

**A**: ECB 模式对相同的明文块产生相同的密文块，泄露数据模式。应使用 CBC、CTR 或 GCM。

### Q4: 如何选择填充方案？

**A**: 
- **PKCS7**: 最常用，推荐
- **ISO7816-4**: 智能卡场景
- **ISO10126**: 需要随机填充
- **ZeroByte**: 特定协议要求

### Q5: SM4 密钥和 IV 如何管理？

**A**:
- 密钥：安全存储，定期更换
- IV：随机生成，可公开传输
- 不要重复使用同一 (key, IV) 对

## 📚 获取帮助

### 文档

- [README.md](./README.md) - 主文档
- [examples/](./examples/) - 完整示例
- [docs/](./docs/) - 详细文档

### 示例代码

```bash
# 查看所有示例
ls examples/*.php

# 运行示例
php examples/sm3-hash.php
php examples/sm4-modes.php
```

### 测试

```bash
# 查看测试用例
ls tests/**/*Test.php

# 运行特定测试
vendor/bin/phpunit tests/Crypto/SM3DigestTest.php
```

### 问题反馈

- 提交 Issue
- 查看已有 Issue
- 参考测试用例

## 🎓 学习路径

### 初学者
1. ✅ 阅读本文档
2. ✅ 运行 `examples/sm3-hash.php`
3. ✅ 运行 `examples/sm4-ecb-simple.php`
4. ✅ 尝试修改示例代码

### 中级用户
1. ✅ 学习 SM2 公钥加密
2. ✅ 运行 `examples/sm2-sign.php`
3. ✅ 了解不同的 SM4 工作模式
4. ✅ 运行 `examples/sm4-modes.php`

### 高级用户
1. ✅ 学习密钥交换协议
2. ✅ 运行 `examples/sm2-keyexchange.php`
3. ✅ 使用底层 API
4. ✅ 查看测试代码了解边界情况

## 🎉 开始使用

现在你已经准备好使用 SM-PHP-BC 了！

```bash
# 1. 安装
composer require sm-php-bc/sm-php-bc

# 2. 运行示例
php examples/sm3-hash.php

# 3. 开始你的项目
```

祝你使用愉快！如有问题，请查看文档或提交 Issue。

---

**相关资源**:
- [完整示例](./examples/)
- [API 文档](./docs/)
- [测试用例](./tests/)
- [项目状态](./PROJECT_STATUS.md)
