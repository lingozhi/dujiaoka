<?php

namespace App\Http\Controllers\Pay;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Illuminate\Http\Request;
use Yansongda\Pay\Pay;

class AlipayController extends PayController
{

    /**
     * 支付宝支付网关
     *
     * @param string $payway
     * @param string $orderSN
     */
    public function gateway(string $payway, string $orderSN)
    {
        try {
            // 加载网关
            $this->loadGateWay($orderSN, $payway);

            $config = [
                'app_id' => $this->payGateway->merchant_id,
                'ali_public_key' => $this->payGateway->merchant_key,
                'private_key' => $this->payGateway->merchant_pem,
                'notify_url' => url($this->payGateway->pay_handleroute . '/notify_url'),
                'return_url' => url($this->payGateway->pay_handleroute . '/return_url') . '?orderSN=' . $this->order->order_sn,
                'http' => [ // optional
                    'timeout' => 10.0,
                    'connect_timeout' => 10.0,
                ],
            ];
            $order = [
                'out_trade_no' => $this->order->order_sn,
                'total_amount' => (float)$this->order->actual_price,
                'subject' => $this->order->order_sn
            ];
            switch ($payway){
                case 'zfbf2f':
                case 'alipayscan':
                    try{
                        $result = Pay::alipay($config)->scan($order)->toArray();
                        $result['payname'] = $this->order->order_sn;
                        $result['actual_price'] = (float)$this->order->actual_price;
                        $result['orderid'] = $this->order->order_sn;
                        $result['jump_payuri'] = $result['qr_code'];
                        return $this->render('static_pages/qrpay', $result, __('dujiaoka.scan_qrcode_to_pay'));
                    } catch (\Exception $e) {
                        return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                    }
                case 'aliweb':
                    try{
                        $result = Pay::alipay($config)->web($order);
                        return $result;
                    } catch (\Exception $e) {
                        return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                    }
                case 'aliwap':
                    try{
                        $result = Pay::alipay($config)->wap($order);
                        return $result;
                    } catch (\Exception $e) {
                        return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                    }
            }
        } catch (RuleValidationException $exception) {
            return $this->err($exception->getMessage());
        }
    }


    /**
     * 同步返回（用户支付完成后跳转）
     */
    public function returnUrl(Request $request)
    {
        $orderSN = $request->input('orderSN') ?: $request->input('out_trade_no');

        \Log::info('支付宝同步返回', [
            'orderSN' => $orderSN,
            'all_params' => $request->all()
        ]);

        if (!$orderSN) {
            \Log::error('支付宝同步返回缺少订单号');
            return redirect()->to(url('/'));
        }

        // 获取订单信息
        $order = $this->orderService->detailOrderSN($orderSN);
        if (!$order) {
            \Log::error('支付宝同步返回订单不存在', ['orderSN' => $orderSN]);
            return redirect()->to(url('/'));
        }

        // 如果订单已完成，直接跳转
        if ($order->status == \App\Models\Order::STATUS_COMPLETED) {
            \Log::info('支付宝同步返回订单已完成', ['orderSN' => $orderSN]);
            return redirect()->to(url('detail-order-sn', ['orderSN' => $orderSN]));
        }

        // 获取支付网关配置
        $payGateway = $this->payService->detail($order->pay_id);
        if (!$payGateway || $payGateway->pay_handleroute != '/pay/alipay') {
            \Log::error('支付宝同步返回支付网关配置错误', ['order' => $order]);
            return redirect()->to(url('detail-order-sn', ['orderSN' => $orderSN]));
        }

        // 尝试验证签名并处理订单
        try {
            $config = [
                'app_id' => $payGateway->merchant_id,
                'ali_public_key' => $payGateway->merchant_key,
                'private_key' => $payGateway->merchant_pem,
            ];

            $pay = Pay::alipay($config);
            $result = $pay->verify(); // 验证同步返回的签名

            \Log::info('支付宝同步返回签名验证成功', [
                'orderSN' => $orderSN,
                'trade_no' => $result->trade_no ?? null,
                'total_amount' => $result->total_amount ?? null
            ]);

            // 如果验证成功，完成订单
            if (isset($result->out_trade_no) && isset($result->total_amount)) {
                try {
                    $this->orderProcessService->completedOrder(
                        $result->out_trade_no,
                        $result->total_amount,
                        $result->trade_no ?? ''
                    );
                    \Log::info('支付宝同步返回订单处理成功', [
                        'orderSN' => $orderSN,
                        'amount' => $result->total_amount,
                        'trade_no' => $result->trade_no ?? null
                    ]);
                } catch (\Exception $e) {
                    \Log::error('支付宝同步返回订单处理失败', [
                        'orderSN' => $orderSN,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $exception) {
            \Log::warning('支付宝同步返回签名验证失败', [
                'orderSN' => $orderSN,
                'error' => $exception->getMessage()
            ]);
        }

        // 无论成功或失败，都重定向到订单详情页
        return redirect()->to(url('detail-order-sn', ['orderSN' => $orderSN]));
    }

    /**
     * 异步通知
     */
    public function notifyUrl(Request $request)
    {
        // 直接输出到 stderr 以便在 Railway 看到
        error_log('[ALIPAY NOTIFY] 开始处理回调');
        error_log('[ALIPAY NOTIFY] 请求数据: ' . json_encode($request->all()));

        // 记录回调数据（调试用）
        \Log::info('支付宝回调接收', [
            'method' => $request->method(),
            'data' => $request->all(),
            'ip' => $request->ip(),
            'headers' => $request->headers->all()
        ]);

        $orderSN = $request->input('out_trade_no');
        if (!$orderSN) {
            error_log('[ALIPAY NOTIFY] 错误: 缺少订单号');
            \Log::error('支付宝回调缺少订单号', ['data' => $request->all()]);
            return 'error';
        }

        error_log('[ALIPAY NOTIFY] 订单号: ' . $orderSN);

        $order = $this->orderService->detailOrderSN($orderSN);
        if (!$order) {
            error_log('[ALIPAY NOTIFY] 错误: 订单不存在');
            \Log::error('支付宝回调订单不存在', ['orderSN' => $orderSN]);
            return 'error';
        }

        error_log('[ALIPAY NOTIFY] 订单状态: ' . $order->status);

        // 检查订单是否已经完成
        if ($order->status == \App\Models\Order::STATUS_COMPLETED) {
            error_log('[ALIPAY NOTIFY] 订单已完成，返回success');
            \Log::info('支付宝回调订单已完成', ['orderSN' => $orderSN]);
            return 'success';
        }

        $payGateway = $this->payService->detail($order->pay_id);
        if (!$payGateway) {
            error_log('[ALIPAY NOTIFY] 错误: 支付网关不存在');
            \Log::error('支付宝回调支付网关不存在', ['pay_id' => $order->pay_id]);
            return 'error';
        }

        error_log('[ALIPAY NOTIFY] 支付网关路由: ' . $payGateway->pay_handleroute);

        if($payGateway->pay_handleroute != '/pay/alipay'){
            error_log('[ALIPAY NOTIFY] 错误: 路由不匹配，期望 /pay/alipay，实际 ' . $payGateway->pay_handleroute);
            \Log::error('支付宝回调路由不匹配', [
                'expected' => '/pay/alipay',
                'actual' => $payGateway->pay_handleroute
            ]);
            return 'fail';
        }

        // 详细检查配置
        error_log('[ALIPAY NOTIFY] App ID: ' . $payGateway->merchant_id);
        error_log('[ALIPAY NOTIFY] 支付宝公钥长度: ' . strlen($payGateway->merchant_key) . ' 字符');
        error_log('[ALIPAY NOTIFY] 应用私钥长度: ' . strlen($payGateway->merchant_pem) . ' 字符');

        if (empty($payGateway->merchant_key)) {
            error_log('[ALIPAY NOTIFY] 错误: merchant_key 为空！请在后台填写支付宝公钥');
            return 'error';
        }

        if (empty($payGateway->merchant_pem)) {
            error_log('[ALIPAY NOTIFY] 错误: merchant_pem 为空！请在后台填写应用私钥');
            return 'error';
        }

        $config = [
            'app_id' => $payGateway->merchant_id,
            'ali_public_key' => $payGateway->merchant_key,
            'private_key' => $payGateway->merchant_pem,
        ];

        \Log::info('支付宝回调配置', [
            'app_id' => $payGateway->merchant_id,
            'has_ali_public_key' => !empty($payGateway->merchant_key),
            'has_private_key' => !empty($payGateway->merchant_pem)
        ]);

        error_log('[ALIPAY NOTIFY] 开始验证签名');

        $pay = Pay::alipay($config);
        try{
            // 验证签名
            $result = $pay->verify();

            error_log('[ALIPAY NOTIFY] 签名验证成功，交易状态: ' . $result->trade_status);

            \Log::info('支付宝回调签名验证成功', [
                'orderSN' => $orderSN,
                'trade_status' => $result->trade_status,
                'trade_no' => $result->trade_no ?? null,
                'total_amount' => $result->total_amount ?? null
            ]);

            if ($result->trade_status == 'TRADE_SUCCESS' || $result->trade_status == 'TRADE_FINISHED') {
                error_log('[ALIPAY NOTIFY] 交易成功，开始处理订单');
                try {
                    $this->orderProcessService->completedOrder(
                        $result->out_trade_no,
                        $result->total_amount,
                        $result->trade_no
                    );
                    error_log('[ALIPAY NOTIFY] 订单处理成功');
                    \Log::info('支付宝订单处理成功', [
                        'orderSN' => $orderSN,
                        'amount' => $result->total_amount,
                        'trade_no' => $result->trade_no
                    ]);
                } catch (\Exception $e) {
                    error_log('[ALIPAY NOTIFY] 订单处理失败: ' . $e->getMessage());
                    \Log::error('支付宝订单处理失败', [
                        'orderSN' => $orderSN,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return 'fail';
                }
            } else {
                error_log('[ALIPAY NOTIFY] 交易状态非成功: ' . $result->trade_status);
                \Log::warning('支付宝回调状态非成功', [
                    'orderSN' => $orderSN,
                    'trade_status' => $result->trade_status
                ]);
            }
            error_log('[ALIPAY NOTIFY] 返回 success');
            return 'success';
        } catch (\Exception $exception) {
            error_log('[ALIPAY NOTIFY] 异常: ' . $exception->getMessage());
            \Log::error('支付宝回调异常', [
                'orderSN' => $orderSN,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return 'fail';
        }
    }

    /**
     * 格式化支付宝公钥
     */
    private function formatPublicKey($key)
    {
        if (empty($key)) {
            return $key;
        }

        // 去除头尾标记和换行
        $key = str_replace([
            '-----BEGIN PUBLIC KEY-----',
            '-----END PUBLIC KEY-----',
            "\r",
            "\n",
            ' '
        ], '', $key);

        // 添加标准头尾
        return "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
    }

    /**
     * 格式化应用私钥
     */
    private function formatPrivateKey($key)
    {
        if (empty($key)) {
            return $key;
        }

        // 去除所有头尾标记和换行
        $cleanKey = str_replace([
            '-----BEGIN RSA PRIVATE KEY-----',
            '-----END RSA PRIVATE KEY-----',
            '-----BEGIN PRIVATE KEY-----',
            '-----END PRIVATE KEY-----',
            "\r",
            "\n",
            ' '
        ], '', $key);

        // 判断密钥类型（通过前几个字符）
        // PKCS#1: MIIEvQ, MIICXg, MIIE...
        // PKCS#8: MIIEvg, MIIJQg...
        $firstChars = substr($cleanKey, 0, 6);

        error_log("[ALIPAY] 私钥前6字符: {$firstChars}, 长度: " . strlen($cleanKey));

        // 尝试 PKCS#1 格式 (RSA PRIVATE KEY)
        if (strpos($firstChars, 'MIIEv') === 0 || strpos($firstChars, 'MIIC') === 0) {
            error_log("[ALIPAY] 使用 PKCS#1 格式 (RSA PRIVATE KEY)");
            return "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($cleanKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }

        // 默认使用 PKCS#8 格式 (PRIVATE KEY)
        error_log("[ALIPAY] 使用 PKCS#8 格式 (PRIVATE KEY)");
        return "-----BEGIN PRIVATE KEY-----\n" .
            wordwrap($cleanKey, 64, "\n", true) .
            "\n-----END PRIVATE KEY-----";
    }

}
