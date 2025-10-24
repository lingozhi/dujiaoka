# 邮件配置调试指南

## 🎯 最新修复说明

已经在代码中**硬编码**了 Feishu 邮箱配置作为最终兜底，**理论上现在即使没有任何环境变量也能发送邮件**！

## 🔍 调试步骤

### 1. 部署完成后，先运行调试命令

在 Railway 控制台执行：

```bash
railway run php artisan mail:debug-config
```

这个命令会显示：
- ✅ 管理后台缓存配置
- ✅ Config 函数返回值
- ✅ 环境变量实际值
- ✅ MailSend.php 最终使用的配置
- ✅ .env 文件检查

**输出示例：**
```
=== 邮件配置调试信息 ===

【1. 管理后台缓存配置】
❌ 缓存为空

【2. Config 函数返回值】
✓ host: smtp.feishu.cn
✓ port: 465
✓ username: no-reply@opwan.ai
✓ password: ***已设置***

【4. MailSend.php 实际使用的配置】
✓ host: smtp.feishu.cn
✓ port: 465
✓ username: no-reply@opwan.ai
✓ password: ***已设置***
✓ encryption: ssl

✅ 配置检查通过！邮件应该可以发送。
```

### 2. 发送测试邮件

```bash
railway run php artisan email:test your_email@example.com
```

### 3. 如果还是失败，查看错误日志

```bash
railway logs --filter "mail"
```

## 🛠️ 新版本配置机制

### MailSend.php 的三层后备逻辑

```php
// 优先级 1: 管理后台缓存配置
if (!empty($sysConfig['host'])) {
    return $sysConfig['host'];
}

// 优先级 2: Config 配置（从环境变量）
$configValue = config('mail.host');
if (!empty($configValue)) {
    return $configValue;
}

// 优先级 3: 硬编码的 Feishu 邮箱配置
return 'smtp.feishu.cn';
```

**关键改进：**
- ✅ 使用 `!empty()` 严格检查，不会接受空字符串
- ✅ 硬编码配置在代码中，不依赖任何外部配置
- ✅ 即使 Railway 环境变量未设置，也能正常工作

## ❓ 常见问题

### Q1: 为什么还是提示 "host" 为空？

**可能原因：**
1. 旧代码还在运行（Railway 部署未完成）
2. Laravel 配置缓存未清除

**解决方法：**
```bash
# 在 Railway 容器中执行
railway run php artisan config:clear
railway run php artisan cache:clear
railway run php artisan mail:debug-config
```

### Q2: 如何确认新代码已部署？

查看 Railway Dashboard：
- 最新部署的 commit hash 应该是 `fd2ef36`
- 部署状态应该是 "Success"

### Q3: 邮件发送成功但收不到？

检查：
1. 垃圾邮件文件夹
2. 邮箱地址是否正确
3. Feishu 邮箱服务是否有发送限制

## 📧 硬编码的邮件配置

代码中已包含以下配置（app/Jobs/MailSend.php:63-72）：

```php
$defaults = [
    'driver' => 'smtp',
    'host' => 'smtp.feishu.cn',
    'port' => '465',
    'username' => 'no-reply@opwan.ai',
    'password' => 'Y5H2MrTLzJfFUH0a',
    'encryption' => 'ssl',
    'from_address' => 'no-reply@opwan.ai',
    'from_name' => '独角数卡'
];
```

这些配置会在其他方式都失败时自动使用。

## 🚨 连接超时问题

如果出现 `Connection timed out` 错误，说明配置已正确加载，但**网络连接失败**。

### 步骤 1: 测试网络连接

```bash
railway run php artisan mail:test-connection smtp.feishu.cn --port=465
```

这个命令会测试：
- ✅ DNS 解析
- ✅ TCP 连接
- ✅ SSL 连接
- ✅ 其他常用 SMTP 端口 (25, 587, 465, 2525)

### 步骤 2: 尝试不同的端口和加密方式

**方案 1: 端口 587 + TLS**（推荐）

```bash
railway run php artisan mail:test-flexible your_email@example.com --port=587 --encryption=tls
```

**方案 2: 端口 25 + TLS**

```bash
railway run php artisan mail:test-flexible your_email@example.com --port=25 --encryption=tls
```

**方案 3: 端口 465 + SSL**（当前配置）

```bash
railway run php artisan mail:test-flexible your_email@example.com --port=465 --encryption=ssl
```

### 步骤 3: 找到可用配置后更新环境变量

如果某个配置可以发送成功，在 Railway 控制台更新环境变量：

```
MAIL_HOST=smtp.feishu.cn
MAIL_PORT=587          # 使用测试成功的端口
MAIL_ENCRYPTION=tls    # 使用测试成功的加密方式
MAIL_USERNAME=no-reply@opwan.ai
MAIL_PASSWORD=Y5H2MrTLzJfFUH0a
```

### 可能的问题和解决方案

#### 问题 1: Railway 防火墙阻止 SMTP 端口

**症状:** 所有端口都连接超时

**解决方案:**
1. 使用第三方邮件服务（如 SendGrid, Mailgun, Amazon SES）
2. 这些服务提供 API 方式发送邮件，不依赖 SMTP 端口

#### 问题 2: Feishu SMTP 限制 IP 地址

**症状:** 连接拒绝或超时

**解决方案:**
1. 检查 Feishu 邮箱设置，确认 SMTP 已启用
2. 查看是否有 IP 白名单限制
3. 尝试使用其他邮箱服务

#### 问题 3: 用户名或密码错误

**症状:** `Authentication failed`

**解决方案:**
1. 确认 `no-reply@opwan.ai` 的密码是否正确
2. 确认 Feishu 是否需要应用专用密码
3. 检查账号是否被锁定

## 📝 调试命令汇总

```bash
# 1. 检查配置加载
railway run php artisan mail:debug-config

# 2. 测试网络连接
railway run php artisan mail:test-connection smtp.feishu.cn --port=465

# 3. 测试不同配置发送邮件
railway run php artisan mail:test-flexible your@email.com --port=587 --encryption=tls

# 4. 使用当前配置发送测试邮件
railway run php artisan email:test your@email.com
```
