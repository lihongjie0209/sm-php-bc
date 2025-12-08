# 任务：对齐 sm-js-bc v0.4.0 功能

**创建日期**: 2025-12-08
**参考版本**: https://github.com/lihongjie0209/sm-js-bc/tree/v0.4.0

## 📋 任务概述

将 sm-php-bc 的功能与 sm-js-bc v0.4.0 版本对齐，主要包括 API 一致性改进和新增测试套件。

## 🎯 主要目标

1. ✅ 实现 API 一致性改进，与 Bouncy Castle Java 保持一致
2. ✅ 添加 API 兼容性测试套件
3. ✅ 更新文档说明新增功能
4. ✅ 确保所有测试通过

## 📊 当前状态分析

### PHP 版本现状 (v0.1.1)
- ✅ SM2/SM3/SM4 核心功能完整
- ✅ 跨语言互操作测试已实现
- ✅ 基础 API 已实现
- ⚠️ 部分 API 一致性特性缺失

### JS 版本 v0.4.0 新增内容
根据 CHANGELOG.md 和 API_CONSISTENCY_AUDIT.md:

1. **SM3Digest 改进**
   - ✅ `reset()` 无参数版本 - PHP已实现
   - ✅ `reset(Memoable)` 方法重载 - PHP已实现
   
2. **SM2Engine 改进**
   - ❌ `SM2Engine::Mode` 静态属性（Java风格枚举访问）- **需要添加**
   
3. **SM2Signer 改进**
   - ❌ `createBasePointMultiplier()` 受保护方法 - **需要检查/添加**
   - ❌ `calculateE()` 受保护方法 - **需要检查/添加**
   - ⚠️ `hashToInteger()` 标记为已弃用 - **需要检查**

4. **测试套件**
   - ❌ API 兼容性测试套件 - **需要创建**

## 🔧 详细实现计划

### 阶段 1: 代码分析和准备 ✅
- [x] 克隆并分析 sm-js-bc v0.4.0 源码
- [x] 阅读 CHANGELOG.md 和 API 文档
- [x] 分析 API_CONSISTENCY_AUDIT.md 
- [x] 分析 API_IMPROVEMENTS.md
- [x] 检查 PHP 现有实现状态
- [x] 创建此任务文档

### 阶段 2: SM2Engine 改进
- [ ] **任务 2.1**: 添加 SM2Engine::Mode 静态属性
  - [ ] 保持向后兼容性
  - [ ] 添加常量别名支持 Java 风格访问
  - [ ] 更新文档说明用法
  
### 阶段 3: SM2Signer 改进
- [ ] **任务 3.1**: 检查并实现 createBasePointMultiplier()
  - [ ] 检查当前实现
  - [ ] 如需要，提取为受保护方法
  - [ ] 确保可被子类覆盖
  
- [ ] **任务 3.2**: 检查并实现 calculateE()
  - [ ] 检查当前实现
  - [ ] 如需要，提取为受保护方法
  - [ ] 确保可被子类覆盖
  
- [ ] **任务 3.3**: 检查 hashToInteger() 弃用状态
  - [ ] 如果存在，添加弃用注释
  - [ ] 建议使用 calculateE() 替代

### 阶段 4: 测试套件创建
- [ ] **任务 4.1**: 创建 APICompatibilityTest.php
  - [ ] SM3Digest::reset() 方法重载测试
  - [ ] SM2Engine::Mode 静态枚举访问测试
  - [ ] SM2Signer 受保护方法测试
  - [ ] 类型兼容性测试
  - [ ] API 方法命名一致性测试

### 阶段 5: 文档更新
- [ ] **任务 5.1**: 更新 CHANGELOG.md
  - [ ] 添加 v0.2.0 版本说明
  - [ ] 列出所有新增功能
  - [ ] 标记已弃用的方法
  
- [ ] **任务 5.2**: 更新 README.md
  - [ ] 更新版本号
  - [ ] 添加新功能说明
  - [ ] 更新兼容性说明
  
- [ ] **任务 5.3**: 创建 API_IMPROVEMENTS.md
  - [ ] 翻译 JS 版本的改进文档
  - [ ] 添加 PHP 特定的使用示例
  
- [ ] **任务 5.4**: 更新 COMPLETED_FEATURES.md
  - [ ] 更新完成度统计
  - [ ] 添加新功能说明

### 阶段 6: 测试和验证
- [ ] **任务 6.1**: 运行所有单元测试
  - [ ] 确保现有测试通过
  - [ ] 确保新测试通过
  
- [ ] **任务 6.2**: 运行跨语言互操作测试
  - [ ] 验证与 sm-js-bc v0.4.0 的兼容性
  
- [ ] **任务 6.3**: 代码审查
  - [ ] 检查代码风格
  - [ ] 检查文档完整性
  - [ ] 检查测试覆盖率

## 📈 预期成果

### 代码变更
- SM2Engine.php: 添加 Mode 静态属性支持
- SM2Signer.php: 添加/优化受保护方法
- tests/Unit/APICompatibilityTest.php: 新建测试文件

### 文档变更
- CHANGELOG.md: 新增 v0.2.0 版本记录
- README.md: 更新特性说明
- docs/API_IMPROVEMENTS.md: 新建文档
- COMPLETED_FEATURES.md: 更新完成状态

### 测试结果
- 所有单元测试通过 (包括新增的 API 兼容性测试)
- 跨语言互操作测试通过
- 代码覆盖率保持或提高

## 📚 参考资料

- sm-js-bc v0.4.0: https://github.com/lihongjie0209/sm-js-bc/tree/v0.4.0
- CHANGELOG: /tmp/sm-js-bc/CHANGELOG.md
- API_CONSISTENCY_AUDIT: /tmp/sm-js-bc/docs/API_CONSISTENCY_AUDIT.md
- API_IMPROVEMENTS: /tmp/sm-js-bc/docs/API_IMPROVEMENTS.md
- API Compatibility Tests: /tmp/sm-js-bc/test/unit/crypto/APICompatibility.test.ts

## 🔄 进度跟踪

- **开始日期**: 2025-12-08
- **当前阶段**: 阶段 1 (代码分析和准备) ✅
- **总体进度**: 15% (1/6 阶段完成)
- **预计完成**: 2025-12-08

## 📝 工作日志

### 2025-12-08 10:03
- ✅ 创建任务文档
- ✅ 分析 sm-js-bc v0.4.0 源码
- ✅ 识别需要对齐的功能点
- ✅ 制定详细实现计划

---

**注意**: 本文档需要在任务开始和完成时更新，以便后续开发参考。
