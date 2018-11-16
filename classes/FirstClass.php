<?php

/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 2018/10/17
 * Time: 下午5:45
 */

namespace classes;

use app\adv\model\Adv;
use app\exceptions\AjaxException;
use app\exceptions\RedirectException;
use app\master\model\Master;
use app\member\model\Member;
use app\notice\model\Notice;
use classes\set\LoginSet;
use think\Db;
use think\Model;
use think\Validate;

class FirstClass
{
    /**
     * 验证master登录情况，验证通过返回master资料array
     *
     * @return Master|array|false|mixed|\PDOStatement|string|Model
     */
    public function is_login()
    {
//        return;
        //尝试获取session中的master信息
        $master = session('master');

        //验证session中的信息格式与过期时间
        if (is_null($master) || !is_array($master) || !isset($master['id']) || !isset($master['time']) || ($master['time'] < time())) self::redirect_exception('/logout', '请重新登录');

        //赋值管理员id
        $master_id = $master['id'];

        //初始化管理员模型
        $master = new Master();

        //尝试获取管理员资料
        $master = $master->where('id', '=', $master_id)->find();

        //没有获取到管理员资料，跳转至登录页面
        if (is_null($master)) self::redirect_exception('/logout', '请重新登录');

        //获取资料数组，去其他数据
        $master = $master->getData();

        //获取当前ip
        $login_ip = $_SERVER["REMOTE_ADDR"];

        //登录ip不同，证明在其他地方登录，跳转至登录页面
        if ($login_ip != $master['login_ip']) self::redirect_exception('/logout', '已在其他地方登录');

        //更新操作时间
        self::refresh_login($master_id);

        //验证成功，返回管理员模型
        return $master;
    }

    /**
     * 重置登录时间
     *
     * @param $master_id
     */
    public function refresh_login($master_id)
    {
        $set = new LoginSet();

        $master = [
            'id' => $master_id,
            'time' => time() + $set->login_time
        ];

        session('master', $master);
    }

    /**
     * 验证member登录情况，验证通过返回member资料array
     *
     * @param int $status
     * @return Member|array|false|\Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed|\PDOStatement|string|Model
     */
    public function is_login_member($status = 2)
    {
//        return;
        //尝试获取session中的member信息
        $member = session('member');

        //验证session中的信息格式与过期时间
        if (is_null($member) || !is_array($member) || !isset($member['id']) || !isset($member['time']) || ($member['time'] < time())) self::redirect_exception('/index-logout');

        //赋值会员id
        $member_id = $member['id'];

        //初始化会员模型
        $member = new Member();

        //尝试获取会员资料
        $member = $member->where('id', '=', $member_id)->find();

        //没有获取到会员资料，跳转至登录页面
        if (is_null($member)) self::redirect_exception('/index-logout');

        //获取资料数组，去其他数据
        $member = $member->getData();

        //验证状态
        if ($member['status'] >= $status) self::redirect_exception('/', '您的账号已经被『' . ($status == '1' ? '冻结' : '禁用') . '』');

        //获取当前ip
        $login_ip = $_SERVER["REMOTE_ADDR"];

        //登录ip不同，证明在其他地方登录，跳转至登录页面
        if ($login_ip != $member['login_ip']) self::redirect_exception('/index-logout');

        //更新操作时间
        self::refresh_login_member($member_id);

        //验证成功，返回会员模型
        return $member;
    }

    /**
     * 重置登录时间
     *
     * @param $member_id
     */
    public function refresh_login_member($member_id)
    {
        $set = new LoginSet();

        $member = [
            'id' => $member_id,
            'time' => time() + $set->login_time
        ];

        session('member', $member);
    }

    /**
     * 分页返回数据
     *
     * @param Model $model
     * @param string $order_name
     * @param string $order_type
     * @param array $where
     * @param string $column
     * @return array
     */
    protected function page($model, $order_name = 'id', $order_type = 'desc', $where = [], $column = '*')
    {
        //页码
        $page = (int)input('page');
        $page = empty($page) ? 1 : $page;

        //单页条数
        $limit = (int)input('limit');
        $limit = empty($limit) ? 20 : $limit;

        //数据条数
        $number = $model->where($where)->count();

        //数据
        $data = $model->where($where)->limit($limit)->page($page)->order($order_name, $order_type)->column($column);

        $i = 0;

        $result = [];

        foreach ($data as $v) {
            $result[$i] = $v;
            $i++;
        }

        //返回格式
        return [
//            'current_page' => $number == 0 ? 0 : $page,
//            'first_page' => $number == 0 ? 0 : 1,
//            'last_page' => $number == 0 ? 0 : ceil($number / $limit),
            'total' => $number,
            'message' => $result,
        ];
    }

    /**
     * 重定向报错
     *
     * @param string $url
     * @param string $errors
     * @throws RedirectException
     */
    protected function redirect_exception($url = '', $errors = '')
    {
        Db::rollback();

        //ajax请求，返回ajax
        if (isset($_GET['ajax'])) self::ajax_exception(999, $errors);

        $result = [
            'url' => $url,//跳转路由
            'message' => $errors//提示代码
        ];

        throw new RedirectException(json_encode($result));
    }

    /**
     * ajax报错
     *
     * @param $code
     * @param $error
     * @throws AjaxException
     */
    protected function ajax_exception($code, $error)
    {
        Db::rollback();

        $result = [
            'status' => 'fails',
            'code' => $code,
            'message' => $error,
        ];

        throw new AjaxException(json_encode($result));
    }

    /**
     * 跳转页面
     *
     * @param $view
     * @param array $data
     * @return \think\response\View
     */
    public function view($view, $data = [])
    {
        $data['errors'] = null;
        $data['success'] = null;

        //获取提示
        $errors = session('errors');
        $success = session('success');

        //初始化errors视图变量
        if (!is_null($errors) && is_string($errors)) $data['errors'] = $errors;

        //初始化success视图变量
        if (!is_null($success) && is_string($success)) $data['success'] = $success;

        //删除提示
        session('errors', null);
        session('success', null);

        //渲染视图
        return view($view, $data);
    }

    /**
     * ajax成功
     *
     * @param string $url
     * @param string $success
     * @param array $other
     * @return \think\response\Json
     */
    public function success($url = '', $success = '操作成功', $other = [])
    {
//        session('success', $success);

//        return redirect(url($url, '', false));

        $result = [
            'status' => 'success',
            'url' => $url,
            'message' => $success,
        ];

        return json(array_merge($result, $other));
    }

    /**
     * 手动验证，返回空array即为通过
     *
     * @param array $data
     * @param array $rule
     * @param array $message
     * @param array $file
     * @return array
     */
    public function validator($data = [], $rule = [], $message = [], $file = [])
    {
        //没有验证条件，直接返回空数组
        if (empty($rule)) return [];

        //初始化验证器
        $validator = new Validate($rule, $message, $file);

        //判断验证是否通过
        if (!$validator->check($data)) {

            //否

            $errors = $validator->getError();

            //返回错误描述
            return $errors;
        }
        //返回空
        return null;
    }

    public function table($result = [])
    {
        $result['status'] = 'success';

        return json($result);
    }


}