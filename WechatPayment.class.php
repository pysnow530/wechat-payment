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
     * 错误信息
     */
    public $error = null;

    const PREPAY_GATEWAY = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    const QUERY_GATEWAY = 'https://api.mch.weixin.qq.com/pay/orderquery';

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
            $notify_url, $trade_type='JSAPI') {
        $data = array();
        $data['appid']        = $this->_config['appid'];
        $data['mch_id']       = $this->_config['mch_id'];
        $data['nonce_str']    = $this->get_nonce_string();
        $data['body']         = $body;
        $data['out_trade_no'] = $out_trade_no;
        $data['total_fee']    = $total_fee;
        $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['notify_url']   = $notify_url;
        $data['trade_type']   = $trade_type;

        $result = $this->post(self::PREPAY_GATEWAY, $data);
        if ($result['return_code'] == 'SUCCESS') {
            return $result['prepay_id'];
        } else {
            $this->error = $result['return_msg'];
            return null;
        }
    }

    /**
     * 获取js支付使用的第二个参数
     */
    public function get_package($prepay_id) {
        $data = array();
        $data['appId'] = $this->_config['appid'];
        $data['timeStamp'] = time();
        $data['nonceStr']  = $this->get_nonce_string();
        $data['package']   = 'prepay_id=' . $prepay_id;
        $data['signType']  = 'MD5';
        $data['paySign']   = $this->sign($data);

        return $data;
    }

    /**
     * 获取发送到通知地址的数据(在通知地址内使用)
     * @return 结果数组，如果不是微信服务器发送的数据返回null
     *          appid
     *          bank_type
     *          cash_fee
     *          fee_type
     *          is_subscribe
     *          mch_id
     *          nonce_str
     *          openid
     *          out_trade_no    商户订单号
     *          result_code
     *          return_code
     *          sign
     *          time_end
     *          total_fee       总金额
     *          trade_type
     *          transaction_id  微信支付订单号
     */
    public function get_back_data() {
        $xml = file_get_contents('php://input');
        $data = $this->xml2array($xml);
        if ($this->validate($data)) {
            return $data;
        } else {
            return null;
        }
    }

    /**
     * 响应微信支付后台通知
     * @param $return_code 返回状态码 SUCCESS/FAIL
     * @param $return_msg  返回信息
     */
    public function response_back($return_code='SUCCESS', $return_msg=null) {
        $data = array();
        $data['return_code'] = $return_code;
        if ($return_msg) {
            $data['return_msg'] = $return_msg;
        }
        $xml = $this->array2xml($data);

        print $xml;
    }

    /**
     * 订单查询接口
     * $param out_trade_no 商户订单号
     * @return 字符串，交易状态
     *          SUCCESS     支付成功
     *          REFUND      转入退款
     *          NOTPAY      未支付
     *          CLOSED      已关闭
     *          REVOKED     已撤销
     *          USERPAYING  用户支付中
     *          NOPAY       未支付
     *          PAYERROR    支付失败
     *          null        订单不存在或其它错误，错误描述$this->error
     */
    public function query_order($out_trade_no) {
        $data = array();
        $data['appid']        = $this->_config['appid'];
        $data['mch_id']       = $this->_config['mch_id'];
        $data['out_trade_no'] = $out_trade_no;
        $data['nonce_str']    = $this->get_nonce_string();
        $result = $this->post(self::QUERY_GATEWAY, $data);
        if ($result['result_code'] == 'SUCCESS') {
            return $result['trade_state'];
        } else {
            $this->error = $result['err_code_des'];
            return null;
        }
    }

    public function array2xml($array) {
        $xml = '<xml>' . PHP_EOL;
        foreach ($array as $k => $v) {
            $xml .= '<$k><![CDATA[$v]]></$k>' . PHP_EOL;
        }
        $xml .= '</xml>';

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
        $data['sign'] = $this->sign($data);

        if (!function_exists('curl_init')) {
            throw new \Exception('Please enable php curl module!');
        }
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
        $string1 = '';
        foreach ($data as $k => $v) {
            if ($v) {
                $string1 .= '$k=$v&';
            }
        }
        $stringSignTemp = $string1 . 'key=' . $this->_config['key'];
        $sign = strtoupper(md5($stringSignTemp));

        return $sign;
    }

    /**
     * 验证是否是腾讯服务器推送数据
     * @param $data 数据数组
     * @return 布尔值
     */
    public function validate($data) {
        if (!isset($data['sign'])) {
            return false;
        }

        $sign = $data['sign'];
        unset($data['sign']);

        return $this->sign($data) == $sign;
    }

    public function get_nonce_string() {
        return str_shuffle('pysnow530pysnow530pysnow530');
    }

}
