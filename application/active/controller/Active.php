<?php

namespace app\active\controller;

use think\Controller;
use think\Request;

class Active extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\active\Active();

        $this->class->is_login();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return $this->class->view('actList');
    }

    public function table()
    {
        $result = $this->class->index();

        return $this->class->table($result);
    }

    public function edit($id)
    {
        $active = $this->class->edit($id);

        return $this->class->view('active', ['active' => $active]);
    }

    public function delete()
    {
        $ids = explode(',', input('id'));

        //删除
        $this->class->delete($ids);

        //反馈成功
        return $this->class->success('/active');
    }

    public function pay($id)
    {
        $this->class->pay($id);

        return $this->class->success('/active-edit/' . $id);
    }

    public function status($id)
    {
        $this->class->status($id);

        return $this->class->success('/active-edit/' . $id);
    }
}
