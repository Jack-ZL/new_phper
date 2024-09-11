<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/12
 * Time: 20:57
 */

namespace app\index\controller;


use think\Db;

class Collection extends Home
{
    /**
     * 查看帖子是否收藏
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function find()
    {
        $this->needLogin();
        $post_id = $this->request->post('cid');
        $map = [
            'user_id'   =>  $this->user['id'],
            'post_id'   =>  $post_id
        ];
        $res_data = [];
        if (Db::name('sns_collection')->where($map)->find()){
            $res_data['collection'] = 1;
        }
        $this->success('','',$res_data);
    }

    /**
     * 添加收藏
     */
    public function add()
    {
        $this->needLogin();
        $post_id = $this->request->post('cid');
        $data = [
            'user_id'   =>  $this->user['id'],
            'post_id'   =>  $post_id,
            'time'  =>  $this->request->time()
        ];
        if (Db::name('sns_collection')->insert($data)){
            $this->success();
        }
        $this->error('服务器错误，请稍后再试');
    }

    /**
     * 取消收藏
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->needLogin();
        $post_id = $this->request->post('cid');
        $map = [
            'user_id'   =>  $this->user['id'],
            'post_id'   =>  $post_id
        ];
        if (Db::name('sns_collection')->where($map)->delete()){
            $this->success('取消成功');
        }
        $this->error('服务器错误，请稍后再试');
    }

}