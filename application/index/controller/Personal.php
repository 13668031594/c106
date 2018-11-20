<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/16
 * Time: 上午12:06
 */

namespace app\index\controller;


use think\Controller;
use think\Request;

class Personal extends Controller
{
    private $class;
    public $member;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\index\Index();

        $this->class->web_close();

        session('floor', 'self');
    }

    //个人中心
    public function personal()
    {
        return $this->class->view('personal', ['member' => $this->class->member]);
    }

    //个人资料
    public function self()
    {
        return $this->class->view('data', ['member' => $this->class->member]);
    }

    //修改昵称
    public function nickname()
    {
        $this->class->nickname();
        return $this->class->success('/');
    }

    //登录密码修改页面
    public function pass()
    {
        return $this->class->view('login-password-change', ['member' => $this->class->member]);
    }

    //修改登录密码
    public function password()
    {
        $this->class->password();
        return $this->class->success('/');
    }

    //支付密码修改页面
    public function pay_pass()
    {
        return $this->class->view('pay-password-change', ['member' => $this->class->member]);
    }

    //支付密码修改
    public function pay_password()
    {
        $this->class->pay_pass();
        return $this->class->success('/');
    }

    //分享
    public function share()
    {
        $route = '/index-reg?referee_account=' . $this->class->member['account'];

        $url = $this->class->make_qr('share', $route);

        return $this->class->view('shared', ['url' => $url]);
    }

    //激活资产页面
    public function act()
    {
        //未完结的订单
        $test = $this->class->active_test();

        //有
        if (!is_null($test)) {

            $recharge = $test->getData();

            $wechat = $this->class->act_pay($recharge);
//dump($recharge);
            return $this->class->view('activate-info', ['order' => $recharge, 'wechat' => json_encode($wechat)]);
        } else {

            return $this->class->view('activate', ['member' => $this->class->member]);
        }

    }

    //激活资产
    public function acted()
    {
        $this->class->validator_act();

        $order = $this->class->act();

        $result = $this->class->act_pay($order);

        return $this->class->success('', null, ['wechat' => $result, 'order' => $order]);
    }

    //激活轮询
    public function info($id)
    {
        $this->class->info($id);

        return $this->class->success();
    }

    //撤销激活订单
    public function act_out($id)
    {
        $this->class->out($id);

        return $this->class->success('/');
    }
}