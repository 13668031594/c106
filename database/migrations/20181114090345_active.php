<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Active extends Migrator
{
    public function up()
    {
        $table = $this->table('active');
        $table->setId('id');

        //订单基础
        $table->addColumn(Column::string('order_number')->setComment('订单号'));
        $table->addColumn(Column::decimal('total', 18)->setComment('支付金额'));
        $table->addColumn(Column::decimal('asset', 18)->setComment('激活资产'));
        $table->addColumn(Column::string('proportion')->setComment('支付比例'));

        //付款情况
        $table->addColumn(Column::char('pay_status', 1)->setDefault(0)->setComment('付款状态，0待付款，1付款'));
        $table->addColumn(Column::char('pay_type', 1)->setNullable()->setComment('付款状态，null未付款，1前台付款，2后台付款'));
        $table->addColumn(Column::timestamp('pay_date')->setNullable()->setComment('付款时间'));

        //操作情况
        $table->addColumn(Column::char('order_status', 2)->setDefault(10)->setComment('订单状态，10待处理，20已处理，30撤回，40取消'));
        $table->addColumn(Column::integer('change_id')->setNullable()->setComment('操作人id'));
        $table->addColumn(Column::string('change_nickname')->setNullable()->setComment('操作人昵称'));
        $table->addColumn(Column::timestamp('change_date')->setNullable()->setComment('操作人时间'));

        //会员情况
        $table->addColumn(Column::integer('member_id')->setComment('会员id'));
        $table->addColumn(Column::string('member_account')->setComment('会员账号'));
        $table->addColumn(Column::string('member_nickname')->setComment('会员昵称'));
        $table->addColumn(Column::timestamp('member_create')->setComment('会员注册时间'));

        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('下单时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('active');
    }
}
