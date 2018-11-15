<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/15
 * Time: 下午3:07
 */

namespace classes\index;


use app\adv\model\Adv;
use app\article\model\Article;
use app\member\model\MemberRecord;
use app\notice\model\Notice;
use classes\FirstClass;
use classes\member\Member;
use classes\set\ContrastArrays;
use classes\set\LoginSet;
use classes\setting\PayAgreement;
use classes\setting\Setting;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Index extends FirstClass
{
    public $member;

    public function __construct()
    {
        $this->member = parent::is_login_member();
    }

    public function view($view, $data = [])
    {
        $data['errors'] = null;
        $data['success'] = null;

        //获取提示
        $errors = session('errors');
        $success = session('success');

        //初始化errors视图变量
        if (!is_null($errors) && is_string($errors)) $data['errors'] = $errors;

        //初始化success视图变量
        if (!is_null($success) && is_string($success)) $data['success'] = $success;

        //删除提示
        session('errors', null);
        session('success', null);

        //设定model
        $model = new Setting();
        $data = array_merge($data, $model->index());

        //渲染视图
        return view($view, $data);
    }

    public function header2($top = 'TD众筹', $array = [])
    {
        //获取公告
        $model = new Notice();
        $result = $model->order('created_at', 'desc')->column('title');
        if (count($result) > 1) {
            $first = array_shift($result);
            array_unshift($result, $first);
            $result[] = $first;
        }
        $array['notice'] = $result;

        //获取广告
        $model = new Adv();
        $result = $model->order('sort', 'asc')->where('show', '=', 'on')->column('id,location,describe');
        foreach ($result as &$v) {
            if (is_null($v['location'])) $v['location'] = 'favicon.ico';
        }
        $array['adv'] = $result;

        $array['member'] = $this->member;

        $array['top'] = $top;

        return $array;
    }

    //公告
    public function notice()
    {
        $model = new Notice();
        $result = parent::page($model);

        return $result;
    }

    //文章
    public function article()
    {
        //获取公告
        $model = new Article();
        $result = parent::page($model);

        return $result;
    }

    //文章详情
    public function article_info($id)
    {
        //获取公告
        $model = new Article();
        $result = $model->where('id', '=', $id)->find();

        if (is_null($result)) parent::redirect_exception('/index-information-hy', '文章不存在');

        return $result->getData();
    }

    //支付协议
    public function pay_agreement()
    {
        $model = new PayAgreement();

        return $model->index();
    }

    //资产记录
    public function record()
    {
        //获取公告
        $model = new Member();

        $result = $model->record($this->member['id']);

        $model = new ContrastArrays();

        $record_array = $model->member_record();

        foreach ($result['message'] as &$v) $v['type'] = $record_array[$v['type']];

        return $result;
    }

    //转入二维码
    public function make_qr($dir, $url)
    {
        $class = new LoginSet();

        //不带LOGO
        vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $url = $class->url . $url;//网址或者是文本内容
        $level = 3;
        $size = 8;
        $ad = $dir . '/' . $this->member['id'] . '.jpg';
        $errorCorrectionLevel = intval($level);//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);

        return '/' . $ad;
    }

    //转出二维码
    public function out_man($id)
    {
        $model = new \app\member\model\Member();

        $member = $model->where('id', '=', $id)->find();

        if (is_null($member)) return null;

        return $member->getData();
    }
}