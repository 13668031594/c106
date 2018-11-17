<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午1:00
 */

namespace classes\member;

use app\active\model\Active;
use app\attendance\model\Attendance;
use app\member\model\MemberRecord;
use app\member\model\Sms;
use classes\FirstClass;
use classes\setting\Setting;
use classes\vendor\StorageClass;

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
        $integer = $asset = number_format(($member->integral * $bili / 10000), 2, '.', '');

        //没有积分转出
        if ($integer <= 0) return '0';

        //转换资产
//        $asset = number_format(($integer / $set['webApIntegral'] * $set['webApAsset']), 2, '.', '');

        //没有资产转入
//        if ($asset <= 0) return '0';

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
        $attendance->proportion = '1:1';
        $attendance->conversion = $bili;
        $attendance->member_id = $member->id;
        $attendance->member_account = $member->account;
        $attendance->member_nickname = $member->nickname;
        $attendance->member_create = $member->getData()['created_at'];

        $attendance->created_at = date('Y-m-d H:i:s');
        $attendance->save();

        session('first_login', $asset);
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
        $member->login_ass = md5(time() . rand(100, 999));
        $member->save();

        return $member->login_ass;
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
            'account' => 'require|max:20|min:6|unique:member,account',
            'pass' => 'require|max:20|min:6',
            'again' => 'require|max:20|min:6',
            'referee_account' => 'min:6|max:20',
            'code' => 'require|length:5'
        ];

        $file = [
            'account' => '账号',
            'pass' => '密码',
            'again' => '验证码',
            'referee_account' => '推广账号',
            'code' => '验证码',
        ];

        //验证
        $result = parent::validator(input(), $rule, [], $file);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        self::test_phone_code();//手机验证码

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

        $member->created_at = date('Y-m-d H:i:s');
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
    public function change_recharge($order_number)
    {
        //寻找订单
        $recharge = new \app\recharge\model\Recharge();
        $recharge = $recharge->where('order_number', '=', $order_number)->where('order_status', '=', '10')->find();
        if (is_null($recharge)) return;

        //修改会员状态
        $member = new \app\member\model\Member();
        $member = $member->where('id', '=', $recharge->member_id)->find();
        if (is_null($member)) return;
        $member->jpj += $recharge->jpj;
        $member->jpj_all += $recharge->jpj;
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

    //付款成功
    public function change_active($order_number)
    {
        //寻找订单
        $active = new Active();
        $active = $active->where('order_number', '=', $order_number)->where('order_status', '=', '10')->find();
        if (is_null($active)) return;

        //修改会员状态
        $member = new \app\member\model\Member();
        $member = $member->where('id', '=', $active->member_id)->find();
        if (is_null($member)) return;
        $member->total += $active->total;
        $member->asset_act += $active->asset;
        $member->save();

        //时间
        $date = date('Y-m-d H:i:s');
        $setting = new Setting();
        $set = $setting->index();

        //修改订单状态
        $active->pay_status = 1;
        $active->pay_type = 1;
        $active->pay_date = $date;
        $active->order_status = 20;
        $active->change_id = $active->member_id;
        $active->change_nickname = $active->member_nickname;
        $active->change_date = $date;
        $active->save();

        //添加会员记录
        $record = new MemberRecord();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->type = 31;
        $record->content = '激活订单成功付款，获得『激活' . $set['webAliasAsset'] . '』' . $active->asset;
        $record->created_at = $date;
        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->asset_now = $member->asset;
        $record->asset_act = $active->asset;
        $record->asset_act_now = $member->asset_act;
        $record->asset_all = $member->asset_all;
        $record->jpj_now = $member->jpj;
        $record->jpj_all = $member->jpj_all;
        $record->save();
    }

    /**
     * 发送验证码前验证
     *
     * @param $phone
     * @param $time
     */
    public function validator_sms_register($phone, $time)
    {
        $term = [
            'phone' => 'require|length:11|unique:member,account',//联系电话，必填
        ];

        $errors = [
            'phone.require' => '请输入联系电话',
            'phone.length' => '请输入11位的联系电话',
            'phone.unique' => '该电话号码已经注册过账号，请更换联系电话或填写账号信息',
        ];

        //参数判断
        $result = parent::validator(['phone' => $phone], $term, $errors);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        //验证上次发送验证码时间
        self::validator_sms_time($phone, $time);
    }

    /**
     * 发送验证码前验证
     *
     * @param $phone
     * @param $time
     */
    public function validator_sms_reset($phone, $time)
    {
        $term = [
            'phone' => 'require|length:11',//联系电话，必填
        ];

        $errors = [
            'phone.require' => '请输入联系电话',
            'phone.length' => '请输入11位的联系电话',
            'phone.unique' => '账号不存在',
        ];

        //参数判断
        $result = parent::validator(['phone' => $phone], $term, $errors);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $test = new \app\member\model\Member();
        $test = $test->where('account', '=', $phone)->find();
        if (is_null($test)) parent::ajax_exception(000, '账号不存在');

        //验证上次发送验证码时间
        self::validator_sms_time($phone, $time);
    }

    /**
     * 验证上次发送验证码时间
     *
     * @param $phone
     * @param $time
     */
    public function validator_sms_time($phone, $time)
    {
        //获取该电话号码最新的验证码
        $test = new Sms();
        $test_code = $test->where('phone', '=', $phone)->order('created_at', 'desc')->find();

        //没有找到数据
        if (!is_null($test_code)) {

            //比较是否超时
            if ($time < $test_code->end) {

                /*$errors = [
                    'status' => 'fails',
                    'test' => '上一个验证码尚未失效，无法再次发送',
                    'time' => [$test_code->end],
                ];*/

                $end = $test_code->end - $time;

                parent::ajax_exception('001', $end);
            }
        }
    }

    //删除所有超时验证码
    public function delete_sms($time)
    {
        $model = new Sms();
        $model->where('end', '<', $time)->delete();
    }

    //发送短信
    public function send_sms($phone, $time, $templateCode = 'SMS_138077711')
    {
        //初始化短信类
        $class = new \classes\member\Sms();

        //生成验证码
        $code = rand(10000, 99999);

        //发送短信
        $result = $class->sendSms($phone, $code, $templateCode);

        //判断回执
        if (!isset($result->Message)) parent::ajax_exception(000, '请刷新重试(message)');

        //判断是否成功
        if ($result->Message != 'OK') {

            //根据状态吗报错
            switch ($result->Code) {

                case 'isv.BUSINESS_LIMIT_CONTROL':
                    $error = '每小时只能发送5条短信';
                    break;
                case 'isv.MOBILE_NUMBER_ILLEGAL':
                    $error = '非法手机号';
                    break;
                case 'isv.MOBILE_COUNT_OVER_LIMIT':
                    //账户不存在
                    $error = '手机号码数量超过限制';
                    break;
                default:
                    $error = '请刷新重试（code）';
                    break;
            }

            parent::ajax_exception(000, $error);
        }

        //生成结束时间
        $end = $time + 120;

        //添加到数据库
        $model = new Sms();
        $model->phone = $phone;
        $model->end = $end;
        $model->code = $code;
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();

        return $end;
    }

    //验证短信
    public function validator_phone()
    {
        $phone = input('account');
        $code = input('code');

        //获取该电话号码最新的验证码
        $test = new Sms();
        $test_code = $test->where('phone', '=', $phone)->order('created_at', 'desc')->find();

        //没有找到数据
        if (is_null($test_code)) parent::ajax_exception(000, '验证码输入错误');

        //当前时间戳
        $now_time = time();

        //比较是否超时
        if ($now_time > $test_code->end) parent::ajax_exception(000, '验证码已经失效,请重新获取');

        //比较验证码是否正确
        if ($code != $test_code->code) parent::ajax_exception(000, '验证码输入错误');
    }

    public function validator_reset()
    {
        //验证条件
        $rule = [
            'account' => 'require|max:20|min:6',
            'pass' => 'require|max:20|min:6',
            'again' => 'require|max:20|min:6',
            'code' => 'require|length:5'
        ];

        $file = [
            'account' => '账号',
            'pass' => '密码',
            'again' => '验证码',
            'code' => '验证码',
        ];

        //验证
        $result = parent::validator(input(), $rule, [], $file);

        //有错误报告则报错
        if (!is_null($result)) parent::ajax_exception(000, $result);

        self::test_phone_code();//手机验证码

        $pass = input('pass');
        $again = input('again');

        if ($pass != $again) parent::ajax_exception(000, '确认密码输入错误');
    }

    /**
     * 注册方法，登录成功返回member模型
     *
     * @return \app\member\model\Member|array|false|\PDOStatement|string|\think\Model
     */
    public function reset()
    {
        //初始化模型
        $member = new \app\member\model\Member();

        //尝试获取管理员信息
        $member = $member->where('account', '=', input('account'))->find();

        //获取失败，账密错误
        if (is_null($member)) parent::ajax_exception(000, '账号不存在');

        $member->password = md5(input('pass'));
        $member->save();

        //返回管理员信息
        return $member;
    }

    public function test_phone_code()
    {
        $code = input('code');
        $phone = input('account');

        $test = new Sms();
        $test = $test->where('phone', '=', $phone)->order('created_at', 'desc')->find();

        if (is_null($test)) parent::ajax_exception(000, '请重新获取验证码');

        if ($test->end < time()) parent::ajax_exception(000, '验证码已过期');

        if ($test->code != $code) parent::ajax_exception(000, '验证码错误');

    }
}