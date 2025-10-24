@echo off
echo 🚀 启动独角数卡本地开发服务器
echo ======================================
echo.

cd /d "%~dp0"

REM 检查 .env 文件
if not exist .env (
    echo ❌ .env 文件不存在！
    echo 请先复制 .env.example 为 .env 并配置数据库
    pause
    exit /b 1
)

REM 检查 vendor 目录
if not exist vendor (
    echo 📦 安装 Composer 依赖...
    composer install --ignore-platform-reqs
)

REM 清除缓存
echo 🧹 清除缓存...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

REM 生成应用密钥（如果需要）
php artisan key:generate --ansi

REM 创建软链接
if not exist public\storage (
    echo 🔗 创建存储软链接...
    php artisan storage:link
)

REM 显示路由列表
echo.
echo 📋 可用路由：
php artisan route:list | findstr "admin"

echo.
echo ======================================
echo ✅ 启动开发服务器在 http://localhost:8000
echo.
echo 前台: http://localhost:8000
echo 后台: http://localhost:8000/admin
echo.
echo 按 Ctrl+C 停止服务器
echo ======================================
echo.

REM 启动 PHP 内置服务器
php -S localhost:8000 -t public
