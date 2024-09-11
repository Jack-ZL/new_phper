<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/28
 * Time: 14:35
 */

namespace app\index\controller;

use EasyWeChat\Factory;
use think\Db;
use app\sns\model\User as UserModel;

class Wechat extends Home
{
    static $app;

    protected $beforeActionList = [
        'appConfig' => ['only' => 'oauth,callback']
    ];

    /**
     * 公众号配置
     */
    protected function appConfig()
    {
        $config = [
            // 线上,由于认证的是订阅号，暂时用“sqy”的
            'app_id' => 'wxfe86822cffbe4fdd',
            'secret' => '51c5865c86da1fc120800e35c5bfda07',

            // 测试号
//            'app_id' => 'wx79772ed7e745f1f2',
//            'secret' => '8ed26cf777a65654ed05b7b76ec05b76',

            /**
             * 日志配置
             *
             * level: 日志级别, 可选为：
             *         debug/info/notice/warning/error/critical/alert/emergency
             * path：日志文件位置(绝对路径!!!)，要求可写权限
             */
            'log' => [
                'default' => config('app_debug')?'dev':'prod', // 默认使用的 channel，生产环境可以改为下面的 prod
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',
                        'path' => LOG_PATH.'wxlog/easywechat.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                    'prod' => [
                        'driver' => 'daily',
                        'path' => LOG_PATH.'wxlog/easywechat.log',
                        'level' => 'info',
                    ],
                ],
            ],

            /**
             * OAuth 配置
             *
             * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
             * callback：OAuth授权完成后的回调页地址
             */
            'oauth' => [
                'scopes'   => ['snsapi_base'],
                'callback' => url('wechat/callback','',false,true)
            ],
        ];

        self::$app = Factory::officialAccount($config);
    }

    /**
     * 微信授权
     */
    public function oauth()
    {
        $oauth = self::$app->oauth;
        $oauth->redirect()->send();
    }

    /**
     * 微信授权回调
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function callback()
    {
        $user = self::$app->oauth->user();
        $openid = $user->getId();
        $uid = Db::name('sns_oauth')
            ->where(['platform'=>'wechat','openid'=>$openid])
            ->value('uid');
        if ($uid){ // 已经存在 openid
            if ($uid == 0){ // 但未绑定账号
                $this->assign('openid',$openid);
                return $this->fetch();
            }
            // 已绑定账号
            $user_info = UserModel::get($uid);
            // 自动登录
            UserModel::autoLogin($user_info,true);
            // 获取跳转地址
            $oauth_callback = session('oauth_callback');
            $jump_url = $oauth_callback ? $oauth_callback : '/';
            if ($oauth_callback){
                session('oauth_callback',null);
            }
            $this->redirect($jump_url);
        }
        // 未存在openid
        if (Db::name('sns_oauth')->insert(['platform'=>'wechat','openid'=>$openid,'create_time'=>$this->request->time()])){
            $this->assign('openid',$openid);
            return $this->fetch();
        }
        $this->error('授权失败，请稍后再试');
    }

    /**
     * 绑定账号
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function bindAccount()
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
                    if (Db::name('sns_oauth')->where(['platform'=>'wechat','openid'=>$data['openid']])->update(['uid'=>$user['id'],'update_time'=>$this->request->time()])){
                        //自动登录
                        UserModel::autoLogin($user,true);
                        $oauth_callback = session('oauth_callback');
                        $jump_url = $oauth_callback ? $oauth_callback : '/';
                        if ($oauth_callback){
                            session('oauth_callback',null);
                        }
                        $this->success('绑定成功',$jump_url);
                    }
                    $this->error('绑定失败，请稍后再试');
                }
            }
            $this->error('账号或密码错误');
        }
    }

}