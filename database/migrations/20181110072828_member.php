<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Member extends Migrator
{
    public function up()
    {
        $table = $this->table('member');
        $table->setId('id');

        //基础字段
        $table->addColumn(Column::string('phone')->setComment('手机号'));
        $table->addColumn(Column::string('account')->setComment('账号'));
        $table->addColumn(Column::string('password')->setComment('密码'));
        $table->addColumn(Column::string('nickname')->setComment('昵称'));
        $table->addColumn(Column::decimal('integral', 18)->setComment('积分'));
        $table->addColumn(Column::decimal('integral_all', 18)->setComment('累计积分'));
        $table->addColumn(Column::decimal('asset', 18)->setComment('资产'));
        $table->addColumn(Column::decimal('asset_act', 18)->setComment('激活资产'));
        $table->addColumn(Column::decimal('asset_all', 18)->setComment('累计资产'));
        $table->addColumn(Column::decimal('jpj', 18)->setComment('家谱卷'));
        $table->addColumn(Column::decimal('jpj_all', 18)->setComment('累计家谱卷'));
        $table->addColumn(Column::decimal('total', 18)->setComment('现金'));
        $table->addColumn(Column::char('created_type', 1)->setDefault(0)->setComment('创建方式，0前台，1后台'));
        $table->addColumn(Column::char('identify', 1)->setDefault(0)->setComment('是否会员，0否，1是'));
        $table->addColumn(Column::char('status', 1)->setDefault(0)->setComment('状态，0正常，1禁用，2冻结'));
        $table->addColumn(Column::string('pay_pass')->setNullable()->setComment('支付密码'));
        $table->addColumn(Column::integer('level')->setDefault(1)->setComment('所在层级'));

        //上级字段
        $table->addColumn(Column::string('families')->setDefault(0)->setComment('上级缓存'));
        $table->addColumn(Column::integer('referee_id')->setDefault(0)->setComment('上级id'));
        $table->addColumn(Column::string('referee_nickname')->setDefault('平台')->setComment('上级昵称'));
        $table->addColumn(Column::string('referee_account')->setDefault('无')->setComment('上级账号'));

        //登录字段
        $table->addColumn(Column::integer('login_times')->setDefault(0)->setComment('登录次数'));
        $table->addColumn(Column::string('login_ip')->setNullable()->setComment('登录ip'));
        $table->addColumn(Column::timestamp('login_time')->setNullable()->setComment('登录时间'));
        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('创建时间'));
        $table->addColumn(Column::timestamp('updated_at')->setNullable()->setComment('更新时间'));

        $table->addColumn(Column::string('wechat_id')->setNullable()->setComment('微信公众号id'));

        $table->save();
    }

    public function down()
    {
        $this->dropTable('member');
    }
}
