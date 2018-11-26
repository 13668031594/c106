<?php

namespace app\member\controller;

use think\Controller;
use think\Request;

class Member extends Controller
{
    private $class;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->class = new \classes\member\Member();

        $this->class->is_login();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return $this->class->view('index');
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        return $this->class->view('member');
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
        return $this->class->success('/member');
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
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
        $member = $this->class->edit($id);

        //视图
        return $this->class->view('member', ['member' => $member]);
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

        //更新
        $this->class->update($id);

        //反馈成功
        return $this->class->success('/member');
    }

    /**
     * 删除指定资源
     *
     * @return \think\Response
     */
    public function delete()
    {
        $ids = explode(',', input('id'));

        //验证资源
        $this->class->validator_delete($ids);

        //删除
        $this->class->delete($ids);

        //反馈成功
        return $this->class->success('/member');
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

    public function wallet($id)
    {
        $member = $this->class->read($id);

        return $this->class->view('wallet', ['member' => $member]);
    }

    public function wallet_update()
    {
        $this->class->validator_wallet();

        $this->class->wallet();

        return $this->class->success('/');
    }

    public function record($id)
    {
        $result = [];
        $result['record_array'] = json_encode($this->class->record_array());
        $result['member'] = $this->class->read($id);


        return $this->class->view('record', $result);
    }

    public function record_table($id)
    {
        $result = $this->class->record($id);

        return $this->class->table($result);
    }

    public function record_delete()
    {
        $ids = explode(',', input('id'));

        //删除
        $this->class->record_delete($ids);

        //反馈成功
        return $this->class->success('/member');
    }

    public function team($id)
    {
        $member = $this->class->read($id);

        $result = $this->class->team($id);

        $result['member'] = $member;
//dump($result);
//exit;
        return $this->class->view('team',$result);
    }
}
