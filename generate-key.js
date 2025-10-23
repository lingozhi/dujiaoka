/**
 * Laravel APP_KEY 生成器 (Node.js 版本)
 * 运行方式：node generate-key.js
 */

const crypto = require('crypto');

// 生成 32 字节的随机数据
const key = crypto.randomBytes(32);

// Base64 编码
const base64Key = key.toString('base64');

// 输出 Laravel 格式的 APP_KEY
console.log('base64:' + base64Key);
console.log('');
console.log('请将上面生成的密钥设置为 Railway 的 APP_KEY 环境变量');
