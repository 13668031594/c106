<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午1:00
 */

namespace classes\member;

use app\member\model\MemberRecord;
use classes\FirstClass;
use classes\setting\Setting;

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

    /**
     * 注册字段验证
     */
    public function validator_reg()
    {
        //验证条件
        $rule = [
            'account' => 'require|max:20|min:6',
            'pass' => 'require|max:20|min:6',
            'again' => 'require|max:20|min:6',
            'referee_account' => 'min:6|max:20'
        ];

        $file = [
            'account' => '账号',
            'pass' => '密码',
            'again' => '验证码',
            'referee_account' => '推广账号'
        ];

        //验证
        $result = parent::validator(input(), $rule, [], $file);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $pass = input('pass');
        $again = input('again');

        if ($pass != $again) parent::ajax_exception(000, '确认密码输入错误');

        //初始化模型
        $member = new \app\member\model\Member();
        //尝试获取管理员信息
        $member = $member->where('account', '=', input('account'))->find();
        //获取失败，账密错误
        if (!is_null($member)) parent::ajax_exception(000, '账号被占用');
    }

    /**
     * 注册方法，登录成功返回member模型
     *
     * @return \app\member\model\Member|array|false|\PDOStatement|string|\think\Model
     */
    public function reg()
    {
        $member = new \app\member\model\Member();

        $member = self::referee_add($member);

        $phone = input('account');
        $nickname = substr($phone, 0, 3) . '****' . substr($phone, 7);
        $pass = input('pass');

        $member->phone = $phone;
        $member->account = $phone;
        $member->nickname = $nickname;
        $member->password = md5($pass);
        $member->pay_pass = md5($pass);
        $member->asset = 0;
        $member->asset_act = 0;
        $member->asset_all = 0;
        $member->save();

        //返回管理员信息
        return $member;
    }

    private function referee_add(\app\member\model\Member $member)
    {
        $p_account = input('referee_account');
        if (empty($p_account)) return $member;

        $test = new \app\member\model\Member();
        $referee = $test->where('account', '=', input('referee_account'))->find();
        if (is_null($referee)) parent::ajax_exception(000, '推广人不存在');

        $referee = $referee->getData();

        $families = empty($referee['families']) ? $referee['id'] : ($referee['families'] . ',' . $referee['id']);

        $member->families = $families;//上级缓存
        $member->referee_id = $referee['id'];//上级id
        $member->referee_account = $referee['account'];//上级账号
        $member->referee_nickname = $referee['nickname'];//上级昵称
        $member->level = $referee['level'] + 1;//自身层级


        return $member;
    }

    public function reg_reward(\app\member\model\Member $member)
    {
        $setting = new Setting();
        $set = $setting->index();

        //没有赠送
        if (($set['userRegisterAsset1'] <= 0) && ($set['userRegisterAsset2'] <= 0)) return $member;

        //赠送记录
        $record = new MemberRecord();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->type = 80;
        $record->created_at = date('Y-m-d H:i:s');
        $record->asset = 0;
        $record->asset_now = 0;
        $record->asset_act = 0;
        $record->asset_act_now = 0;
        $record->asset_all = 0;
        $record->content = '注册赠送；';

        //赠送未激活
        if ($set['userRegisterAsset1'] > 0) {

            $member->asset += $set['userRegisterAsset1'];
            $member->asset_all += $set['userRegisterAsset1'];

            $record->asset += $set['userRegisterAsset1'];
            $record->asset_now += $set['userRegisterAsset1'];
            $record->asset_all += $set['userRegisterAsset1'];
            $record->content .= '赠送『' . $set['webAliasAsset'] . '』' . $set['userRegisterAsset1'] . '；';
        }

        //赠送激活
        if ($set['userRegisterAsset2'] > 0) {

            $member->asset_act += $set['userRegisterAsset2'];
            $member->asset_all += $set['userRegisterAsset2'];

            $record->asset_act += $set['userRegisterAsset2'];
            $record->asset_act_now += $set['userRegisterAsset2'];
            $record->asset_all += $set['userRegisterAsset2'];
            $record->content .= '赠送『激活' . $set['webAliasAsset'] . '』' . $set['userRegisterAsset2'] . '；';
        }

        //保存
        $member->save();
        $record->save();
    }
}