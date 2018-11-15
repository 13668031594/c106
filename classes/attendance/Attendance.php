<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/14
 * Time: 下午5:47
 */

namespace classes\attendance;


use classes\FirstClass;

class Attendance extends FirstClass
{
    public $model;

    public function __construct()
    {
        $this->model = new \app\attendance\model\Attendance();
    }

    public function index()
    {
        $where = [];

        $startTime = input('startTime');
        $endTime = input('endTime');

        if (!empty($startTime) && !empty($endTime)) {
            $where['created_at'][] = ['>=', $startTime];
            $where['created_at'][] = ['<', $endTime];
        } elseif (!empty($startTime)) {
            $where['created_at'] = ['>=', $startTime];
        } elseif (!empty($endTime)) {
            $where['created_at'] = ['<', $endTime];
        }

        $result = parent::page($this->model, 'created_at', 'desc', $where);

        foreach ($result['message'] as &$v)$v['conversion'] = $v['conversion'] / 100 . '%';

        return $result;
    }
}