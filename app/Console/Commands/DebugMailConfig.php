<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DebugMailConfig extends Command
{
    protected $signature = 'mail:debug-config';
    protected $description = 'Debug email configuration from all sources';

    public function handle()
    {
        $this->info('=== 邮件配置调试信息 ===');
        $this->info('');

        // 1. 检查缓存配置
        $this->line('【1. 管理后台缓存配置】');
        $sysConfig = cache('system-setting', []);
        if (empty($sysConfig)) {
            $this->error('❌ 缓存为空');
        } else {
            $this->displayMailConfig($sysConfig, 'cache');
        }
        $this->info('');

        // 2. 检查 config() 函数返回值
        $this->line('【2. Config 函数返回值】');
        $configValues = [
            'driver' => config('mail.driver'),
            'host' => config('mail.host'),
            'port' => config('mail.port'),
            'username' => config('mail.username'),
            'password' => config('mail.password') ? '***已设置***' : '❌ 未设置',
            'encryption' => config('mail.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
        foreach ($configValues as $key => $value) {
            if (empty($value) || $value === '❌ 未设置') {
                $this->error("❌ {$key}: " . ($value ?: '空值'));
            } else {
                $this->line("✓ {$key}: {$value}");
            }
        }
        $this->info('');

        // 3. 直接检查环境变量
        $this->line('【3. 环境变量（$_ENV 和 getenv）】');
        $envVars = [
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
        ];

        foreach ($envVars as $var) {
            $value = getenv($var) ?: ($_ENV[$var] ?? null);
            if (empty($value)) {
                $this->error("❌ {$var}: 未设置");
            } else {
                $displayValue = (strpos($var, 'PASSWORD') !== false) ? '***已设置***' : $value;
                $this->line("✓ {$var}: {$displayValue}");
            }
        }
        $this->info('');

        // 4. 模拟 MailSend.php 的逻辑
        $this->line('【4. MailSend.php 实际使用的配置】');
        $finalConfig = [
            'driver' => $sysConfig['driver'] ?? config('mail.driver', 'smtp'),
            'host' => $sysConfig['host'] ?? config('mail.host', 'smtp.feishu.cn'),
            'port' => $sysConfig['port'] ?? config('mail.port', '465'),
            'username' => $sysConfig['username'] ?? config('mail.username', 'no-reply@opwan.ai'),
            'password' => $sysConfig['password'] ?? config('mail.password', 'Y5H2MrTLzJfFUH0a'),
            'encryption' => $sysConfig['encryption'] ?? config('mail.encryption', 'ssl'),
            'from_address' => $sysConfig['from_address'] ?? config('mail.from.address', 'no-reply@opwan.ai'),
            'from_name' => $sysConfig['from_name'] ?? config('mail.from.name', '独角数卡'),
        ];

        foreach ($finalConfig as $key => $value) {
            $displayValue = ($key === 'password') ? '***已设置***' : $value;
            if (empty($value)) {
                $this->error("❌ {$key}: 空值！");
            } else {
                $this->line("✓ {$key}: {$displayValue}");
            }
        }
        $this->info('');

        // 5. 检查 .env 文件
        $this->line('【5. .env 文件检查】');
        if (file_exists(base_path('.env'))) {
            $this->info('✓ .env 文件存在');
            $envContent = file_get_contents(base_path('.env'));
            if (strpos($envContent, 'MAIL_HOST') !== false) {
                $this->info('✓ .env 文件包含 MAIL_HOST 配置');
            } else {
                $this->warn('.env 文件不包含 MAIL_HOST 配置');
            }
        } else {
            $this->error('❌ .env 文件不存在');
        }
        $this->info('');

        // 总结
        $allConfigured = !empty($finalConfig['host']) &&
                        !empty($finalConfig['username']) &&
                        !empty($finalConfig['password']);

        if ($allConfigured) {
            $this->info('✅ 配置检查通过！邮件应该可以发送。');
        } else {
            $this->error('❌ 配置不完整！邮件无法发送。');
            $this->warn('建议：检查 Railway 环境变量是否正确设置');
        }

        return 0;
    }

    private function displayMailConfig($config, $source)
    {
        $fields = ['driver', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name'];
        foreach ($fields as $field) {
            $value = $config[$field] ?? null;
            if (empty($value)) {
                $this->error("❌ {$field}: 未设置");
            } else {
                $displayValue = ($field === 'password') ? '***已设置***' : $value;
                $this->line("✓ {$field}: {$displayValue}");
            }
        }
    }
}
