<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午1:00
 */

namespace classes\member;


use app\notice\model\Notice;
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
        return session('member_account');
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
        ];

        $file = [
            'account' => '账号',
            'password' => '密码',
            'captcha' => '验证码',
        ];

        //验证
        $result = parent::validator(input(), $rule, [], $file);

        //有错误报告则报错
        if (!is_null($result)) parent::redirect_exception('/index-login', $result);
    }

    /**
     * 登录方法，登录成功返回member模型
     *
     * @return \app\member\model\Member|array|false|\PDOStatement|string|\think\Model
     */
    public function login()
    {
        //初始化模型
        $member = new \app\member\model\Member();

        //尝试获取管理员信息
        $member = $member->where('account', '=', input('account'))
            ->where('password', '=', md5(input('password')))
            ->find();

        //获取失败，账密错误
        if (is_null($member)) parent::redirect_exception('/index-login', '账号或密码错误');

        //返回管理员信息
        return $member;
    }

    /**
     * 记住账号功能
     *
     * @param $account
     */
    public function save_account($account)
    {
        if (input('recommend') == '1') session('member_account', $account);
    }

    /**
     * 修改登录信息
     *
     * @param \think\Model $member
     */
    public function refresh_member(\think\Model $member)
    {
        $member->login_times += 1;
        $member->login_time = date('Y-m-d H:i:s');
        $member->login_ip = $_SERVER["REMOTE_ADDR"];
        $member->save();
    }

    /**
     * 注销
     */
    public function logout()
    {
        session('member', null);
    }
}