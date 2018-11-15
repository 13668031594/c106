<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/10/17
 * Time: 下午5:57
 */

namespace classes\master;

use classes\FirstClass;
use classes\ListInterface;

class Master extends FirstClass implements ListInterface
{
    public $model;

    public function __construct()
    {
        $this->model = new \app\master\model\Master();
    }

    public function index()
    {
        return parent::page($this->model);
    }

    public function create()
    {
        // TODO: Implement create() method.
    }

    public function save()
    {
        $master = $this->model;
        $master->nickname = input('nickname');
        $master->account = input('account');
        $master->password = md5(input('password'));
//        $master->phone = input('phone');
        $master->created_at = date('Y-m-d H:i:s');
        $master->save();
    }

    public function read($id)
    {
        $master = $this->model->where('id', '=', $id)->find();

        if (is_null($master)) parent::redirect_exception('/master', ['管理员不存在']);

        return $master->getData();
    }

    public function edit($id)
    {
        return self::read($id);
    }

    public function update($id)
    {
        $master = $this->model->where('id', '=', $id)->find();

        if (is_null($master)) parent::ajax_exception(204, ['管理员不存在']);

        $master->nickname = input('nickname');
        if (input('password') != 'w!c@n#m$b%y^') $master->password = md5(input('password'));
//        $master->phone = input('phone');
        $master->updated_at = date('Y-m-d H:i:s');
        $master->save();
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->delete();
    }

    public function validator_save()
    {
        $rule = [
            'nickname' => 'require|min:2|max:48|unique:master,nickname',
            'account' => 'require|min:6|max:20|unique:master,account',
            'password' => 'require|min:6|max:20',
//            'phone' => 'min:8|max:15',
        ];

        $file = [
            'nickname' => '昵称',
            'account' => '账号',
            'password' => '密码',
//            'phone' => '联系电话',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(202, $result);
    }

    public function validator_update($id)
    {
        $rule = [
            'nickname' => 'require|min:2|max:48|unique:master,nickname,' . $id . ',id',
            'password' => 'require|min:6|max:20',
//            'phone' => 'min:8|max:15',
        ];

        $file = [
            'nickname' => '昵称',
            'password' => '密码',
//            'phone' => '联系电话',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(204, $result);
    }

    public function validator_delete($id)
    {
        if (in_array(1, $id)) parent::ajax_exception(203, '无法删除初始管理员');
    }

    /**
     * 特殊验证：初始管理员无法被其他人编辑
     *
     * @param $master
     * @param $id
     */
    public function validator_special($master, $id)
    {
        if (($master['id'] != $id) && ($id == 1)) parent::ajax_exception(209, '无权修改该管理员的信息');
    }
}