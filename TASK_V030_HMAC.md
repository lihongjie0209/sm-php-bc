# 任务：实现 v0.3.0 - HMAC-SM3 支持

**创建日期**: 2025-12-08
**参考版本**: sm-js-bc v0.4.0

## 📋 任务概述

实现 HMAC-SM3 消息认证码功能，与 sm-js-bc 和 Bouncy Castle Java 保持兼容。

## 🎯 实现目标

1. ✅ 实现 Mac 接口
2. ✅ 实现 HMac 类（支持任意 Digest）
3. ✅ 添加完整的单元测试
4. ✅ 添加使用示例
5. ✅ 更新文档

## 🔧 实现计划

### 阶段 1: 接口和类实现
- [ ] 创建 `src/Crypto/Mac.php` 接口
- [ ] 创建 `src/Crypto/Macs/HMac.php` 类
- [ ] 实现 HMAC 算法（RFC 2104）

### 阶段 2: 测试实现
- [ ] 创建 `tests/Unit/Crypto/Macs/HMacTest.php`
- [ ] 基础属性测试
- [ ] 初始化测试
- [ ] HMAC 计算测试
- [ ] 密钥长度处理测试
- [ ] 增量更新测试
- [ ] 边界条件测试

### 阶段 3: 示例和文档
- [ ] 创建 `examples/hmac_sm3_demo.php`
- [ ] 更新 CHANGELOG.md
- [ ] 更新 README.md
- [ ] 更新 COMPLETED_FEATURES.md

## 📚 参考实现

### sm-js-bc
- `src/crypto/Mac.ts` - Mac 接口
- `src/crypto/macs/HMac.ts` - HMac 实现
- `test/unit/crypto/macs/HMac.test.ts` - 测试用例

### Bouncy Castle Java
- `org.bouncycastle.crypto.Mac` - Mac 接口
- `org.bouncycastle.crypto.macs.HMac` - HMac 实现

## 🔄 进度跟踪

- **开始日期**: 2025-12-08
- **当前阶段**: 阶段 1 (接口和类实现)
- **总体进度**: 0%

## 📝 工作日志

### 2025-12-08
- 创建任务文档
- 开始实现 Mac 接口和 HMac 类
