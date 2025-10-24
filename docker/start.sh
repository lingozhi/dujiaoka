#!/bin/bash
set -e

echo "=========================================="
echo "🚀 独角数卡启动"
echo "=========================================="

# 获取端口
PORT=${PORT:-8080}
echo "监听端口: $PORT"

# 替换 Nginx 配置中的端口
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/nginx.conf

# 显示环境信息
echo "APP_ENV: ${APP_ENV}"
echo "APP_URL: ${APP_URL}"
echo "DB_HOST: ${DB_HOST}"

# 等待数据库
echo "⏳ 等待数据库连接..."
for i in {1..30}; do
    if php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; then
        echo "✅ 数据库连接成功"
        break
    fi
    echo "等待数据库... ($i/30)"
    sleep 2
done

# 运行迁移
echo "📊 运行数据库迁移..."
php artisan migrate --force --no-interaction || echo "迁移失败（可能已存在）"

# 清除缓存
echo "🧹 清除缓存..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 优化
echo "⚡ 优化应用..."
php artisan config:cache
php artisan view:cache

# 创建存储链接
if [ ! -L /app/public/storage ]; then
    php artisan storage:link
fi

# 启动 PHP-FPM
echo "🔧 启动 PHP-FPM..."
php-fpm -D

# 启动队列处理器
echo "🔄 启动队列处理器..."
php artisan queue:work redis --sleep=3 --tries=3 --daemon > /app/storage/logs/queue.log 2>&1 &

echo "=========================================="
echo "✅ 启动完成"
echo "前台: ${APP_URL}"
echo "后台: ${APP_URL}/admin"
echo "=========================================="

# 启动 Nginx（前台运行）
echo "🌐 启动 Nginx..."
nginx -g "daemon off;"
