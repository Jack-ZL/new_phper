<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/12
 * Time: 18:08
 */

namespace app\index\controller;


class Instructions extends Home
{
    public function terms()
    {
        return $this->fetch();
    }

}