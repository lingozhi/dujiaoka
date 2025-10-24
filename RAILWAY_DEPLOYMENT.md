# Railway 部署指南

## 前置准备

1. 注册 [Railway](https://railway.app/) 账号
2. 安装 Railway CLI（可选）：`npm i -g @railway/cli`

## 部署步骤

### 方法一：通过 Railway 控制台部署（推荐）

1. **创建新项目**
   - 登录 Railway 控制台
   - 点击 "New Project"
   - 选择 "Deploy from GitHub repo"
   - 选择本项目的 GitHub 仓库

2. **配置 Dockerfile**
   - Railway 会自动检测 Dockerfile
   - 如果需要使用 Railway 专用配置，在项目设置中指定：
     - Dockerfile Path: `Dockerfile.railway`

3. **添加持久化存储卷**
   - 在项目设置中点击 "Volumes"
   - 点击 "New Volume"
   - 挂载路径设置为：`/data`
   - 这将确保数据在重新部署时不会丢失

4. **配置环境变量**

   在 Railway 控制台的 "Variables" 标签页添加以下环境变量：

   ```
   # 应用基础配置
   APP_NAME=独角数卡
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-app.railway.app  # 替换为实际域名

   # 数据库配置（推荐使用 Railway 提供的 MySQL）
   DB_CONNECTION=mysql
   DB_HOST=${{MYSQL.MYSQL_HOST}}        # Railway 自动注入
   DB_PORT=${{MYSQL.MYSQL_PORT}}
   DB_DATABASE=${{MYSQL.MYSQL_DATABASE}}
   DB_USERNAME=${{MYSQL.MYSQL_USER}}
   DB_PASSWORD=${{MYSQL.MYSQL_PASSWORD}}

   # 或使用 SQLite（适合小规模）
   # DB_CONNECTION=sqlite
   # DB_DATABASE=/data/database/database.sqlite

   # Redis 配置（可选，推荐使用 Railway 提供的 Redis）
   REDIS_HOST=${{REDIS.REDIS_HOST}}
   REDIS_PASSWORD=${{REDIS.REDIS_PASSWORD}}
   REDIS_PORT=${{REDIS.REDIS_PORT}}

   # 其他配置
   WEB_DOCUMENT_ROOT=/app/public
   TZ=Asia/Shanghai
   ```

5. **添加数据库服务（可选但推荐）**
   - 在同一项目中点击 "New Service"
   - 选择 "Database" → "Add MySQL" 或 "Add Redis"
   - Railway 会自动配置连接信息

6. **部署**
   - 点击 "Deploy" 按钮
   - 等待构建和部署完成
   - 访问 Railway 提供的 URL

### 方法二：通过 Railway CLI 部署

1. **登录 Railway**
   ```bash
   railway login
   ```

2. **初始化项目**
   ```bash
   cd /path/to/dujiaoka
   railway init
   ```

3. **添加持久化卷**
   ```bash
   railway volume add -m /data
   ```

4. **部署**
   ```bash
   # 使用 Railway 专用 Dockerfile
   railway up --dockerfile Dockerfile.railway
   ```

5. **配置环境变量**
   ```bash
   railway variables set APP_ENV=production
   railway variables set APP_URL=https://your-app.railway.app
   # ... 其他环境变量
   ```

## 持久化数据说明

Railway 的 `/data` 目录将持久化以下内容：

- `/data/.env` - 环境配置文件
- `/data/install.lock` - 安装锁文件
- `/data/uploads` - 上传的图片等资源
- `/data/storage` - 日志和缓存文件
- `/data/database` - SQLite 数据库文件（如使用 SQLite）

## 首次安装

1. 部署完成后，访问你的应用 URL
2. 系统会自动跳转到安装向导
3. 按照提示完成安装配置
4. 安装完成后，`install.lock` 文件会保存在 `/data` 目录，确保不会重复安装

## 常见问题

### 1. 数据库连接失败

检查环境变量是否正确配置，特别是 Railway 自动注入的数据库变量。

### 2. 文件上传后丢失

确保在 Railway 项目中已添加 Volume 并挂载到 `/data` 目录。

### 3. 权限问题

Dockerfile.railway 中已配置自动权限设置，如仍有问题，检查 `/data` 目录权限。

### 4. 如何查看日志

```bash
# 使用 Railway CLI
railway logs

# 或在 Railway 控制台的 "Deployments" 标签页查看
```

### 5. 如何连接到数据库

```bash
# 使用 Railway CLI
railway connect mysql
# 或
railway connect redis
```

## 性能优化建议

1. **使用 MySQL 而非 SQLite**
   - 在 Railway 中添加 MySQL 服务
   - 性能更好，适合生产环境

2. **配置 Redis 缓存**
   - 在 Railway 中添加 Redis 服务
   - 提升应用响应速度

3. **配置 CDN**
   - 将 `/data/uploads` 中的静态资源同步到 CDN
   - 减少服务器带宽消耗

4. **定期备份**
   - 定期备份 `/data` 目录
   - 使用 Railway 的备份功能

## 更新应用

1. 推送代码到 GitHub 仓库
2. Railway 会自动检测并重新部署
3. 持久化数据不会受影响

## 费用说明

- Railway 提供免费额度
- 超出免费额度后按使用量计费
- 建议查看 [Railway 定价](https://railway.app/pricing)

## 技术支持

- [Railway 文档](https://docs.railway.app/)
- [独角数卡官方文档](https://github.com/assimon/dujiaoka)
