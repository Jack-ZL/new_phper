<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/10
 * Time: 17:38
 */

namespace app\index\controller;

use think\Db;
use think\helper\Time;

class Reply extends Home
{
    /**
     * 处理赞操作
     */
    public function zan()
    {
        $this->needLogin();

        $reply_id = $this->request->post('id');
        $ok = $this->request->post('ok');

        // 启动事务
        Db::startTrans();
        try{
            if ($ok == 'false'){
                // 写入记录
                Db::name('sns_zan')->insert(['reply_id'=>$reply_id,'user_id'=>$this->user['id'],'time'=>$this->request->time()]);
                // 总赞数+1
                Db::name('sns_reply')
                    ->where('id',$reply_id)
                    ->setInc('like');
            } else {
                // 删除记录
                Db::name('sns_zan')->where(['user_id'=>$this->user['id'],'reply_id'=>$reply_id])->delete();
                // 总赞数-1
                Db::name('sns_reply')
                    ->where('id',$reply_id)
                    ->setDec('like');
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $this->error('服务器错误，请稍后再试');
            // 回滚事务
            Db::rollback();
        }
        $this->success('');
    }

    /**
     * 采纳回答
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function accept()
    {
        $this->needLogin();

        $reply_id = $this->request->param('id');

        $reply = Db::name('sns_reply')->where('id',$reply_id)->find();
        
        if($reply['user_id'] == $this->user['id']){
            $this->error('采纳自己的回答，好玩吗？');
        }

        // 采纳奖励
        $post_reward = Db::name('sns_post')
            ->where('id',$reply['post_id'])->value('reward');

        // 启动事务
        Db::startTrans();
        try{
            // 采纳记录
            Db::name('sns_reply')->where('id',$reply_id)->update(['adopt'=>1]);
            Db::name('sns_post')->where('id',$reply['post_id'])->update(['reply_accept'=>$reply_id,'solved'=>1]);
            // 被采纳者增加积分
            Db::name('sns_user')
                ->where('id',$reply['user_id'])
                ->setInc('score',$post_reward);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $this->error('服务器错误，请稍后再试');
            // 回滚事务
            Db::rollback();
        }

        send_message($reply['user_id'],$this->user['id'],$reply['post_id'],'采纳了你的回答，赏 '.$post_reward.' '.config('sns_score_name'));
        $this->success('采纳成功');
    }

    /**
     * 回帖周榜
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function top()
    {
        // 回帖周榜
        $reply_week = Db::name('sns_reply')
            ->alias('r')
            ->join('sns_user u','u.id = r.user_id')
            ->where('r.time','>',Time::lastWeek()[1])
            ->field('r.id,count(r.id) as total,r.user_id as uid,u.nickname,u.avatar_default,u.avatar')
            ->group('r.user_id')
            ->order('total','desc')
            ->limit(10)
            ->select();

        foreach ($reply_week as &$item){
            //头像
            if($item['avatar'] == 0){
                $item['avatar'] = '/static/home/img/avatar/'.$item['avatar_default'].'.png';
            } else {
                $item['avatar'] = get_file_path($item['avatar']);
            }
        }

        $this->success('','',$reply_week);
    }

    /**
     * 获取回复内容
     */
    public function get()
    {
        $content = Db::name('sns_reply')
            ->where('id',input('id'))
            ->value('content');
        $this->success('','',['content'=>$content]);
    }

    /**
     * 编辑
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update()
    {
        if (Db::name('sns_reply')->where('id',input('id'))->update(['content'=>input('content')])){
            $this->success('编辑成功');
        }
        $this->error('编辑失败');
    }

    /**
     * 删除帖子
     * @param $id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($id)
    {
        if (Db::name('sns_reply')->where('id',$id)->delete()){
            $this->success('删除成功');
        }
        $this->error('删除失败');
    }
}