<?php

namespace app\index\controller;

use classes\vendor\Wechat;
use think\Controller;
use think\Db;
use think\Request;

class Recharge extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\index\Recharge();
    }

    public function save()
    {
        $this->class->validator_save();

        $order = $this->class->save();

        $result = $this->class->pay($order);

        return $this->class->success('', null, ['wechat' => $result]);
    }

    public function notify(Request $request)
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
            $this->class->info($model->order_number);
        }

        Db::commit();

        //返回微信回调成功
        return 'success';
    }
}
