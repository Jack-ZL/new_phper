<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/9
 * Time: 13:41
 */

namespace app\index\controller;

use app\sns\model\User as UserModel;

use sms\Ucpaas;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Auth extends Home
{
    /**
     * 获取验证码
     * @throws \think\exception\DbException
     */
    public function code()
    {
        $account = $this->request->post('account');
        $image_code = $this->request->post('imagecode');
        if (!captcha_check($image_code,'',config('captcha'))){
            $this->error('图形验证码错误');
        }
        $account_type = account_type($account);
        if (!$account_type){
            $this->error('请输入正确的手机号或邮箱');
        }

        $forget = $this->request->post('forget');
        if ($forget){// 重置密码
            if (null === UserModel::get([$account_type => $account])){
                $this->error('账号不存在');
            }
        } else {// 注册
            if (UserModel::get([$account_type => $account])){
                $this->error('账号已存在');
            }
        }

//        生成验证码
        $code = rand(1111,9999);
//        缓存验证码，3分钟有效, attempts(尝试次数)
        $cache_value = ['code'=>$code,'attempts'=>0,'time'=>time()];
        cache('auth_code_'.$account,$cache_value,180);
        if ($account_type === 'mobile'){
            $sendRes = $this->sendSms($account,$code);
        } else {
//            $this->error('抱歉，邮箱注册服务维护中，请先使用手机验证','',$code);
            $sendRes = $this->sendEmail($account,$code);
        }
        if ($sendRes === false){
            $this->error($sendRes);
        }
//        debug 模式 返回验证码
//        if (!config('app_debug')){
//            $code = '验证码发送成功';
//        }
        $this->success();
    }

    private function sendSms($mobile,$code){
        $Ucpaas = new Ucpaas();
        return $Ucpaas->send($code,$mobile);
    }

    private function sendEmail($account,$code){
        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            //Server settings
//            $mail->SMTPDebug = 2;                                 // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = 'smtp.exmail.qq.com';  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = 'service@newphper.com';                 // SMTP username
            $mail->Password = 'Conv4sfhpAi7Scjk';                           // SMTP password
            $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 465;                                    // TCP port to connect to
//            $mail->Port = 25;                                    // TCP port to connect to

            //Recipients
            $mail->setFrom('service@newphper.com', 'NewPHPer');
            $mail->addAddress($account);     // Add a recipient
//            $mail->addAddress('ellen@example.com');               // Name is optional
//            $mail->addReplyTo('info@example.com', 'Information');
//            $mail->addCC('cc@example.com');
//            $mail->addBCC('bcc@example.com');

            //Attachments
//            $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
//            $mail->Subject = '您的验证码 —— NewPHPer';
            $mail->Subject = "=?utf-8?B?" . base64_encode("您的验证码 —— NewPHPer") . "?=";
            $mail->Body    = '您的验证码是 <b>'.$code.'</b>';
            $mail->AltBody = '您的验证码是 <b>'.$code.'</b>';

            $mail->send();
        } catch (Exception $e) {
//            echo $mail->ErrorInfo;
            return false;
        }
        return true;
    }
}