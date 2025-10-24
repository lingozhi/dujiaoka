#!/bin/bash
# Railway 邮件服务配置脚本

echo "配置 Railway 邮件环境变量..."
echo ""

railway variables set MAIL_MAILER=smtp
railway variables set MAIL_HOST=smtp.feishu.cn
railway variables set MAIL_PORT=465
railway variables set MAIL_USERNAME=no-reply@opwan.ai
railway variables set MAIL_PASSWORD=Y5H2MrTLzJfFUH0a
railway variables set MAIL_ENCRYPTION=ssl
railway variables set MAIL_FROM_ADDRESS=no-reply@opwan.ai
railway variables set "MAIL_FROM_NAME=独角数卡"

echo ""
echo "✓ 邮件配置完成！"
echo "Railway 将自动重新部署（约3-5分钟）"
