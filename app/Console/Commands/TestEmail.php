<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {to_email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration by sending a test email';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $toEmail = $this->argument('to_email');

        $this->info('Testing email configuration...');
        $this->info('SMTP Host: ' . config('mail.mailers.smtp.host'));
        $this->info('SMTP Port: ' . config('mail.mailers.smtp.port'));
        $this->info('SMTP Encryption: ' . config('mail.mailers.smtp.encryption'));
        $this->info('From Address: ' . config('mail.from.address'));
        $this->info('');

        try {
            Mail::raw('这是一封测试邮件，来自独角数卡系统。\n\nThis is a test email from Dujiaoka.\n\n发送时间: ' . now(), function ($message) use ($toEmail) {
                $message->to($toEmail)
                    ->subject('独角数卡 - 邮件服务测试 | Dujiaoka Email Test');
            });

            $this->info('✓ Test email sent successfully to: ' . $toEmail);
            $this->info('Please check your inbox and spam folder.');

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send test email');
            $this->error('Error: ' . $e->getMessage());

            return 1;
        }
    }
}
