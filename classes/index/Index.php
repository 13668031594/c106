<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午3:07
 */

namespace classes\index;


use app\active\model\Active;
use app\adv\model\Adv;
use app\article\model\Article;
use app\member\model\MemberRecord;
use app\notice\model\Notice;
use classes\FirstClass;
use classes\member\Member;
use classes\set\ContrastArrays;
use classes\set\LoginSet;
use classes\setting\PayAgreement;
use classes\setting\Setting;
use classes\vendor\Wechat;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Index extends FirstClass
{
    public $member;

    public function __construct()
    {
        $this->member = parent::is_login_member();
    }

    public function view($view, $data = [])
    {
        $data['errors'] = null;
        $data['success'] = null;

        //获取提示
        $errors = session('errors');
        $success = session('success');

        //初始化errors视图变量
        if (!is_null($errors) && is_string($errors)) $data['errors'] = $errors;

        //初始化success视图变量
        if (!is_null($success) && is_string($success)) $data['success'] = $success;

        //删除提示
        session('errors', null);
        session('success', null);

        //设定model
        $model = new Setting();
        $data = array_merge($data, $model->index());

        //渲染视图
        return view($view, $data);
    }

    public function header2($top = '众筹', $array = [])
    {
        //获取公告
        $model = new Notice();
        $result = $model->order('created_at', 'desc')->column('title');
        if (count($result) > 1) {
            $first = array_shift($result);
            array_unshift($result, $first);
            $result[] = $first;
        }
        $array['notice'] = $result;

        //获取广告
        $model = new Adv();
        $result = $model->order('sort', 'asc')->where('show', '=', 'on')->column('id,location,describe');
        foreach ($result as &$v) {
            if (is_null($v['location'])) $v['location'] = 'favicon.ico';
        }
        $array['adv'] = $result;

        $array['member'] = $this->member;

        $array['top'] = $top;

        return $array;
    }

    //公告
    public function notice()
    {
        $model = new Notice();
        $result = parent::page($model);

        return $result;
    }

    //文章
    public function article()
    {
        //获取公告
        $model = new Article();
        $result = parent::page($model);

        return $result;
    }

    //文章详情
    public function article_info($id)
    {
        //获取公告
        $model = new Article();
        $result = $model->where('id', '=', $id)->find();

        if (is_null($result)) parent::redirect_exception('/index-information-hy', '文章不存在');

        return $result->getData();
    }

    //支付协议
    public function pay_agreement()
    {
        $model = new PayAgreement();

        return $model->index();
    }

    //资产记录
    public function record()
    {
        //获取公告
        $model = new Member();

        $result = $model->record($this->member['id']);

        $model = new ContrastArrays();

        $record_array = $model->member_record();

        foreach ($result['message'] as &$v) $v['type'] = $record_array[$v['type']];

        return $result;
    }

    //转入二维码
    public function make_qr($dir, $url)
    {
        $class = new LoginSet();

        //不带LOGO
        vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $url = $class->url . $url;//网址或者是文本内容
        $level = 3;
        $size = 8;
        $ad = $dir . '/' . $this->member['id'] . '.jpg';
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);

        return '/' . $ad;
    }

    //转出二维码
    public function out_man($id)
    {
        $model = new \app\member\model\Member();

        $member = $model->where('id', '=', $id)->find();

        if (is_null($member)) return null;

        return $member->getData();
    }

    //修改昵称
    public function nickname()
    {
        $rule = [
            'nickname' => 'require|min:1|max:48'
        ];

        $file = [
            'nickname' => '昵称',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $member['id'] = $this->member['id'];
        $member['nickname'] = input('nickname');
        $model = new \app\member\model\Member();
        $model->saveAll([$member]);

    }

    //修改登录密码
    public function password()
    {
        $rule = [
            'old' => 'require|min:6|max:20',
            'new' => 'require|min:6|max:20',
            'again' => 'require|min:6|max:20'
        ];

        $file = [
            'old' => '旧密码',
            'new' => '新密码',
            'again' => '确认密码',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $old = input('old');
        $new = input('new');
        $again = input('again');
        if ($new != $again) parent::ajax_exception(000, '确认密码输入错误');
        if (md5($old) != $this->member['password']) parent::ajax_exception(000, '旧密码输入错误');

        $member['id'] = $this->member['id'];
        $member['password'] = md5($new);
        $model = new \app\member\model\Member();
        $model->saveAll([$member]);
    }

    //修改支付密码
    public function pay_pass()
    {
        $rule = [
            'old' => 'require|min:6|max:20',
            'new' => 'require|min:6|max:20',
            'again' => 'require|min:6|max:20'
        ];

        $file = [
            'old' => '旧密码',
            'new' => '新密码',
            'again' => '确认密码',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $old = input('old');
        $new = input('new');
        $again = input('again');
        if ($new != $again) parent::ajax_exception(000, '确认密码输入错误');
        if (md5($old) != $this->member['pay_pass']) parent::ajax_exception(000, '旧密码输入错误');

        $member['id'] = $this->member['id'];
        $member['pay_pass'] = md5($new);
        $model = new \app\member\model\Member();
        $model->saveAll([$member]);
    }


    //验证下单字段
    public function validator_act()
    {
        $rule = [
            'amount' => 'require|integer|between:1,100000000',
            'pay_pass' => 'require',
            'payActAllot' => 'require',
        ];

        $message = [
            'payActAllot.require' => '请刷新重试',
        ];

        $file = [
            'amount' => '激活资产',
            'pay_pass' => '支付密码'
        ];

        $result = parent::validator(input(), $rule, $message, $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //验证支付密码
        if (md5(input('pay_pass')) != $this->member['pay_pass']) parent::ajax_exception(000, '支付密码错误');

        //验证兑换比例
        $setting = new Setting();
        $set = $setting->index();

        if ((input('payActAllot') != $set['payActAllot'])) parent::ajax_exception(000, '请刷新重试001');
    }

    //下单
    public function act()
    {
        //计算获得支付金额
        $amount = input('amount');
        $bili1 = input('payActAllot') <= 0 ? 0 : (input('payActAllot') / 10000);
        $bili2 = input('payActAllot') <= 0 ? 0 : (input('payActAllot') / 100);
        $total = number_format(($amount * $bili1), 2, '.', '');

        $total = ($total < 1) ? 1 : $total;

        $order = new Active();
        $order->order_number = self::new_order();
        $order->total = $total;
        $order->asset = $amount;
        $order->proportion = $bili2 . '%';
        $order->member_id = $this->member['id'];
        $order->member_account = $this->member['account'];
        $order->member_nickname = $this->member['nickname'];
        $order->member_create = $this->member['created_at'];
        $order->created_at = date('Y-m-d H:i:s');
        $order->save();

        return $order->getData();
    }

    //获取新的订单号
    private function new_order()
    {
        $pattern = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';//字幕字符串

        $key = '';

        //再随机2位字幕
        for ($i = 0; $i < 2; $i++) {
            $key .= $pattern[rand(0, 25)];    //生成php随机数
        }

        $key .= time();//时间戳

        //验证订单号是否被占用
        $test = new \app\recharge\model\Recharge();
        $test = $test->where('order_number', '=', $test)->find();

        if (!is_null($test)) {

            return self::new_order();
        } else {

            return $key;
        }
    }

    public function act_pay($order)
    {
        if (isset($order['order_status']) && $order['order_status'] != '10') parent::ajax_exception(000, '订单已锁定，无法支付');
        if (empty($this->member['wechat_id'])) parent::ajax_exception(000, '请从微信公众号重新登录');

        $result = [
            'body' => '资产激活',
            'out_trade_no' => $order['order_number'] . '_' . time(),//订单号
//            'total_fee' => ($order['total'] * 100),//金额，精确到分
            'total_fee' => 1,//金额，精确到分
            'order_type' => 'active',//订单类型，回调路由组成部分
            'openid' => $this->member['wechat_id']
        ];

        $class = new Wechat();

        $result = $class->jsapi($result);

        //重新配置并获取微信签名
        $sign = $class->jsapi_sign($result);

        return $sign;
    }

}