<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/12
 * Time: 19:49
 */

namespace app\index\controller;


use think\Db;

class Message extends Home
{
    /**
     * 获取未读消息数
     * @return \think\response\Json
     */
    public function nums()
    {
        $this->needLogin();

        $count = Db::name('sns_message')
            ->where(['to'=>$this->user['id'],'trash'=>0,'status'=>0])
            ->count();

        return json(['code'=>1,'count'=>$count]);
    }

    /**
     * 标记为已读
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function read()
    {
        $this->needLogin();

        Db::name('sns_message')
            ->where(['to'=>$this->user['id'],'trash'=>0,'status'=>0])
            ->update(['status'=>1]);

        $this->success();
    }

    /**
     * 删除帖子
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->needLogin();

        $all = $this->request->post('all');
        $id = $this->request->post('id');

        $delete = false;

        if ($all){
            $delete = Db::name('sns_message')
                ->where(['to'=>$this->user['id'],'trash'=>0])
                ->update(['trash'=>1]);
        } else if ($id){
            $delete = Db::name('sns_message')
                ->where(['id'=>$id])
                ->update(['trash'=>1]);
        } else {
            $this->error('是人不是？');
        }

        if ($delete){
            $this->success('删除成功');
        }

        $this->error('服务器错误，请稍后再试');
    }
}