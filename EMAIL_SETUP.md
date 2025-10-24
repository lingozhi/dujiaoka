# 邮件服务配置指南

## ✉️ 飞书邮箱配置信息

已配置的邮箱服务：
- **邮箱地址**: no-reply@opwan.ai
- **SMTP服务器**: smtp.feishu.cn
- **端口**: 465 (SSL)
- **加密方式**: SSL

## 📝 本地测试步骤

### 1. 清除配置缓存
```bash
php artisan config:clear
```

### 2. 发送测试邮件
```bash
# 将 your_email@example.com 替换为您的真实邮箱
php artisan email:test your_email@example.com
```

**示例**：
```bash
php artisan email:test test@gmail.com
```

### 3. 检查输出
成功的输出应该类似：
```
Testing email configuration...
SMTP Host: smtp.feishu.cn
SMTP Port: 465
SMTP Encryption: ssl
From Address: no-reply@opwan.ai

✓ Test email sent successfully to: your_email@example.com
Please check your inbox and spam folder.
```

## 🚀 Railway 环境配置

### 方法1：通过 Railway 控制台（推荐）

1. 打开 [Railway Dashboard](https://railway.app/dashboard)
2. 选择你的项目
3. 进入 "Variables" 标签
4. 添加以下环境变量：

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.feishu.cn
MAIL_PORT=465
MAIL_USERNAME=no-reply@opwan.ai
MAIL_PASSWORD=Y5H2MrTLzJfFUH0a
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=no-reply@opwan.ai
MAIL_FROM_NAME=独角数卡
```

5. 保存后，Railway 会自动重新部署

### 方法2：使用 Railway CLI

```bash
railway variables set MAIL_MAILER=smtp
railway variables set MAIL_HOST=smtp.feishu.cn
railway variables set MAIL_PORT=465
railway variables set MAIL_USERNAME=no-reply@opwan.ai
railway variables set MAIL_PASSWORD=Y5H2MrTLzJfFUH0a
railway variables set MAIL_ENCRYPTION=ssl
railway variables set MAIL_FROM_ADDRESS=no-reply@opwan.ai
railway variables set MAIL_FROM_NAME=独角数卡
```

## 🔧 在线测试（部署到 Railway 后）

### 使用 Railway CLI 连接到容器测试：

```bash
# 连接到 Railway 容器
railway run bash

# 在容器中执行测试
php artisan email:test your_email@example.com
```

### 或者通过管理后台测试：

1. 登录管理后台
2. 进入系统设置 → 邮件配置
3. 使用"发送测试邮件"功能

## ❗ 常见问题

### Q1: 收不到测试邮件？
**解决方案**：
1. 检查垃圾邮件文件夹
2. 确认邮箱服务器设置正确
3. 查看 Railway 日志：`railway logs`

### Q2: 报错 "Connection could not be established"
**解决方案**：
1. 确认端口 465 没有被防火墙阻止
2. 检查 MAIL_ENCRYPTION 设置为 `ssl`
3. 确认密码正确（专用密码，不是登录密码）

### Q3: 报错 "Authentication failed"
**解决方案**：
1. 确认使用的是专用密码：`Y5H2MrTLzJfFUH0a`
2. 确认邮箱地址：`no-reply@opwan.ai`
3. 检查飞书邮箱是否启用了 SMTP 服务

### Q4: 发送成功但收不到邮件
**解决方案**：
1. 检查邮件服务器日志
2. 确认收件人邮箱地址正确
3. 检查发件箱是否有发送记录
4. 联系飞书邮箱技术支持

## 📊 邮件发送日志

查看邮件发送日志：
```bash
# 本地
tail -f storage/logs/laravel.log

# Railway
railway logs --filter "mail"
```

## 🔐 安全建议

1. **不要提交 .env 文件到 Git**
   - .env 文件已在 .gitignore 中
   - 敏感信息应该只通过环境变量配置

2. **定期更换专用密码**
   - 在飞书邮箱管理后台重新生成
   - 同步更新 Railway 环境变量

3. **限制发送频率**
   - 避免被标记为垃圾邮件
   - 建议配置邮件队列

## 🎯 应用场景

配置好邮件服务后，系统会在以下场景自动发送邮件：

✅ 用户注册确认
✅ 订单支付成功通知
✅ 密码重置链接
✅ 卡密发货通知
✅ 系统异常告警

## 📞 技术支持

如遇到问题：
1. 查看 `storage/logs/laravel.log`
2. 检查 Railway 部署日志
3. 参考飞书邮箱文档：[飞书邮箱帮助中心](https://www.feishu.cn/hc/)

---

**注意**: 专用密码请妥善保管，不要泄露给他人！
