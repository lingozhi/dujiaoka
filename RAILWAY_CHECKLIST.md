# Railway 部署检查清单

## 📋 部署前检查

### 1️⃣ 准备工作

- [ ] 已注册 Railway 账号
- [ ] 代码已推送到 GitHub/GitLab
- [ ] 已准备好 APP_KEY：`base64:hDVkYhfkUjaePiaI1tcBT7G8bh2A8RQxwWIGkq7BO18=`
- [ ] 已阅读 `RAILWAY_DEPLOY.md` 部署指南

### 2️⃣ 创建 Railway 项目

- [ ] 在 Railway 创建新项目
- [ ] 选择 "Deploy from GitHub repo"
- [ ] 选择独角数卡仓库
- [ ] 添加 MySQL 数据库服务
- [ ] 添加 Redis 数据库服务

### 3️⃣ 配置环境变量（共 22 个）

#### 基础配置（7 个）

- [ ] `APP_NAME=独角数卡`
- [ ] `APP_ENV=production`
- [ ] `APP_KEY=base64:hDVkYhfkUjaePiaI1tcBT7G8bh2A8RQxwWIGkq7BO18=`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://dujiaoka-production-1f88.up.railway.app`
- [ ] `LOG_CHANNEL=stack`
- [ ] `ADMIN_HTTPS=true` ⚠️ **重要！**

#### 数据库配置（6 个）

- [ ] `DB_CONNECTION=mysql`
- [ ] `DB_HOST=${{RAILWAY_PRIVATE_DOMAIN}}`
- [ ] `DB_PORT=3306`
- [ ] `DB_DATABASE=railway`
- [ ] `DB_USERNAME=root`
- [ ] `DB_PASSWORD=xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ`

#### Redis 配置（3 个）

- [ ] `REDIS_HOST=${{RAILWAY_PRIVATE_DOMAIN}}`
- [ ] `REDIS_PASSWORD=uyHHIOPBMBaysbXxKIhxVNgzZTbqONEs`
- [ ] `REDIS_PORT=6379`

#### 缓存和队列配置（4 个）

- [ ] `CACHE_DRIVER=redis`
- [ ] `QUEUE_CONNECTION=redis`
- [ ] `SESSION_DRIVER=file`
- [ ] `SESSION_LIFETIME=120`

#### 后台管理配置（2 个）

- [ ] `DUJIAO_ADMIN_LANGUAGE=zh_CN`
- [ ] `ADMIN_ROUTE_PREFIX=/admin`

#### 其他配置（1 个）

- [ ] `BROADCAST_DRIVER=log`

---

## 🚀 部署检查

### 4️⃣ 触发部署

- [ ] 保存所有环境变量
- [ ] Railway 自动开始部署
- [ ] 在 "Deployments" 标签查看部署进度
- [ ] 等待部署成功（约 3-5 分钟）

### 5️⃣ 检查部署日志

在 Railway 的 Observability 或 Deployments 日志中，确认看到以下输出：

- [ ] `✅ 数据库连接成功！`
- [ ] `📦 检测到首次部署` 或 `♻️ 检测到更新部署`
- [ ] `✅ 数据库迁移完成`
- [ ] `✅ 队列处理器已启动`
- [ ] `✨ 应用启动完成！`

### 6️⃣ 生成访问域名

- [ ] 在 Settings → Networking 中生成域名
- [ ] 或绑定自定义域名
- [ ] 域名格式：`https://xxx.up.railway.app`

---

## ✅ 部署后验证

### 7️⃣ 访问测试

- [ ] 访问前台：`https://dujiaoka-production-1f88.up.railway.app`
- [ ] 访问后台：`https://dujiaoka-production-1f88.up.railway.app/admin`
- [ ] 页面样式正常加载（无 404 错误）
- [ ] 无跨域错误
- [ ] 无 HTTPS 相关错误

### 8️⃣ 功能测试

- [ ] 后台可以正常登录（无 0err 错误）
- [ ] 首页可以正常显示
- [ ] 图片和静态资源正常加载
- [ ] 表单提交正常工作

### 9️⃣ 性能检查

在 Railway Shell 中运行以下命令检查：

```bash
# 检查队列进程
ps aux | grep queue

# 查看应用日志
tail -f storage/logs/laravel.log

# 查看队列日志
tail -f storage/logs/queue.log

# 检查缓存
php artisan cache:clear
php artisan config:cache
```

- [ ] 队列进程正在运行
- [ ] 日志无严重错误
- [ ] 缓存工作正常

---

## 🔧 常见问题排查

### ❌ 部署失败

**问题**：Docker 构建失败

**检查**：
- [ ] 查看 Deployments 日志中的错误信息
- [ ] 确认 Dockerfile 文件存在
- [ ] 确认 railway-start.sh 文件存在

**问题**：数据库连接失败

**检查**：
- [ ] MySQL 服务是否已启动
- [ ] `DB_HOST` 是否使用 `${{RAILWAY_PRIVATE_DOMAIN}}`
- [ ] 数据库密码是否正确

**问题**：启动脚本执行失败

**检查**：
- [ ] 查看日志中具体错误
- [ ] 确认 railway-start.sh 有执行权限
- [ ] 确认所有必需的环境变量已设置

### ⚠️ 后台登录 0err

**解决方案**：
- [ ] 确认 `ADMIN_HTTPS=true` 已设置
- [ ] 清除浏览器缓存
- [ ] 检查 `APP_URL` 是否正确

### ⚠️ 资源加载 404

**解决方案**：
- [ ] 确认 `APP_URL` 与实际域名一致
- [ ] 运行 `php artisan storage:link`
- [ ] 检查 public 目录权限

### ⚠️ 队列任务不执行

**解决方案**：
- [ ] 确认 Redis 配置正确
- [ ] 在 Shell 中运行 `ps aux | grep queue` 检查进程
- [ ] 查看 `storage/logs/queue.log`

---

## 📊 监控建议

### 日常监控

- [ ] 定期查看 Railway Dashboard 的资源使用情况
- [ ] 设置 UptimeRobot 等服务监控应用可用性
- [ ] 定期备份数据库

### 日志管理

在 Railway 中查看日志：
```bash
# 实时日志
railway logs

# 或在 Shell 中
tail -f storage/logs/laravel.log
```

---

## 🎯 下一步优化

部署成功后，可以考虑：

- [ ] 绑定自定义域名
- [ ] 配置 CDN 加速静态资源
- [ ] 设置自动备份策略
- [ ] 配置邮件服务
- [ ] 添加监控告警
- [ ] 配置 SSL 证书（Railway 自动提供）

---

## 📞 获取帮助

- Railway 文档：https://docs.railway.app
- Railway Discord：https://discord.gg/railway
- 查看项目日志排查问题

---

**祝部署成功！🎉**

完成以上所有检查项后，你的独角数卡应用就可以正常运行了。
