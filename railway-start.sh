#!/bin/bash

set -e  # 遇到错误立即退出

echo "=========================================="
echo "🚀 独角数卡 Railway 部署启动脚本"
echo "=========================================="
echo "启动时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "环境: ${APP_ENV:-未设置}"
echo "域名: ${APP_URL:-未设置}"
echo "=========================================="

# 创建必要的目录
echo "📁 创建必要的目录..."
mkdir -p /app/storage/logs
mkdir -p /app/storage/framework/cache
mkdir -p /app/storage/framework/sessions
mkdir -p /app/storage/framework/views
mkdir -p /app/bootstrap/cache

# 设置权限
echo "🔐 设置文件权限..."
chmod -R 755 /app/storage
chmod -R 755 /app/bootstrap/cache

# 等待数据库就绪
echo "🔍 等待数据库连接..."
MAX_TRIES=30
COUNTER=0
until php artisan db:show 2>/dev/null || [ $COUNTER -eq $MAX_TRIES ]; do
    COUNTER=$((COUNTER+1))
    echo "⏳ 数据库尚未就绪，等待中... ($COUNTER/$MAX_TRIES)"
    sleep 2
done

if [ $COUNTER -eq $MAX_TRIES ]; then
    echo "❌ 数据库连接超时，请检查数据库配置！"
    exit 1
fi

echo "✅ 数据库连接成功！"

# 检查是否首次部署（通过检查 migrations 表是否存在）
echo "🔍 检查部署状态..."
FIRST_DEPLOY=false
if ! php artisan migrate:status 2>/dev/null | grep -q "Migration name"; then
    echo "📦 检测到首次部署"
    FIRST_DEPLOY=true
else
    echo "♻️  检测到更新部署"
fi

# 运行数据库迁移
echo "📊 运行数据库迁移..."
if php artisan migrate --force --no-interaction; then
    echo "✅ 数据库迁移完成"
else
    echo "⚠️  数据库迁移失败，但继续启动..."
fi

# 首次部署时运行数据填充
if [ "$FIRST_DEPLOY" = true ]; then
    echo "🌱 首次部署，运行数据填充..."
    if php artisan db:seed --force --no-interaction 2>/dev/null; then
        echo "✅ 数据填充完成"
    else
        echo "⚠️  数据填充失败（可能已有数据），继续启动..."
    fi
fi

# 清除缓存
echo "🧹 清除旧缓存..."
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 优化应用
echo "⚡ 优化应用性能..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 创建存储链接
if [ ! -L /app/public/storage ]; then
    echo "🔗 创建存储软链接..."
    php artisan storage:link || true
else
    echo "✅ 存储软链接已存在"
fi

# 启动队列 worker（后台运行）
echo "🔄 启动队列处理器..."
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --daemon > /app/storage/logs/queue.log 2>&1 &
QUEUE_PID=$!
echo "✅ 队列处理器已启动 (PID: $QUEUE_PID)"

# 显示应用信息
echo ""
echo "=========================================="
echo "✨ 应用启动完成！"
echo "=========================================="
echo "📌 应用名称: ${APP_NAME:-独角数卡}"
echo "📌 前台地址: ${APP_URL:-https://your-app.railway.app}"
echo "📌 后台地址: ${APP_URL:-https://your-app.railway.app}${ADMIN_ROUTE_PREFIX:-/admin}"
echo "📌 队列进程: PID $QUEUE_PID"
echo "📌 完成时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
echo ""

# 启动 supervisord（包含 PHP-FPM 和 Nginx）
echo "🌐 启动 Web 服务器..."
exec supervisord -n -c /opt/docker/etc/supervisor.conf
