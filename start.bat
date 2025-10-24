@echo off
cd /d "%~dp0"

echo 🚀 启动独角数卡本地服务器
echo ======================================
echo.

echo 📋 后台地址: http://localhost:8000/admin
echo.
echo 按 Ctrl+C 停止服务器
echo ======================================
echo.

C:\tools\php74\php.exe -S localhost:8000 -t public
pause
