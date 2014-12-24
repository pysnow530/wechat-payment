<?php
/**
 * @file WechatPayment.class.php
 * @description 微信支付类
 *
 * @author pysnow530, pysnow530@163.com
 * @date 2014年12月23日 17:24:47
 */
namespace Library;

class WechatPayment {

    /**
     * 微信支付配置数组
     * appid  公众账号
     * mch_id 商户号
     * key    加密key
     */
    private $_config;

    /**
     * 保存传入的数据
     */
    private $_nonce_str;

    /**
     * 错误信息
     */
    public $error = null;

    const PREPAY_GATEWAY = "https://api.mch.weixin.qq.com/pay/unifiedorder";

    /**
     * @param $config 微信支付配置数组
     */
    public function __construct($config) {
        $this->_config = $config;
    }

    /**
     * 获取预支付ID
     * @param $body         商品描述
     * @param $out_trade_no 商户订单号
     * @param $total_fee    总金额(单位分)
     * @param $notify_url   通知地址
     * @param $trade_type   交易类型
     */
    public function get_prepay_id($body, $out_trade_no, $total_fee,
            $notify_url, $trade_type="JSAPI") {
        $data = array();
        $data["appid"]        = $this->_config["appid"];
        $data["mch_id"]       = $this->_config["mch_id"];
        $data["nonce_str"] = $this->_nonce_str = str_shuffle("asdfghjkl");
        $data["body"]         = $body;
        $data["out_trade_no"] = $out_trade_no;
        $data["total_fee"]    = $total_fee;
        $data["spbill_create_ip"] = $_SERVER["REMOTE_ADDRESS"];
        $data["notify_url"]   = $notify_url;
        $data["trade_type"]   = $trade_type;

        $result = $this->post(self::PREPAY_GATEWAY, $data);
        if ($result["return_code"] == "SUCCESS") {
            return $result["prepay_id"];
        } else {
            $this->error = $result["return_msg"];
            return null;
        }
    }

    /**
     * 获取js支付使用的第二个参数
     */
    public function get_package($prepay_id) {
        $data = array();
        $data["appId"] = $this->_config["appid"];
        $data["timeStamp"] = time();
        $data["nonceStr"]  = $this->_nonce_str;
        $data["package"]   = "prepay_id=$prepay_id";
        $data["signType"]  = "MD5";
        $data["paySign"]   = $this->sign($data);

        return json_encode($data);
    }

    public function array2xml($array) {
        $xml = "<xml>" . PHP_EOL;
        foreach ($array as $k => $v) {
            $xml .= "<$k><![CDATA[$v]]></$k>" . PHP_EOL;
        }
        $xml .= "</xml>";

        return $xml;
    }

    public function xml2array($xml) {
        $array = array();
        foreach ((array) simplexml_load_string($xml) as $k => $v) {
            $array[$k] = (string) $v;
        }

        return $array;
    }

    public function post($url, $data) {
        $data["sign"] = $this->sign($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->array2xml($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        $array = $this->xml2array($content);

        return $array;
    }

    public function sign($data) {
        ksort($data);
        $string1 = "";
        foreach ($data as $k => $v) {
            if ($v) {
                $string1 .= "$k=$v&";
            }
        }
        $stringSignTemp = $string1 . "key=" . $this->_config["key"];
        $sign = strtoupper(md5($stringSignTemp));

        return $sign;
    }

}
