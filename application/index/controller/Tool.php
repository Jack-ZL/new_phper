<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/19
 * Time: 17:40
 */

namespace app\index\controller;


class Tool extends Home
{
    public function index()
    {
        return $this->fetch();
    }

    /**
     * md5 加密
     * @return mixed
     */
    public function md5()
    {
        if ($this->request->isAjax())
        {
            $text = input('text');
            $this->success('','',md5($text));
        }
        return $this->fetch();
    }

    /**
     * 时间戳转换
     * @return mixed
     */
    public function timestamp()
    {
        if ($this->request->isAjax())
        {
            $timestamp = $this->request->param('timestamp');
            $str = $this->request->param('str');
            if ($timestamp){
                $res = date('Y-m-d H:i:s',$timestamp);
            } else {
                $res = strtotime($str);
            }
            $this->success('','',$res);
        }
        return $this->fetch();
    }
}