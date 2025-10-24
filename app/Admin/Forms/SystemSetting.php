<?php

namespace App\Admin\Forms;

use App\Models\BaseModel;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Form
{
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        try {
            // 记录收到的数据（调试用）
            \Log::info('SystemSetting handle called', [
                'input_keys' => array_keys($input),
                'has_host' => isset($input['host']),
            ]);

            // 确保缓存目录存在（Railway 持久化支持）
            $cacheDir = storage_path('framework/cache/data');
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            // 保存配置到缓存（使用永久存储）
            Cache::forever('system-setting', $input);

            // 验证保存
            $saved = Cache::get('system-setting');

            // 记录日志
            \Log::info('System settings saved successfully', [
                'cache_driver' => config('cache.default'),
                'saved_host' => $saved['host'] ?? 'empty',
                'input_host' => $input['host'] ?? 'empty',
            ]);

            return $this
                ->response()
                ->success(admin_trans('system-setting.rule_messages.save_system_setting_success'))
                ->refresh();

        } catch (\Exception $e) {
            // 捕获异常并返回错误信息
            \Log::error('Failed to save system settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this
                ->response()
                ->error('保存失败: ' . $e->getMessage());
        }
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->tab(admin_trans('system-setting.labels.base_setting'), function () {
            $this->text('title', admin_trans('system-setting.fields.title'))->required();
            $this->image('img_logo', admin_trans('system-setting.fields.img_logo'));
            $this->text('text_logo', admin_trans('system-setting.fields.text_logo'));
            $this->text('keywords', admin_trans('system-setting.fields.keywords'));
            $this->textarea('description', admin_trans('system-setting.fields.description'));
            $this->select('template', admin_trans('system-setting.fields.template'))
                ->options(config('dujiaoka.templates'))
                ->required();
            $this->select('language', admin_trans('system-setting.fields.language'))
                ->options(config('dujiaoka.language'))
                ->required();
            $this->text('manage_email', admin_trans('system-setting.fields.manage_email'));
            $this->number('order_expire_time', admin_trans('system-setting.fields.order_expire_time'))
                ->default(5)
                ->required();
            $this->switch('is_open_anti_red', admin_trans('system-setting.fields.is_open_anti_red'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->switch('is_open_img_code', admin_trans('system-setting.fields.is_open_img_code'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->switch('is_open_search_pwd', admin_trans('system-setting.fields.is_open_search_pwd'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->switch('is_open_google_translate', admin_trans('system-setting.fields.is_open_google_translate'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->editor('notice', admin_trans('system-setting.fields.notice'));
            $this->textarea('footer', admin_trans('system-setting.fields.footer'));
        });
        $this->tab(admin_trans('system-setting.labels.order_push_setting'), function () {
            $this->switch('is_open_server_jiang', admin_trans('system-setting.fields.is_open_server_jiang'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->text('server_jiang_token', admin_trans('system-setting.fields.server_jiang_token'));
            $this->switch('is_open_telegram_push', admin_trans('system-setting.fields.is_open_telegram_push'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->text('telegram_bot_token', admin_trans('system-setting.fields.telegram_bot_token'));
            $this->text('telegram_userid', admin_trans('system-setting.fields.telegram_userid'));
            $this->switch('is_open_bark_push', admin_trans('system-setting.fields.is_open_bark_push'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->switch('is_open_bark_push_url', admin_trans('system-setting.fields.is_open_bark_push_url'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->text('bark_server', admin_trans('system-setting.fields.bark_server'));
            $this->text('bark_token', admin_trans('system-setting.fields.bark_token'));
            $this->switch('is_open_qywxbot_push', admin_trans('system-setting.fields.is_open_qywxbot_push'))
                ->default(BaseModel::STATUS_CLOSE);
            $this->text('qywxbot_key', admin_trans('system-setting.fields.qywxbot_key'));
        });
        $this->tab(admin_trans('system-setting.labels.mail_setting'), function () {
            $this->text('driver', admin_trans('system-setting.fields.driver'))->default('smtp')->required();
            $this->text('host', admin_trans('system-setting.fields.host'));
            $this->text('port', admin_trans('system-setting.fields.port'))->default(587);
            $this->text('username', admin_trans('system-setting.fields.username'));
            $this->text('password', admin_trans('system-setting.fields.password'));
            $this->text('encryption', admin_trans('system-setting.fields.encryption'));
            $this->text('from_address', admin_trans('system-setting.fields.from_address'));
            $this->text('from_name', admin_trans('system-setting.fields.from_name'));
        });
        $this->tab(admin_trans('system-setting.labels.geetest'), function () {
            $this->text('geetest_id', admin_trans('system-setting.fields.geetest_id'));
            $this->text('geetest_key', admin_trans('system-setting.fields.geetest_key'));
            $this->switch('is_open_geetest', admin_trans('system-setting.fields.is_open_geetest'))->default(BaseModel::STATUS_CLOSE);
        });

        // 移除有问题的 confirm 调用，修复表单提交
        // 注意：修改系统设置后需要重启 PHP Worker 才能生效
    }

    public function default()
    {
        return Cache::get('system-setting');
    }

}
