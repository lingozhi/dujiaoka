<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestFeishuDirect extends Command
{
    protected $signature = 'mail:test-feishu {email}';
    protected $description = 'Test Feishu SMTP with new password directly';

    public function handle()
    {
        $toEmail = $this->argument('email');

        $this->info('=== 飞书 SMTP 直接测试 ===');
        $this->info('');

        // 飞书配置（使用新密码）
        $config = [
            'host' => 'smtp.feishu.cn',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'no-reply@opwan.ai',
            'password' => 'e4ggOjilNVagDnOn',
            'from_address' => 'no-reply@opwan.ai',
            'from_name' => '独角数卡',
        ];

        $this->line('配置信息:');
        $this->line("Host: {$config['host']}");
        $this->line("Port: {$config['port']}");
        $this->line("Encryption: {$config['encryption']}");
        $this->line("Username: {$config['username']}");
        $this->line("Password: " . str_repeat('*', strlen($config['password'])));
        $this->line("To: {$toEmail}");
        $this->info('');

        // 先测试网络连接
        $this->line('【步骤 1: 测试网络连接】');
        $this->testConnection($config['host'], $config['port']);
        $this->info('');

        // 尝试发送邮件
        $this->line('【步骤 2: 发送测试邮件】');
        $this->sendEmail($config, $toEmail);

        return 0;
    }

    private function testConnection($host, $port)
    {
        // DNS 解析
        $this->line("DNS 解析 {$host}...");
        $ip = gethostbyname($host);
        if ($ip === $host) {
            $this->error("❌ DNS 解析失败");
            return false;
        } else {
            $this->info("✓ DNS 解析成功: {$ip}");
        }

        // TCP 连接
        $this->line("尝试 TCP 连接到 {$host}:{$port}...");
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);

        if ($socket) {
            $this->info("✓ TCP 连接成功");
            fclose($socket);
            return true;
        } else {
            $this->error("❌ TCP 连接失败: {$errstr} (错误码: {$errno})");
            $this->warn("可能原因: Railway 无法访问 Feishu SMTP 服务器");
            return false;
        }
    }

    private function sendEmail($config, $toEmail)
    {
        try {
            $transport = (new \Swift_SmtpTransport($config['host'], $config['port'], $config['encryption']))
                ->setUsername($config['username'])
                ->setPassword($config['password']);

            $mailer = new \Swift_Mailer($transport);

            $message = (new \Swift_Message('独角数卡 - 飞书 SMTP 测试'))
                ->setFrom([$config['from_address'] => $config['from_name']])
                ->setTo([$toEmail])
                ->setBody("这是飞书 SMTP 测试邮件\n\n配置：\nHost: {$config['host']}\nPort: {$config['port']}\nEncryption: {$config['encryption']}\n\n发送时间: " . date('Y-m-d H:i:s'), 'text/plain');

            $this->line('正在发送...');
            $result = $mailer->send($message);

            if ($result) {
                $this->info('');
                $this->info('✅ 邮件发送成功！');
                $this->info('请检查收件箱（包括垃圾邮件）');
                $this->info('');
                $this->info('飞书 SMTP 在 Railway 上可用！');
                $this->info('可以在管理后台使用此配置。');
            } else {
                $this->error('❌ 发送失败（未知原因）');
            }

        } catch (\Swift_TransportException $e) {
            $this->error('');
            $this->error('❌ 发送失败: ' . $e->getMessage());
            $this->info('');

            if (strpos($e->getMessage(), 'timed out') !== false || strpos($e->getMessage(), 'Connection') !== false) {
                $this->warn('⚠️  网络连接问题！');
                $this->line('');
                $this->line('确认：Feishu SMTP 在 Railway 上无法访问。');
                $this->line('');
                $this->info('解决方案：');
                $this->line('1. 使用备选邮件服务（Resend, Gmail, SendGrid）');
                $this->line('2. 参考文档: ALTERNATIVE_MAIL_SERVICES.md');
                $this->line('');
                $this->comment('推荐使用 Resend（最简单）:');
                $this->line('  注册: https://resend.com/signup');
                $this->line('  配置: smtp.resend.com:587 (TLS)');
            } elseif (strpos($e->getMessage(), 'Authentication') !== false) {
                $this->warn('⚠️  认证失败！');
                $this->line('请检查用户名和密码是否正确。');
            }

        } catch (\Exception $e) {
            $this->error('❌ 发生错误: ' . $e->getMessage());
        }
    }
}
