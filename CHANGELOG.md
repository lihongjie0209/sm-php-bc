# Changelog

所有项目的重要更改都将记录在此文件中。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，
并且本项目遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

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

[0.1.0]: https://github.com/lihongjie0209/sm-php-bc/releases/tag/v0.1.0
