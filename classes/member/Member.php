<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/10
 * Time: 下午4:15
 */

namespace classes\member;


use app\member\model\MemberRecord;
use classes\FirstClass;
use classes\ListInterface;
use classes\set\ContrastArrays;
use classes\setting\Setting;
use think\Db;
use think\Exception;

class Member extends FirstClass implements ListInterface
{
    public $member;
    public $record;

    public function __construct()
    {
        $this->member = new \app\member\model\Member();
        $this->record = new MemberRecord();
    }

    public function index()
    {
        $where = [];

        $account = input('account');
        $identify = input('identify');

        if (!empty($account)) $where['account'] = ['like', '%' . $account . '%'];
        if (!empty($identify)) $where['identify'] = ['=', $identify];

        return parent::page($this->member, 'id', 'desc', $where);
    }

    public function create()
    {
        // TODO: Implement create() method.
    }

    /**
     * 注册会员
     */
    public function save()
    {
        $model = self::referee_add($this->member);
        $model->account = input('account');
        $model->phone = input('account');
        $model->nickname = input('nickname');
        $model->status = input('status');
        $model->identify = input('identify');
        $model->password = md5(input('password'));
        $model->pay_pass = md5(input('pay_pass'));
        $model->created_type = 1;
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public function read($id)
    {
        $member = $this->member->where('id', '=', $id)->find();

        if (is_null($member)) parent::redirect_exception('/member', ['会员不存在']);

        return $member->getData();
    }

    public function edit($id)
    {
        return self::read($id);
    }

    public function update($id)
    {
        $model = $this->member->where('id', '=', $id)->find();

        if (is_null($model)) parent::redirect_exception('/member', ['会员不存在']);

        $model->phone = input('account');
        $model->nickname = input('nickname');
        $model->status = input('status');
        $model->identify = input('identify');
        if (input('password') != 'w!c@n#m$b%y^') $model->password = md5(input('password'));
        if (input('pay_pass') != 'w!c@n#m$b%y^') $model->pay_pass = md5(input('pay_pass'));
        $model->updated_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public function delete($id)
    {
        $this->member->whereIn('id', $id)->delete();
        $this->record->whereIn('member_id',$id)->delete();
    }

    public function validator_save()
    {
        $rule = [
            'referee_account' => 'min:6|max:20',
            'nickname' => 'require|min:2|max:48|unique:member,nickname',
            'account' => 'require|min:6|max:20|unique:member,account',
            'password' => 'require|min:6|max:20',
            'pay_pass' => 'require|min:6|max:20',
//            'phone' => 'min:8|max:15',
            'status' => 'require',
            'identify' => 'require'
        ];

        $file = [
            'referee_account' => '推荐人账号',
            'nickname' => '昵称',
            'account' => '账号',
            'password' => '密码',
            'pay_pass' => '支付密码',
            'status' => '状态',
            'identify' => '身份',
//            'phone' => '联系电话',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(502, $result);

        $identify = [0, 1];
        $status = [0, 1, 2];

        if (!in_array(input('identify'), $identify)) parent::ajax_exception(502, '身份错误');
        if (!in_array(input('status'), $status)) parent::ajax_exception(502, '状态错误');
    }

    public function validator_update($id)
    {
        $rule = [
            'nickname' => 'require|min:2|max:48|unique:member,nickname,' . $id . ',id',
            'password' => 'require|min:6|max:20',
            'pay_pass' => 'require|min:6|max:20',
            'status' => 'require',
            'identify' => 'require',
        ];

        $file = [
            'referee_account' => '推荐人账号',
            'nickname' => '昵称',
            'password' => '密码',
            'pay_pass' => '支付密码',
            'status' => '状态',
            'identify' => '身份',
//            'phone' => '联系电话',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(503, $result);

        $identify = [0, 1];
        $status = [0, 1, 2];

        if (!in_array(input('identify'), $identify)) parent::ajax_exception(503, '身份错误');
        if (!in_array(input('status'), $status)) parent::ajax_exception(503, '状态错误');
    }

    public function validator_delete($id)
    {
        // TODO: Implement validator_delete() method.
    }

    /**
     * 添加上级信息
     *
     * @param \app\member\model\Member $member
     * @return \app\member\model\Member
     */
    private function referee_add(\app\member\model\Member $member)
    {
        $referee_account = input('referee_account');
        if (empty($referee_account)) return $member;

        $test = new \app\member\model\Member();
        $referee = $test->where('account', '=', input('referee_account'))->find();
        if (is_null($referee)) parent::ajax_exception(503, '上级不存在');

        $referee = $referee->getData();

        $families = empty($referee['families']) ? $referee['id'] : ($referee['families'] . ',' . $referee['id']);

        $member->families = $families;//上级缓存
        $member->referee_id = $referee['id'];//上级id
        $member->referee_account = $referee['account'];//上级账号
        $member->referee_nickname = $referee['nickname'];//上级昵称
        $member->level = $referee['level'] + 1;//自身层级

        return $member;
    }

    public function validator_wallet()
    {
        $rule = [
            'type' => 'require',
            'assetType' => 'requireIf:type,0',
            'number' => 'require|integer|between:0.01,100000000',
        ];

        $file = [
            'type' => '充值类型',
            'assetType' => '资产类型',
            'number' => '充值数量'
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(505, $result);
    }

    public function wallet()
    {
        Db::startTrans();

        //寻找会员模型
        $member = $this->member->where('id', '=', input('id'))->find();

        //判断
        if (is_null($member)) parent::ajax_exception(506, '会员不存在');

        //获取变化数值
        $number = input('number');

        //获取默认设定
        $setting = new Setting();
        $set = $setting->index();

        //初始化会员记录
        $record = new MemberRecord();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->content = '管理员为您充值『';

        //按类型充值
        switch (input('type')) {
            case '0':
                $type = input('assetType');

                if ($type == '2') {

                    $member->asset_act += $number;

                    $record->asset_act = $number;
                    $record->content .= '激活' . $set['webAliasAsset'];
                } elseif ($type == '1') {

                    $member->asset += $number;

                    $record->asset = $number;
                    $record->content .= '未激活' . $set['webAliasAsset'];
                } else {

                    parent::ajax_exception(508, '充值资产类型错误');
                }

                $member->asset_all += $number;

                break;
            case '1':

                $member->integral += $number;
                $member->integral_all += $number;

                $record->integral = $number;
                $record->content .= $set['webAliasPoint'];

                break;
            case '2':

                $member->jpj += $number;
                $member->jpj_all += $number;

                $record->jpj = $number;
                $record->content .= $set['webAliasJpj'];

                break;
            default:
                parent::ajax_exception(507, '充值类型错误');
                break;
        }
//        parent::ajax_exception(506, '123');

        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->asset_now = $member->asset;
        $record->asset_act_now = $member->asset_act;
        $record->asset_all = $member->asset_all;
        $record->jpj_now = $member->jpj;
        $record->jpj_all = $member->jpj_all;
        $record->type = 10;
        $record->content .= '』：' . $number;
        $record->created_at = date('Y-m-d H:i:s');

        $record->save();
        $member->save();

        Db::commit();

    }

    public function record($id)
    {
        $where = [];

        $where['member_id'] = ['=', $id];

        switch (input('type')) {
            case '1':
                $where['asset|asset_act'] = ['<>', 0];
                break;
            case '2':
                $where['integral'] = ['<>', 0];
                break;
            case '3':
                $where['jpj'] = ['<>', 0];
                break;
            default:
                break;
        }

        $startTime = input('startTime');
        $endTime = input('endTime');

        if (!empty($startTime) && !empty($endTime)) {
            $where['created_at'][] = ['>=', $startTime];
            $where['created_at'][] = ['<', $endTime];
        } elseif (!empty($startTime)) {
            $where['created_at'] = ['>=', $startTime];
        } elseif (!empty($endTime)) {
            $where['created_at'] = ['<', $endTime];
        }

        return parent::page($this->record, 'created_at', 'desc', $where);
    }

    public function record_array()
    {
        $class = new ContrastArrays();

        return $class->member_record();
    }

    public function record_delete($id)
    {
        $this->record->whereIn('id', $id)->delete();
    }

    public function team($member_id)
    {
        //结果数组
        $result = [
            'number' => 0,
            'team' => null,
        ];

        //初始化模型
        $model = new \app\member\model\Member();

        //获取下级信息
        $team = $model->where('families', 'like', '%' . $member_id . '%')->column('id,referee_id,nickname');

        //没有下级
        if (count($team) <= 0) return $result;

        $result['number'] = count($team);//下级总数

        //下级结果数组
        $fathers = [];

        foreach ($team as $v) {
//dump($v);
            $fathers[$v['referee_id']][] = $v;
        }

        $result['team'] = json_encode(self::get_tree($member_id, $fathers));

        return $result;
    }

    public function get_tree($father_id, $team)
    {
        if (!isset($team[$father_id])) return null;

        $result = [];

        foreach ($team[$father_id] as $k => $v) {

            $result[$k]['name'] = $v['nickname'];
            if (isset($team[$v['id']])) $result[$k]['children'] = self::get_tree($v['id'], $team);
        }

        return $result;
    }
}