<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/9
 * Time: 21:51
 */

namespace app\index\controller;


use think\Db;
use think\helper\Time;

class Sign extends Home
{
    /**
     * 签到操作
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function in()
    {
        $this->needLogin();

        $today_start_time = Time::today()[0];
        $sign_log_today = Db::name('sns_sign_in')
            ->where('user_id',$this->user['id'])
            ->whereTime('time','>',$today_start_time)
            ->find();
        if ($sign_log_today){
            $this->error('今天已签到');
        }

        $sign_log_yesterday = Db::name('sns_sign_in')
            ->where('user_id',$this->user['id'])
            ->whereBetween('time',Time::yesterday())
            ->find();

        $data = [
            'user_id'   =>  $this->user['id'],
            'time'  =>  $this->request->time()
        ];

        if ($sign_log_yesterday) {
            $data['serial'] = $sign_log_yesterday['serial'] + 1;
        } else {
            $data['serial'] = 1;
        }

        $data['reward'] = get_reward_by_serial($data['serial']);

        // 启动事务
        Db::startTrans();
        try{
            // 写入签到日志
            Db::name('sns_sign_in')->insert($data);
            // 写入积分日志
            Db::name('sns_score_log')->insert([
                'user_id'=>$this->user['id'],
                'score'=>$data['reward'],
                'remark'=>'签到奖励',
                'time'=>$this->request->time()
                ]);
            // 写入总积分
            Db::name('sns_user')
                ->where('id',$this->user['id'])
                ->setInc('score',$data['reward']);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $this->error('服务器错误，请稍后再试');
            // 回滚事务
            Db::rollback();
        }
        $this->success('签到成功','',['signed'=>1,'experience'=>$data['reward'],'days'=>$data['serial']]);
    }

    public function top()
    {
        // 最新签到
        $newest = Db::name('sns_sign_in')
            ->alias('sign')
            ->join('sns_user user','user.id = sign.user_id')
            ->limit(20)
            ->field('sign.id,sign.time,sign.user_id,sign.serial,user.nickname,user.avatar_default,user.avatar')
            ->order('sign.id','desc')
            ->select();
        $uids = [];
        $newest_ = [];
        foreach ($newest as &$item){
            //去重
            if (in_array($item['user_id'],$uids)){
                continue;
            }
            $uids[] = $item['user_id'];
            //头像
            if($item['avatar'] == 0){
                $item['avatar'] = '/static/home/img/avatar/'.$item['avatar_default'].'.png';
            } else {
                $item['avatar'] = get_file_path($item['avatar']);
            }
            //时间转换
            $item['time'] = date('m-d H:i:s',$item['time']);
            $newest_[] = $item;
        }

        // 今日最快
        $fastest_today = Db::name('sns_sign_in')
            ->alias('sign')
            ->join('sns_user user','user.id = sign.user_id')
            ->limit(20)
            ->field('sign.id,sign.time,sign.user_id,sign.serial,user.nickname,user.avatar_default,user.avatar')
            ->order('sign.id')
            ->where('time','>',Time::today()[0])
            ->select();
        foreach ($fastest_today as &$item){
            //头像
            if($item['avatar'] == 0){
                $item['avatar'] = '/static/home/img/avatar/'.$item['avatar_default'].'.png';
            } else {
                $item['avatar'] = get_file_path($item['avatar']);
            }
            //时间转换
            $item['time'] = date('H:i:s',$item['time']);
        }

        //总签到榜
        $total = Db::name('sns_sign_in')
            ->alias('sign')
            ->join('sns_user user','user.id = sign.user_id')
            ->limit(20)
            ->field('sign.id,sign.user_id,user.nickname,user.avatar_default,user.avatar,count(sign.id) as serial')
            ->group('sign.user_id')
            ->order('serial','desc')
            ->select();
        foreach ($total as &$item){
            //头像
            if($item['avatar'] == 0){
                $item['avatar'] = '/static/home/img/avatar/'.$item['avatar_default'].'.png';
            } else {
                $item['avatar'] = get_file_path($item['avatar']);
            }
        }

        $this->success('','',[$newest_,$fastest_today,$total]);
    }

}