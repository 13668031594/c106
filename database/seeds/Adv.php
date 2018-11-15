<?php

use think\migration\Seeder;

class Adv extends Seeder
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
        $model = new \app\adv\model\Adv();

        $count = $model->count();

        $insert = [];

        $date = date('Y-m-d H:i:s');

        for ($i = ($count + 1); $i < 6; $i++) {

            $insert[$i]['title'] = '广告' . $i;
            $insert[$i]['sort'] = $i;
            $insert[$i]['describe'] = '描述' . $i;
            $insert[$i]['created_at'] = $date;
        }

        if (count($insert) > 0) $model->insertAll($insert);
    }
}