<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午4:23
 */

namespace classes\index;


use app\member\model\Member;
use app\member\model\MemberRecord;
use app\recharge\model\RechargePay;
use classes\FirstClass;
use classes\setting\Setting;
use classes\vendor\Wechat;

class Recharge extends FirstClass
{
    public $member;

    public function __construct()
    {
        $this->member = parent::is_login_member();

        if ($this->member['status'] == '1') parent::ajax_exception(000, '您的账号被冻结了');
    }

    //验证下单字段
    public function validator_save()
    {
        $rule = [
            'amount' => 'require|integer|between:1,100000000',
            'pay_pass' => 'require',
            'webAjAmount' => 'require',
            'webAjJpj' => 'require',
        ];

        $message = [
            'webAjAmount.require' => '请刷新重试',
            'webAjJpj.require' => '请刷新重试',
        ];

        $file = [
            'amount' => '众筹金额',
            'pay_pass' => '支付密码'
        ];

        $result = parent::validator(input(), $rule, $message, $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //验证支付密码
        if (md5(input('pay_pass')) != $this->member['pay_pass']) parent::ajax_exception(000, '支付密码错误');

        //验证兑换比例
        $setting = new Setting();
        $set = $setting->index();

        if ((input('webAjAmount') != $set['webAjAmount']) || (input('webAjJpj') != $set['webAjJpj'])) parent::ajax_exception(000, '请刷新重试001');
    }

    //下单
    public function save()
    {
        //计算获得家谱卷
        $jpj = input('amount') / input('webAjAmount') * input('webAjJpj');

        $order = new \app\recharge\model\Recharge();
        $order->order_number = self::new_order();
        $order->total = input('amount');
        $order->jpj = number_format($jpj, 2, '.', '');
        $order->proportion = input('webAjAmount') . ':' . input('webAjJpj');
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

        $key = time();//时间戳

        //再随机2位字幕
        for ($i = 0; $i < 2; $i++) {
            $key .= $pattern[rand(0, 25)];    //生成php随机数
        }

        //验证订单号是否被占用
        $test = new \app\recharge\model\Recharge();
        $test = $test->where('order_number', '=', $test)->find();

        if (!is_null($test)) {

            return self::new_order();
        } else {

            return $key;
        }
    }

    public function pay($order)
    {
        if (isset($order['order_status']) && $order['order_status'] != '10') parent::ajax_exception(000, '订单已锁定，无法支付');
        if (empty($this->member['wechat_id'])) parent::ajax_exception(000, '请从微信公众号重新登录');

        $result = [
            'body' => '家谱众筹',
            'out_trade_no' => $order['order_number'] . '_' . time(),//订单号
//            'total_fee' => ($order['total'] * 100),//金额，精确到分
            'total_fee' => 1,//金额，精确到分
            'order_type' => 'recharge',//订单类型，回调路由组成部分
            'openid' => $this->member['wechat_id']
        ];

        $class = new Wechat();

        $result = $class->jsapi($result);

        //重新配置并获取微信签名
        $sign = $class->jsapi_sign($result);

        return $sign;
    }

    //付款成功
    public function change($order_number)
    {
        //寻找订单
        $recharge = new \app\recharge\model\Recharge();
        $recharge = $recharge->where('order_number', '=', $order_number)->where('order_status', '=', '10')->find();
        if (is_null($recharge)) return;

        //修改会员状态
        $member = new Member();
        $member = $member->where('id', '=', $recharge->member_id)->find();
        if (is_null($member)) return;
        $member->jpj += $recharge->jpj;
        $member->jpj_all += $recharge->jpj_all;
        $member->total += $recharge->total;
        $member->save();

        //时间
        $date = date('Y-m-d H:i:s');
        $setting = new Setting();
        $set = $setting->index();

        //修改订单状态
        $recharge->pay_status = 1;
        $recharge->pay_type = 1;
        $recharge->pay_date = $date;
        $recharge->order_status = 20;
        $recharge->change_id = $recharge->member_id;
        $recharge->change_nickname = $recharge->member_nickname;
        $recharge->change_date = $date;
        $recharge->save();

        //添加会员记录
        $record = new MemberRecord();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->type = 21;
        $record->content = '直充订单成功付款，获得『' . $set['webAliasJpj'] . '』' . $recharge->jpj;
        $record->created_at = $date;
        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->asset_now = $member->asset;
        $record->asset_act_now = $member->asset_act;
        $record->asset_all = $member->asset_all;
        $record->jpj = $recharge->jpj;
        $record->jpj_now = $member->jpj;
        $record->jpj_all = $member->jpj_all;
        $record->save();
    }

    //轮询
    public function info($id)
    {
        $recharge = new \app\recharge\model\Recharge();
        $recharge = $recharge->where('id', '=', $id)->where('order_status', '==', '10')->find();
        if (is_null($recharge)) parent::ajax_exception(000, '');
    }
}