<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Conversion extends Migrator
{
    public function up()
    {
        $table = $this->table('conversion');
        $table->setId('id');
        $table->addColumn(Column::string('content', 255)->setComment('描述'));
        $table->addColumn(Column::integer('number')->setComment('日转人数'));
        $table->addColumn(Column::decimal('integral', 18, 2)->setComment('日转积分'));
        $table->addColumn(Column::decimal('asset', 18, 2)->setComment('日转资产'));
        $table->addColumn(Column::timestamp('created_at')->setNullable()->setComment('日转时间'));
        $table->save();
    }

    public function down()
    {
        $this->dropTable('conversion');
    }
}
