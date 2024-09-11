<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/9
 * Time: 13:14
 */

namespace app\sns\model;

use think\Model as ThinkModel;

class User extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__SNS_USER__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 自动登录
     * @param $user
     * @param bool $remember
     * @return mixed
     */
    public static function autoLogin($user, $remember = false)
    {
        // 记录登录SESSION和COOKIES
        $auth = array(
            'id'              => $user['id'],
            'nickname'        => $user['nickname'],
            'avatar'          => $user['avatar'],
            'avatar_default'  => $user['avatar_default'],
            'last_login_time' => $user['last_login_time'],
            'last_login_ip'   => request()->ip(1),
        );

        session('sns_user_auth', $auth);
        session('sns_user_auth_login', data_auth_sign($auth));

        // 记住登录
        if ($remember) {
            $login_token = $user['nickname'].$user['id'].$user['last_login_time'];
            cookie('sns_u', $user['id'], 24 * 3600 * 7);
            cookie('sns_token', data_auth_sign($login_token), 24 * 3600 * 7);
        }
    }
}