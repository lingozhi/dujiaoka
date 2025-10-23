#!/bin/bash

# Railway 健康检查脚本

# 检查 Nginx 是否运行
if ! pgrep -x "nginx" > /dev/null; then
    echo "❌ Nginx 未运行"
    exit 1
fi

# 检查 PHP-FPM 是否运行
if ! pgrep -x "php-fpm" > /dev/null; then
    echo "❌ PHP-FPM 未运行"
    exit 1
fi

# 检查队列进程是否运行
if ! pgrep -f "queue:work" > /dev/null; then
    echo "⚠️  队列进程未运行（非致命错误）"
fi

# 检查应用是否可访问
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/)
if [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 302 ]; then
    echo "✅ 应用运行正常 (HTTP $HTTP_CODE)"
    exit 0
else
    echo "❌ 应用响应异常 (HTTP $HTTP_CODE)"
    exit 1
fi
