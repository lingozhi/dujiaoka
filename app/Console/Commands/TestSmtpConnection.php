<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSmtpConnection extends Command
{
    protected $signature = 'mail:test-connection {host=smtp.feishu.cn} {--port=465} {--timeout=10}';
    protected $description = 'Test SMTP server connection';

    public function handle()
    {
        $host = $this->argument('host');
        $port = $this->option('port');
        $timeout = $this->option('timeout');

        $this->info("=== SMTP 连接测试 ===");
        $this->info("");
        $this->line("目标: {$host}:{$port}");
        $this->line("超时: {$timeout} 秒");
        $this->info("");

        // 测试 1: DNS 解析
        $this->line("【1. DNS 解析测试】");
        $ip = gethostbyname($host);
        if ($ip === $host) {
            $this->error("❌ DNS 解析失败！无法解析 {$host}");
            return 1;
        } else {
            $this->info("✓ DNS 解析成功: {$host} → {$ip}");
        }
        $this->info("");

        // 测试 2: TCP 连接（普通端口）
        $this->line("【2. TCP 连接测试】");
        $this->line("尝试连接 {$host}:{$port} (无加密)...");

        $errno = 0;
        $errstr = '';
        $startTime = microtime(true);
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $elapsed = round((microtime(true) - $startTime) * 1000);

        if ($socket) {
            $this->info("✓ TCP 连接成功！耗时: {$elapsed}ms");
            fclose($socket);
        } else {
            $this->error("❌ TCP 连接失败！");
            $this->error("错误码: {$errno}");
            $this->error("错误信息: {$errstr}");
            $this->warn("可能原因: Railway 防火墙阻止了端口 {$port}");
        }
        $this->info("");

        // 测试 3: SSL/TLS 连接（端口 465 使用 SSL）
        if ($port == 465) {
            $this->line("【3. SSL 连接测试】");
            $this->line("尝试 SSL 连接 ssl://{$host}:{$port}...");

            $errno = 0;
            $errstr = '';
            $startTime = microtime(true);
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
            $elapsed = round((microtime(true) - $startTime) * 1000);

            if ($socket) {
                $this->info("✓ SSL 连接成功！耗时: {$elapsed}ms");
                fclose($socket);
            } else {
                $this->error("❌ SSL 连接失败！");
                $this->error("错误码: {$errno}");
                $this->error("错误信息: {$errstr}");
            }
        }
        $this->info("");

        // 测试 4: 尝试其他常用 SMTP 端口
        $this->line("【4. 测试其他 SMTP 端口】");
        $ports = [25, 587, 465, 2525];
        foreach ($ports as $testPort) {
            $this->line("测试端口 {$testPort}...");
            $socket = @fsockopen($host, $testPort, $errno, $errstr, 5);
            if ($socket) {
                $this->info("  ✓ 端口 {$testPort} 可访问");
                fclose($socket);
            } else {
                $this->line("  ✗ 端口 {$testPort} 不可访问");
            }
        }
        $this->info("");

        // 建议
        $this->line("【建议】");
        if ($socket === false && $port == 465) {
            $this->warn("端口 465 (SSL) 连接失败，建议尝试：");
            $this->line("1. 使用端口 587 + TLS 加密");
            $this->line("2. 在 Railway 环境变量中设置：");
            $this->line("   MAIL_PORT=587");
            $this->line("   MAIL_ENCRYPTION=tls");
            $this->info("");
            $this->line("然后运行:");
            $this->comment("php artisan mail:test-connection {$host} --port=587");
        }

        return 0;
    }
}
