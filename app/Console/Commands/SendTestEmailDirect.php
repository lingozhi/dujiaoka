<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendTestEmailDirect extends Command
{
    protected $signature = 'mail:send-direct {to_email}';
    protected $description = 'Send test email with hardcoded configuration (bypass all cache)';

    public function handle()
    {
        $toEmail = $this->argument('to_email');

        $this->info('=== 直接发送测试邮件（硬编码配置）===');
        $this->info('');
        $this->line('收件人: ' . $toEmail);
        $this->info('');

        // 完全硬编码的配置（Feishu）
        $config = [
            'host' => 'smtp.feishu.cn',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'no-reply@opwan.ai',
            'password' => 'Y5H2MrTLzJfFUH0a',
            'from_address' => 'no-reply@opwan.ai',
            'from_name' => '独角数卡测试',
        ];

        $this->line('使用配置:');
        $this->line('Host: ' . $config['host']);
        $this->line('Port: ' . $config['port']);
        $this->line('Encryption: ' . $config['encryption']);
        $this->line('Username: ' . $config['username']);
        $this->line('Password: ' . str_repeat('*', strlen($config['password'])));
        $this->info('');

        $this->line('正在发送邮件...');

        try {
            // 使用原生 PHPMailer 或 SwiftMailer
            $transport = (new \Swift_SmtpTransport($config['host'], $config['port'], $config['encryption']))
                ->setUsername($config['username'])
                ->setPassword($config['password']);

            $mailer = new \Swift_Mailer($transport);

            $message = (new \Swift_Message('独角数卡 - 直接测试邮件'))
                ->setFrom([$config['from_address'] => $config['from_name']])
                ->setTo([$toEmail])
                ->setBody("这是一封测试邮件（硬编码配置）\n\n配置信息：\nHost: {$config['host']}\nPort: {$config['port']}\nEncryption: {$config['encryption']}\n\n发送时间：" . date('Y-m-d H:i:s') . "\n\n如果您收到这封邮件，说明 SMTP 配置正确！", 'text/plain');

            $result = $mailer->send($message);

            if ($result) {
                $this->info('');
                $this->info('✅ 邮件发送成功！');
                $this->info('请检查收件箱（包括垃圾邮件）');
            } else {
                $this->error('');
                $this->error('❌ 邮件发送失败（未知原因）');
            }

            return 0;

        } catch (\Swift_TransportException $e) {
            $this->error('');
            $this->error('❌ 传输错误: ' . $e->getMessage());
            $this->info('');

            if (strpos($e->getMessage(), 'Connection') !== false) {
                $this->warn('⚠️  网络连接问题！');
                $this->line('');
                $this->line('Feishu SMTP 在 Railway 上可能无法访问。');
                $this->line('建议使用备选邮件服务：');
                $this->line('');
                $this->line('1. Resend (推荐): smtp.resend.com:587');
                $this->line('2. Gmail: smtp.gmail.com:587');
                $this->line('3. SendGrid: smtp.sendgrid.net:587');
                $this->line('');
                $this->comment('查看详细配置: ALTERNATIVE_MAIL_SERVICES.md');
            }

            return 1;

        } catch (\Exception $e) {
            $this->error('');
            $this->error('❌ 发送失败: ' . $e->getMessage());
            return 1;
        }
    }
}
