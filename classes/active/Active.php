<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/14
 * Time: 下午3:23
 */

namespace classes\active;

use app\member\model\Member;
use app\member\model\MemberRecord;
use classes\FirstClass;
use classes\setting\Setting;
use think\Db;

class Active extends FirstClass
{
    public $model;

    public function __construct()
    {
        $this->model = new \app\active\model\Active();
    }

    public function index()
    {
        $keywordType = input('keywordType');
        $keyword = input('keyword');

        $where = [];

        if (!empty($keyword)) switch ($keywordType) {
            case '1':
                $where['member_account'] = ['=', $keyword];
                break;
            case '0':
                $where['order_number'] = ['=', $keyword];
                break;
            default:
                break;
        }


        return parent::page($this->model, null, null, $where);
    }

    public function edit($id)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::redirect_exception('/active', ['订单不存在']);

        return $model->getData();
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->where('order_status', '<>', 10)->delete();
    }

    public function pay($id)
    {
        $order = $this->model->where('id', '=', $id)->find();

        if (is_null($order)) parent::ajax_exception(0, '订单不存在');
        if ($order->pay_status == '1') parent::ajax_exception(0, '请勿重复付款');

        $order->pay_status = '1';
        $order->pay_type = '2';
        $order->pay_date = date('Y-m-d H:i:s');
        $order->save();
    }

    public function status($id)
    {
        Db::startTrans();

        //订单获取
        $order = $this->model->where('id', '=', $id)->find();

        //获取成功
        if (is_null($order)) parent::ajax_exception(0, '订单不存在');

        //未锁定
        if ($order->order_status != '10') parent::ajax_exception(0, '订单已锁定');

        //新状态获取
        $status = input('status');

        //合法的状态码
        $array = [1, 2];

        //状态码合法
        if (!in_array($status, $array)) parent::ajax_exception(0, '状态错误');

        //获取管理员
        $master = parent::is_login();

        $status = $status == '1' ? 20 : 30;

        //修改订单状态
        $order->order_status = $status;
        $order->change_id = $master['id'];
        $order->change_nickname = $master['nickname'];
        $order->change_date = date('Y-m-d H:i:s');
        $order->save();

        //状态为处理，发放积分
        if ($status == '20') {

            self::integralAdd($order->getData());
        }else{

            self::integralBack($order->getData());
        }

        Db::commit();
    }

    private function integralAdd($order)
    {
        $setting = new Setting();
        $set = $setting->index();

        //会员寻找与家谱卷添加
        $member = new Member();
        $member = $member->where('id', '=', $order['member_id'])->find();
        if (is_null($member)) return;
        $member->total += $order['total'];
        $member->asset_act += $order['asset'];
        $member->save();

        //会员变更记录
        $record = new MemberRecord();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->asset_now = $member->asset;
        $record->asset_act = $order['asset'];
        $record->asset_act_now = $member->asset_act;
        $record->asset_all = $member->asset_all;
        $record->jpj_now = $member->jpj;
        $record->jpj_all = $member->jpj_all;
        $record->type = '30';
        $record->content = '管理员处理了您的激活订单(订单号：' . $order['order_number'] . ')，激活『' . $set['webAliasAsset'] . '』：' . $order['asset'];
        $record->created_at = date('Y-m-d H:i:s');
        $record->save();
    }

    private function integralBack($order)
    {
        $setting = new Setting();
        $set = $setting->index();

        //会员寻找与家谱卷添加
        $member = new Member();
        $member = $member->where('id', '=', $order['member_id'])->find();
        if (is_null($member)) return;
        $member->total += $order['total'];
        $member->asset += $order['asset'];
        $member->save();

        //会员变更记录
        $record = new MemberRecord();
        $record->member_id = $member->id;
        $record->account = $member->account;
        $record->nickname = $member->nickname;
        $record->integral_now = $member->integral;
        $record->integral_all = $member->integral_all;
        $record->asset = $order['asset'];
        $record->asset_now = $member->asset;
        $record->asset_act_now = $member->asset_act;
        $record->asset_all = $member->asset_all;
        $record->jpj_now = $member->jpj;
        $record->jpj_all = $member->jpj_all;
        $record->type = '30';
        $record->content = '管理员撤销了您的激活订单(订单号：' . $order['order_number'] . ')，返还『' . $set['webAliasAsset'] . '』：' . $order['asset'];
        $record->created_at = date('Y-m-d H:i:s');
        $record->save();
    }
}