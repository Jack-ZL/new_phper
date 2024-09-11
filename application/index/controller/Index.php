<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\index\controller;

use think\Db;
use think\helper\Time;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home
{
    /**
     * 首页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        // 顶置贴
        $top = $this->getPost(['post.top'=>1],'post.top_sort');
        if ($top->items()) {
            $this->assign('top',$top);
        }
        // 首页贴
        $home_post = $this->getPost([],['post.id'=>'desc']);
        $this->assign('home_post',$home_post);
        // 栏目列表
        $this->assign('column',get_column());
        // 栏目
        $this->assign('navActive','home');
        // 今日签到情况
        $sign_log_today = Db::name('sns_sign_in')
            ->where('user_id',$this->user['id'])
            ->whereTime('time','>',Time::today()[0])
            ->find();
        if ($sign_log_today) {
            $this->assign('sign_log_today',$sign_log_today);
        }
        // 连续签到
        $sign_log_yesterday = Db::name('sns_sign_in')
            ->where('user_id',$this->user['id'])
            ->whereBetween('time',Time::yesterday())
            ->find();
        if ($sign_log_yesterday) {
            $this->assign('sign_log_yesterday',$sign_log_yesterday);
            $sign_serial = $sign_log_yesterday['serial'] + 1;
            if ($sign_log_today){
                $sign_serial_ed = $sign_log_yesterday['serial'] + 1; //已连续签到天数
            } else {
                $sign_serial_ed = $sign_log_yesterday['serial'];
            }
        } else {
            $sign_serial = 1;
            if ($sign_log_today){
                $sign_serial_ed = 1;
            } else {
                $sign_serial_ed = 0;
            }
        }
        // 今日签到可获奖励
        $this->assign('today_sign_in_reward',get_reward_by_serial($sign_serial));
        // 已连续签到天数
        $this->assign('sign_serial_ed',$sign_serial_ed);
        // 本周热议
        $this->assign('hot_week',$this->getHotPost());

        return $this->fetch();
    }

    /**
     * 栏目
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function column()
    {
        $name = $this->request->param('name');
        $map = $this->request->param('map');

        $where = [];
        if ($name !== 'all'){
            $column_id = Db::name('sns_column')->where('name',$name)->value('id');
            $where['post.column_id'] = $column_id;
        }

        if ($map){
            if ($map === 'unsolved'){
                $where['post.solved'] = 0; // 未结
            }else if ($map === 'solved'){
                $where['post.solved'] = 1; // 已结
            }else if ($map === 'essence'){
                $where['post.essence'] = 1; // 精华
            }
        }

        $order = ['post.id'=>'desc'];

        $post = $this->getPost($where,$order);

        if ($post->items()){
            $this->assign('post',$post);
        }
        $this->assign('page',$post->render());
        // 栏目列表
        $this->assign('column',get_column());
        // 栏目
        $this->assign('navActive',$name);
        $this->assign('mapActive',$map);
        // 获取本周热议
        $hot = $this->getHotPost();
        if (count($hot)){
            $this->assign('hot',$hot);
        }
        return $this->fetch();
    }

    private function getPost($where,$order)
    {
        $where['post.status'] = 1;
        return Db::name('sns_post')
            ->alias('post')
            ->join('sns_user user','post.user_id = user.id','LEFT')
            ->join('sns_column column','post.column_id = column.id','LEFT')
            ->where($where)
            ->order($order)
            ->field('post.id,post.user_id,post.solved,post.essence,post.top,post.title,post.reward,post.reply,post.create_time,user.id as uid,user.avatar,user.avatar_default,user.nickname,user.authentication,column.title as column_title')
            ->paginate(20);
    }

    /**
     * 点击用户昵称进入用户主页
     * @param $username
     */
    public function jump($username)
    {
        $uid = Db::name('sns_user')
            ->where('nickname',$username)
            ->value('id');
        $this->redirect('user/home',['id'=>$uid]);
    }
}
