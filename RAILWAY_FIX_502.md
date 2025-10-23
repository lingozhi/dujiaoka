# Railway 502 错误修复指南

## 🔴 问题

- 部署后出现 502 错误
- 日志显示：`Command "db:show" is not defined`
- 数据库连接超时

## ✅ 解决方案

### 问题 1：数据库配置不正确

你当前的配置使用了 `${{RAILWAY_PRIVATE_DOMAIN}}`，但这个变量不是 Railway MySQL 插件提供的。

**正确的配置应该是：**

Railway 在添加 MySQL 和 Redis 插件后，会自动注入以下变量：
- `MYSQLHOST`
- `MYSQLPORT`
- `MYSQLDATABASE`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `REDISHOST`
- `REDISPORT`
- `REDISPASSWORD`

### 修复步骤

#### 步骤 1：修改数据库环境变量

在 Railway Variables 中，**删除或修改**以下变量：

**删除这些（错误的）：**
```
DB_HOST=${{RAILWAY_PRIVATE_DOMAIN}}
REDIS_HOST=${{RAILWAY_PRIVATE_DOMAIN}}
```

**改为（正确的）：**
```
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}

REDIS_HOST=${{REDISHOST}}
REDIS_PORT=${{REDISPORT}}
REDIS_PASSWORD=${{REDISPASSWORD}}
```

#### 步骤 2：推送代码更新

启动脚本已经修复了 `db:show` 命令的问题。

1. 提交并推送代码：
```bash
git add .
git commit -m "修复 Railway 启动脚本"
git push
```

2. Railway 会自动重新部署

#### 步骤 3：检查 Railway 变量

在 Railway Dashboard 中：

1. 进入你的 **MySQL 服务**
2. 查看 "Variables" 标签，确认有以下变量：
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

3. 进入你的 **Redis 服务**
4. 查看 "Variables" 标签，确认有以下变量：
   - `REDISHOST`
   - `REDISPORT`
   - `REDISPASSWORD`

5. 进入你的 **应用服务**
6. 在 "Variables" 标签中，确认数据库配置引用了这些变量

### 完整的环境变量列表（修复版）

打开 `RAILWAY_VARS_FIXED.txt` 文件，按照里面的配置重新设置。

关键变化：

**旧配置（错误）：**
```bash
DB_HOST=${{RAILWAY_PRIVATE_DOMAIN}}
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ
```

**新配置（正确）：**
```bash
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}
```

## 🔍 验证修复

### 1. 查看部署日志

在 Railway Deployments 中，应该看到：

```
🔍 等待数据库连接...
✅ 数据库连接成功！
📦 检测到首次部署
📊 运行数据库迁移...
✅ 数据库迁移完成
...
✨ 应用启动完成！
```

### 2. 如果仍然失败

在 Railway Shell 中手动测试数据库连接：

```bash
# 进入应用的 Shell

# 测试数据库连接
php artisan tinker
# 然后在 tinker 中运行：
DB::connection()->getPdo();
# 如果成功，会显示 PDO 对象

# 退出 tinker
exit

# 查看环境变量
echo $DB_HOST
echo $DB_DATABASE
echo $DB_USERNAME
```

### 3. 查看详细日志

```bash
# 查看 Laravel 日志
tail -f storage/logs/laravel.log

# 查看 Nginx 错误日志
tail -f /var/log/nginx/error.log

# 查看 PHP-FPM 错误日志
tail -f /var/log/php/error.log
```

## 🎯 快速检查清单

- [ ] 代码已推送（包含修复的 railway-start.sh）
- [ ] Railway Variables 中的 DB_HOST 改为 `${{MYSQLHOST}}`
- [ ] Railway Variables 中的 DB_PORT 改为 `${{MYSQLPORT}}`
- [ ] Railway Variables 中的 DB_DATABASE 改为 `${{MYSQLDATABASE}}`
- [ ] Railway Variables 中的 DB_USERNAME 改为 `${{MYSQLUSER}}`
- [ ] Railway Variables 中的 DB_PASSWORD 改为 `${{MYSQLPASSWORD}}`
- [ ] Railway Variables 中的 REDIS_HOST 改为 `${{REDISHOST}}`
- [ ] Railway Variables 中的 REDIS_PORT 改为 `${{REDISPORT}}`
- [ ] Railway Variables 中的 REDIS_PASSWORD 改为 `${{REDISPASSWORD}}`
- [ ] MySQL 服务正在运行
- [ ] Redis 服务正在运行
- [ ] 应用服务已重新部署

## 📸 Railway 配置截图示例

### MySQL 服务变量（自动生成，无需手动添加）

Railway 会自动提供：
```
MYSQLHOST=monorail.proxy.rlwy.net
MYSQLPORT=12345
MYSQLDATABASE=railway
MYSQLUSER=root
MYSQLPASSWORD=xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ
MYSQL_URL=mysql://root:xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ@...
```

### 应用服务变量（你需要添加的）

在你的应用服务中引用 MySQL 变量：
```
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}
```

**注意**：Railway 会自动将 `${{MYSQLHOST}}` 替换为实际的主机地址。

## ⚠️ 常见错误

### 错误 1：直接使用 RAILWAY_PRIVATE_DOMAIN

❌ **错误**：
```
DB_HOST=${{RAILWAY_PRIVATE_DOMAIN}}
```

这个变量是服务的通用域名，不是 MySQL 的专用地址。

✅ **正确**：
```
DB_HOST=${{MYSQLHOST}}
```

### 错误 2：硬编码密码

❌ **错误**：
```
DB_PASSWORD=xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ
```

如果 Railway 重新生成了密码，这个就失效了。

✅ **正确**：
```
DB_PASSWORD=${{MYSQLPASSWORD}}
```

### 错误 3：MySQL 和 Redis 服务未启动

确保在 Railway 项目中：
- 已添加 MySQL 数据库（Database → Add MySQL）
- 已添加 Redis 数据库（Database → Add Redis）
- 两个服务都显示为"Active"状态

## 🆘 仍然无法解决？

### 方案 A：使用 DATABASE_URL

如果变量引用有问题，可以尝试使用 Railway 的 DATABASE_URL：

```bash
DATABASE_URL=${{MYSQL_URL}}
```

然后修改 Laravel 配置解析这个 URL。

### 方案 B：查看完整的环境变量

在 Railway Shell 中：
```bash
env | grep -E "(MYSQL|REDIS|DB_)"
```

检查变量是否正确注入。

### 方案 C：重新创建服务

如果问题持续：
1. 备份数据
2. 删除 MySQL 和 Redis 服务
3. 重新添加
4. 重新配置环境变量

## 📞 获取帮助

如果以上步骤都无法解决：

1. 在 Railway Shell 运行：
```bash
php artisan config:clear
php artisan cache:clear
env | grep -E "(MYSQL|REDIS|DB_)" > /tmp/env.txt
cat /tmp/env.txt
```

2. 截图发送完整的部署日志
3. 截图 Railway Variables 配置页面

---

**按照这个指南修复后，应该可以正常部署了！**
