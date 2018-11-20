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

        $this->class->web_close();
    }

    public function save()
    {
        $this->class->validator_save();

        $order = $this->class->save();

        $result = $this->class->pay($order);

        return $this->class->success('', null, ['wechat' => $result, 'order' => $order]);
    }

    public function info($id)
    {
        $this->class->info($id);

        return $this->class->success('/');
    }

    public function out($id)
    {
        $this->class->out($id);

        return $this->class->success('/');
    }
}
