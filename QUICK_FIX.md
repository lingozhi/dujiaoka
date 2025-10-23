# 快速修复 - 使用公网连接

## ✅ 方案：直接使用 Railway 公网地址

这是最简单的方式，不需要变量引用，直接填写具体的连接信息。

## 📋 第 1 步：获取 Redis 公网地址

### 方法 A：通过 Railway Dashboard

1. 在 Railway 项目中，点击 **Redis** 服务
2. 进入 **Settings** 标签
3. 向下滚动找到 **Networking** 部分
4. 点击 **Enable Public Networking**（如果还没开启）
5. 复制显示的公网地址，格式类似：
   ```
   xxxxx.proxy.rlwy.net:12345
   ```

### 方法 B：通过 Variables 查看

1. 点击 Redis 服务
2. 进入 **Variables** 标签
3. 找到类似这些的变量：
   - `REDIS_PUBLIC_URL`
   - 或 `REDIS_URL`（包含 proxy.rlwy.net 的那个）

示例：
```
redis://default:uyHHIOPBMBaysbXxKIhxVNgzZTbqONEs@monorail.proxy.rlwy.net:23456
```

从这个 URL 中提取：
- 主机：`monorail.proxy.rlwy.net`
- 端口：`23456`

## 📝 第 2 步：在 Railway 应用中配置环境变量

使用以下配置（直接复制粘贴）：

### 基础配置（不用改）
```
APP_NAME=独角数卡
APP_ENV=production
APP_KEY=base64:hDVkYhfkUjaePiaI1tcBT7G8bh2A8RQxwWIGkq7BO18=
APP_DEBUG=false
APP_URL=https://dujiaoka-production-1f88.up.railway.app
LOG_CHANNEL=stack
```

### 数据库配置（使用你的 MySQL 公网地址）
```
DB_CONNECTION=mysql
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=59325
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ
```

### Redis 配置（替换为你的 Redis 公网地址）
```
REDIS_HOST=【你的 Redis 主机，如：monorail.proxy.rlwy.net】
REDIS_PORT=【你的 Redis 端口，如：23456】
REDIS_PASSWORD=uyHHIOPBMBaysbXxKIhxVNgzZTbqONEs
```

### 其他配置（不用改）
```
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=file
SESSION_LIFETIME=120
BROADCAST_DRIVER=log
DUJIAO_ADMIN_LANGUAGE=zh_CN
ADMIN_ROUTE_PREFIX=/admin
ADMIN_HTTPS=true
```

## 🚀 第 3 步：保存并重新部署

1. 在 Railway 应用的 Variables 页面，确认所有变量都已添加
2. 保存后，Railway 会自动重新部署
3. 等待部署完成（约 2-3 分钟）

## ✅ 第 4 步：验证部署

在部署日志中，应该看到：

```
==========================================
🔍 数据库配置调试信息
==========================================
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=59325
...
🔍 等待数据库连接...
✅ 数据库连接成功！
📊 运行数据库迁移...
✅ 数据库迁移完成
...
✨ 应用启动完成！
```

## 📊 完整的环境变量列表

复制以下内容，逐个添加到 Railway（记得替换 Redis 地址）：

| 变量名 | 值 |
|--------|---|
| APP_NAME | 独角数卡 |
| APP_ENV | production |
| APP_KEY | base64:hDVkYhfkUjaePiaI1tcBT7G8bh2A8RQxwWIGkq7BO18= |
| APP_DEBUG | false |
| APP_URL | https://dujiaoka-production-1f88.up.railway.app |
| LOG_CHANNEL | stack |
| DB_CONNECTION | mysql |
| DB_HOST | yamanote.proxy.rlwy.net |
| DB_PORT | 59325 |
| DB_DATABASE | railway |
| DB_USERNAME | root |
| DB_PASSWORD | xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ |
| REDIS_HOST | 【你的 Redis 主机】 |
| REDIS_PORT | 【你的 Redis 端口】 |
| REDIS_PASSWORD | uyHHIOPBMBaysbXxKIhxVNgzZTbqONEs |
| CACHE_DRIVER | redis |
| QUEUE_CONNECTION | redis |
| SESSION_DRIVER | file |
| SESSION_LIFETIME | 120 |
| BROADCAST_DRIVER | log |
| DUJIAO_ADMIN_LANGUAGE | zh_CN |
| ADMIN_ROUTE_PREFIX | /admin |
| ADMIN_HTTPS | true |

**共 22 个变量**

## ⚠️ 注意事项

### 1. 公网 vs 内网

- ✅ **优点**：配置简单，不需要变量引用
- ⚠️ **缺点**：使用公网连接，速度比内网稍慢
- 💡 **建议**：先用公网跑起来，以后再优化

### 2. 端口号可能变化

Railway 的公网端口可能会变化（虽然不常见）。如果以后突然连不上数据库：
1. 重新检查 MySQL/Redis 的公网地址
2. 更新环境变量中的端口号

### 3. 如果找不到 Redis 公网地址

执行以下步骤启用：

1. 点击 Redis 服务
2. 进入 Settings
3. 找到 Networking
4. 勾选 "Enable Public Networking"
5. 复制生成的地址

## 🎯 下一步

告诉我：
1. ✅ 你找到的 Redis 公网地址是什么？
2. ✅ 配置完成后，部署日志是否显示"数据库连接成功"？

找到 Redis 地址后，我帮你确认配置是否正确！
