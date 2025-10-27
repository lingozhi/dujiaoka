#!/bin/bash

# Railway 启动脚本

echo "🚀 Railway 启动中..."

# 1. 创建 storage 符号链接
echo "📁 创建 storage 链接..."
php artisan storage:link

# 2. 清理缓存
echo "🧹 清理缓存..."
php artisan config:clear
php artisan cache:clear

# 3. 启动服务
echo "✅ 启动服务..."
php artisan serve --host=0.0.0.0 --port=$PORT
