FROM webdevops/php-nginx:7.4

# 设置工作目录
WORKDIR /app

# 复制项目文件
COPY . /app

# 安装 Composer 依赖
RUN composer install --ignore-platform-reqs --no-dev --optimize-autoloader && \
    composer dump-autoload --optimize

# 设置权限
RUN chmod +x /app/railway-start.sh && \
    chmod +x /app/healthcheck.sh && \
    chmod -R 755 /app/storage && \
    chmod -R 755 /app/bootstrap/cache && \
    chown -R application:application /app

# 暴露端口
EXPOSE 80

# 健康检查（可选，Railway 会自动检测）
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD /app/healthcheck.sh || exit 1

# 使用 railway-start.sh 启动
CMD ["/app/railway-start.sh"]
