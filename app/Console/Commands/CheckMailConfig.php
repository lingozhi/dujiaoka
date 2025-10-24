<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckMailConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:check-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check email configuration status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== Email Configuration Check ===');
        $this->info('');

        $configs = [
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
            'MAIL_PASSWORD' => config('mail.mailers.smtp.password') ? '***已设置***' : '❌ 未设置',
            'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
        ];

        $allConfigured = true;

        foreach ($configs as $key => $value) {
            if (empty($value) || $value === '❌ 未设置') {
                $this->error("❌ {$key}: 未配置");
                $allConfigured = false;
            } else {
                $this->line("✓ {$key}: {$value}");
            }
        }

        $this->info('');

        if ($allConfigured) {
            $this->info('✓ 所有邮件配置已正确设置！');
            $this->info('');
            $this->info('可以使用以下命令测试邮件发送：');
            $this->comment('php artisan email:test your_email@example.com');
            return 0;
        } else {
            $this->error('❌ 邮件配置不完整！');
            $this->info('');
            $this->warn('请在 Railway 控制台添加以下环境变量：');
            $this->line('');
            $this->line('MAIL_MAILER=smtp');
            $this->line('MAIL_HOST=smtp.feishu.cn');
            $this->line('MAIL_PORT=465');
            $this->line('MAIL_USERNAME=no-reply@opwan.ai');
            $this->line('MAIL_PASSWORD=Y5H2MrTLzJfFUH0a');
            $this->line('MAIL_ENCRYPTION=ssl');
            $this->line('MAIL_FROM_ADDRESS=no-reply@opwan.ai');
            $this->line('MAIL_FROM_NAME=独角数卡');
            return 1;
        }
    }
}
