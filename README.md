#wechat-payment

##微信支付类(WechatPayment)<br>

###JSAPI支付步骤:<br>
####1. 使用微信支付配置参数生成支付类实例<br>
            $config = array(<br>
                "appid"  => APPID,<br>
                "mch_id" => MCH_ID,<br>
                "key"    => KEY,<br>
            );<br>
            $payment = new \Library\WechatPayment($config);<br>
####2. 传递订单参数获取预支付id<br>
            $prepay_id = $payment->get_prepay_id(<br>
                            "一斤大白菜",       // 商品描述<br>
                            "E1234567890",      // 商户订单号<br>
                            "1",                // 总金额(单位：分)<br>
                            "http://example.com/pay/notify" // 通知地址<br>
                        );<br>
####3. 获取js调起支付的第二项参数<br>
            $package = $payment->get_package($prepay_id);<br>
####4. 使用js发起支付<br>
            print "<br>
                <script><br>
                    WeixinJSBridge.invoke("getBrandWCPayRequest", $package, function(data) {<br>
                        if (data.err_msg == "get_brand_wcpay_request:ok") {<br>
                            // 支付成功操作<br>
                        } else {<br>
                            // 支付失败操作<br>
                        }<br>
                    });<br>
                </script><br>
            ";<br>
