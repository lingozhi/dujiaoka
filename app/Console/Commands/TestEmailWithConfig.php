<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\MailServiceProvider;

class TestEmailWithConfig extends Command
{
    protected $signature = 'mail:test-flexible {to_email} {--host=smtp.feishu.cn} {--port=587} {--encryption=tls} {--username=no-reply@opwan.ai} {--password=Y5H2MrTLzJfFUH0a}';
    protected $description = 'Test email with custom SMTP configuration';

    public function handle()
    {
        $toEmail = $this->argument('to_email');
        $host = $this->option('host');
        $port = $this->option('port');
        $encryption = $this->option('encryption');
        $username = $this->option('username');
        $password = $this->option('password');

        $this->info('=== 使用自定义配置测试邮件发送 ===');
        $this->info('');

        $this->line("配置信息：");
        $this->line("Host: {$host}");
        $this->line("Port: {$port}");
        $this->line("Encryption: {$encryption}");
        $this->line("Username: {$username}");
        $this->line("Password: " . str_repeat('*', strlen($password)));
        $this->line("To: {$toEmail}");
        $this->info('');

        // 设置邮件配置
        $mailConfig = [
            'driver' => 'smtp',
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'from' => [
                'address' => $username,
                'name' => '独角数卡测试'
            ],
            'password' => $password,
            'encryption' => $encryption
        ];

        config(['mail' => array_merge(config('mail'), $mailConfig)]);
        (new MailServiceProvider(app()))->register();

        $this->info('正在发送测试邮件...');

        try {
            Mail::raw("这是一封测试邮件。\n\n配置信息：\nHost: {$host}\nPort: {$port}\nEncryption: {$encryption}\n\n如果您收到这封邮件，说明邮件配置成功！", function ($message) use ($toEmail) {
                $message->to($toEmail)
                    ->subject('独角数卡 - SMTP 测试邮件 (' . date('Y-m-d H:i:s') . ')');
            });

            $this->info('');
            $this->info('✅ 邮件发送成功！');
            $this->info('请检查收件箱（包括垃圾邮件文件夹）');
            $this->info('');
            $this->info('如果收到邮件，请在 Railway 环境变量中设置：');
            $this->comment("MAIL_HOST={$host}");
            $this->comment("MAIL_PORT={$port}");
            $this->comment("MAIL_ENCRYPTION={$encryption}");

            return 0;
        } catch (\Exception $e) {
            $this->error('');
            $this->error('❌ 邮件发送失败！');
            $this->error('错误信息: ' . $e->getMessage());
            $this->info('');

            if (strpos($e->getMessage(), 'Connection timed out') !== false) {
                $this->warn('连接超时！可能原因：');
                $this->line('1. Railway 网络无法访问该 SMTP 服务器');
                $this->line('2. 端口被防火墙阻止');
                $this->line('3. SMTP 服务器地址或端口不正确');
                $this->info('');
                $this->line('建议尝试：');
                $this->comment("php artisan mail:test-flexible {$toEmail} --port=587 --encryption=tls");
                $this->comment("php artisan mail:test-flexible {$toEmail} --port=25 --encryption=tls");
            } elseif (strpos($e->getMessage(), 'Authentication') !== false) {
                $this->warn('认证失败！请检查用户名和密码是否正确');
            }

            return 1;
        }
    }
}
