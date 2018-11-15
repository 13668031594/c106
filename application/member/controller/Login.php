<?php

namespace app\member\controller;

use think\Controller;
use think\Request;

class Login extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\member\Login();
    }

    //登录页面
    public function view()
    {
        //获取保存的账号
        $account = $this->class->account();

        //视图
        return $this->class->view('login', ['account' => $account]);
    }

    //登录方法
    public function login()
    {
        //验证字段
        $this->class->validator_login();

        //验证登录
        $member = $this->class->login();

        //修改管理员登录信息
        $this->class->refresh_member($member);

        //保存账号
        $this->class->save_account($member->account);

        //保存登陆者session
        $this->class->refresh_login_member($member->id);

        //重定向到首页
        $this->redirect('/');
    }

    //注销方法
    public function logout()
    {
        //注销管理员session
        $this->class->logout();

        //调用view方法
        return self::view();
    }
}
