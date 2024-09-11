<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/8
 * Time: 22:33
 */

namespace app\index\controller;

use app\sns\model\User as UserModel;
use think\Db;

class User extends Home
{
    protected $beforeActionList = [
        'hasLogin'  =>  ['only' =>  'login,reg']
    ];

    protected function hasLogin()
    {
        if ($this->user){
            $this->redirect('index');
        }
    }

    // 用户中心
    public function index()
    {
        $this->needLogin();

        // 获取我发表的帖子
        $post = Db::name('sns_post')
            ->where('user_id',$this->user['id'])
            ->where('status',1)
            ->order('id','desc')
            ->select();

        $this->assign('my_post',$post);


        // 获取我收藏的帖子
        $collection_post = Db::name('sns_collection')
            ->alias('c')
            ->where('c.user_id',$this->user['id'])
            ->join('sns_post p','p.user_id = c.post_id')
            ->field('c.id,c.time,c.post_id,p.title')
            ->select();

        $this->assign('collection_post',$collection_post);

        return $this->fetch();
    }

    /**
     * 个人信息设置
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function set()
    {
        $this->needLogin();

        if ($this->request->isAjax())
        {
            $type = $this->request->post('type');

            $update_data = [];
            $success_msg = '';
            switch ($type){
                case 'base':
                    $city = $this->request->post('city');
                    $sign = $this->request->post('sign');
                    $gender = $this->request->post('gender');
                    $nickname = $this->request->post('nickname');
                    if ($nickname != $this->user['nickname'] && UserModel::get(['nickname'=>$nickname])){
                        $this->error('昵称已被占用，请换一个');
                    }
                    $update_data = ['city'=>$city,'sign'=>$sign,'gender'=>$gender,'nickname'=>$nickname];
                    $success_msg = '信息修改成功';
                    break;
                case 'avatar':
                    $avatar = $this->request->post('avatar');
                    if (!$avatar){
                        $this->error('未知错误');
                    }
                    $update_data['avatar'] = $avatar;
                    $success_msg = '头像修改成功';
                    break;
                case 'pass':
                    $nowpass = $this->request->post('nowpass');
                    $pass = $this->request->post('pass');
                    $repass = $this->request->post('repass');
                    if (strlen($pass) < 6 || strlen($pass) >16){
                        $this->error('密码长度6-16位');
                    }
                    if ($pass != $repass){
                        $this->error('两次密码输入不一致');
                    }
                    if (!password_verify($nowpass,$this->user['password'])){
                        $this->error('当前密码输入错误');
                    }
                    $update_data['password'] = password_hash($nowpass,1);
                    $success_msg = '密码修改成功';
                    break;
                default:
                    $this->error('是人不是？');
                    break;
            }
            if (UserModel::update($update_data,['id'=>$this->user['id']])){
                $this->success($success_msg);
            }
            $this->error('服务器错误，请稍后再试');
        }

        return $this->fetch();
    }

    /**
     * 我的消息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function message()
    {
        $this->needLogin();

        $message = Db::name('sns_message')
            ->alias('m')
            ->join('sns_user u','u.id = m.from','LEFT')
            ->join('sns_post p','p.id = m.post_id','LEFT')
            ->where(['m.to'=>$this->user['id'],'m.trash'=>0])
            ->field('m.id,m.to,m.from,m.post_id,m.reply_id,m.content,m.type,m.create_time,u.nickname,p.title')
            ->order('id','desc')
            ->select();
        if (count($message)>0){
            $this->assign('msg',$message);
        }
        return $this->fetch();
    }

    /**
     * 用户主页
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function home($id)
    {
        // 用户信息
        $user = UserModel::get($id);
        $this->assign('u',$user);

        // 获取最新30个帖子
        $post = Db::name('sns_post')
            ->where('user_id',$id)
            ->order('id','desc')
            ->limit(30)
            ->select();
        if ($post){
            $this->assign('post',$post);
        }

        // 获取最新30条回复
        $reply = Db::name('sns_reply')
            ->alias('r')
            ->join('sns_post p','r.post_id = p.id','LEFT')
            ->limit(30)
            ->where('r.user_id',$id)
            ->field('r.id,r.post_id,p.title,r.content,r.time')
            ->select();
        if ($reply){
            $this->assign('reply',$reply);
        }
        return $this->fetch();
    }

    /**
     * 登录
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function login()
    {
        if ($this->request->isAjax()){
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'User.login');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);
            // 验证码
            if (!captcha_check($data['imagecode'],'',config('captcha'))){
                $this->error('图形验证码错误');
            }
            $account_type = account_type($data['account']);
            if (!$account_type){
                $this->error('请输入正确的手机号或邮箱');
            }
            if ($user = UserModel::get([$account_type => $data['account']])){
                if (password_verify($data['pass'],$user['password'])){
                    UserModel::autoLogin($user,true);
                    $oauth_callback = session('oauth_callback');
                    $jump_url = $oauth_callback ? $oauth_callback : '/';
                    if ($oauth_callback){
                        session('oauth_callback',null);
                    }
                    $this->success('登录成功',$jump_url);
                }
            }
            $this->error('账号或密码错误');
        }
        $oauth_callback = $this->request->get('oauth_callback');
        if ($oauth_callback){
            session('oauth_callback',$oauth_callback);
        }
        if (is_wechat()){ // 如果是微信访问
            $this->redirect('wechat/oauth');
        }
        return $this->fetch();
    }



    /**
     * 注册
     * @return mixed
     */
    public function reg()
    {
        if ($this->request->isAjax()){
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'User.reg');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);
            // 验证码
            $auth_code = cache('auth_code_'.$data['account']);
            if (!$auth_code){
                $this->error('您未获取验证码或已过期，请重新获取');
            }
            if ($data['vercode'] != $auth_code['code']){
                if ($auth_code['attempts'] === 2){ // 尝试3次
                    cache('auth_code_'.$data['account'],null);
                    $this->error('验证码错误次数超限，请重新获取');
                }
                $auth_code['attempts'] = $auth_code['attempts']+1;
                cache('auth_code_'.$data['account'],$auth_code,time()-$auth_code['time']);
                $this->error('短信/邮箱验证码错误');
            }
//            验证码正确，清楚缓存
            cache('auth_code_'.$data['account'],null);

            $account_type = account_type($data['account']);

            $user_data = [
                $account_type => $data['account'],
                $account_type.'_bind' => 1,
                'nickname'  =>  $data['nickname'],
                'password'  =>  password_hash($data['pass'],1),
                'avatar_default'   =>  rand(2,33),
                'register_ip'   =>  $this->request->ip(1),
                'status'    =>  1,
                'create_time'   =>  $this->request->time(),
                'update_time'   =>  $this->request->time()
            ];
            $uid = 0;
            // 启动事务
            Db::startTrans();
            try{
                // 写入帖子记录
                $uid = Db::name('sns_user')->insertGetId($user_data);
                // 写入积分日志
                Db::name('sns_score_log')->insert([
                    'user_id'=>$uid,
                    'score'=> 20,
                    'remark'=>'注册奖励',
                    'time'=>$this->request->time()
                ]);
                // 写入总积分
                Db::name('sns_user')
                    ->where('id',$uid)
                    ->setInc('score',20);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                $this->error('服务器错误，请稍后再试');
                // 回滚事务
                Db::rollback();
            }
            send_message($uid,0,0,'恭喜您加入 '.config('web_site_title').' 大家庭,奉上 20 '.config('sns_score_name').'作为奖励');
            send_message($uid,0,0,'对了，差点忘了说：PHP 是世界上最好的语言');
            $this->success('注册成功,奖励 20 '.config('sns_score_name'),'user/login');
        }
        return $this->fetch();
    }

    /**
     * 重置密码
     * @return mixed
     */
    public function forget()
    {
        if ($this->request->isAjax()){
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'User.forget');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);
            // 验证码
            $auth_code = cache('auth_code_'.$data['account']);
            if (!$auth_code){
                $this->error('您未获取验证码或已过期，请重新获取');
            }
            if ($data['vercode'] != $auth_code['code']){
                if ($auth_code['attempts'] === 2){ // 尝试3次
                    cache('auth_code_'.$data['account'],null);
                    $this->error('验证码错误次数超限，请重新获取');
                }
                $auth_code['attempts'] = $auth_code['attempts']+1;
                cache('auth_code_'.$data['account'],$auth_code,time()-$auth_code['time']);
                $this->error('短信/邮箱验证码错误');
            }
//            验证码正确，清楚缓存
            cache('auth_code_'.$data['account'],null);

            $account_type = account_type($data['account']);

            $user_data = [
                'password'  =>  password_hash($data['pass'],1),
                'update_time'   =>  $this->request->time()
            ];

            $uid = Db::name('sns_user')->where([$account_type=>$data['account']])->value('id');

            // 启动事务
            Db::startTrans();
            try{
                // 写入新密码
                Db::name('sns_user')
                    ->where('id',$uid)->update($user_data);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                $this->error('服务器错误，请稍后再试');
                // 回滚事务
                Db::rollback();
            }
            send_message($uid,0,0,'您的密码已重置成功,要牢记噢');
            $this->success('重置成功','user/login');
        }
        return $this->fetch();
    }

    /**
     * 登出
     */
    public function logout()
    {
        session('sns_user_auth',null);
        session('sns_user_auth_login',null);
        cookie('sns_u',null);
        cookie('sns_token',null);
        $this->redirect('/');
    }

    /**
     * 赠送积分
     */
    public function give()
    {
        $this->needLogin();
        $to = $this->request->param('to');
        $val = $this->request->param('val');

        if ($to == $this->user['id']){
            $this->error('额~自己送自己，好玩吗？');
        }
        if ($val > $this->user['score']){
            $this->error('您的'.config('sns_score_name').'不足');
        }

        $to_user_info = Db::name('sns_user')->where('id',$to)
            ->field('nickname,score')->find();

        // 启动事务
        Db::startTrans();
        try{
            // 计入被赠送者账户
            Db::name('sns_user')
                ->where('id',$to)->setInc('score',$val);
            // 被赠送者积分日志
            Db::name('sns_score_log')
                ->insert(['user_id'=>$to,'score'=>$val,'remark'=>$this->user['nickname'].'赠送','time'=>$this->request->time()]);
            // 从赠送者账户扣除
            Db::name('sns_user')
                ->where('id',$this->user['id'])->setDec('score',$val);
            // 赠送者积分日志
            Db::name('sns_score_log')
                ->insert(['user_id'=>$to,'score'=>-(int)$val,'remark'=>'赠送给'.$to_user_info['nickname'],'time'=>$this->request->time()]);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $this->error('服务器错误，请稍后再试');
            // 回滚事务
            Db::rollback();
        }
        send_message($to,$this->user['id'],0,'赠送了'.$val.config('sns_score_name'),'give');
        $this->success('赠送成功，已经通知对方','',(int)$to_user_info['score']+(int)$val);
    }

    /**
     * 草稿箱
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function draft()
    {
        $this->needLogin();

        // 获取草稿箱帖子
        $draft = Db::name('sns_post')
            ->where('user_id',$this->user['id'])
            ->where('status',0)
            ->order('id','desc')
            ->select();

        $this->assign('draft',$draft);
        return $this->fetch();
    }

    /**
     * 赞赏码提交
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function appreciate()
    {
        $this->needLogin();

        if ($this->request->isAjax()){
            $appreciate_code = $this->request->param('id');
            $data = [
                'appreciate_code'=>$appreciate_code,
                'appreciate_code_status'=>2, //状态未待审核
                'appreciate_apply_time'=>$this->request->time()
            ];
            if (Db::name('sns_user')->where('id',$this->user['id'])->update($data)){
                $this->success('申请成功，我们将尽快审核');
            }
            $this->error('申请失败，请稍后再试');
        }

        if($this->user['appreciate_code'] == 0){
            // 分享文章
            $share_log = Db::name('sns_post')
                ->where('user_id',$this->user['id'])
                ->where('column_id',2)
                ->limit(3)
                ->field('id')
                ->select();
            //分享过3篇才能申请
        if (count($share_log) === 3){
            $this->assign('can',1);
        }
        } else {
            $this->assign('status',$this->user['appreciate_code_status']);
        }
        return $this->fetch();
    }

}