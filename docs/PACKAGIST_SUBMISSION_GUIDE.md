# Packagist 提交指南

## 🎯 项目已准备就绪

✅ **所有准备工作已完成！**

- GitHub 仓库: https://github.com/lihongjie0209/sm-php-bc
- 版本标签: v0.1.0
- GitHub Release: https://github.com/lihongjie0209/sm-php-bc/releases/tag/v0.1.0
- 测试状态: ✅ 170 个测试全部通过

## 📋 提交到 Packagist 的详细步骤

### 第一步：登录 Packagist

1. 打开浏览器访问 https://packagist.org/
2. 如果还没有账户，点击 "Sign Up" 注册
3. 使用 GitHub 账号登录（推荐）或使用邮箱密码登录

### 第二步：提交包

1. 登录后，点击页面右上角的 **"Submit"** 按钮
   
2. 在表单中填写：
   - **Repository URL**: 
     ```
     https://github.com/lihongjie0209/sm-php-bc
     ```
   - 确保 URL 格式正确，包含 https:// 前缀

3. 点击 **"Check"** 按钮
   - Packagist 会验证仓库是否存在
   - 会检查 composer.json 是否有效
   - 会显示包的基本信息预览

4. 验证通过后，点击 **"Submit"** 按钮
   - 包会被添加到 Packagist
   - 你将被重定向到包的页面

### 第三步：配置自动更新

**选项 A：GitHub Webhook（推荐）**

这样每次推送到 GitHub 时，Packagist 会自动更新。

1. 在 Packagist 包页面，找到 **"API Token"** 部分
   - 复制显示的 token（类似：`abcd1234-5678-90ef-ghij-klmnopqrstuv`）

2. 打开 GitHub 仓库设置：
   ```
   https://github.com/lihongjie0209/sm-php-bc/settings/hooks
   ```

3. 点击 **"Add webhook"** 按钮

4. 填写表单：
   - **Payload URL**: 
     ```
     https://packagist.org/api/github?username=lihongjie0209
     ```
     （替换 `lihongjie0209` 为你的 Packagist 用户名）
   
   - **Content type**: 选择 `application/json`
   
   - **Secret**: 粘贴步骤 1 中复制的 Packagist API Token
   
   - **Which events would you like to trigger this webhook?**: 
     选择 **"Just the push event."**
   
   - 勾选 **"Active"**

5. 点击 **"Add webhook"** 按钮

6. 测试 webhook：
   - 在 webhook 列表中点击刚创建的 webhook
   - 点击 "Recent Deliveries" 标签
   - 检查是否有成功的请求（绿色勾号）

**选项 B：GitHub Action 自动同步**

项目已包含 `.github/workflows/packagist-sync.yml`。

1. 访问 GitHub 仓库的 Secrets 设置：
   ```
   https://github.com/lihongjie0209/sm-php-bc/settings/secrets/actions
   ```

2. 点击 **"New repository secret"**

3. 添加 Secret：
   - **Name**: `PACKAGIST_TOKEN`
   - **Value**: 你的 Packagist API Token
   - 点击 **"Add secret"**

4. 每次推送或发布新版本时，GitHub Action 会自动通知 Packagist 更新

### 第四步：验证发布

1. 等待几分钟，让 Packagist 处理包

2. 访问包页面：
   ```
   https://packagist.org/packages/lihongjie0209/sm-php-bc
   ```

3. 检查以下信息：
   - ✅ 版本号显示为 `v0.1.0`
   - ✅ 描述、关键词、作者信息正确
   - ✅ 依赖关系正确（PHP >= 8.1, ext-gmp）
   - ✅ License 显示为 MIT
   - ✅ 链接到 GitHub 仓库

4. 测试安装：
   ```bash
   # 创建测试目录
   mkdir test-sm-php-bc
   cd test-sm-php-bc
   
   # 初始化 composer
   composer init --no-interaction
   
   # 安装包
   composer require lihongjie0209/sm-php-bc
   
   # 验证安装
   composer show lihongjie0209/sm-php-bc
   ```

5. 创建测试脚本 `test.php`：
   ```php
   <?php
   require 'vendor/autoload.php';
   
   use SmBc\SM3Digest;
   
   $digest = new SM3Digest();
   $digest->update("Hello, SM3!", 0, 11);
   $hash = $digest->doFinal();
   
   echo "SM3 Hash: " . bin2hex($hash) . "\n";
   echo "✅ sm-php-bc 安装成功！\n";
   ```

6. 运行测试：
   ```bash
   php test.php
   ```

### 第五步：更新项目徽章（可选）

在 README.md 中添加 Packagist 徽章：

```markdown
[![Latest Stable Version](https://poser.pugx.org/lihongjie0209/sm-php-bc/v/stable)](https://packagist.org/packages/lihongjie0209/sm-php-bc)
[![Total Downloads](https://poser.pugx.org/lihongjie0209/sm-php-bc/downloads)](https://packagist.org/packages/lihongjie0209/sm-php-bc)
[![License](https://poser.pugx.org/lihongjie0209/sm-php-bc/license)](https://packagist.org/packages/lihongjie0209/sm-php-bc)
[![PHP Version Require](https://poser.pugx.org/lihongjie0209/sm-php-bc/require/php)](https://packagist.org/packages/lihongjie0209/sm-php-bc)
```

## 🎉 成功标志

当你看到以下内容时，说明发布成功：

1. ✅ Packagist 页面正常显示包信息
2. ✅ `composer require lihongjie0209/sm-php-bc` 可以正常安装
3. ✅ GitHub webhook 显示绿色勾号
4. ✅ GitHub Actions 运行成功
5. ✅ 包可以在 Packagist 搜索到

## 🔧 故障排除

### 问题 1: Packagist 显示 "Could not find package"

**解决方法**:
- 检查 GitHub 仓库是否公开
- 确认 composer.json 存在且格式正确
- 运行 `composer validate` 检查语法

### 问题 2: Webhook 失败

**解决方法**:
- 检查 API Token 是否正确
- 确认 webhook URL 格式正确
- 查看 webhook 的 "Recent Deliveries" 获取错误信息

### 问题 3: 版本未更新

**解决方法**:
- 确认已推送 git 标签：`git push origin v0.1.0`
- 在 Packagist 包页面手动点击 "Update" 按钮
- 等待几分钟让 Packagist 处理

### 问题 4: Composer 安装失败

**解决方法**:
```bash
# 清除缓存
composer clear-cache

# 使用详细模式查看错误
composer require lihongjie0209/sm-php-bc -vvv

# 检查 PHP 版本和扩展
php -v
php -m | grep gmp
```

## 📞 获取帮助

- **Packagist 文档**: https://packagist.org/about
- **Composer 文档**: https://getcomposer.org/doc/
- **GitHub Issues**: https://github.com/lihongjie0209/sm-php-bc/issues

## ✅ 检查清单

发布前确认：

- [ ] composer.json 中的信息完整准确
- [ ] LICENSE 文件存在
- [ ] README.md 详细且最新
- [ ] CHANGELOG.md 记录了版本变更
- [ ] 所有测试通过
- [ ] 代码已推送到 GitHub
- [ ] 版本标签已创建并推送
- [ ] GitHub Release 已创建

Packagist 提交后确认：

- [ ] 包在 Packagist 可见
- [ ] 版本号正确
- [ ] 可以通过 composer 安装
- [ ] Webhook 配置成功
- [ ] 测试安装功能正常

---

**祝发布顺利！** 🚀
