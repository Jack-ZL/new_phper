<?php
/**
 * 问社区 - 垂直问答社区系统
 * Author: Rafi Wong <rafiwong@qq.com>
 * Date: 2017/12/20
 * Time: 20:25
 */

namespace app\index\validate;

use think\Validate;

class Post extends Validate
{
    protected $rule = [
        'column|栏目' => 'require',
        'title|标题'=> 'require',
        'content|详情内容'=> 'require',
        'reward|悬赏'=> 'require',
        'imagecode|图形验证码'=> 'require',
    ];

    protected $message = [
        'column.require' => '请选择栏目',
        'reward.require' => '请选择悬赏',
    ];

}