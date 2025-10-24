FROM webdevops/php-nginx:7.4

# 设置环境变量
ENV WEB_DOCUMENT_ROOT=/app/public
ENV APP_ENV=production
ENV ADMIN_HTTPS=true

# 复制应用代码
COPY . /app
WORKDIR /app

# 复制自定义 Nginx 配置
COPY nginx-static.conf /opt/docker/etc/nginx/vhost.common.d/static-files.conf

# 安装 Composer 依赖
RUN [ "sh", "-c", "composer install --ignore-platform-reqs" ]

# 创建 minified 版本的静态资源（用于生产环境）
RUN cd /app/public/vendor/dcat-admin/dcat/css && \
    for file in dcat-app*.css; do \
        if [ ! -f "${file%.css}.min.css" ]; then \
            cp "$file" "${file%.css}.min.css"; \
        fi \
    done && \
    cd /app/public/vendor/dcat-admin/dcat/js && \
    for file in *.js; do \
        if [[ "$file" != *.min.js ]] && [[ "$file" != *.map ]]; then \
            cp "$file" "${file%.js}.min.js"; \
        fi \
    done

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
    # 处理安装锁文件 - 确保始终链接到持久化目录\n\
    # 删除可能存在的旧文件\n\
    rm -f /app/install.lock\n\
    \n\
    # 如果 /data 中没有 install.lock，但 app 中有（构建时的），复制过去\n\
    if [ ! -f /data/install.lock ] && [ -f /app/install.lock.bak ]; then\n\
        echo "Restoring install.lock from backup..."\n\
        cp /app/install.lock.bak /data/install.lock\n\
    fi\n\
    \n\
    # 创建软链接，无论 /data/install.lock 是否存在\n\
    echo "Creating install.lock symlink to /data..."\n\
    ln -sf /data/install.lock /app/install.lock\n\
    \n\
    # 如果已经安装过，显示状态\n\
    if [ -f /data/install.lock ]; then\n\
        echo "Installation lock found - app already installed"\n\
    else\n\
        echo "No installation lock - ready for first-time setup"\n\
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
# 创建 storage 软链接\n\
if [ ! -L /app/public/storage ]; then\n\
    echo "Creating storage symlink..."\n\
    php artisan storage:link 2>/dev/null || true\n\
fi\n\
\n\
# 发布管理面板静态资源\n\
echo "Publishing admin panel assets..."\n\
php artisan admin:publish --force 2>/dev/null || true\n\
\n\
# Laravel 初始化（如果有 .env 文件）\n\
if [ -f /app/.env ]; then\n\
    echo "Running Laravel initialization..."\n\
    php artisan config:clear 2>/dev/null || true\n\
    php artisan cache:clear 2>/dev/null || true\n\
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
