# 微信支付类

> 该类主要提供php对微信支付的封装。

**IMPORT**：由于本人已很久没有做微信支付方面的开发，该支付类可能已经过时，所以不要在生产环境使用该支付类。大家可以把这份实现当作一个demo学习，有微信支付方面的问题欢迎讨论交流。谢谢支持！

## 编码依据

该类的编写主要参考《[微信公众号支付接口文档v3.3.6](api_v3.3.6.pdf)》。

## 使用说明

具体的使用方式参考[demo.php](demo.php)。

    <?php
    require __DIR__ . '/WechatPayment.class.php';

    $config = array(
        'appid'  => 'APPID',
        'mch_id' => 'MCH_ID',
        'key'    => 'KEY',
    );
    $payment = new \Library\WechatPayment($config);
    $prepay_id = $payment->get_prepay_id(
        '一斤大白菜',       // 商品描述
        'E1234567890',      // 商户订单号
        '1',                // 总金额(单位：分)
        'http://example.com/pay/notify' // 通知地址
    );
    $package = json_encode($payment->get_package($prepay_id));
    echo '
        <script>
            WeixinJSBridge.invoke("getBrandWCPayRequest", ' . $package . ', function(data) {
                if (data.err_msg == "get_brand_wcpay_request:ok") {
                    // 支付成功操作
                } else {
                    // 支付失败操作
                }
            });
        </script>
    ';
