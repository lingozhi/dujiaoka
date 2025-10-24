# 备选邮件服务配置指南

## ❌ 问题说明

Feishu SMTP (`smtp.feishu.cn`) 在 Railway 上无法连接，原因可能是：
1. Railway 限制了 SMTP 端口访问
2. Feishu SMTP 服务器限制了 Railway 的 IP 地址
3. 网络路由问题

## ✅ 推荐解决方案

以下是三个**免费且可靠**的邮件服务，在 Railway 上都能正常工作：

---

## 方案 1: Resend（推荐⭐⭐⭐⭐⭐）

**优点：**
- ✅ 专为开发者设计，简单易用
- ✅ 免费额度：每月 3000 封
- ✅ 支持自定义域名
- ✅ 在 Railway 上完美工作

**配置步骤：**

### 1. 注册 Resend
访问：https://resend.com/signup

### 2. 获取 API Key
- 登录后进入 "API Keys" 页面
- 点击 "Create API Key"
- 复制生成的 API Key（以 `re_` 开头）

### 3. 在 Railway 设置环境变量

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxxxxxxxxxxxxxxxxxxx  # 你的 API Key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=onboarding@resend.dev  # 或你验证的域名邮箱
MAIL_FROM_NAME=独角数卡
```

### 4. 测试
```bash
railway run php artisan mail:test-flexible your@email.com --host=smtp.resend.com --port=587 --encryption=tls --username=resend --password=re_your_api_key
```

---

## 方案 2: Gmail（免费⭐⭐⭐⭐）

**优点：**
- ✅ 完全免费
- ✅ 稳定可靠
- ✅ 每天最多 500 封邮件

**配置步骤：**

### 1. 启用两步验证
1. 访问：https://myaccount.google.com/security
2. 启用"两步验证"

### 2. 生成应用专用密码
1. 访问：https://myaccount.google.com/apppasswords
2. 选择"应用"："邮件"
3. 选择"设备"："其他（自定义名称）"，输入"Railway"
4. 点击"生成"
5. 复制 16 位密码（格式：xxxx xxxx xxxx xxxx）

### 3. 在 Railway 设置环境变量

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx  # 应用专用密码
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME=独角数卡
```

### 4. 测试
```bash
railway run php artisan mail:test-flexible your@email.com --host=smtp.gmail.com --port=587 --encryption=tls --username=your_email@gmail.com --password="your_app_password"
```

---

## 方案 3: SendGrid（专业⭐⭐⭐⭐⭐）

**优点：**
- ✅ 免费额度：每天 100 封（永久免费）
- ✅ 专业的邮件服务
- ✅ 送达率高
- ✅ 详细的发送统计

**配置步骤：**

### 1. 注册 SendGrid
访问：https://signup.sendgrid.com/

### 2. 验证邮箱地址
1. 登录后进入 Settings → Sender Authentication
2. 点击 "Verify a Single Sender"
3. 填写发件人信息（可以用 no-reply@yourdomain.com）
4. 点击验证邮件中的链接

### 3. 创建 API Key
1. 进入 Settings → API Keys
2. 点击 "Create API Key"
3. 选择 "Restricted Access"
4. 在 "Mail Send" 权限中选择 "Full Access"
5. 复制 API Key（以 `SG.` 开头）

### 4. 在 Railway 设置环境变量

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey  # 固定值，就是字面上的 "apikey"
MAIL_PASSWORD=SG.xxxxxxxxxxxxxxxxxxxx  # 你的 SendGrid API Key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_verified_email@example.com
MAIL_FROM_NAME=独角数卡
```

### 5. 测试
```bash
railway run php artisan mail:test-flexible your@email.com --host=smtp.sendgrid.net --port=587 --encryption=tls --username=apikey --password=SG.your_api_key
```

---

## 方案 4: Mailgun（备选）

**优点：**
- ✅ 免费额度：每月 5000 封（前 3 个月）
- ✅ 专业级服务

**配置步骤：**

### 1. 注册
访问：https://signup.mailgun.com/new/signup

### 2. 获取 SMTP 凭证
1. 进入 Sending → Domain settings
2. 找到 "SMTP credentials" 部分
3. 创建 SMTP 用户

### 3. 配置环境变量

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-sandbox-domain.mailgun.org
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=postmaster@your-sandbox-domain.mailgun.org
MAIL_FROM_NAME=独角数卡
```

---

## 🚀 快速测试命令

等 Railway 部署完成后，测试任一方案：

```bash
# Resend
railway run php artisan mail:test-flexible your@email.com --host=smtp.resend.com --port=587 --encryption=tls --username=resend --password=re_your_key

# Gmail
railway run php artisan mail:test-flexible your@email.com --host=smtp.gmail.com --port=587 --encryption=tls --username=your@gmail.com --password="your_app_password"

# SendGrid
railway run php artisan mail:test-flexible your@email.com --host=smtp.sendgrid.net --port=587 --encryption=tls --username=apikey --password=SG.your_key

# Mailgun
railway run php artisan mail:test-flexible your@email.com --host=smtp.mailgun.org --port=587 --encryption=tls --username=postmaster@sandbox.mailgun.org --password=your_password
```

---

## 📊 服务对比

| 服务 | 免费额度 | 难度 | 推荐度 | 备注 |
|------|---------|------|--------|------|
| **Resend** | 3000/月 | ⭐ | ⭐⭐⭐⭐⭐ | 最简单，开发者友好 |
| **Gmail** | 500/天 | ⭐⭐ | ⭐⭐⭐⭐ | 完全免费，需要应用密码 |
| **SendGrid** | 100/天 | ⭐⭐ | ⭐⭐⭐⭐⭐ | 专业，送达率高 |
| **Mailgun** | 5000/月 | ⭐⭐⭐ | ⭐⭐⭐ | 免费期 3 个月 |

---

## 💡 我的建议

**如果你的需求：**

### 1. 简单快速上线 → **Resend**
- 注册即用，无需复杂配置
- 免费额度充足（3000/月）

### 2. 完全免费 → **Gmail**
- 适合个人项目
- 每天 500 封足够用

### 3. 专业商用 → **SendGrid**
- 专业的邮件服务
- 详细的统计和日志
- 送达率更高

---

## ⚠️ 重要提示

1. **不要将密码提交到 Git**：所有密码都应该只配置在 Railway 环境变量中
2. **测试成功后更新硬编码配置**：在 `app/Jobs/MailSend.php` 中更新默认值
3. **选择一个方案后就固定使用**：不要频繁切换邮件服务

---

## 🆘 需要帮助？

如果某个方案测试成功，告诉我，我会帮你：
1. 更新代码中的硬编码配置
2. 更新文档
3. 确保配置在 Railway 重启后仍然有效
