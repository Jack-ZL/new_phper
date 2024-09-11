<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/9
 * Time: 13:14
 */

namespace app\sns\model;

use think\Model as ThinkModel;

class Post extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__SNS_POST__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}