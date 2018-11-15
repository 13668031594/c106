<?php

namespace app\conversion\controller;

use think\Controller;
use think\Request;

class Conversion extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\conversion\Conversion();

        $this->class->is_login();
    }

    public function day()
    {
        return $this->class->view('day');
    }

    public function day_table()
    {
        $result = $this->class->day();

        return $this->class->table($result);
    }

    public function day_details()
    {
        $result = $this->class->one_day();

        return $this->class->view('dayDetails',$result);
    }

    public function day_details_table()
    {
        $result = $this->class->day_details();

        return $this->class->table($result);
    }
}
