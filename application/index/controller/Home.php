<?php

namespace app\index\controller;

use app\common\controller\Common;
use app\sns\model\User as UserModel;
use think\Db;
use think\helper\Time;

/**
 * 前台公共控制器
 * @package app\index\controller
 */
class Home extends Common
{
    protected $user;
    /**
     * 初始化方法
     */
    protected function _initialize()
    {
        // 系统开关
        if (!config('web_site_status')) {
            $this->error('站点维护中，请稍后访问~');
        }

        // 判断是否登录
        if ($uid = has_login()){
            $this->user = UserModel::get($uid);
            $this->assign('user',$this->user);
        }
    }

    /**
     * 检测是否需要登录
     */
    protected function needLogin()
    {
        if (!$this->user){
            if ($this->request->isAjax()){
                $this->error('请登录后操作');
            }
            // 登录回调
            session('oauth_callback',$this->request->url());
            $this->redirect('user/login');
        }
    }

    /**
     * 获取本周热议帖子
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getHotPost(){
        // 本周热议
         return Db::name('sns_post')
            ->where('create_time','>',Time::lastWeek()[1])
            ->where('reply','>',0)
            ->order('reply','desc')
            ->limit(15)
            ->field('id,title,reply')
            ->select();
    }
}
