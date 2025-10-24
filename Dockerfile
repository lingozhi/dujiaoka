FROM webdevops/php-nginx:7.4

# 设置环境变量
ENV WEB_DOCUMENT_ROOT=/app/public
ENV APP_ENV=production

# 复制应用代码
COPY . /app
WORKDIR /app

# 安装 Composer 依赖
RUN [ "sh", "-c", "composer install --ignore-platform-reqs" ]

# 创建增强的启动脚本，支持 Railway /data 持久化存储
RUN echo '#!/bin/bash\n\
# 检查 /data 目录是否存在（Railway 环境）\n\
if [ -d "/data" ]; then\n\
    echo "Detected /data directory, setting up persistence..."\n\
    \n\
    # 初始化 .env 文件\n\
    if [ ! -f /data/.env ]; then\n\
        echo "Copying default .env file..."\n\
        cp /app/.env.example /data/.env 2>/dev/null || true\n\
    fi\n\
    ln -sf /data/.env /app/.env\n\
    \n\
    # 处理安装锁文件\n\
    if [ -f /data/install.lock ]; then\n\
        ln -sf /data/install.lock /app/install.lock\n\
    fi\n\
    \n\
    # 创建持久化目录\n\
    mkdir -p /data/uploads /data/storage/logs /data/storage/framework/cache /data/storage/framework/sessions /data/storage/framework/views /data/database\n\
    \n\
    # 软链接上传目录\n\
    if [ -d /app/public/uploads ]; then\n\
        rm -rf /app/public/uploads\n\
    fi\n\
    ln -sf /data/uploads /app/public/uploads\n\
    \n\
    # 软链接 storage 子目录\n\
    rm -rf /app/storage/logs && ln -sf /data/storage/logs /app/storage/logs\n\
    rm -rf /app/storage/framework/cache && ln -sf /data/storage/framework/cache /app/storage/framework/cache\n\
    rm -rf /app/storage/framework/sessions && ln -sf /data/storage/framework/sessions /app/storage/framework/sessions\n\
    rm -rf /app/storage/framework/views && ln -sf /data/storage/framework/views /app/storage/framework/views\n\
    \n\
    # 设置权限\n\
    chown -R application:application /data 2>/dev/null || true\n\
    chmod -R 775 /data 2>/dev/null || true\n\
    \n\
    echo "Persistence setup completed."\n\
fi\n\
\n\
# Laravel 初始化（如果有 .env 文件）\n\
if [ -f /app/.env ]; then\n\
    echo "Running Laravel initialization..."\n\
    php artisan config:cache 2>/dev/null || true\n\
    php artisan route:cache 2>/dev/null || true\n\
    php artisan view:cache 2>/dev/null || true\n\
fi\n\
\n\
# 启动 Laravel 队列工作进程\n\
php artisan queue:work >/tmp/work.log 2>&1 &\n\
\n\
# 启动 supervisord\n\
supervisord' > /app/start.sh

# 设置执行权限
RUN chmod +x /app/start.sh
RUN [ "sh", "-c", "chmod -R 777 /app" ]

# 暴露端口
EXPOSE 80 9000

# 启动命令
CMD [ "sh", "/app/start.sh" ]
