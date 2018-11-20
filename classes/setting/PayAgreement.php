<?php
/**
 * Created by PhpStorm.
 * User=>yangyang
 * Date=>2018/11/8
 * Time=>下午3:23
 */

namespace classes\setting;


use classes\FirstClass;
use classes\vendor\StorageClass;

class PayAgreement extends FirstClass
{
    public $storage;

    public function __construct()
    {
        $this->storage = new StorageClass('payAgreement.txt');
    }

    public function index()
    {
        //读取设定文件
        $set = $this->storage->get();

        //获取默认配置
        $result = self::defaults();

        //设定文件存在，修改返回配置
        if (!is_array($set)) {

            //格式化配置信息
            $set = json_decode($set, true);

            //循环设定数据
            foreach ($result as $k => &$v) {

                //设定文件中有的设定，修改之
                if (isset($set[$k])) $v = $set[$k];
            }
        }

        //返回设定文件
        return $result;
    }

    //保存配置文件
    public function save()
    {
        $result['content'] = input('fwb-content');

        //保存到文件
        $this->storage->save(json_encode($result));
    }

    //验证
    public function save_validator()
    {
        $rule = [
            'fwb-content' => 'min:0|max:50000',
        ];

        $file = [
            'fwb-content' => '正文',
        ];

        $result = parent::validator(input(), $rule, [], $file);

        if (!is_null($result)) parent::ajax_exception(0, $result);
    }

    //充值，删除配置文件
    public function reset()
    {
        $this->storage->unlink_files();
    }

    private function defaults()
    {
        return [
            'content' => '这个管理员很懒，还什么都没有写'
        ];
    }
}