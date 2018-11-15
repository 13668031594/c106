<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Attendance extends Migrator
{
    public function up()
    {
        $table = $this->table('attendance');
        $table->setId('id');

        //签到基础
        $table->addColumn(Column::decimal('integral', 18)->setComment('日转积分'));
        $table->addColumn(Column::decimal('integral_now', 18)->setComment('日转后积分'));
        $table->addColumn(Column::decimal('asset', 18)->setComment('日转资产'));
        $table->addColumn(Column::decimal('asset_now', 18)->setComment('日转后资产'));
        $table->addColumn(Column::string('proportion')->setComment('资分比例'));
        $table->addColumn(Column::string('conversion')->setComment('日转速率'));

        //会员情况
        $table->addColumn(Column::integer('member_id')->setComment('会员id'));
        $table->addColumn(Column::string('member_account')->setComment('会员账号'));
        $table->addColumn(Column::string('member_nickname')->setComment('会员昵称'));
        $table->addColumn(Column::timestamp('member_create')->setComment('会员注册时间'));

        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('签到时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('attendance');
    }
}
