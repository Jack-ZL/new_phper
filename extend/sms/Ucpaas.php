<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/8/25
 * Time: 18:41
 */

namespace sms;

class Ucpaas
{
    protected $ucpass;

    public function __construct()
    {
        //初始化必填
        //填写在开发者控制台首页上的Account Sid
        $options['accountsid']='8333bae11c1fe894bab4349b50761a73';
        //填写在开发者控制台首页上的Auth Token
        $options['token']='23fef273fb537df3be27c32a597bac20';

        //初始化 $options必填
        $this->ucpass = new UcpaasLib($options);
    }

    /**
     * 发送短信
     * @param $param
     * @param $mobile
     * @return mixed
     */
    public function send($param,$mobile)
    {
        // 应用的ID，可在开发者控制台内的短信产品下查看
        $appid = "ca8b75f41a3c4369ad607aabf7f0a27e";
        // 可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
        $templateid = "408261";

        $result = $this->ucpass->SendSms($appid,$templateid,$param,$mobile,'');

        $result = json_decode($result,true);

        if ($result['code'] === '000000'):
            return true;
        else:
            return false;
        endif;
    }

}