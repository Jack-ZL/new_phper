<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/9
 * Time: 13:20
 */

use app\sns\model\User as UserModel;
use think\Db;

if (!function_exists('account_type')){
    /**
     * 判断账号类型
     * @param $account
     * @return bool|string
     */
    function account_type($account){
        // 匹配登录方式
        if (preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $account)) {
            // 邮箱
            return 'email';
        } elseif (preg_match("/^1\d{10}$/", $account)) {
            // 手机号
            return 'mobile';
        } else {
            return false;
        }
    }
}

if (!function_exists('has_login')){
    function has_login()
    {
        $user = session('sns_user_auth');
        if (empty($user)) {
            // 判断是否记住登录
            if (cookie('?sns_u') && cookie('?sns_token')) {
                $UserModel = new UserModel();
                $user = $UserModel::get(cookie('sns_u'));
                if ($user) {
                    $login_token = data_auth_sign($user['nickname'].$user['id'].$user['last_login_time']);
                    if (cookie('sns_token') == $login_token) {
                        // 自动登录
                        UserModel::autoLogin($user);
                        return $user['id'];
                    }
                }
            };
            return false;
        }else{
            return session('sns_user_auth_login') == data_auth_sign($user) ? $user['id'] : false;
        }
    }
}

if (!function_exists('get_column')) {
    /**
     * 获取栏目列表
     * @param bool $addPost 是否发帖
     * @param bool $admin 是否管理员
     * @return array|false|mixed|PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function get_column($addPost = false,$admin = false)
    {
        $column = cache('sns_column');
        if (!$column){
            $column = Db::name('sns_column')
                ->where('status',1)
                ->order('sort')
                ->field('id,name,title,dot,user_group')
                ->select();
            cache('sns_column',$column);
        }
        if ($addPost && $admin === false){
            $column_ = [];
            foreach ($column as $item){
                if ($item['user_group'] != 0){
                    continue;
                }
                $column_[] = $item;
            }
            return $column_;
        } else {
            return $column;
        }
    }
}

if (!function_exists('reward_options')){
    /**
     * 悬赏选项
     * @return array
     */
    function reward_options(){
        return [
            '20'    =>  20,
            '30'    =>  30,
            '50'    =>  50,
            '60'    =>  60,
            '80'    =>  80
        ];
    }
}

if (!function_exists('tran_time')){
    /**
     * 时间转换
     * @param $time
     * @return false|string
     */
    function tran_time($time)
    {
        $val = time() - $time;

        if ($val < 60)
        {
            $str = '刚刚';
        }
        elseif ($val < 60 * 60)
        {
            $min = floor($val/60);
            $str = $min.'分钟前';
        }
        elseif ($val < 60 * 60 * 24)
        {
            $h = floor($val/(60*60));
            $str = $h.'小时前 ';
        }
        elseif ($val < 60 * 60 * 24 * 7)
        {
            $d = floor($val/(60*60*24));
            $str = $d.'天前';
        }
        else
        {
            $str = date('Y-m-d',$time);
        }
        return $str;
    }
}

if (!function_exists('get_reward_by_serial')){
    /**
     * 根据连续签到获取对应奖励
     * @param $serial
     * @return int
     */
    function get_reward_by_serial($serial)
    {
        if ($serial >= 30){
            return 20;
        } else if ($serial >= 15){
            return 15;
        } else if ($serial >= 5){
            return 10;
        } else {
            return 5;
        }
    }
}

if(!function_exists('send_message')){
    /**
     * 发送消息
     * @param $to
     * @param int $from
     * @param int $post_id
     * @param string $content
     * @param string $type
     * @param int $reply_id
     */
    function send_message($to, $from = 0, $post_id = 0, $content = '', $type = '', $reply_id = 0)
    {
        $data = [
            'to'    =>  $to,
            'from'    =>  $from,
            'post_id'    =>  $post_id,
            'content'    =>  $content,
            'type'    =>  $type,
            'reply_id'    =>  $reply_id,
            'create_time'=> time()
        ];
        Db::name('sns_message')->insert($data);
    }
}

if (!function_exists('seo_description')){
    /**
     * 截取文章内容为seo描述
     * @param $text
     * @return string
     */
    function seo_description($text)
    {
        $sub_res = mb_substr($text,0,150,'utf-8');
        return strip_tags($sub_res);
    }
}

if(!function_exists('is_wechat')){
    /**
     * 判断是否微信访问
     * @return bool
     */
    function is_wechat(){
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }
}