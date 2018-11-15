<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ConversionDetails extends Migrator
{
    public function up()
    {
        $table = $this->table('conversion_details');
        $table->setId('id');
        $table->addColumn(Column::integer('conversion_id')->setComment('日转记录id'));
        $table->addColumn(Column::integer('member_id')->setComment('会员id'));
        $table->addColumn(Column::string('member_nickname')->setComment('会员昵称'));
        $table->addColumn(Column::string('member_account')->setComment('会员账号'));
        $table->addColumn(Column::decimal('integral', 18, 2)->setComment('积分变化'));
        $table->addColumn(Column::decimal('integral_all', 18, 2)->setComment('变化后积分'));
        $table->addColumn(Column::decimal('asset', 18, 2)->setComment('资产变化'));
        $table->addColumn(Column::decimal('asset_all', 18, 2)->setComment('变化后资产'));
        $table->addColumn(Column::decimal('conversion_integral', 18, 2)->setComment('日转积分基数'));
        $table->addColumn(Column::integer('conversion')->setComment('日转速率'));
        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('日转时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('conversion_details');
    }
}
