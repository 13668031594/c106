<?php

namespace app\master\controller;

use think\Controller;
use think\Request;

class Master extends Controller
{
    private $class;
    private $master;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        //初始化类库
        $this->class = new \classes\master\Master();

        //验证登录,并获取管理员信息
        $this->master = $this->class->is_login();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //获取管理员列表
//        $result = $this->class->index();

        //视图
        return $this->class->view('index',['master' => $this->master['id']]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //视图
        return $this->class->view('master', ['type' => 'create']);
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save()
    {
        //验证字段
        $this->class->validator_save();

        //添加
        $this->class->save();

        //反馈成功
        return $this->class->success('/master');
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //获取数据
        $master = $this->class->read($id);

        //视图
        return $this->class->view('master_read', ['master' => $master]);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //获取数据
        $master = $this->class->edit($id);

        //视图
        return $this->class->view('master', ['master' => $master, 'type' => 'edit']);
    }

    /**
     * 保存更新的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function update($id)
    {
        //验证字段
        $this->class->validator_update($id);

        //特殊验证
        $this->class->validator_special($this->master, $id);

        //更新
        $this->class->update($id);

        //反馈成功
        return $this->class->success('/master');
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {
        $ids = explode(',', input('id'));

        //验证资源
        $this->class->validator_delete($ids);

        //删除
        $this->class->delete($ids);

        //反馈成功
        return $this->class->success('/master');
    }

    public function table()
    {
        $result = $this->class->index();

        return $this->class->table($result);
    }

    public function store()
    {
        $id = input('id');

        if (empty($id)) return self::save();
        else return self::update($id);
    }
}
