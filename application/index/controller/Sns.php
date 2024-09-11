<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/26
 * Time: 11:04
 */

namespace app\index\controller;

use anerg\OAuth2\OAuth;
use think\Db;
use app\sns\model\User as UserModel;

class Sns extends Home
{
    private $config;

    /**
     * 第三方登录，执行跳转操作
     * @param $name
     * @return \think\response\Redirect
     */
    /**
     * 第三方登录，执行跳转操作
     * @param $name
     * @param bool $state
     * @return \think\response\Redirect
     */
    public function auth($name, $state = false)
    {
        //获取配置
        $this->config = config('sns.' . $name);

        //设置回跳地址
        $this->config['callback'] = $this->makeCallback($name,$state);

        //可以设置代理服务器，一般用于调试国外平台
//        $this->config['proxy'] = 'http://127.0.0.1:1080';

        /**
         * 对于微博，如果登录界面要适用于手机，则需要设定->setDisplay('mobile')
         *
         * 对于微信，如果是公众号登录，则需要设定->setDisplay('mobile')，否则是WEB网站扫码登录
         *
         * 其他登录渠道的这个设置没有任何影响，为了统一，可以都写上
         */
        return redirect(OAuth::$name($this->config)->setDisplay('mobile')->getRedirectUrl());

        /**
         * 如果需要微信代理登录，则需要：
         *
         * 1.将wx_proxy.php放置在微信公众号设定的回调域名某个地址，如 http://www.abc.com/proxy/wx_proxy.php
         * 2.config中加入配置参数proxy_url，地址为 http://www.abc.com/proxy/wx_proxy.php
         *
         * 然后获取跳转地址方法是getProxyURL，如下所示
         */
//        $this->config['proxy_url'] = 'http://www.abc.com/proxy/wx_proxy.php';
//        return redirect(OAuth::$name($this->config)->setDisplay('mobile')->getProxyURL());
    }

    public function callback($name, $state = false)
    {
        //获取配置
        $this->config = config('sns.' . $name);
        //设置回跳地址
        $this->config['callback'] = $this->makeCallback($name,$state);

        //获取格式化后的第三方用户信息
//        $snsInfo = OAuth::$name($this->config)->userinfo();
//
//        //获取第三方返回的原始用户信息
//        $snsInfoRaw = OAuth::$name($this->config)->userinfoRaw();
//
//        dump($snsInfoRaw);
        //获取第三方openid
        $openid = OAuth::$name($this->config)->openid();

        $oauth_info = Db::name('sns_oauth')
            ->where(['platform'=>$name,'openid'=>$openid])
            ->find();

        if ($state && $state === 'bind'){
            $data = [
                'platform'=>$name,
                'openid'=>$openid,
                'uid'=>$this->user['id'],
                'create_time'=>$this->request->time()
            ];
            if (Db::name('sns_oauth')->insert($data)){
                $this->redirect('user/set');
            }
        } else {
            if ($oauth_info){ // 已经绑定
                $user_info = UserModel::get($oauth_info['uid']);
                UserModel::autoLogin($user_info,true);
                $this->redirect('/');
            } else { // 未绑定
                $this->error('您的账号未绑定，请登录后绑定该平台');
            }
        }
    }

    /**
     * 生成回跳地址
     * @param $name
     * @param bool $state
     * @return string
     */
    private function makeCallback($name,$state = false)
    {
        $param = ['name'=>$name];
        if ($state){
            $param['state'] = $state;
        }
        //注意需要生成完整的带http的地址
        return url('sns/callback', $param, false, true);
    }
}