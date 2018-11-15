<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Recharge extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\index\Recharge();

        $this->class->member = $this->class->is_login_member();
    }

    public function save()
    {
        $this->class->validator_save();

        $order = $this->class->save();

        $result = $this->class->pay($order);

        return $this->class->success('', null, ['wechat' => $result]);
    }
}
