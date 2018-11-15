<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/10/18
 * Time: 下午3:23
 */

namespace classes\master;

use classes\FirstClass;

class Login extends FirstClass
{
    /**
     * 返回记住的账号
     *
     * @return mixed
     */
    public function account()
    {
        return session('master_account');
    }

    /**
     * 登录字段验证
     */
    public function validator_login()
    {
        //验证条件
        $rule = [
            'account' => 'require|max:20|min:6',
            'password' => 'require|max:20|min:6',
            'captcha' => 'require|captcha',
        ];

        $file = [
            'account' => '账号',
            'password' => '密码',
            'captcha' => '验证码',
        ];

        //验证描述
        $message = [
            /*'account.require' => '请输入账号',
            'account.between' => '账号长度错误',
            'password.require' => '请输入密码',
            'password.between' => '密码长度错误',
            'captcha.require' => '请输入验证码',
            'captcha.captcha' => '验证码输入错误',*/
        ];

        //验证
        $result = parent::validator(input(), $rule, $message, $file);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(101, $result);
    }

    /**
     * 登录方法，登录成功返回master模型
     *
     * @return \app\master\model\Master|array|false|\PDOStatement|string|\think\Model
     */
    public function login()
    {
        //初始化模型
        $master = new \app\master\model\Master();

        //尝试获取管理员信息
        $master = $master->where('account', '=', input('account'))
            ->where('password', '=', md5(input('password')))
            ->find();

        //获取失败，账密错误
        if (is_null($master)) parent::ajax_exception(102, '账号或密码错误');

        //返回管理员信息
        return $master;
    }

    /**
     * 记住账号功能
     *
     * @param $account
     */
    public function save_account($account)
    {
        if (input('recommend') == '1') session('master_account', $account);
    }

    /**
     * 修改登录信息
     *
     * @param \think\Model $master
     */
    public function refresh_master(\think\Model $master)
    {
        $master->login_times += 1;
        $master->login_time = date('Y-m-d H:i:s');
        $master->login_ip = $_SERVER["REMOTE_ADDR"];
        $master->save();
    }

    /**
     * 注销
     */
    public function logout()
    {
        session('master', null);
    }
}