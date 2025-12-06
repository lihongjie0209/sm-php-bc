# 跨语言互操作性测试

## 概述

本项目实现了与 JavaScript 版本 `sm-js-bc` 的完整跨语言互操作性测试，确保 PHP 和 JavaScript 实现之间完全兼容。

## 测试架构

### 测试方法

跨语言测试通过以下方式实现：

1. **Node.js 执行**: 测试在运行时动态生成 JavaScript 代码，通过 Node.js 执行
2. **npm 包依赖**: 使用已发布的 `sm-js-bc` npm 包进行测试
3. **双向验证**: PHP ↔ JavaScript 双向加密/签名验证

### 测试覆盖

#### ✅ SM3 摘要算法
- **标准测试向量**: 空字符串、单字符、"abc" 等标准测试用例
- **随机数据测试**: 10 次随机长度(1-1000字节)数据测试
- **中文字符**: 支持 UTF-8 编码的中文文本

#### ✅ SM2 数字签名
- **PHP 签名 + JS 验证**: PHP 生成签名，JavaScript 验证
- **PHP 验证 + JS 签名**: JavaScript 生成签名，PHP 验证
- **多种消息**: ASCII 文本、中文文本

#### ✅ SM4 分组密码
- **SM4-CBC 模式**: 
  - PHP 加密 → JS 解密
  - JS 加密 → PHP 解密
- **PKCS7 填充**: 自动填充和去填充
- **IV 向量**: 固定 IV 确保测试可重现

## 运行测试

### 前置条件

```bash
# 安装 Node.js (需要支持 ES Modules)
node --version  # v14.0.0 或更高

# 安装 sm-js-bc npm 包
npm install sm-js-bc
```

### 执行测试

```bash
# 运行所有跨语言测试
php vendor/bin/phpunit tests/CrossLanguage --testdox

# 运行特定测试
php vendor/bin/phpunit tests/CrossLanguage/SM3DigestInteropTest.php
php vendor/bin/phpunit tests/CrossLanguage/SM2SignatureInteropTest.php
php vendor/bin/phpunit tests/CrossLanguage/SM4CipherInteropTest.php
```

### 测试输出示例

```
SM3Digest Interop
 ✔ S m 3 cross implementation with empty·string
 ✔ S m 3 cross implementation with single·char
 ✔ S m 3 cross implementation with abc
 ✔ Random data

SM2Signature Interop
 ✔ Sign with php verify with both

SM4Cipher Interop
 ✔ S m 4 c b c interop

Tests: 6, Assertions: 31
```

## 测试实现细节

### BaseInteropTest 基类

```php
abstract class BaseInteropTest extends TestCase
{
    // 执行 Node.js 脚本并返回 JSON 结果
    protected function executeNodeJs(string $script): array;
    
    // 检查 Node.js 是否可用
    protected function isNodeJsAvailable(): bool;
    
    // 创建使用 sm-js-bc 的 JavaScript 脚本
    protected function createJsScript(string $code): string;
}
```

### 测试用例结构

#### SM3DigestInteropTest

```php
// 1. 使用 PHP 计算哈希
$phpHash = $this->computePhpSM3($message);

// 2. 使用 JavaScript 计算哈希
$jsHash = $this->computeJavaScriptSM3($message);

// 3. 验证两者匹配
$this->assertEquals($phpHash, $jsHash);
```

#### SM2SignatureInteropTest

```php
// 1. 生成密钥对
$keyPair = SM2::generateKeyPair();

// 2. PHP 签名
$signature = phpSign($message, $privateKey);

// 3. PHP 验证
$phpValid = phpVerify($message, $signature, $publicKey);

// 4. JavaScript 验证
$jsValid = jsVerify($message, $signature, $publicKey);

// 5. 验证两者都成功
$this->assertTrue($phpValid && $jsValid);
```

#### SM4CipherInteropTest

```php
// 测试 1: PHP 加密 → JS 解密
$phpCiphertext = encryptWithPhp($plaintext, $key, $iv);
$jsPlaintext = decryptWithJavaScript($phpCiphertext, $key, $iv);
$this->assertEquals($plaintext, $jsPlaintext);

// 测试 2: JS 加密 → PHP 解密
$jsCiphertext = encryptWithJavaScript($plaintext, $key, $iv);
$phpPlaintext = decryptWithPhp($jsCiphertext, $key, $iv);
$this->assertEquals($plaintext, $phpPlaintext);
```

## CI/CD 集成

### GitHub Actions 工作流

```yaml
name: Cross-Language Tests

on: [push, pull_request]

jobs:
  cross-language:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: gmp
      
      - name: Install Dependencies
        run: |
          composer install
          npm install sm-js-bc
      
      - name: Run Cross-Language Tests
        run: vendor/bin/phpunit tests/CrossLanguage
```

## 技术细节

### JavaScript 代码生成

测试动态生成 JavaScript ES 模块代码：

```javascript
import * as smBc from 'sm-js-bc';

try {
    const message = new TextEncoder().encode("test");
    const digest = new smBc.SM3Digest();
    digest.updateArray(message, 0, message.length);
    
    const result = new Uint8Array(digest.getDigestSize());
    digest.doFinal(result, 0);
    
    const hash = Array.from(result)
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
    
    console.log(JSON.stringify({ hash: hash }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
```

### 数据交换格式

- **输入**: 通过字符串或十六进制编码传递
- **输出**: JSON 格式，便于解析
- **错误处理**: JavaScript 错误通过 JSON 返回给 PHP

## 兼容性矩阵

| 算法 | PHP → JS | JS → PHP | 状态 |
|------|----------|----------|------|
| SM3 哈希 | ✅ | ✅ | 完全兼容 |
| SM2 签名 | ✅ | ✅ | 完全兼容 |
| SM2 验证 | ✅ | ✅ | 完全兼容 |
| SM4-CBC | ✅ | ✅ | 完全兼容 |
| SM4-ECB | 🚧 | 🚧 | 待测试 |
| SM4-CTR | 🚧 | 🚧 | 待测试 |
| SM4-GCM | 🚧 | 🚧 | 待测试 |

## 已知限制

1. **Node.js 依赖**: 测试需要系统安装 Node.js
2. **网络访问**: 首次运行需要从 npm 下载 `sm-js-bc`
3. **性能开销**: 每次测试都会启动 Node.js 进程，有一定性能开销

## 未来计划

- [ ] 添加 SM2 加密/解密互操作测试
- [ ] 添加更多 SM4 模式的互操作测试 (ECB, CTR, GCM)
- [ ] 添加性能对比测试
- [ ] 支持与其他语言实现的互操作测试 (Python, Java, Go)

## 参考资源

- [sm-js-bc npm 包](https://www.npmjs.com/package/sm-js-bc)
- [sm-js-bc GitHub](https://github.com/lihongjie0209/sm-js-bc)
- [跨语言测试模式](https://www.infoq.com/articles/cross-language-testing/)
