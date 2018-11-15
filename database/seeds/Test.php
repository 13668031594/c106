<?php

use think\migration\Seeder;

class Test extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
//        self::conversion_details();//模拟日转
//        self::recharge_order();//模拟直充下单
//        self::active_order();//模拟激活下单
//        self::attendance();//模拟签到
    }

    private function conversion_details()
    {
        $conversion = new \app\conversion\model\Conversion();

        $c = $conversion->order('created_at', 'desc')->find()->getData();

        $insert = [];

        if (!is_null($c)) {

            for ($i = 1; $i < 100; $i++) {

                $insert[$i]['conversion_id'] = $c['id'];
                $insert[$i]['member_id'] = $i;
                $insert[$i]['member_nickname'] = 'member_nickname_' . $i;
                $insert[$i]['member_account'] = 'member_account_' . $i;
                $insert[$i]['integral'] = 0 - $i;
                $insert[$i]['integral_all'] = 100 - $i;
                $insert[$i]['asset'] = $i;
                $insert[$i]['asset_all'] = $i + 100;
                $insert[$i]['conversion_integral'] = 100;
                $insert[$i]['conversion'] = 0;
                $insert[$i]['created_at'] = $c['created_at'];
            }
        }

        $model = new \app\conversion\model\ConversionDetails();

        if (count($insert) > 0) $model->insertAll($insert);
    }

    private function recharge_order()
    {
        $member = new \app\member\model\Member();

        $member = $member->column('id,nickname,created_at,account');

        if (count($member) <= 0) return;

        $insert = [];
        $i = 1;
        $date = date('Y-m-d H:i:s');

        foreach ($member as $v) {

            $insert[$i]['order_number'] = 'ABC0000' . $i;
            $insert[$i]['total'] = 1000;
            $insert[$i]['jpj'] = 1000;
            $insert[$i]['proportion'] = '1:1';
            $insert[$i]['member_id'] = $v['id'];
            $insert[$i]['member_nickname'] = $v['nickname'];
            $insert[$i]['member_create'] = $v['created_at'];
            $insert[$i]['member_account'] = $v['account'];
            $insert[$i]['created_at'] = $date;

            $i++;
        }

        if (count($insert) > 0) {
            $model = new \app\recharge\model\Recharge();
            $model->insertAll($insert);
        }
    }

    private function active_order()
    {
        $member = new \app\member\model\Member();

        $member = $member->column('id,nickname,created_at,account');

        if (count($member) <= 0) return;

        $insert = [];
        $i = 1;
        $date = date('Y-m-d H:i:s');

        foreach ($member as $v) {

            $insert[$i]['order_number'] = 'ABC0000' . $i;
            $insert[$i]['total'] = 200;
            $insert[$i]['asset'] = 1000;
            $insert[$i]['proportion'] = '2000';
            $insert[$i]['member_id'] = $v['id'];
            $insert[$i]['member_nickname'] = $v['nickname'];
            $insert[$i]['member_create'] = $v['created_at'];
            $insert[$i]['member_account'] = $v['account'];
            $insert[$i]['created_at'] = $date;

            $i++;
        }

        if (count($insert) > 0) {
            $model = new \app\active\model\Active();
            $model->insertAll($insert);
        }
    }

    private function attendance()
    {
        $member = new \app\member\model\Member();

        $member = $member->column('id,nickname,created_at,account,integral,asset');

        if (count($member) <= 0) return;

        $insert = [];
        $i = 1;
        $date = date('Y-m-d H:i:s');

        foreach ($member as $v) {

            $insert[$i]['integral'] = 0 - 200;
            $insert[$i]['integral_now'] = $v['integral'];
            $insert[$i]['asset'] = 20;
            $insert[$i]['asset_now'] = $v['asset'];
            $insert[$i]['proportion'] = '1:10';
            $insert[$i]['conversion'] = '1000';

            $insert[$i]['member_id'] = $v['id'];
            $insert[$i]['member_nickname'] = $v['nickname'];
            $insert[$i]['member_create'] = $v['created_at'];
            $insert[$i]['member_account'] = $v['account'];
            $insert[$i]['created_at'] = $date;

            $i++;
        }

        if (count($insert) > 0) {
            $model = new \app\attendance\model\Attendance();
            $model->insertAll($insert);
        }
    }
}