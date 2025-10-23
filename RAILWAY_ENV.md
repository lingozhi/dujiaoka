# Railway 部署环境变量配置

## 必需的环境变量

请在 Railway 项目的 Variables 页面中添加以下环境变量：

### 1. 基础应用配置

```bash
# 应用名称
APP_NAME=独角数卡

# 环境模式（生产环境使用 production）
APP_ENV=production

# 应用密钥（32位随机字符串，可以用以下命令生成）
# php artisan key:generate --show
APP_KEY=base64:your-32-character-key-here

# 关闭调试模式（生产环境必须为 false）
APP_DEBUG=false

# 应用 URL（你的 Railway 域名，例如：https://your-app.railway.app）
APP_URL=https://your-app.railway.app
```

### 2. 数据库配置（MySQL）

Railway 会自动提供 MySQL 数据库的连接信息，你可以使用：

```bash
# 数据库类型
DB_CONNECTION=mysql

# 数据库主机（使用 Railway MySQL 插件提供的内部地址）
DB_HOST=${{MYSQL.MYSQL_PRIVATE_URL}}

# 数据库端口
DB_PORT=3306

# 数据库名
DB_DATABASE=railway

# 数据库用户名
DB_USERNAME=root

# 数据库密码（使用 Railway 提供的密码）
DB_PASSWORD=${{MYSQL.MYSQL_PASSWORD}}
```

**注意**：如果 Railway 提供了 `DATABASE_URL`，你也可以直接使用：
```bash
DATABASE_URL=${{MYSQL.DATABASE_URL}}
```

### 3. Redis 配置

Railway 可以添加 Redis 插件，添加后使用以下配置：

```bash
# Redis 主机（使用 Railway Redis 插件提供的内部地址）
REDIS_HOST=${{REDIS.REDIS_PRIVATE_URL}}

# Redis 密码（如果有）
REDIS_PASSWORD=${{REDIS.REDIS_PASSWORD}}

# Redis 端口
REDIS_PORT=6379

# Redis 数据库索引
REDIS_DB=0

# Redis 缓存数据库索引
REDIS_CACHE_DB=1
```

### 4. 缓存和队列配置

```bash
# 缓存驱动（推荐使用 redis）
CACHE_DRIVER=redis

# 队列连接（推荐使用 redis 异步处理）
QUEUE_CONNECTION=redis

# 会话驱动
SESSION_DRIVER=file

# 会话生命周期（分钟）
SESSION_LIFETIME=120

# 广播驱动
BROADCAST_DRIVER=log

# 日志通道
LOG_CHANNEL=stack
```

### 5. 后台管理配置

```bash
# 后台语言
# zh_CN = 简体中文
# zh_TW = 繁体中文
# en = 英文
DUJIAO_ADMIN_LANGUAGE=zh_CN

# 后台登录路径（建议修改为自定义路径增加安全性）
ADMIN_ROUTE_PREFIX=/admin

# 是否开启 HTTPS（Railway 自动提供 HTTPS，设置为 true）
ADMIN_HTTPS=true
```

### 6. 其他可选配置

```bash
# 邮件配置（如需发送邮件）
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"

# 极验证配置（如需使用极验证）
GEETEST_ID=your-geetest-id
GEETEST_KEY=your-geetest-key
```

## 快速配置步骤

### 方式一：使用 Railway CLI

1. 安装 Railway CLI：
```bash
npm i -g @railway/cli
```

2. 登录 Railway：
```bash
railway login
```

3. 关联项目：
```bash
railway link
```

4. 批量设置环境变量（创建 .env.railway 文件后执行）：
```bash
railway variables set $(cat .env.railway)
```

### 方式二：在 Railway Dashboard 中手动添加

1. 打开你的 Railway 项目
2. 进入项目的 Variables 标签页
3. 点击 "New Variable"
4. 逐个添加上述环境变量

## 重要提醒

1. **APP_KEY**：必须是一个 base64 编码的 32 字符密钥，可以本地运行 `php artisan key:generate --show` 生成
2. **APP_URL**：必须设置为你的 Railway 应用域名，否则会出现资源加载问题
3. **ADMIN_HTTPS**：Railway 自动提供 HTTPS，必须设置为 `true`
4. **DB_HOST / REDIS_HOST**：使用 Railway 的私有网络地址而非公网地址，性能更好且更安全
5. **数据库和 Redis**：需要在 Railway 项目中添加对应的插件服务

## 首次部署后的初始化

部署成功后，需要运行以下命令初始化数据库：

```bash
# 在 Railway 项目中打开 Shell，运行：
php artisan migrate --force
php artisan db:seed --force
```

## 队列处理

本项目需要队列处理异步任务，Railway 的启动脚本会自动启动队列 worker。
如果需要手动管理，可以在 Railway Shell 中运行：

```bash
php artisan queue:work --tries=3
```

## 遇到问题？

- 后台登录出现 0err：检查 `ADMIN_HTTPS` 是否设置为 `true`
- 资源加载失败：检查 `APP_URL` 是否正确设置为你的域名
- 队列任务不执行：确保 Redis 配置正确，并且队列 worker 正在运行
- 数据库连接失败：检查数据库配置，确保使用 Railway 提供的正确连接信息
