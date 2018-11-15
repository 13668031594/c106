<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/10/17
 * Time: 下午5:57
 */

namespace classes\article;

use classes\FirstClass;
use classes\ListInterface;

class Article extends FirstClass implements ListInterface
{
    public $model;

    public function __construct()
    {
        $this->model = new \app\article\model\Article();
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
        $master = $this->is_login();

        $model = $this->model;
        $model->title = input('title');
        $model->content = input('fwb-content');
        $model->author = input('author');
        $model->describe = input('describe');
        $model->master_id = $master['id'];
        $model->master_nickname = $master['nickname'];
//        $model->master_id = 123;
//        $model->master_nickname = 123;
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public function read($id)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::redirect_exception('/article', ['文章不存在']);

        return $model->getData();
    }

    public function edit($id)
    {
        return self::read($id);
    }

    public function update($id)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::ajax_exception(401, ['文章不存在']);

        $model->title = input('title');
        $model->content = input('fwb-content');
        $model->author = input('author');
        $model->describe = input('describe');
        $model->save();
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->delete();
    }

    public function validator_save()
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'fwb-content' => 'require|min:1|max:2000',
            'author'=> 'require|min:1|max:255',
            'describe'=> 'require|min:1|max:255',
        ];

        $file = [
            'title' => '标题',
            'fwb-content' => '内容',
            'author' => '作者',
            'describe' => '描述',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(402, $result);
    }

    public function validator_update($id)
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'fwb-content' => 'require|min:1|max:2000',
            'author'=> 'require|min:1|max:255',
            'describe'=> 'require|min:1|max:255',
        ];

        $file = [
            'title' => '标题',
            'fwb-content' => '内容',
            'author' => '作者',
            'describe' => '描述',
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(403, $result);
    }

    public function validator_delete($id)
    {
        // TODO: Implement validator_delete() method.
    }

}