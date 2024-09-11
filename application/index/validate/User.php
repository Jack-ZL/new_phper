<?php

namespace app\index\validate;

use think\Validate;

/**
 * 用户验证器
 * @package app\index\validate
 */
class User extends Validate
{
    //定义验证规则
    protected $rule = [
        'account|手机/邮箱'  => 'require',
        'vercode|短信/邮箱验证码'  => 'require',
        'imagecode|图形验证码'  => 'require',
        'nickname|昵称'  => 'require|unique:sns_user',
        'pass|密码'  => 'require|length:6,16',
        'repass|确认密码'=>'require|confirm:pass',
        'agreement|用户服务条款'  =>  'require'
    ];

    //定义验证提示
    protected $message = [
        'account.require' => '请输入账号',
        'vercode.require' => '请输入短信/邮箱验证码',
        'imagecode.require' => '请输入图形验证码',
        'nickname.require' => '请输入昵称',
        'nickname.unique' => '该昵称已被占用',
        'pass.require' => '请输入密码',
        'pass.length'  => '密码长度6-16位',
        'repass.confirm'     => '两次输入的密码不一致',
        'agreement.require'     => '您必须 [同意用户服务条款] 才能注册'
    ];

    //定义验证场景
    protected $scene = [
        //注册
        'reg'  =>  ['account', 'vercode', 'nickname', 'pass', 'repass', 'agreement'],
        //重置密码
        'forget'  =>  ['account', 'vercode', 'pass', 'repass'],
        //登录
        'login' =>  ['account', 'pass', 'imagecode']
    ];
}
