<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/9
 * Time: 11:21
 */

namespace app\sns\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

class Index extends Admin
{
    public function index()
    {
        return ZBuilder::make('table')
            ->fetch();
    }

}