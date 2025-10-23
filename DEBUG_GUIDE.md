# Railway 数据库连接调试指南

## 🔍 第一步：推送代码并查看调试信息

我已经在启动脚本中添加了详细的调试信息。

### 1. 推送代码

```bash
git push
```

### 2. 等待 Railway 重新部署

- 进入 Railway Dashboard
- 进入 Deployments 标签
- 等待部署开始

### 3. 查看部署日志，找到调试信息区域

在日志中找到这个部分：

```
==========================================
🔍 数据库配置调试信息
==========================================
DB_CONNECTION=mysql
DB_HOST=xxxxx
DB_PORT=xxxxx
DB_DATABASE=xxxxx
DB_USERNAME=xxxxx
DB_PASSWORD=xxxxx***（已隐藏）

Redis 配置：
REDIS_HOST=xxxxx
REDIS_PORT=xxxxx
REDIS_PASSWORD=xxxxx***（已隐藏）

Railway 变量检查：
MYSQLHOST=xxxxx
MYSQLPORT=xxxxx
MYSQLDATABASE=xxxxx
MYSQLUSER=xxxxx
MYSQL_URL=xxxxx...（已截断）
==========================================
```

### 4. 把这部分日志复制给我

**重点查看：**
- `DB_HOST` 是否有值？是什么？
- `MYSQLHOST` 是否有值？是什么？
- 如果都是空的，说明变量配置有问题

### 5. 查看连接失败的错误信息

日志中会在第 1、15、30 次尝试时输出错误原因：

```
⚠️  连接失败原因: SQLSTATE[HY000] [2002] Connection refused
```

把这个错误信息也复制给我。

## 🎯 第二步：检查 Railway 服务配置

### 检查 MySQL 服务

1. 在 Railway 项目中，点击 **MySQL** 服务（不是你的应用）
2. 进入 "Variables" 标签
3. 查看有哪些变量，截图发给我

**应该包含：**
- `MYSQLHOST` 或 `MYSQL_HOST`
- `MYSQLPORT` 或 `MYSQL_PORT`
- `MYSQLDATABASE` 或 `MYSQL_DATABASE`
- `MYSQLUSER` 或 `MYSQL_USER`
- `MYSQLPASSWORD` 或 `MYSQL_PASSWORD`
- `MYSQL_URL`

### 检查 Redis 服务

1. 点击 **Redis** 服务
2. 进入 "Variables" 标签
3. 查看有哪些变量，截图发给我

**应该包含：**
- `REDISHOST` 或 `REDIS_HOST`
- `REDISPORT` 或 `REDIS_PORT`
- `REDISPASSWORD` 或 `REDIS_PASSWORD`
- `REDIS_URL`

### 检查你的应用服务

1. 点击你的**应用服务**
2. 进入 "Variables" 标签
3. 确认你添加了所有环境变量
4. 截图发给我

## 🔧 可能的问题和解决方案

### 问题 1：变量名不匹配

Railway 可能使用不同的变量名：
- `MYSQLHOST` vs `MYSQL_HOST`
- `MYSQLDATABASE` vs `MYSQL_DATABASE`

**解决方案：**
根据 MySQL 服务实际提供的变量名调整配置。

### 问题 2：变量引用不生效

`${{MYSQLHOST}}` 这种语法可能不工作。

**解决方案 A：使用 Raw Variables**

在你的应用服务 Variables 中，不要用 `${{}}` 引用，而是：

1. 点击 "+ New Variable"
2. 选择 "Add a reference"（或类似选项）
3. 从 MySQL 服务中引用变量

**解决方案 B：使用连接 URL**

改用 `DATABASE_URL` 和 `REDIS_URL`：

```bash
DATABASE_URL=${{MYSQL_URL}}
REDIS_URL=${{REDIS_URL}}
```

然后修改 Laravel 配置解析这些 URL。

### 问题 3：MySQL 服务未完全启动

**解决方案：**

1. 检查 MySQL 服务状态是否为 "Active"
2. 查看 MySQL 服务的日志
3. 尝试重启 MySQL 服务

### 问题 4：网络隔离

Railway 的服务可能在不同的网络中。

**解决方案：**

确保所有服务（应用、MySQL、Redis）都在同一个 **Project** 下。

## 📋 完整的调试清单

请逐项检查并告诉我结果：

- [ ] 推送了最新代码
- [ ] Railway 重新部署完成
- [ ] 查看了部署日志中的调试信息
- [ ] `DB_HOST` 的值是：`__________`
- [ ] `MYSQLHOST` 的值是：`__________`
- [ ] `MYSQL_URL` 的值开头是：`mysql://...`
- [ ] 连接失败的错误信息是：`__________`
- [ ] MySQL 服务状态是：Active / Inactive / Crashed
- [ ] Redis 服务状态是：Active / Inactive / Crashed
- [ ] MySQL 服务提供的变量名是：`__________`（截图）
- [ ] 应用服务的变量配置：`__________`（截图）

## 🆘 临时解决方案：手动填写连接信息

如果变量引用一直有问题，可以临时使用硬编码方式：

### 1. 获取 MySQL 连接信息

在 Railway MySQL 服务的 "Connect" 标签，找到：
- Host
- Port
- Database
- Username
- Password

### 2. 在应用服务中直接填写

```bash
DB_HOST=containers-us-west-xxx.railway.app
DB_PORT=7890
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=实际的密码
```

**注意：这不是最佳方案，但可以先让应用跑起来。**

## 📞 把这些信息发给我

1. ✅ 已推送代码：是 / 否
2. ✅ 部署日志中的调试信息（整个调试区域）
3. ✅ 连接失败的错误信息
4. ✅ MySQL 服务的 Variables 截图
5. ✅ 应用服务的 Variables 截图
6. ✅ MySQL 服务的状态

有了这些信息，我就能准确定位问题并给出解决方案！
