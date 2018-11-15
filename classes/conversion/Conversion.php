<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/11/9
 * Time: 下午3:45
 */

namespace classes\conversion;

use app\conversion\model\ConversionDetails;
use classes\FirstClass;

class Conversion extends FirstClass
{
    public function day()
    {
        $model = new \app\conversion\model\Conversion();

        return parent::page($model);
    }

    public function day_details()
    {
        $conversion = input('id');

        if (empty($conversion)) parent::ajax_exception(0, 'id错误');

        $model = new ConversionDetails();

        $where['conversion_id'] = $conversion;

        return parent::page($model,'integral','desc',$where);
    }

    public function one_day()
    {
        $conversion = input('id');

        if (empty($conversion)) parent::ajax_exception(0, 'id错误');

        $model = new \app\conversion\model\Conversion();

        $result = $model->where('id','=',$conversion)->find();

        return $result->getData();
    }
}