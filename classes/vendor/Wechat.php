<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午5:02
 */

namespace classes\vendor;

use app\recharge\model\RechargePay;
use classes\set\ContrastArrays;
use classes\set\LoginSet;

class Wechat
{
    //微信接口地址
    public $wechat_url = 'https://api.weixin.qq.com';

    //通行证缓存名称
    public $cache_name = 'item\Wechat\token';

    //公众号id
    public $appid = 'wxf5dce90eea5f9fef';

    //公众号密码
    public $secret = 'f8c075ccf1457a63b30f2f2a74dd8c44';

    //加密密匙，随机生成
    public $EncodingAESKey = 'M6T1OclkiLeUWXZMIj7rkG1YxisUKXnZ';

    //商户号
    public $wechat_id = '1508961381';

    //票据
    public $access_token;

    private $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    private $set;

    public $time;

    /**
     * 公众号支付
     *
     * @param $order
     * @return mixed
     */
    public function jsapi($order)
    {
        //进行配置
        self::set($order, 'JSAPI');

        //加入openid
        $this->set['openid'] = $order['openid'];

        return self::wechat_pay_set();
    }

    public function wechat_pay_set()
    {
        //配置签名
        $this->set['sign'] = self::sign($this->set);

        //进行支付
        $result = self::pay();

        //反馈
        return $result;
    }

    /**
     * 进行配置
     *
     * @param $order
     * @param $trade_type
     */
    private function set($order, $trade_type)
    {
        //判断订单信息
        if (!isset($order['body']) || is_null($order['body'])) exit('请传入订单标题');
        if (!isset($order['out_trade_no']) || is_null($order['out_trade_no'])) exit('请传入订单号');
        if (!isset($order['total_fee']) || is_null($order['total_fee'])) exit('请传入订单金额');
        if (!isset($order['order_type']) || is_null($order['order_type'])) exit('请传入订单类型');

        $login_set = new LoginSet();

        $this->time = time();

        $set = [
            'appid' => $this->appid,
            'mch_id' => $this->wechat_id,
            'nonce_str' => md5($this->time . rand(000, 999)),
            'body' => $order['body'],
            'out_trade_no' => $order['out_trade_no'],
            'total_fee' => $order['total_fee'],
            'spbill_create_ip' => '14.111.54.177',
            'notify_url' => $login_set->url . '/wechat-notify-' . $order['order_type'],
            'trade_type' => $trade_type,
        ];

        $this->set = $set;
    }

    /**
     * 生成签名
     *
     * @param $set
     * @return string
     */
    private function sign($set)
    {
        //排序
        ksort($set, SORT_STRING);

        return self::sign_implode($set);
    }

    /**
     * 生成签名方法
     *
     * @param $set
     * @return string
     */
    private function sign_implode($set)
    {
        //初始化字符串
        $stringArray = [];

        //循环组合字符串
        foreach ($set as $k => $v) {

            $str = $k . '=' . $v;

            $stringArray[] = $str;
        }

        $stringA = implode('&', $stringArray);

        $stringSignTemp = $stringA . '&key=' . $this->EncodingAESKey;//拼接

        $sign = md5($stringSignTemp);//加密
        $sign = strtoupper($sign);//大写

        //反馈
        return $sign;
    }

    /**
     * 访问微信支付客户端
     *
     * @return mixed
     */
    private function pay()
    {
        $set = self::array_to_xml($this->set);

        $result = self::url_post($this->url, $set);

        $result = self::xml_to_array($result);

        return $result;
    }

    /**
     * 格式转换，array转xml
     *
     * @param $array
     * @return string
     */
    public function array_to_xml($array)
    {
        $xml = "<xml>\n";

        foreach ($array as $key => $val) {

            if (is_array($val)) {

                $xml .= "<" . $key . ">" . self::array_to_xml($val) . "</" . $key . ">\n";
            } else {

                $xml .= "<" . $key . ">" . $val . "</" . $key . ">\n";
            }
        }

        $xml .= "</xml>";

        return $xml;
    }


    /**
     * 格式转换，xml转array
     *
     * @param $xml
     * @return mixed
     */
    public function xml_to_array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    public function jsapi_sign($result)
    {
        $array = [
            'appId' => $result['appid'],
            'timeStamp' => (string)$this->time,
            'nonceStr' => $result['nonce_str'],
            'package' => 'prepay_id=' . $result['prepay_id'],
            'signType' => 'MD5',
        ];

        $array['paySign'] = self::sign($array);

        return $array;
    }

    /**
     * 静默获取openid
     *
     * @return mixed
     */
    public function openid()
    {
        $code = $_GET['code'];

        $url = $this->wechat_url . '/sns/oauth2/access_token?appid=' . $this->appid . '&secret=' . $this->secret . '&code=' . $code . '&grant_type=authorization_code';

        $result = self::get_wechat_json($url);

        return $result;
    }

    /**
     * 访问微信，并然会结果数组,返回json
     *
     * @param $url
     * @return mixed
     */
    public function get_wechat_json($url)
    {
        $result = self::url_get($url);

        return json_decode($result, true);
    }

    /**
     * 访问url，get
     *
     * @param string $url
     * @return mixed|string
     */
    public function url_get($url)
    {
        //初始化一个curl会话
        $ch = curl_init();
        //初始化CURL回话链接地址，设置要抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        //对认证证书来源的检查，FALSE表示阻止对证书的合法性检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //设置将获得的结果是否保存在字符串中还是输出到屏幕上，0输出，非0不输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //执行请求，获取结果
        $result = curl_exec($ch);
        //关闭会话
        curl_close($ch);

        //反馈结果
        return $result;
    }

    /**
     * 访问url，post
     *
     * @param $url
     * @param $post_data
     * @param int $timeout
     * @return mixed
     */
    public function url_post($url, $post_data, $timeout = 5)
    {
        /*$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($post_data != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
//            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);
//        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https路径必填参数
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//https路径必填参数
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);//https路径必填参数
        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 添加支付记录
     *
     * @param $array
     * @param $xml
     * @return RechargePay|bool
     */
    public function is_pay($array, $xml)
    {
        $out_trade_no = $array['out_trade_no'];
        list($order, $time) = explode('_', $out_trade_no);

        $date = date('Y-m-d H:i:s', $time);

        $test = new RechargePay();
        $test = $test->where('order_number', '=', $order)->where('make_time', '=', $date)->find();

        if (!is_null($test)) return false;

        $model = new RechargePay();
        $model->xml = $xml;
        $model->order_number = $order;
        $model->total = ($array['total_fee'] / 100);
        $model->trade_type = $array['trade_type'];
        $model->make_time = $date;
        $model->return_code = $array['return_code'];
        $model->result_code = $array['result_code'];
        $model->save();

        return $model;
    }
}