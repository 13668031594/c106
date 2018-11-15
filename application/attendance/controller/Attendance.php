<?php

namespace app\attendance\controller;

use think\Controller;
use think\Request;

class Attendance extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\attendance\Attendance();

        $this->class->is_login();
    }

    public function index()
    {
        return $this->class->view('attendance');
    }

    public function table()
    {
        $result = $this->class->index();

        return $this->class->table($result);
    }

}
