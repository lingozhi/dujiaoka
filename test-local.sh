#!/bin/bash

# 本地测试脚本 - 模拟 Railway 环境

echo "🧪 本地测试 Railway 部署"
echo "======================================"

# 停止并删除旧容器
docker stop dujiaoka-test 2>/dev/null || true
docker rm dujiaoka-test 2>/dev/null || true

# 构建镜像
echo "📦 构建 Docker 镜像..."
docker build -t dujiaoka-test .

# 运行容器（模拟 Railway 环境变量）
echo "🚀 启动容器..."
docker run -d \
  --name dujiaoka-test \
  -p 8080:8080 \
  -e PORT=8080 \
  -e APP_ENV=production \
  -e APP_KEY=base64:hDVkYhfkUjaePiaI1tcBT7G8bh2A8RQxwWIGkq7BO18= \
  -e APP_DEBUG=false \
  -e APP_URL=http://localhost:8080 \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=yamanote.proxy.rlwy.net \
  -e DB_PORT=59325 \
  -e DB_DATABASE=railway \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=xnpMbYdjNkfYSRljymEkrJUimzZBQiRZ \
  -e REDIS_HOST=centerbeam.proxy.rlwy.net \
  -e REDIS_PORT=21831 \
  -e REDIS_PASSWORD=uyHHIOPBMBaysbXxKIhxVNgzZTbqONEs \
  -e CACHE_DRIVER=redis \
  -e SESSION_DRIVER=file \
  -e QUEUE_CONNECTION=redis \
  -e ADMIN_ROUTE_PREFIX=/admin \
  dujiaoka-test

# 等待容器启动
echo "⏳ 等待容器启动..."
sleep 10

# 查看日志
echo ""
echo "📋 容器日志："
echo "======================================"
docker logs dujiaoka-test

# 测试访问
echo ""
echo "🧪 测试访问："
echo "======================================"
echo "测试 test.php:"
curl -I http://localhost:8080/test.php

echo ""
echo "测试 /admin:"
curl -I http://localhost:8080/admin

echo ""
echo "======================================"
echo "✅ 测试完成"
echo ""
echo "查看完整日志: docker logs -f dujiaoka-test"
echo "进入容器: docker exec -it dujiaoka-test bash"
echo "停止容器: docker stop dujiaoka-test"
