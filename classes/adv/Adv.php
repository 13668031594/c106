<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/10/17
 * Time: 下午5:57
 */

namespace classes\adv;

use app\adv\model\AdvImages;
use classes\FirstClass;
use classes\ListInterface;

class Adv extends FirstClass implements ListInterface
{
    public $model;
    public $image;

    public function __construct()
    {
        $this->model = new \app\adv\model\Adv();
        $this->image = new AdvImages();
    }

    public function index()
    {
        $result = parent::page($this->model, 'sort', 'asc');

        foreach ($result['message'] as &$v) {

            if (is_null($v['location'])) $v['location'] = 'favicon.ico';
        }

        return $result;
    }

    public function create()
    {
        // TODO: Implement create() method.
    }

    public function save()
    {
        $model = $this->model;
        $model->title = input('title');
        $model->image = input('imageId');
        $model->show = input('show');
        $model->created_at = date('Y-m-d H:i:s');
        $model->save();
    }

    public function read($id)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::redirect_exception('/adv', ['广告不存在']);

        if (is_null($model->location)) $model->location = '/favicon.ico';

        return $model->getData();
    }

    public function edit($id)
    {
        return self::read($id);
    }

    public function update($id)
    {
        $model = $this->model->where('id', '=', $id)->find();

        if (is_null($model)) parent::ajax_exception(601, ['广告不存在']);

        $image = new AdvImages();
        $image = $image->where('id',input('imageId'))->find();

        $model->title = input('title');
        $model->image = $image->id;
        $model->location = $image->location;
        $model->show = input('show');
        $model->describe = input('describe');
        $model->save();

        $image->adv = $model->id;
        $image->save();

        $images = new AdvImages();
        $images->where('adv','=',$model->id)->where('id','<>',$image->id)->update(['adv' => null]);
    }

    public function delete($id)
    {
        $this->model->whereIn('id', $id)->delete();
    }

    public function validator_save()
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'describe' => 'require|min:1|max:255',
            'image' => 'require'
        ];

        $file = [
            'title' => '标题',
            'describe' => '描述',
            'show' => '是否显示',
            'image' => '图片'
        ];

        $result = parent::validator(input(), $rule, [], $file);
        if (!is_null($result)) parent::ajax_exception(602, $result);
    }

    public function validator_update($id)
    {
        $rule = [
            'title' => 'require|min:1|max:255',
            'describe' => 'require|min:1|max:255',
            'show' => 'require',
            'imageId' => 'require|unique:adv,image,' . $id . ',id'
        ];

        $message = [
            'imageId.unique' => '不能使用其他广告中的图片'
        ];

        $file = [
            'title' => '标题',
            'describe' => '描述',
            'show' => '是否显示',
            'imageId' => '图片'
        ];

        $result = parent::validator(input(), $rule, $message, $file);
        if (!is_null($result)) parent::ajax_exception(602, $result);
    }

    public function validator_delete($id)
    {
        // TODO: Implement validator_delete() method.
    }

    public function image()
    {
//        parent::ajax_exception(600, '123');

        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('images');

        $location = 'adv_' . input('id');

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => (1024 * 1024), 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'adv_image', $location);
//        parent::ajax_exception(600, gettype($info));

        if ($info) {

            $model = $this->image;
            $model->location = '/adv_image/' . $info->getSaveName();
            $model->created_at = date('Y-m-d H:i:s');
            $model->save();

            return [
                'image' => $model->location,
                'imageId' => $model->id,
            ];
        }

        // 上传失败获取错误信息
        parent::ajax_exception(600, $file->getError());
    }

    //删除过期图片
    public function image_delete()
    {
        $date = date('Y-m-d H:i:s',strtotime('-1 day'));

        $model = new AdvImages();

        $result = $model->where('created_at', '<', $date)->where('adv', null)->select();

        if (count($result) > 0)foreach ($result as $v) {
//            unlink(substr($v->location, 1))
            if (!is_null($v->location) && is_file($v->location)) unlink($v->location);
        }

        $model = new AdvImages();
        $model->where('created_at', '<', $date)->where('adv', null)->delete();
    }
}