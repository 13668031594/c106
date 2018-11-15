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
        return $this->class->success();
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
        return $this->class->success();
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
        return $this->class->success();
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
        return $this->class->view('activate', ['member' => $this->class->member]);
    }

    //激活资产
    public function acted()
    {
        return $this->class->success();
    }
}