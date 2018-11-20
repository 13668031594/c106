<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午8:13
 */

namespace classes\index;


use app\member\model\Member;
use app\member\model\MemberRecord;
use classes\FirstClass;
use classes\setting\Setting;
use think\Db;

class AssetChange extends FirstClass
{
    public $member;
    public $in_member;
    public $asset;
    public $set;

    public function __construct()
    {
        $this->member = parent::is_login_member();

        if ($this->member['status'] == '1') parent::ajax_exception(000, '您的账号被冻结了');
    }

    //转出3连
    public function validator_out()
    {
        $setting = new Setting();
        $this->set = $setting = $setting->index();
        $set = $setting['payActBase'];
        $this->asset = $asset = $setting['webAliasAsset'];

        $rule = [
            'account' => 'require|min:6|max:20',
            'number' => 'require|integer|between:1,100000000'
        ];
        $file = [
            'account' => '转出账号',
            'number' => '转出' . $asset
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $test = input('number') % $set;
        if (!empty($test)) parent::ajax_exception(000, '转出激活' . $asset . '至少为' . $set . '，且为' . $set . '的倍数');

        if (input('number') > $this->member['asset_act']) parent::ajax_exception(000, '激活' . $asset . '不足');

        $account = input('account');

        if ($account == $this->member['account']) parent::ajax_exception(000, '不能转给自己');

        $model = new Member();
        $this->in_member = $model->where('account', '=', $account)->find();

        if (is_null($this->in_member)) parent::ajax_exception(000, '转出会员不存在');
        if ($this->in_member->status >= '2') parent::ajax_exception(000, '该会员已被冻结或禁用');
    }

    public function out()
    {
        Db::startTrans();

        $setting = new Setting();
        $set = $setting->index();

        $record = [];
        $update = [];

        $number = input('number');//转移资产
        $asset = number_format($number / $set['webJaJpj'] * $set['webJaAsset'], 2, '.', '');

        $member = new Member();//会员模型

        $time = time();
        $date = date('Y-m-d H:i:s', $time);

        //转出会员
        $member = $member->where('id', '=', $this->member['id'])->find();
        $in_member = $this->in_member;

        //转出激活资产
        $update[$member->id]['id'] = $member->id;
        $update[$member->id]['asset'] = $member->asset;
        $update[$member->id]['asset_act'] = $member->asset_act - $number;
        $update[$member->id]['asset_all'] = $member->asset_all;

        //转入未激活资产
        $update[$in_member->id]['id'] = $in_member->id;
        $update[$in_member->id]['asset'] = $in_member->asset + $asset;
        $update[$in_member->id]['asset_act'] = $in_member->asset_act;
        $update[$in_member->id]['asset_all'] = $in_member->asset_all + $asset;

        $records['member_id'] = $member->id;
        $records['account'] = $member->account;
        $records['nickname'] = $member->nickname;
        $records['integral_now'] = $member->integral;
        $records['integral_all'] = $member->integral_all;
        $records['asset'] = 0;
        $records['asset_now'] = $member->asset;
        $records['asset_act'] = 0 - $number;
        $records['asset_act_now'] = $member->asset_act - $number;
        $records['asset_all'] = $member->asset_all;
        $records['jpj_now'] = $member->jpj;
        $records['jpj_all'] = $member->jpj_all;
        $records['type'] = 40;
        $records['content'] = '转出『激活' . $this->asset . '』' . $number . ',转入账号：' . $in_member->account;
        $records['other'] = $member->id . '_' . $time;
        $records['created_at'] = $date;
        $record[] = $records;

        $records['member_id'] = $in_member->id;
        $records['account'] = $in_member->account;
        $records['nickname'] = $in_member->nickname;
        $records['integral_now'] = $in_member->integral;
        $records['integral_all'] = $in_member->integral_all;
        $records['asset'] = $asset;
        $records['asset_now'] = $in_member->asset + $asset;
        $records['asset_act'] = 0;
        $records['asset_act_now'] = $in_member->asset_act;
        $records['asset_all'] = $in_member->asset_all + $asset;
        $records['jpj_now'] = $in_member->jpj;
        $records['jpj_all'] = $in_member->jpj_all;
        $records['type'] = 50;
        $records['content'] = '转入『' . $this->asset . '』' . $asset . ',转出账号：' . $member->account;
        $records['other'] = $member->id . '_' . $time;
        $records['created_at'] = $date;
        $record[] = $records;

        $result = self::fenyong($in_member->families, $asset, $record, $update, $in_member, $member, $date, $time);

        krsort($result['record']);
        $model = new MemberRecord();
        $model->insertAll($result['record']);

        $member = new Member();
        $member->saveAll($result['update']);

        Db::commit();
    }

    public function fenyong($families = null, $number, $record = [], $update = [], $in_member, $member, $date, $time)
    {
        //没有上级
        if (empty($families)) return [
            'record' => $record,
            'update' => $update
        ];

        //上级id
        $ids = explode(',', $families);

        //获取所有上级
        $members = new Member();
        $members = $members->whereIn('id', $ids)->column('*');

        //没有上级
        if (count($members) <= 0) return [
            'record' => $record,
            'update' => $update,
        ];

        //反向排序
        ksort($ids);

        //分佣层级
        $i = 20;

        //循环分佣
        foreach ($ids as $v) {

            if (!isset($members[$v])) continue;//没有会员
            if ($i <= 0) break;//分佣完结

            //会员
            $m = $members[$v];

            //获取分佣
            if ($i == 20) {

                $bili = $m['identify'] == '0' ? $this->set['userIntroduceNot'] : $this->set['userIntroduce'];

                $number = number_format(($number * $bili / 10000), 2, '.', '');
            } else {

                $number = number_format(($number * $this->set['userCommissDecay'] / 10000), 2, '.', '');
            }

            //没有金额
            if ($number <= 0) break;

            //是否已经进入了修改列表
            if (isset($update[$v])) {

                $update[$v]['asset'] += $number;
                $update[$v]['asset_all'] += $number;
            } else {

                $update[$v]['id'] = $m['id'];
                $update[$v]['asset'] = $m['asset'] + $number;
                $update[$v]['asset_act'] = $m['asset_act'];
                $update[$v]['asset_all'] = $m['asset_all'] + $number;
            }

            $records['member_id'] = $m['id'];
            $records['account'] = $m['account'];
            $records['nickname'] = $m['nickname'];
            $records['integral_now'] = $m['integral'];
            $records['integral_all'] = $m['integral_all'];
            $records['asset'] = $number;
            $records['asset_now'] = $update[$v]['asset'];
            $records['asset_act'] = 0;
            $records['asset_act_now'] = $update[$v]['asset_act'];
            $records['asset_all'] = $update[$v]['asset_all'];
            $records['jpj_now'] = $m['jpj'];
            $records['jpj_all'] = $m['jpj_all'];
            $records['type'] = 60;
            $records['content'] = '交易分佣『' . $this->asset . '』' . $number . ',交易账号：' . $in_member->account;
            $records['other'] = $member->id . '_' . $time;
            $records['created_at'] = $date;
            $record[] = $records;

            $i--;
        }

        return [
            'record' => $record,
            'update' => $update,
        ];
    }

    //转换5连
    public function validator_exchange()
    {
        $setting = new Setting();
        $this->set = $setting->index();

        $rule = [
            'radio' => 'require',
            'number' => 'require|integer|between:1,100000000',
            'pay_pass' => 'require|min:6|max:20',
        ];

        $file = [
            'radio' => '转换模式',
            'number' => '转换数量',
            'pay_pass' => '支付密码',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(000, $result);

        $test = input('number') % 100;
        if (!empty($test)) parent::ajax_exception(000, '转换数量至少为100，且为100的倍数');

        $pay_pass = input('pay_pass');

        if (md5($pay_pass) != $this->member['pay_pass']) parent::ajax_exception(000, '支付密码错误');
    }

    public function exchange()
    {
        Db::startTrans();

        $type = input('radio');

        switch ($type) {
            case '1':
                self::exchange1();
                break;
            case '2':
                self::exchange2();
                break;
//            case '3':
//                self::exchange3();
//                break;
            case '4':
                self::exchange4();
                break;
            case '5':
                self::exchange5();
                break;
            default:
                parent::ajax_exception(000, '转换模式错误');
                break;
        }

//        Db::rollback();
        Db::commit();
    }

    private function exchange1()
    {
        //转换数量
        $number = input('number');

        if ($number > ($this->member['asset'])) parent::ajax_exception(000, $this->set['webAliasAsset'] . '不足');

        $exchange = $number / $this->set['webApAsset'] * $this->set['webApIntegral'];
        $exchange = number_format($exchange, 2, '.', '');

        if ($exchange <= 0) parent::ajax_exception(000, '按当前比例转换后，转入' . $this->set['webAliasPoint'] . '为0，请转换更多' . $this->set['webAliasAsset']);

        $update['id'] = $this->member['id'];
        $update['asset'] = $this->member['asset'] - $number;
        $update['integral'] = $this->member['integral'] + $exchange;
        $update['integral_all'] = $this->member['integral_all'] + $exchange;

        $records['member_id'] = $this->member['id'];
        $records['account'] = $this->member['account'];
        $records['nickname'] = $this->member['nickname'];
        $records['integral'] = $exchange;
        $records['integral_now'] = $this->member['integral'] + $exchange;
        $records['integral_all'] = $this->member['integral_all'] + $exchange;
        $records['asset'] = 0 - $number;
        $records['asset_now'] = $this->member['asset'] - $number;
        $records['asset_act_now'] = $this->member['asset_act'];
        $records['asset_all'] = $this->member['asset_all'];
        $records['jpj_now'] = $this->member['jpj'];
        $records['jpj_all'] = $this->member['jpj'];
        $records['type'] = 70;
        $records['content'] = '『' . $this->set['webAliasAsset'] . '』(' . $number . '),转换为『' . $this->set['webAliasPoint'] . '』(' . $exchange . '),转换比例为' . $this->set['webApAsset'] . ':' . $this->set['webApIntegral'];
        $records['created_at'] = date('Y-m-d H:i:s');

        $member = new Member();
        $member->saveAll([$update]);
        $record = new MemberRecord();
        $record->insert($records);
    }

    private function exchange2()
    {
        //转换数量
        $number = input('number');

        if ($number > ($this->member['asset'])) parent::ajax_exception(000, $this->set['webAliasAsset'] . '不足');

        $exchange = $number / $this->set['webJaAsset'] * $this->set['webJaJpj'];
        $exchange = number_format($exchange, 2, '.', '');

        if ($exchange <= 0) parent::ajax_exception(000, '按当前比例转换后，转入' . $this->set['webAliasJpj'] . '为0，请转换更多' . $this->set['webAliasAsset']);

        $update['id'] = $this->member['id'];
        $update['asset'] = $this->member['asset'] - $number;
        $update['jpj'] = $this->member['jpj'] + $exchange;
        $update['jpj_all'] = $this->member['jpj_all'] + $exchange;

        $records['member_id'] = $this->member['id'];
        $records['account'] = $this->member['account'];
        $records['nickname'] = $this->member['nickname'];
        $records['jpj'] = $exchange;
        $records['jpj_now'] = $this->member['jpj'] + $exchange;
        $records['jpj_all'] = $this->member['jpj_all'] + $exchange;
        $records['asset'] = 0 - $number;
        $records['asset_now'] = $this->member['asset'] - $number;
        $records['asset_act_now'] = $this->member['asset_act'];
        $records['asset_all'] = $this->member['asset_all'];
        $records['type'] = 70;
        $records['content'] = '『' . $this->set['webAliasAsset'] . '』(' . $number . '),转换为『' . $this->set['webAliasJpj'] . '』(' . $exchange . '),转换比例为' . $this->set['webJaAsset'] . ':' . $this->set['webJaJpj'];
        $records['created_at'] = date('Y-m-d H:i:s');

        $member = new Member();
        $member->saveAll([$update]);
        $record = new MemberRecord();
        $record->insert($records);
    }

    private function exchange3()
    {
        //转换数量
        $number = input('number');

        if ($number > ($this->member['jpj'])) parent::ajax_exception(000, $this->set['webAliasJpj'] . '不足');

        //转换比例
        if ($this->set['webAllot'] >= 10000) {

            $asset = $number / $this->set['webJaJpj'] * $this->set['webJaAsset'];
            $asset = number_format($asset, 2, '.', '');
            $integral = 0;
        } else {

            $asset = $number / $this->set['webJaJpj'] * $this->set['webJaAsset'] * $this->set['webAllot'] / 10000;
            $asset = number_format($asset, 2, '.', '');
            $integral = $number / $this->set['webJaJpj'] * $this->set['webJaAsset'] * $this->set['webApIntegral'] / $this->set['webApAsset'] * (10000 - $this->set['webAllot']) / 10000;
            $integral = number_format($integral, 2, '.', '');
        }

        if ($asset <= 0) parent::ajax_exception(000, '按当前比例转换后，转入' . $this->set['webAliasAsset'] . '为0，请转换更多' . $this->set['webAliasJpj']);

        $update['id'] = $this->member['id'];
        $update['asset'] = $this->member['asset'] + $asset;
        $update['asset_all'] = $this->member['asset_all'] + $asset;
        $update['integral'] = $this->member['integral'] + $integral;
        $update['integral_all'] = $this->member['integral_all'] + $integral;
        $update['jpj'] = $this->member['jpj'] - $number;

        $records['member_id'] = $this->member['id'];
        $records['account'] = $this->member['account'];
        $records['nickname'] = $this->member['nickname'];
        $records['jpj'] = 0 - $number;
        $records['jpj_now'] = $this->member['jpj'] - $number;
        $records['jpj_all'] = $this->member['jpj_all'];
        $records['integral'] = $integral;
        $records['integral_now'] = $this->member['integral'] + $integral;
        $records['integral_all'] = $this->member['integral_all'] + $integral;
        $records['asset'] = $asset;
        $records['asset_now'] = $this->member['asset'] + $asset;
        $records['asset_act_now'] = $this->member['asset_act'];
        $records['asset_all'] = $this->member['asset_all'] + $asset;
        $records['type'] = 70;
        $records['content'] = '『' . $this->set['webAliasJpj'] . '』(' . $number . '),转换为『' . $this->set['webAliasAsset'] . '』(' . $asset . '),转换比例为' . $this->set['webJaJpj'] . ':' . $this->set['webJaAsset'] . '，积分比例为' . ((10000 - $this->set['webAllot']) / 100) . '%,转换积分:' . $integral;
        $records['created_at'] = date('Y-m-d H:i:s');

        $member = new Member();
        $member->saveAll([$update]);
        $record = new MemberRecord();
        $record->insert($records);
    }

    private function exchange4()
    {
        //转换数量
        $number = input('number');

        if ($number > ($this->member['jpj'])) parent::ajax_exception(000, $this->set['webAliasJpj'] . '不足');

        $asset = $number;

        $update['id'] = $this->member['id'];
        $update['asset_act'] = $this->member['asset_act'] + $asset;
        $update['asset_all'] = $this->member['asset_all'] + $asset;
        $update['jpj'] = $this->member['jpj'] - $number;

        $records['member_id'] = $this->member['id'];
        $records['account'] = $this->member['account'];
        $records['nickname'] = $this->member['nickname'];
        $records['jpj'] = 0 - $number;
        $records['jpj_now'] = $this->member['jpj'] - $number;
        $records['jpj_all'] = $this->member['jpj_all'];
        $records['integral_now'] = $this->member['integral'];
        $records['integral_all'] = $this->member['integral_all'];
        $records['asset_act'] = $asset;
        $records['asset_now'] = $this->member['asset'];
        $records['asset_act_now'] = $this->member['asset_act'] + $asset;
        $records['asset_all'] = $this->member['asset_all'] + $asset;
        $records['type'] = 70;
        $records['content'] = '『' . $this->set['webAliasJpj'] . '』(' . $number . '),转换为『激活' . $this->set['webAliasAsset'] . '』(' . $asset . '),转换比例为1:1';
        $records['created_at'] = date('Y-m-d H:i:s');

        $member = new Member();
        $member->saveAll([$update]);
        $record = new MemberRecord();
        $record->insert($records);
    }

    private function exchange5()
    {
        //转换数量
        $number = input('number');

        if ($number > ($this->member['jpj'])) parent::ajax_exception(000, $this->set['webAliasJpj'] . '不足');

        //转换比例
        $bili = $this->set['webJaAsset'] * $this->set['webApIntegral'] / $this->set['webApAsset'];
        $integral = $number / $this->set['webJaJpj'] * $bili;
        $integral = number_format($integral, 2, '.', '');

        if ($integral <= 0) parent::ajax_exception(000, '按当前比例转换后，转入' . $this->set['webAliasPoint'] . '为0，请转换更多' . $this->set['webAliasJpj']);

        $update['id'] = $this->member['id'];
        $update['integral'] = $this->member['integral'] + $integral;
        $update['integral_all'] = $this->member['integral_all'] + $integral;
        $update['jpj'] = $this->member['jpj'] - $number;

        $records['member_id'] = $this->member['id'];
        $records['account'] = $this->member['account'];
        $records['nickname'] = $this->member['nickname'];
        $records['jpj'] = 0 - $number;
        $records['jpj_now'] = $this->member['jpj'] - $number;
        $records['jpj_all'] = $this->member['jpj_all'];
        $records['integral'] = $integral;
        $records['integral_now'] = $this->member['integral'] + $integral;
        $records['integral_all'] = $this->member['integral_all'] + $integral;
        $records['asset_now'] = $this->member['asset'];
        $records['asset_act_now'] = $this->member['asset_act'];
        $records['asset_all'] = $this->member['asset_all'];
        $records['type'] = 70;
        $records['content'] = '『' . $this->set['webAliasJpj'] . '』(' . $number . '),转换为『' . $this->set['webAliasPoint'] . '』(' . $integral . '),转换比例为' . $this->set['webJaJpj'] . ':' . $bili;
        $records['created_at'] = date('Y-m-d H:i:s');

        $member = new Member();
        $member->saveAll([$update]);
        $record = new MemberRecord();
        $record->insert($records);
    }

}