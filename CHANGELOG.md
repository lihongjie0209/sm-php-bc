# Changelog

所有项目的重要更改都将记录在此文件中。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，
并且本项目遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

## [0.3.0] - 2025-12-08

### 新增
- 🔐 **HMAC-SM3 支持** - 完整实现 HMAC 消息认证码（RFC 2104）
- ✅ `Mac` 接口 - 通用 MAC 算法接口
- ✅ `HMac` 类 - 支持任意 Digest 算法的 HMAC 实现
- 🧪 **完整测试覆盖** - 19 个测试用例，覆盖所有功能
- 📚 **使用示例** - examples/hmac_sm3_demo.php 包含 6 个实用场景

### 特性
- ✅ 支持任意长度密钥（超长密钥自动哈希）
- ✅ 支持增量更新（update/updateBytes）
- ✅ 支持重置和重用
- ✅ 完全兼容 Bouncy Castle Java 和 sm-js-bc
- ✅ 通过标准测试向量验证

### 测试
- 🧪 所有 200 个单元测试通过（605 个断言）
- ✅ 与 sm-js-bc 的测试向量一致

## [0.2.0] - 2025-12-08

### 新增
- 🎯 **API 一致性改进** - 与 Bouncy Castle Java 和 sm-js-bc v0.4.0 API 保持一致
- ✅ `SM2Engine::Mode` 常量数组 - 支持 Java 风格的枚举访问 (`SM2Engine::Mode['C1C2C3']`)
- ✅ `SM2Signer::calculateE()` 受保护方法 - 允许子类自定义 e 值计算
- ✅ `SM2Signer::createBasePointMultiplier()` 受保护方法 - 增强可扩展性（API 兼容）
- 🧪 **API 兼容性测试套件** - 11个新测试用例验证 API 一致性

### 改进
- 📚 完善 SM2Signer 的扩展性设计
- 🔒 所有 API 改进保持向后兼容

### 已弃用
- ⚠️ `SM2Signer::hashToInteger()` - 请使用 `calculateE()` 替代（将在 v1.0.0 移除）

### 文档
- 📄 新增 `docs/API_IMPROVEMENTS.md` - API 改进详细说明
- 📋 更新 `TASK_ALIGNMENT_V040.md` - 完整的功能对齐分析和规划

## [0.1.1] - 2025-12-06

### 新增
- 🔄 添加与 sm-js-bc 的跨语言互操作性测试
- 🧪 新增 Node.js 脚本自动化测试 PHP 与 JavaScript 实现的兼容性
- 🚀 完善 CI/CD 流水线，自动运行跨语言测试
- ✅ 测试覆盖 SM2、SM3、SM4 各种模式

### 改进
- 📋 优化测试结构，确保与 JS 版本测试用例一致
- 📝 改进文档，添加跨语言互操作性说明
- 🔧 增强 GitHub Actions 工作流

### 修复
- 🐛 修复 composer.json 中不应包含 version 字段的警告
- 🎨 优化代码结构和命名空间

## [0.1.0] - 2025-12-06

### 新增
- 🎉 首次发布
- ✅ SM2 椭圆曲线算法
  - 密钥对生成
  - 签名/验签
  - 加密/解密
  - 密钥交换
- ✅ SM3 密码杂凑算法
  - 标准哈希
  - HMAC 支持
  - KDF 密钥派生
- ✅ SM4 分组密码算法
  - ECB 模式
  - CBC 模式
  - CFB 模式
  - OFB 模式
  - CTR 模式
  - GCM 认证加密模式
- ✅ 多种填充方案
  - PKCS7
  - ISO7816-4
  - ISO10126
  - ZeroByte
- ✅ 完整的测试覆盖
- 📝 详细的中文文档
- 📖 丰富的使用示例

### 特性
- 🚀 纯 PHP 实现，无外部依赖
- 🔒 参考 Bouncy Castle 架构设计
- ✨ 完整的 PSR-4 自动加载
- 🧪 完善的单元测试
- 📦 支持 Composer 安装
- 🌐 与 JS 版本互操作兼容

[0.1.1]: https://github.com/lihongjie0209/sm-php-bc/releases/tag/v0.1.1
[0.1.0]: https://github.com/lihongjie0209/sm-php-bc/releases/tag/v0.1.0
