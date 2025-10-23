#!/bin/bash

set -e  # 遇到错误立即退出

# 配置 Nginx 监听 Railway 的 PORT
export SERVICE_NGINX_LISTEN="0.0.0.0:${PORT:-8080}"

echo "=========================================="
echo "🚀 独角数卡 Railway 部署启动脚本"
echo "=========================================="
echo "启动时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "监听端口: ${PORT:-8080}"
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

# 动态生成 Nginx 配置
RAILWAY_PORT=${PORT:-80}
echo "🔧 生成 Nginx 配置，监听端口: $RAILWAY_PORT"

cat > /opt/docker/etc/nginx/vhost.conf << EOF
server {
    listen $RAILWAY_PORT default_server;
    listen [::]:$RAILWAY_PORT default_server;

    server_name _;
    root /app/public;
    index index.php index.html;

    client_max_body_size 50M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        include fastcgi_params;

        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_connect_timeout 60;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    location ~ /\. {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
EOF

echo "✅ Nginx 配置已生成"

# 调试：输出数据库配置信息
echo "=========================================="
echo "🔍 数据库配置调试信息"
echo "=========================================="
echo "DB_CONNECTION=${DB_CONNECTION}"
echo "DB_HOST=${DB_HOST}"
echo "DB_PORT=${DB_PORT}"
echo "DB_DATABASE=${DB_DATABASE}"
echo "DB_USERNAME=${DB_USERNAME}"
echo "DB_PASSWORD=${DB_PASSWORD:0:5}***（已隐藏）"
echo ""
echo "Redis 配置："
echo "REDIS_HOST=${REDIS_HOST}"
echo "REDIS_PORT=${REDIS_PORT}"
echo "REDIS_PASSWORD=${REDIS_PASSWORD:0:5}***（已隐藏）"
echo ""
echo "Railway 变量检查："
echo "MYSQLHOST=${MYSQLHOST}"
echo "MYSQLPORT=${MYSQLPORT}"
echo "MYSQLDATABASE=${MYSQLDATABASE}"
echo "MYSQLUSER=${MYSQLUSER}"
echo "MYSQL_URL=${MYSQL_URL:0:30}...（已截断）"
echo "=========================================="
echo ""

# 等待数据库就绪
echo "🔍 等待数据库连接..."
MAX_TRIES=30
COUNTER=0

# 使用 PHP 脚本测试数据库连接（兼容 Laravel 6）
while [ $COUNTER -lt $MAX_TRIES ]; do
    ERROR_MSG=$(php -r "
        require '/app/vendor/autoload.php';
        \$app = require_once '/app/bootstrap/app.php';
        \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
        \$kernel->bootstrap();
        try {
            \$pdo = DB::connection()->getPdo();
            echo 'SUCCESS';
            exit(0);
        } catch (Exception \$e) {
            echo \$e->getMessage();
            exit(1);
        }
    " 2>&1)

    if [ $? -eq 0 ]; then
        echo "✅ 数据库连接成功！"
        break
    fi

    COUNTER=$((COUNTER+1))
    if [ $COUNTER -eq 1 ] || [ $COUNTER -eq 15 ] || [ $COUNTER -eq 30 ]; then
        echo "⚠️  连接失败原因: $ERROR_MSG"
    fi
    echo "⏳ 数据库尚未就绪，等待中... ($COUNTER/$MAX_TRIES)"
    sleep 2
done

if [ $COUNTER -eq $MAX_TRIES ]; then
    echo "=========================================="
    echo "❌ 数据库连接超时！"
    echo "=========================================="
    echo "最后一次错误: $ERROR_MSG"
    echo ""
    echo "请检查："
    echo "1. MySQL 服务是否正在运行"
    echo "2. 环境变量是否正确配置"
    echo "3. 网络连接是否正常"
    echo "=========================================="
    exit 1
fi

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
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# 优化应用
echo "⚡ 优化应用性能..."
php artisan config:cache 2>/dev/null || echo "⚠️  配置缓存失败，跳过"

# 路由缓存可能失败（如果使用了闭包路由），不是致命问题
if php artisan route:cache 2>/dev/null; then
    echo "✅ 路由缓存成功"
else
    echo "⚠️  路由缓存失败（路由中可能使用了闭包），跳过"
fi

php artisan view:cache 2>/dev/null || echo "⚠️  视图缓存失败，跳过"

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
supervisord -c /opt/docker/etc/supervisor.conf &
SUPERVISOR_PID=$!

# 等待 supervisord 启动
sleep 3

# 重新加载 Nginx 配置
echo "🔄 重新加载 Nginx 配置..."
nginx -s reload 2>/dev/null || supervisorctl reload nginx 2>/dev/null || true

echo "✅ 服务启动完成"

# 保持进程运行
wait $SUPERVISOR_PID
