<?php

use think\migration\Seeder;

class First extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        //设置表中的起始id
        \think\Db::query("ALTER TABLE young_conversion AUTO_INCREMENT = 100000");
        \think\Db::query("ALTER TABLE young_member AUTO_INCREMENT = 10000");
        \think\Db::query("ALTER TABLE young_attendance AUTO_INCREMENT = 10000");
    }
}