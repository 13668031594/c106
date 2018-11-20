<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

//后台登录页面
Route::get('login', 'master/Login/view');

//后台登录方法
Route::post('login', 'master/Login/login');

//后台注销登录
Route::get('logout', 'master/Login/logout');

//首页
Route::get('admin', 'master/Login/index');

//管理员模块
Route::get('master', 'master/Master/index');
Route::get('master-table', 'master/Master/table');
Route::get('master-create', 'master/Master/create');
Route::post('master', 'master/Master/store');
Route::get('master-edit/:id', 'master/Master/edit');
Route::get('master-delete', 'master/Master/delete');

//系统参数配置
Route::get('setting', 'setting/Setting/index');
Route::get('setting-reset', 'setting/Setting/reset');
Route::post('setting', 'setting/Setting/save');

//支付协议
Route::get('payAgreement', 'setting/Payagreement/index');
Route::post('payAgreement', 'setting/Payagreement/save');

//日转日志
Route::get('day', 'conversion/Conversion/day');
Route::get('day-table', 'conversion/Conversion/day_table');
Route::get('dayDetails', 'conversion/Conversion/day_details');
Route::get('dayDetailsTable', 'conversion/Conversion/day_details_table');

//公告模块
Route::get('notice', 'notice/Notice/index');
Route::get('notice-table', 'notice/Notice/table');
Route::get('notice-create', 'notice/Notice/create');
Route::post('notice', 'notice/Notice/store');
Route::get('notice-edit/:id', 'notice/Notice/edit');
Route::get('notice-delete', 'notice/Notice/delete');

//文章模块
Route::get('article', 'article/Article/index');
Route::get('article-table', 'article/Article/table');
Route::get('article-create', 'article/Article/create');
Route::post('article', 'article/Article/store');
Route::get('article-edit/:id', 'article/Article/edit');
Route::get('article-delete', 'article/Article/delete');

//会员模块
Route::get('member', 'member/Member/index');
Route::get('member-table', 'member/Member/table');
Route::get('member-create', 'member/Member/create');
Route::post('member', 'member/Member/store');
Route::get('member-edit/:id', 'member/Member/edit');
Route::get('member-delete', 'member/Member/delete');
Route::get('wallet/:id', 'member/Member/wallet');//钱包
Route::post('wallet', 'member/Member/wallet_update');//充值
Route::get('record/:id', 'member/Member/record');//钱包流水
Route::get('record-table/:id', 'member/Member/record_table');//流水table
Route::get('record-delete', 'member/Member/record_delete');//删除流水
Route::get('team/:id', 'member/Member/team');//团队展示l

//广告管理
Route::get('adv', 'adv/Adv/index');
Route::get('adv-table', 'adv/Adv/table');
//Route::get('adv-create', 'adv/Adv/create');
Route::post('adv', 'adv/Adv/store');
Route::get('adv-edit/:id', 'adv/Adv/edit');
//Route::get('adv-delete', 'adv/Adv/delete');
Route::post('adv-image', 'adv/Adv/image');

//直充订单管理
Route::get('recharge', 'recharge/Recharge/index');
Route::get('recharge-table', 'recharge/Recharge/table');
Route::get('recharge-edit/:id', 'recharge/Recharge/edit');
Route::get('recharge-delete', 'recharge/Recharge/delete');
Route::get('recharge-pay/:id', 'recharge/Recharge/pay');//付款
Route::get('recharge-status/:id', 'recharge/Recharge/status');//订单状态修改

//激活订单管理
Route::get('active', 'active/Active/index');
Route::get('active-table', 'active/Active/table');
Route::get('active-edit/:id', 'active/Active/edit');
Route::get('active-delete', 'active/Active/delete');
Route::get('active-pay/:id', 'active/Active/pay');//付款
Route::get('active-status/:id', 'active/Active/status');//订单状态修改

//签到列表
Route::get('attendance', 'attendance/Attendance/index');
Route::get('attendance-table', 'attendance/Attendance/table');

/************前台路由**************/
//登录
Route::get('index-login', 'member/Login/view');
Route::post('index-login', 'member/Login/login');
Route::get('index-logout', 'member/Login/logout');
Route::get('index-reg', 'member/Login/reg');
Route::post('index-reg', 'member/Login/register');
Route::get('index-reset', 'member/Login/res');
Route::post('index-reset', 'member/Login/reset');
Route::post('wechat-notify-recharge', 'member/Login/notify_recharge');//微信众筹回调
Route::post('wechat-notify-active', 'member/Login/notify_active');//激活资产回调
Route::get('index-reg-sms/:phone', 'member/Login/sms_reg');//注册短信发送
Route::get('index-reset-sms/:phone', 'member/Login/sms_reset');//注册短信发送

//首页
Route::get('/index', 'member/Login/exchange_code');//微信进入
Route::get('/', 'index/Index/index');//首页
Route::get('index-family', 'index/Index/family');//家谱
Route::get('index-memorial', 'index/Index/memorial');//纪念堂
Route::get('index-shared', 'index/Index/shared');//家族共享
Route::get('index-information', 'index/Index/information');//咨讯中心
Route::get('index-information-table', 'index/Index/information_table');//咨讯中心-翻页
Route::get('index-information-hy', 'index/Index/information_hy');//文章
Route::get('index-information-hy-table', 'index/Index/information_hy_table');//文章-翻页
Route::get('index-information-info/:id', 'index/Index/information_info');//文章详情
Route::get('index-crowd', 'index/Index/crowd');//众筹
Route::post('index-crowd', 'index/Recharge/save');//众筹-统一下单
Route::get('index-crowd-info/:id', 'index/Recharge/info');//支付轮询
Route::get('index-crowd-out/:id', 'index/Recharge/out');//支付轮询
Route::get('index-financial', 'index/Index/financial');//财务
Route::get('index-financial-table', 'index/Index/financial_table');//财务-翻页
Route::get('index-shift-to-qr', 'index/Index/shift_to_qr');//转入二维码
Route::get('index-roll-out/:id', 'index/Index/roll_out');//转出
Route::post('index-roll-out', 'index/Assetchange/asset_out');//转出
Route::get('index-exchange', 'index/Index/exchange');//转换
Route::post('index-exchange', 'index/Assetchange/exchange');//转换
Route::get('index-worship','index/Index/worship');//祭拜
Route::get('index-added','index/Index/added');//增值服务worship.html

//个人中心
Route::get('index-personal', 'index/Personal/personal');
Route::get('index-self', 'index/Personal/self');
Route::post('index-self', 'index/Personal/nickname');
Route::get('index-password', 'index/Personal/pass');
Route::post('index-password', 'index/Personal/password');
Route::get('index-pay-pass', 'index/Personal/pay_pass');
Route::post('index-pay-pass', 'index/Personal/pay_password');
Route::get('index-share', 'index/Personal/share');
Route::get('index-act', 'index/Personal/act');
Route::post('index-act', 'index/Personal/acted');
Route::get('index-act-info/:id', 'index/Personal/info');
Route::get('index-act-out/:id', 'index/Personal/act_out');
Route::get('index-pay-note', 'index/Personal/pay_note');
Route::get('index-pay-note-table', 'index/Personal/pay_note_table');