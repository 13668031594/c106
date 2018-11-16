<?php

namespace app\member\controller;

use classes\index\Index;
use classes\index\Recharge;
use classes\vendor\StorageClass;
use classes\vendor\Wechat;
use think\Controller;
use think\Db;
use think\Exception;
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

        //首次登录
        $this->class->first_login($member);

        //绑定微信
        $this->class->wechat_banding($member->id);

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

        //注册验证
        $this->class->validator_reg();

        //注册
        $member = $this->class->reg();

        //注册奖励
        $this->class->reg_reward($member);

        //绑定微信
        $this->class->wechat_banding($member->id);

        Db::commit();

//        session('errors', '注册成功');

        return $this->class->success('/index-login');
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

//        exit($openid);

        //保存openid
        session('openid', $openid);

        //跳转到首页
        return redirect('/');
    }

    //直充订单回调
    public function notify_recharge(Request $request)
    {
        Db::startTrans();

        //初始化操作类
        $class = new Wechat();

        //获取微信回调信息，xml格式
        $xml = $request->getContent();

        //转为array
        $array = $class->xml_to_array($xml);

        //添加支付记录
        $model = $class->is_pay($array, $xml);

        //判断,已经添加过了
        if ($model === false) return 'success';

        //判断
        if (($array['return_code'] == 'SUCCESS') && ($array['result_code'] == 'SUCCESS')) {

            //付款成功
            $this->class->change_recharge($model->order_number);
        }

        Db::commit();

        //返回微信回调成功
        return 'success';
    }

    //激活订单回调
    public function notify_active(Request $request)
    {
        Db::startTrans();

        //初始化操作类
        $class = new Wechat();

        //获取微信回调信息，xml格式
        $xml = $request->getContent();

        //转为array
        $array = $class->xml_to_array($xml);

        //添加支付记录
        $model = $class->is_pay($array, $xml);

        //判断,已经添加过了
        if ($model === false) return 'success';

        //判断
        if (($array['return_code'] == 'SUCCESS') && ($array['result_code'] == 'SUCCESS')) {

            //付款成功
            $this->class->change_active($model->order_number);
        }

        Db::commit();

        //返回微信回调成功
        return 'success';
    }

    //短信发送
    public function sms_reg($phone)
    {
        //当前时间戳
        $time = time();

        //验证
        $this->class->validator_sms_register($phone, $time);

        //删除所有过期验证码
        $this->class->delete_sms($time);

        //发送
        $end = $this->class->send_sms($phone, $time);

        //反馈
        return $this->class->success('', '发送成功', ['time' => $end]);
    }

    //密码找回
    public function res()
    {
        return $this->class->view('password-back');
    }

    //密码找回短信
    public function sms_reset($phone)
    {
        //当前时间戳
        $time = time();

        //验证
        $this->class->validator_sms_reset($phone, $time);

        //删除所有过期验证码
        $this->class->delete_sms($time);

        //发送
        $end = $this->class->send_sms($phone, $time);

        //反馈
        return $this->class->success('', '发送成功', ['time' => $end]);
    }

    //密码找回方法
    public function reset()
    {
        $this->class->validator_reset();

        $this->class->reset();

        return $this->class->success();
    }
}
