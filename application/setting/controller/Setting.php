<?php

namespace app\setting\controller;

use think\Controller;
use think\Request;

class Setting extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\setting\Setting();

        $this->class->is_login();//验证登录
    }

    public function index()
    {
        $result = $this->class->index();

        return $this->class->view('index', $result);
    }

    public function save()
    {
        $this->class->save_validator();

        $this->class->save();

        return $this->class->success('/setting');
    }
}
