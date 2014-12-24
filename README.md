#wechat-payment(编码依据：微信公众号支付接口文档v3.3.6)

##微信支付类(WechatPayment)

###JSAPI支付步骤:
####1. 使用微信支付配置参数生成支付类实例
            $config = array(
                "appid"  => APPID,
                "mch_id" => MCH_ID,
                "key"    => KEY,
            );
            $payment = new \Library\WechatPayment($config);
####2. 传递订单参数获取预支付id
            $prepay_id = $payment->get_prepay_id(
                            "一斤大白菜",       // 商品描述
                            "E1234567890",      // 商户订单号
                            "1",                // 总金额(单位：分)
                            "http://example.com/pay/notify" // 通知地址
                        );
####3. 获取js调起支付的第二项参数
            $package = $payment->get_package($prepay_id);
####4. 使用js发起支付
            print "
                <script>
                    WeixinJSBridge.invoke("getBrandWCPayRequest", $package, function(data) {
                        if (data.err_msg == "get_brand_wcpay_request:ok") {
                            // 支付成功操作
                        } else {
                            // 支付失败操作
                        }
                    });
                </script>
            ";
