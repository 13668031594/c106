<?php

use think\migration\Migrator;
use think\migration\db\Column;

class RechargePay extends Migrator
{
    public function up()
    {
        $table = $this->table('recharge_pay');
        $table->setId('id');

        //签到基础
        $table->addColumn(Column::string('xml')->setComment('完整返回'));
        $table->addColumn(Column::string('order_number')->setComment('订单号'));
        $table->addColumn(Column::decimal('total', 18)->setComment('金额'));
        $table->addColumn(Column::decimal('trade_type', 18)->setComment('订单类型'));
        $table->addColumn(Column::timestamp('make_time')->setComment('下单时间'));
        $table->addColumn(Column::string('return_code')->setComment('微信反馈状态码1'));
        $table->addColumn(Column::string('result_code')->setComment('微信反馈状态码2'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('recharge_pay');
    }
}
