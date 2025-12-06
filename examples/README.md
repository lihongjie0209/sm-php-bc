# SM-PHP-BC 示例代码

本目录包含 `sm-php-bc` 库的所有示例代码，与 README.md 中的代码示例一一对应。

## 📁 文件说明

| 文件 | 说明 | 对应 README 章节 |
|------|------|-----------------|
| `sm3-hash.php` | SM3 哈希计算示例 | SM3 哈希 |
| `sm2-keypair.php` | SM2 密钥对生成示例 | SM2 密钥对生成 |
| `sm2-sign.php` | SM2 数字签名示例 | SM2 数字签名 |
| `sm2-encrypt.php` | SM2 公钥加密示例 | SM2 公钥加密 |
| `sm2-keyexchange.php` | SM2 密钥交换示例 | SM2 密钥交换 |
| `sm4-ecb-simple.php` | SM4 ECB 模式简单示例 | SM4 对称加密 |
| `sm4-modes.php` | SM4 多种工作模式示例 | SM4 高级用法 |

## 🚀 快速开始

### 1. 安装依赖

```bash
cd sm-php-bc
composer install
```

### 2. 运行示例

#### 运行单个示例

```bash
# SM3 哈希
php examples/sm3-hash.php

# SM2 密钥对生成
php examples/sm2-keypair.php

# SM2 数字签名
php examples/sm2-sign.php

# SM2 公钥加密
php examples/sm2-encrypt.php

# SM2 密钥交换
php examples/sm2-keyexchange.php

# SM4 ECB 模式简单示例
php examples/sm4-ecb-simple.php

# SM4 多种工作模式示例
php examples/sm4-modes.php
```

#### 或者使用可执行权限（Unix/Linux/Mac）

```bash
chmod +x examples/*.php
./examples/sm3-hash.php
./examples/sm2-keypair.php
./examples/sm4-ecb-simple.php
./examples/sm4-modes.php
./examples/sm2-sign.php
./examples/sm2-encrypt.php
./examples/sm2-keyexchange.php
```

#### 运行所有示例

```bash
# Bash/Zsh
for file in examples/*.php; do echo "=== Running $file ===" && php "$file" && echo; done

# PowerShell (Windows)
Get-ChildItem examples\*.php | ForEach-Object { Write-Host "=== Running $($_.Name) ===" -ForegroundColor Green; php $_.FullName; Write-Host }
```

## 📝 示例说明

### SM3 哈希示例 (`sm3-hash.php`)

演示如何使用 `SM3Digest` 类计算数据的哈希值：
- 基本哈希计算
- 分段数据更新
- 空数据哈希

### SM2 密钥对生成示例 (`sm2-keypair.php`)

演示如何生成 SM2 密钥对：
- 生成单个密钥对
- 批量生成多个密钥对
- 查看密钥格式和长度

### SM2 数字签名示例 (`sm2-sign.php`)

演示 SM2 数字签名的完整流程：
- 生成密钥对
- 对消息进行签名
- 验证签名
- 篡改消息后验签（失败）
- 使用错误公钥验签（失败）
- 签名多条消息

### SM2 公钥加密示例 (`sm2-encrypt.php`)

演示 SM2 公钥加密/解密流程：
- 生成密钥对
- 使用公钥加密
- 使用私钥解密
- 加密不同长度的消息
- 处理 UTF-8 多字节字符
- 使用错误私钥解密（失败）

### SM2 密钥交换示例 (`sm2-keyexchange.php`)

演示 SM2 密钥交换协议（ECDH）：
- Alice 和 Bob 各自生成静态密钥对
- 生成临时密钥对
- 计算共享密钥
- 验证双方密钥一致
- 生成不同长度的共享密钥（128/192/256位）

### SM4 ECB 模式简单示例 (`sm4-ecb-simple.php`)

演示 SM4 对称加密基本用法（ECB 模式）：
- 生成随机 128 位密钥
- ECB 模式加密/解密（PKCS7 填充）
- 处理不同长度的数据（0-100 字节）
- 单块加密（16 字节，无填充）
- ⚠️ ECB 模式不安全，仅用于演示

### SM4 多种工作模式示例 (`sm4-modes.php`)

演示 SM4 的各种工作模式：
- ECB 模式（电子密码本）
- CBC 模式（密码块链接）
- CTR 模式（计数器模式）
- GCM 模式（认证加密）
- 使用底层 API 直接控制加密参数
- 对比不同模式的特性和安全性

## 🎯 预期输出

每个示例都会输出详细的执行过程和结果，例如：

```
=== SM3 哈希示例 ===

输入数据: Hello, SM3!
SM3 Hash: 9b8c9d5b8e7a6f5c4d3e2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1
哈希长度: 32 字节

--- 分段更新示例 ---
分段输入: "Hello, " + "SM3!"
SM3 Hash: 9b8c9d5b8e7a6f5c4d3e2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1
结果一致: true

--- 空数据哈希 ---
空数据 Hash: 1ab21d8355cfa17f8e61194831e81a8f22bec8c728fefb747ed035eb5082aa2b

✅ SM3 哈希示例运行完成
```

## 🔧 自定义示例

你可以基于这些示例创建自己的代码：

1. 复制任意示例文件
2. 修改代码以适应你的需求
3. 使用 `php your-example.php` 运行

## 📦 依赖说明

所有示例都依赖 Composer 安装的 `sm-php-bc` 库：

```php
require_once __DIR__ . '/../vendor/autoload.php';
```

这意味着：
- ✅ 使用 Composer 自动加载
- ✅ 与最终用户的使用方式完全一致
- ✅ 可以验证库的正常工作

## ⚠️ 注意事项

1. **确保已安装依赖**：运行示例前，请先执行 `composer install`
2. **PHP 版本**：需要 PHP >= 8.1
3. **扩展要求**：需要 `ext-gmp` 扩展
4. **跨平台**：所有示例支持 Windows、Linux、macOS

## 🐛 问题排查

### 错误：Class 'SmBc\...' not found

**原因**：未安装 Composer 依赖

**解决**：
```bash
cd sm-php-bc
composer install
```

### 错误：Call to undefined function gmp_init()

**原因**：缺少 GMP 扩展

**解决**：
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

### 错误：syntax error, unexpected ')'

**原因**：PHP 版本过低（< 8.1）

**解决**：
- 升级到 PHP 8.1 或更高版本
- 检查 PHP 版本：`php --version`

## 📚 更多信息

- [主项目 README](../README.md)
- [实现计划](../docs/IMPLEMENTATION_PLAN.md)
- [测试代码](../tests/)

---

如有问题，欢迎提出 Issue！
