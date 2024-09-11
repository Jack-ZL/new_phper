<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/8
 * Time: 23:42
 */

namespace app\index\controller;

use app\sns\model\Post as PostModel;
use app\sns\model\User as UserModel;
use think\Db;

class Post extends Home
{
    /**
     * 发表新帖
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        $this->needLogin();

        if ($this->request->isAjax()){
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data,'Post');
            if (true !== $result){
                $this->error($result);
            }
            $reward_options = reward_options();
            if (!isset($reward_options[$data['reward']])){
                $this->error('悬赏失败');
            }
            if (!isset($data['draft'])){ // 不是草稿
                $user_score = UserModel::where('id',$this->user['id'])->value('score');
                if ($data['reward'] > $user_score){
                    $this->error(config('sns_score_name').'不足，您可以先存到草稿箱');
                }
            }

            // 验证码
            if (!captcha_check($data['imagecode'],'',config('captcha'))){
                $this->error('图形验证码错误');
            }
            // 帖子数据
            $post_data = [
                'column_id' => $data['column'],
                'user_id' => $this->user['id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'reward' => $data['reward'],
                'status' => 1,
                'create_time'   =>  $this->request->time()
            ];

            if (isset($data['draft'])){ //存草稿箱
                $post_data['status'] = 0;
            }

            // 启动事务
            Db::startTrans();
            try{
                // 写入帖子记录
                Db::name('sns_post')->insert($post_data);
                if (!isset($data['draft'])){ //不是草稿，扣积分
                    // 写入积分日志
                    Db::name('sns_score_log')->insert([
                        'user_id'=>$this->user['id'],
                        'score'=> -(int)$data['reward'],
                        'remark'=>'发帖悬赏扣除',
                        'time'=>$this->request->time()
                    ]);
                    // 写入总积分
                    Db::name('sns_user')
                        ->where('id',$this->user['id'])
                        ->setDec('score',(int)$data['reward']);
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                $this->error('服务器错误，请稍后再试');
                // 回滚事务
                Db::rollback();
            }

            if (isset($data['draft'])){ //存草稿箱
                $msg = '保存草稿成功';
                $jump = 'user/draft';
            } else {
                $msg = '发表成功';
                $jump = '/';
            }

            $this->success($msg,$jump);

        }

        // 管理员可以获取所有列表
        if ($this->user['id'] != 333){
            $this->assign('column',get_column(true));
        } else {
            $this->assign('column',get_column(true,true));
        }

        $this->assign('reward',reward_options());
        return $this->fetch();
    }

    /**
     * 编辑帖子
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $this->needLogin();

        if ($this->request->isAjax()){
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data,'Post');
            if (true !== $result){
                $this->error($result);
            }

            // 验证码
            if (!captcha_check($data['imagecode'],'',config('captcha'))){
                $this->error('图形验证码错误');
            }
            // 帖子数据
            $post_data = [
                'column_id' => $data['column'],
                'title' => $data['title'],
                'content' => $data['content'],
                'update_time'=>$this->request->time()
            ];

            $original_status = Db::name('sns_post')->where('id',$data['id'])->value('status');

            if ($original_status === 0 && !isset($data['draft'])){
                // 编辑草稿并发布
                $user_score = UserModel::where('id',$this->user['id'])->value('score');
                // 检测积分是否足够
                if ($data['reward'] > $user_score){
                    $this->error(config('sns_score_name').'不足，您可以先存到草稿箱');
                }
                // 发布状态
                $post_data['status'] = 1;
            }

            // 启动事务
            Db::startTrans();
            try{
                // 写入帖子记录
                Db::name('sns_post')->where('id',$data['id'])->update($post_data);
                if ($original_status === 0 && !isset($data['draft'])){
                    // 编辑草稿并发布
                    // 写入积分日志
                    Db::name('sns_score_log')->insert([
                        'user_id'=>$this->user['id'],
                        'score'=> -(int)$data['reward'],
                        'remark'=>'发帖悬赏扣除',
                        'time'=>$this->request->time()
                    ]);
                    // 写入总积分
                    Db::name('sns_user')
                        ->where('id',$this->user['id'])
                        ->setDec('score',(int)$data['reward']);
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                $this->error('服务器错误，请稍后再试');
                // 回滚事务
                Db::rollback();
            }

            if (isset($data['draft'])){
                $this->success('保存成功',url('user/draft'));
            }

            $this->success('编辑成功',url('post/detail',['id'=>$data['id']]));
        }

        $post = PostModel::get($id);
        $this->assign('post',$post);
        // 管理员可以获取所有列表
        if ($this->user['id'] != 333){
            $this->assign('column',get_column(true));
        } else {
            $this->assign('column',get_column(true,true));
        }
        $this->assign('reward',reward_options());
        return $this->fetch();
    }

    /**
     * 详情
     * @param null $id
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id = null)
    {
        if ($id === null){
            abort(404);
        }
        $post = PostModel::get($id);
        if (!$post){
            abort(404);
        }
        $this->assign('post',$post);
        // 栏目名称
        $column_name = Db::name('sns_column')->where('id',$post['column_id'])->value('title');
        $this->assign('column_name',$column_name);
        // 作者信息
        $author = Db::name('sns_user')->where('id',$post['user_id'])
            ->field('id,avatar,avatar_default,nickname,authentication,appreciate_code,appreciate_code_status')
            ->find();
        $this->assign('author',$author);
        // 栏目列表
        $this->assign('column',get_column());
        // 当前浏览用户id
        $view_user_id = $this->user?$this->user['id']:0;
        // 获取回复
        $reply = Db::name('sns_reply')
            ->alias('reply')
            ->join('sns_user u','u.id = reply.user_id','LEFT')
            ->join('sns_zan zan', 'zan.reply_id = reply.id and zan.user_id = '.$view_user_id,'LEFT')
            ->where('reply.post_id',$id)
            ->order('reply.id','desc')
            ->field('reply.id,reply.user_id,reply.content,reply.like,reply.adopt,reply.time,u.avatar,u.avatar_default,u.nickname,u.authentication,zan.id as zan')
            ->paginate(10);
        if ($reply->items()){
            $this->assign('reply',$reply);
        }
        // 栏目
        $this->assign('navActive','');

        // 获取本周热议
        $hot = $this->getHotPost();
        if (count($hot)){
            $this->assign('hot',$hot);
        }

        // 写入浏览记录
        PostModel::where('id',$id)->setInc('view');

        return $this->fetch();
    }

    /**
     * 回复
     */
    public function reply()
    {
        $this->needLogin();

        $post_id = $this->request->post('post_id');
        $content = $this->request->post('content');
        if (!$post_id || !$content){
            $this->error('参数错误');
        }

        $data = [
            'post_id'   =>  $post_id,
            'user_id'   =>  $this->user['id'],
            'content'   =>  $content,
            'time'      =>  $this->request->time()
        ];
        $reply_id = 0;
        // 启动事务
        Db::startTrans();
        try{
            // 写入回答记录
            $reply_id = Db::name('sns_reply')->insertGetId($data);
            // 总回答数+1
            Db::name('sns_post')
                ->where('id',$post_id)
                ->setInc('reply');
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $this->error('服务器错误，请稍后再试');
            // 回滚事务
            Db::rollback();
        }

        // 是否 @ 其他人
        preg_match_all("/@(.*?) /",$content,$res);

        $to_users = $res[1];

        if (count($to_users)>0){
            foreach ($to_users as $item){
                if ($to = Db::name('sns_user')->where('nickname',$item)->value('id')){
                    // 通知@的人
                    send_message($to,$this->user['id'],$post_id,'','reply',$reply_id);
                }
            }
        } else {
            // 否则通知作者
            $author_id = Db::name('sns_post')
                ->where('id',$post_id)->value('user_id');
            if ($author_id != $this->user['id']){ // 本人回复不通知
                send_message($author_id,$this->user['id'],$post_id,'','reply',$reply_id);
            }
        }
        $this->success('回复成功');
    }

    /**
     * 点击消息跳转到回复
     * @param $id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function msgToReply($id)
    {
//        dump($id);
        $message = Db::name('sns_message')
            ->where('id',$id)
            ->find();
        if ($message['status'] == 0){
            Db::name('sns_message')->where('id',$id)->update(['status'=>1]);
        }
        $jump = url('post/detail',['id'=>$message['post_id']]).'#item-'.$message['reply_id'];
        $this->redirect($jump);
    }
}