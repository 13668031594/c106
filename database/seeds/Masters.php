<?php

use think\migration\Seeder;

class Masters extends Seeder
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
        $master = new \app\master\model\Master();

        $test = $master->find();

        $date = date('Y-m-d H:i:s');

        if (is_null($test)){

            $master->nickname = '超级管理员';
            $master->account = 'admins';
            $master->password = md5('asdasd123');
            $master->created_at = $date;
            $master->save();
        }
    }
}