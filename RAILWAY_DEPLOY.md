# 独角数卡 Railway 快速部署指南

## 前置准备

1. 注册 [Railway](https://railway.app) 账号
2. 准备好项目代码仓库（GitHub/GitLab）
3. 准备一个 APP_KEY（本地运行 `php artisan key:generate --show` 获取）

## 部署步骤

### 第一步：创建 Railway 项目

1. 登录 Railway Dashboard
2. 点击 "New Project"
3. 选择 "Deploy from GitHub repo"
4. 选择你的独角数卡仓库

### 第二步：添加数据库服务

1. 在项目中点击 "New"
2. 选择 "Database"
3. 选择 "Add MySQL"
4. 等待 MySQL 服务创建完成

### 第三步：添加 Redis 服务

1. 在项目中点击 "New"
2. 选择 "Database"
3. 选择 "Add Redis"
4. 等待 Redis 服务创建完成

### 第四步：配置环境变量

在你的应用服务中，点击 "Variables" 标签，添加以下环境变量：

#### 必需变量

```
APP_NAME=独角数卡
APP_ENV=production
APP_KEY=base64:你的32位密钥
APP_DEBUG=false
APP_URL=https://your-app.railway.app
LOG_CHANNEL=stack
```

#### 数据库配置

Railway 会自动注入 MySQL 相关变量，你只需要添加：

```
DB_CONNECTION=mysql
DB_HOST=${{MYSQL.MYSQL_PRIVATE_URL}}
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=${{MYSQL.MYSQL_PASSWORD}}
```

#### Redis 配置

```
REDIS_HOST=${{REDIS.REDIS_PRIVATE_URL}}
REDIS_PASSWORD=${{REDIS.REDIS_PASSWORD}}
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

#### 缓存和队列配置

```
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=file
SESSION_LIFETIME=120
BROADCAST_DRIVER=log
```

#### 后台管理配置

```
DUJIAO_ADMIN_LANGUAGE=zh_CN
ADMIN_ROUTE_PREFIX=/admin
ADMIN_HTTPS=true
```

### 第五步：触发部署

1. 保存所有环境变量
2. Railway 会自动触发部署
3. 查看 "Deployments" 标签监控部署进度
4. 等待部署完成（首次部署约 3-5 分钟）

### 第六步：初始化数据库

部署成功后，需要初始化数据库：

1. 在 Railway 项目中，点击你的应用服务
2. 进入 "Settings" 标签
3. 找到并打开 "Shell"
4. 运行以下命令：

```bash
# 运行数据库迁移
php artisan migrate --force

# 运行数据填充（如果需要初始数据）
php artisan db:seed --force
```

### 第七步：访问应用

1. 在 "Settings" 中找到 "Networking" 部分
2. 点击 "Generate Domain" 生成公开域名
3. 或者绑定你自己的域名
4. 访问 `https://your-domain.railway.app/admin` 进入后台

## 常见问题

### 1. 如何获取 APP_KEY？

在本地项目目录运行：

```bash
php artisan key:generate --show
```

复制输出的密钥（包含 `base64:` 前缀）

### 2. 后台登录提示 0err

确保环境变量 `ADMIN_HTTPS=true`，因为 Railway 自动提供 HTTPS

### 3. 资源加载失败（404 或跨域错误）

检查 `APP_URL` 是否正确设置为你的 Railway 域名

### 4. 队列任务不执行

1. 确认 Redis 配置正确
2. 检查启动脚本是否正常运行
3. 在 Shell 中运行 `ps aux | grep queue` 检查队列进程

### 5. 数据库连接失败

1. 确保 MySQL 服务已启动
2. 检查数据库连接变量是否使用 Railway 提供的内部地址
3. 确认使用的是 `MYSQL_PRIVATE_URL` 而不是公网地址

### 6. 部署失败

1. 查看 "Deployments" 的日志找出错误原因
2. 确保 Dockerfile 存在且配置正确
3. 检查 railway-start.sh 脚本权限

### 7. 如何查看日志？

在 Railway Dashboard 中：
- 进入你的应用服务
- 点击 "Observability" 标签
- 查看实时日志输出

或在 Shell 中运行：
```bash
tail -f /app/storage/logs/laravel.log
tail -f /app/storage/logs/queue.log
```

### 8. 如何重新部署？

方式一：推送代码到 GitHub
- Railway 会自动检测并重新部署

方式二：手动触发
- 在 "Deployments" 中点击 "Redeploy"

### 9. 如何更新环境变量？

1. 进入 "Variables" 标签
2. 修改或添加变量
3. 保存后 Railway 会自动重新部署

## 性能优化建议

### 1. 使用 Redis 缓存

确保以下配置启用 Redis：
```
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2. 开启 Laravel 优化

这些已在 `railway-start.sh` 中自动执行：
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. 配置 CDN（可选）

将 `public/assets` 等静态资源上传到 CDN，加速访问

## 备份建议

### 数据库备份

在 Railway Shell 中运行：
```bash
php artisan backup:run
```

或使用 Railway 的自动备份功能（Pro 计划）

### 文件备份

定期备份 `storage` 目录的上传文件

## 监控和告警

1. 在 Railway Dashboard 查看资源使用情况
2. 设置告警通知（Pro 计划）
3. 使用第三方监控服务（如 UptimeRobot）监控应用可用性

## 成本估算

Railway 采用按使用量计费：
- Starter 计划：$5/月 + 使用量
- 每月 $5 包含 $5 的使用额度
- MySQL + Redis + Web 应用预计 $10-20/月

## 技术支持

- Railway 文档：https://docs.railway.app
- 独角数卡文档：查看项目 README.md
- Railway Discord：https://discord.gg/railway

## 更新日志

- 2024-01-XX：初始版本，支持 Railway 一键部署
- 自动化数据库迁移
- 集成队列处理
- 优化启动脚本

---

**祝部署顺利！** 如有问题，请查看 Railway 日志或在 GitHub 提交 Issue。
