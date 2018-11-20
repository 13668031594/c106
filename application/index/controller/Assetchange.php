<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class AssetChange extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\index\AssetChange();

        $this->class->web_close();
    }

    public function asset_out()
    {
        $this->class->validator_out();

        $this->class->out();

        return $this->class->success('/');
    }

    public function exchange()
    {
        $this->class->validator_exchange();

        $this->class->exchange();

        return $this->class->success('/');
    }
}
