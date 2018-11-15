<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Index extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\index\Index();

        session('floor', 'index');
    }

    public function header()
    {
        return $this->class->view('header');
    }

    public function header2()
    {
        return $this->class->view('header2');
    }

    public function floor()
    {
        return $this->class->view('floor');
    }

    //首页
    public function index()
    {
        $result = $this->class->header2('众筹');

        $result['first_login'] = session('first_login');

        session('first_login', null);

        return $this->class->view('index', $result);
    }

    //家谱
    public function family()
    {
        $result = $this->class->header2('家谱');

        return $this->class->view('family', $result);
    }

    //纪念堂
    public function memorial()
    {
        $result = $this->class->header2('纪念堂');

        return $this->class->view('memorial-hall', $result);
    }

    //家族共享
    public function shared()
    {
        $result = $this->class->header2('家族共享');

        return $this->class->view('family-shared', $result);
    }

    //资讯中心
    public function information()
    {
        $result = $this->class->header2('资讯中心');

        $result['all_notice'] = $this->class->notice();

        return $this->class->view('information', $result);
    }

    //资讯中心-翻页
    public function information_table()
    {
        $result = $this->class->notice();

        return $this->class->table($result);
    }

    //文章
    public function information_hy()
    {
        $result = $this->class->header2('行业资讯');

        $result['article'] = $this->class->article();

        return $this->class->view('information-hy', $result);
    }

    //文章-翻页
    public function information_hy_table()
    {
        $result = $this->class->article();

        return $this->class->table($result);
    }

    //文章-详情
    public function information_info($id)
    {
        $result = $this->class->article_info($id);

        return $this->class->view('information-info', $result);
    }

    //众筹
    public function crowd()
    {
        $result = $this->class->pay_agreement();
        $result['member'] = $this->class->member;

        return $this->class->view('crowd', $result);
    }

    //财务
    public function financial()
    {
        $result = $this->class->header2('财务');

        $result['record'] = $this->class->record();
        $result['type'] = input('type');

        return $this->class->view('financial', $result);
    }

    //财务翻页
    public function financial_table()
    {
        $result = $this->class->record();

        return $this->class->table($result);
    }

    //转入二维码
    public function shift_to_qr()
    {
        $route = '/index-roll-out/' . $this->class->member['id'];

        $url = $this->class->make_qr('asset_in', $route);

        return $this->class->view('shift-to-qr', ['url' => $url]);
    }

    //转出页面
    public function roll_out($id)
    {
        $out_man = $this->class->out_man($id);

        return $this->class->view('roll-out', ['out_man' => $out_man]);
    }

    //转换
    public function exchange()
    {
        $result['member'] = $this->class->member;

        return $this->class->view('exchange', $result);
    }
}
