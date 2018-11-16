<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午1:00
 */

namespace classes\member;

use app\attendance\model\Attendance;
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

    //每天首次登录，日转
    public function first_login(\app\member\model\Member $member)
    {
        //没有积分
        if (empty($member->integral)) return '0';

        //今天已经参加日转
        $test = new Attendance();
        $test = $test->where('created_at', '>=', (date('Y-m-d ') . '00:00:00'))->where('member_id', '=', $member->id)->find();
        if (!is_null($test)) return '0';

        //初始化各种参数
        $setting = new Setting();
        $set = $setting->index();

        //会员加速
        if ($member->identify == '1') {

            $bili = floor($member->integral / $set['speedAmount']) * $set['speedAllot'];//加速比例
            $bili += $set['webScale'];//叠加基础速率
            if ($bili > $set['speedMax']) $bili = $set['speedMax'];//不得高于最大速率
        } else {

            $bili = $set['webScale'];
        }

        //日转积分
        $integer = number_format(($member->integral * $bili / 10000), 2, '.', '');

        //没有积分转出
        if ($integer <= 0) return '0';

        //转换资产
        $asset = number_format(($integer / $set['webApIntegral'] * $set['webApAsset']), 2, '.', '');

        //没有资产转入
        if ($asset <= 0) return '0';

        $member->integral -= $integer;
        $member->asset += $asset;
        $member->asset_all += $asset;
        $member->save();

        //赠送记录
        $record = new MemberRecord();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->type = 90;
        $record->created_at = date('Y-m-d H:i:s');
        $record->asset = $asset;
        $record->asset_now = $member->asset;
        $record->asset_act_now = $member->asset_act;
        $record->asset_all = $member->asset_all;
        $record->integral = 0 - $integer;
        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->content = '每日签到,日转『' . $set['webAliasPoint'] . '』' . $integer . '，获得『' . $set['webAliasAsset'] . '』' . $asset . '，日转速率：' . ($bili / 100) . '%';
        $record->save();

        $attendance = new Attendance();
        $attendance->integral = 0 - $integer;
        $attendance->integral_now = $member->integral;
        $attendance->asset = $asset;
        $attendance->asset_now = $member->asset;
        $attendance->proportion = $set['webApAsset'] . ':' . $set['webApIntegral'];
        $attendance->conversion = $bili;
        $attendance->member_id = $member->id;
        $attendance->member_account = $member->account;
        $attendance->member_nickname = $member->nickname;
        $attendance->member_create = $member->getData()['created_at'];

        $attendance->created_at = date('Y-m-d H:i:s');
        $attendance->save();

        session('first_login', '1');
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

        return $member;
    }

    //绑定微信id
    public function wechat_banding($id)
    {
        //获取openid
        $openid = session('openid');

        //没有
        if (is_null($openid)) return;

        //会员模型
        $member = new \app\member\model\Member();

        //获取符合条件的会员
        $member = $member->where('id', '=', $id)->find();

        //没有符合条件的会员
        if (is_null($member) || !empty($member->wechat_id)) return;

        //更新微信id
        $member->wechat_id = $openid;

        //保存
        $member->save();
    }

    //直充付款成功
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
}