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

class Setting extends FirstClass
{
    public $storage;

    public function __construct()
    {
        $this->storage = new StorageClass('sysSetting.txt');
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
        //获取提交的参数
        $set = input();

        //获取原始配置
        $result = self::defaults();

        //循环修改
        foreach ($result as $k => &$v) {

            //设定文件中有的设定，修改之
            if (isset($set[$k])) {

                $v = $set[$k];
            }
        }

        //保存到文件
        $this->storage->save(json_encode($result));
    }

    //验证
    public function save_validator()
    {
        $rule = [
            'webAliasAsset' => 'require|min:0|max:20',
            'webAliasPoint' => 'require|min:0|max:20',
            'webAliasJpj' => 'require|min:0|max:20',
            'payActAllot' => 'require|integer|min:0|max:10000',
            'payActBase' => 'require|integer|min:1|max:100000000',
            'speedAllot' => 'require|integer|min:0|max:10000',
            'speedAmount' => 'require|integer|min:0|max:100000000',
            'speedMax' => 'require|integer|min:0|max:10000',
            'userCommissDecay' => 'require|integer|min:0|max:10000',
            'userIntroduce' => 'require|integer|min:0|max:10000',
            'userIntroduceNot' => 'require|integer|min:0|max:10000',
            'userRegisterAsset1' => 'require|integer|min:0|max:100000000',
            'userRegisterAsset2' => 'require|integer|min:0|max:100000000',
            'userRegisterSwitch' => 'require',
            'userUpgrade' => 'require|integer|min:0|max:100000000',
            'webAjAmount' => 'require|integer|min:1|max:100',
            'webAjJpj' => 'require|integer|min:1|max:100000000',
            'webApAsset' => 'require|integer|min:1|max:100000000',
            'webApIntegral' => 'require|integer|min:1|max:100000000',
            'webJaAsset' => 'require|integer|min:1|max:100000000',
            'webJaJpj' => 'require|integer|min:1|max:100000000',
            'webAllot' => 'require|integer|min:0|max:10000',
            'webDaySwitch' => 'require',
            'webScale' => 'require|integer|min:1|max:10000',
            'webSwitch' => 'require',
            'webCloseReason' => 'min:0|max:1000',
        ];

        $file = [
            'webAliasAsset' => '资产别名',
            'webAliasPoint' => '积分别名',
            'webAliasJpj' => '家谱卷别名',
            'payActAllot' => '激活资产支付比例',
            'payActBase' => '收付款基数',
            'speedAllot' => '加速比例-比例',
            'speedAmount' => '加速比例-积分',
            'speedMax' => '会员加速上限',
            'userCommissDecay' => '分佣衰减',
            'userIntroduce' => '会员直推比例',
            'userIntroduceNot' => '非会员直推比例',
            'userRegisterAsset1' => '注册赠送资产-未激活',
            'userRegisterAsset2' => '注册赠送资产-激活',
            'userRegisterSwitch' => '开启会员注册',
            'userUpgrade' => '自动升级会员所需积分',
            'webAjAmount' => '现金转家谱卷-现金',
            'webAjJpj' => '现金转家谱卷-家谱卷',
            'webApAsset' => '资产转积分-资产',
            'webApIntegral' => '资产转积分-积分',
            'webJaAsset' => '家谱卷转资产-资产',
            'webJaJpj' => '家谱卷转资产-家谱卷',
            'webAllot' => '家谱卷兑换比例',
            'webDaySwitch' => '日转开关',
            'webScale' => '日转比例',
            'webSwitch' => '网站开关',
            'webCloseReason' => '网站关闭理由',
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
            //资产别名
            'webAliasAsset' => '资产',
            //积分别名
            'webAliasPoint' => '积分',
            //家谱劵别名
            'webAliasJpj' => '家谱卷',
            //激活资产支付比例
            'payActAllot' => "2000",
            //收付款基数
            'payActBase' => "100",
            //加速比例-比例
            'speedAllot' => "100",
            //加速比例-积分
            'speedAmount' => "1000",
            //会员加速上限
            'speedMax' => "1000",
            //分佣衰减-衰减后
            'userCommissDecay' => "5000",
            //会员直推-比例
            'userIntroduce' => "500",
            //非会员直推-比例
            'userIntroduceNot' => "400",
            //注册送资产-未激活
            'userRegisterAsset1' => "1000",
            //注册送资产-激活
            'userRegisterAsset2' => "0",
            //开启会员注册
            'userRegisterSwitch' => "on",
            //升级会员所需积分
            'userUpgrade' => "2000",
            //现金转家谱劵-现金
            'webAjAmount' => "1",
            //现金转家谱劵-家谱劵
            'webAjJpj' => "1",
            //资产转积分-资产
            'webApAsset' => "1",
            //资产转积分-积分
            'webApIntegral' => "100",
            //网站关闭理由
            'webCloseReason' => "",
            //家谱劵转资产-资产
            'webJaAsset' => "5",
            //家谱劵转资产-家谱劵
            'webJaJpj' => "1",
            //家谱卷兑换
            'webAllot' => "8000",
            //日转开关
            'webDaySwitch' => "off",
            //日转比例
            'webScale' => "100",
            //网站开关
            'webSwitch' => "on",
        ];
    }
}