<?php

namespace app\member\controller;

use classes\vendor\Wechat;
use think\Controller;
use think\Db;
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
        Db::startTrans();

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

        $this->class->first_login($member);

        Db::commit();

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

    //注册页面
    public function reg()
    {
        $account = input('referee_account');

        return $this->class->view('register', ['referee_account' => $account]);
    }

    //注册会员
    public function register()
    {
        Db::startTrans();
        $this->class->validator_reg();
        $member = $this->class->reg();
        $this->class->reg_reward($member);
        Db::commit();

        session('errors', ['注册成功']);

        return $this->class->success();
    }

    //静默授权，获取openid
    public function exchange_code()
    {
//        exit('123');
//        return redirect('http://zc.ythx123.com/#/transition/123');

        $url = 'http://zc.ythx123.com/#/transition';

        //初始化class
        $class = new Wechat();

        //找微信要数据
        $result = $class->openid();

        //赋值openid
        $openid = isset($result['openid']) ? $result['openid'] : null;

        exit($openid);

        //保存openid
        session('openid', $openid);

        //跳转到首页
        redirect('/');
    }
}
