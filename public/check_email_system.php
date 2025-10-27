<?php
/**
 * 检查邮件系统配置和队列状态
 * 访问: https://faka.opwan.ai/check_email_system.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

echo "=== 邮件系统检查 ===\n\n";

// 1. 检查邮件配置
echo "1. 邮件配置:\n";
$sysConfig = cache('system-setting', []);
echo "   MAIL_DRIVER: " . config('mail.driver') . "\n";
echo "   MAIL_HOST: " . config('mail.host') . "\n";
echo "   MAIL_PORT: " . config('mail.port') . "\n";
echo "   MAIL_USERNAME: " . config('mail.username') . "\n";
echo "   MAIL_PASSWORD: " . (config('mail.password') ? '已设置 (' . strlen(config('mail.password')) . ' 字符)' : '未设置') . "\n";
echo "   MAIL_ENCRYPTION: " . config('mail.encryption') . "\n";
echo "   MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "   MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

// 2. 检查队列配置
echo "2. 队列配置:\n";
echo "   QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "   队列驱动说明:\n";
echo "   - sync: 同步执行（立即执行，不使用队列）\n";
echo "   - database: 数据库队列（需要 php artisan queue:work）\n";
echo "   - redis: Redis队列（需要 php artisan queue:work）\n\n";

if (config('queue.default') == 'sync') {
    echo "   ⚠️  当前使用 sync 驱动，邮件会立即发送（不使用队列）\n\n";
} else {
    echo "   ✅ 当前使用 " . config('queue.default') . " 队列\n";
    echo "   ⚠️  需要确保 queue:work 进程正在运行！\n\n";
}

// 3. 检查队列任务数量（如果是 database 驱动）
if (config('queue.default') == 'database') {
    echo "3. 队列任务统计:\n";
    try {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        echo "   待处理任务: {$pendingJobs}\n";
        echo "   失败任务: {$failedJobs}\n\n";

        if ($pendingJobs > 0) {
            echo "   ⚠️  有 {$pendingJobs} 个待处理任务！请运行 queue:work\n";
            echo "   最近5个待处理任务:\n";
            $jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(5)->get();
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $displayName = $payload['displayName'] ?? '未知';
                $attempts = $job->attempts;
                echo "   - 任务: {$displayName}, 尝试次数: {$attempts}\n";
            }
            echo "\n";
        }

        if ($failedJobs > 0) {
            echo "   ❌ 有 {$failedJobs} 个失败任务！\n";
            echo "   最近一个失败任务:\n";
            $failed = DB::table('failed_jobs')->orderBy('id', 'desc')->first();
            if ($failed) {
                echo "   - UUID: {$failed->uuid}\n";
                echo "   - Connection: {$failed->connection}\n";
                echo "   - Queue: {$failed->queue}\n";
                echo "   - 失败时间: {$failed->failed_at}\n";
                echo "   - 异常: " . substr($failed->exception, 0, 200) . "...\n\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ 无法查询队列表: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "3. 队列任务统计: (当前非 database 驱动，跳过)\n\n";
}

// 4. 检查邮件模板
echo "4. 邮件模板检查:\n";
try {
    $templates = DB::table('emailtpls')->whereNull('deleted_at')->get();
    echo "   总共 " . count($templates) . " 个邮件模板\n";

    $requiredTokens = ['card_send_user_email', 'manual_send_manage_mail'];
    foreach ($requiredTokens as $token) {
        $tpl = DB::table('emailtpls')->where('tpl_token', $token)->whereNull('deleted_at')->first();
        if ($tpl) {
            echo "   ✅ {$token}: {$tpl->tpl_name}\n";
        } else {
            echo "   ❌ {$token}: 缺失！\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ 无法查询邮件模板: " . $e->getMessage() . "\n\n";
}

// 5. 检查最近已完成的订单
echo "5. 最近已完成的订单 (status=2):\n";
try {
    $orders = DB::table('orders')
        ->where('status', 2)
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();

    if (count($orders) == 0) {
        echo "   ⚠️  没有已完成的订单\n\n";
    } else {
        echo "   总共 " . count($orders) . " 个最近已完成的订单:\n";
        foreach ($orders as $order) {
            echo "   - 订单号: {$order->order_sn}, 邮箱: {$order->email}, 完成时间: {$order->updated_at}\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ 无法查询订单: " . $e->getMessage() . "\n\n";
}

// 6. 检查 Laravel 日志中的邮件相关错误
echo "6. 检查最近的邮件发送日志:\n";
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    echo "   日志文件: {$logFile}\n";
    $log = file_get_contents($logFile);

    // 查找邮件相关的日志
    if (strpos($log, 'MailSend') !== false) {
        echo "   ✅ 发现 MailSend 相关日志\n";
    } else {
        echo "   ⚠️  未发现 MailSend 相关日志\n";
    }

    if (strpos($log, 'Swift_TransportException') !== false || strpos($log, 'Connection refused') !== false) {
        echo "   ❌ 发现邮件发送连接错误！\n";
    }

    // 显示最后50行日志
    $lines = array_slice(explode("\n", $log), -50);
    $mailLines = array_filter($lines, function($line) {
        return stripos($line, 'mail') !== false || stripos($line, 'MailSend') !== false;
    });

    if (count($mailLines) > 0) {
        echo "   最近的邮件相关日志:\n";
        foreach (array_slice($mailLines, -10) as $line) {
            echo "   " . substr($line, 0, 150) . "\n";
        }
    }
    echo "\n";
} else {
    echo "   ⚠️  今天的日志文件不存在: {$logFile}\n\n";
}

// 7. 测试邮件发送
echo "7. 测试邮件发送建议:\n";
echo "   访问以下URL测试发送邮件:\n";
echo "   https://faka.opwan.ai/test_send_email.php?to=你的邮箱地址\n\n";

echo "=== 检查完成 ===\n";
