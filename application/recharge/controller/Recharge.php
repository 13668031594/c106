<?php

namespace app\recharge\controller;

use think\Controller;
use think\Request;

class Recharge extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\recharge\Recharge();

        $this->class->is_login();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return $this->class->view('rechargeList');
    }

    public function table()
    {
        $result = $this->class->index();

        return $this->class->table($result);
    }

    public function edit($id)
    {
        $recharge = $this->class->edit($id);

        return $this->class->view('recharge', ['recharge' => $recharge]);
    }

    public function delete()
    {
        $ids = explode(',', input('id'));

        //删除
        $this->class->delete($ids);

        //反馈成功
        return $this->class->success('/recharge');
    }

    public function pay($id)
    {
        $this->class->pay($id);

        return $this->class->success('/recharge-edit/' . $id);
    }

    public function status($id)
    {
        $this->class->status($id);

        return $this->class->success('/recharge-edit/' . $id);
    }
}
