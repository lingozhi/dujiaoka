FROM php:7.4-fpm-alpine

# 安装系统依赖和 Nginx
RUN apk add --no-cache \
    nginx \
    bash \
    curl \
    vim \
    git \
    zip \
    unzip \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev

# 安装 PHP 扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        mysqli \
        zip \
        intl \
        bcmath \
        soap \
        mbstring \
        pcntl \
        opcache

# 安装 Redis 扩展
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# 安装 Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /app

# 复制项目文件
COPY . /app

# 安装 Composer 依赖
RUN composer install --ignore-platform-reqs --no-dev --optimize-autoloader

# 创建 Nginx 配置
RUN mkdir -p /run/nginx && \
    rm -f /etc/nginx/http.d/default.conf

# 创建目录
RUN mkdir -p \
    /app/storage/logs \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/bootstrap/cache \
    /var/log/nginx \
    /var/log/php-fpm

# 设置权限
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# 复制 Nginx 和 PHP-FPM 配置
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# 暴露端口由 Railway 的 $PORT 决定
EXPOSE 8080

# 启动脚本
CMD ["/start.sh"]
