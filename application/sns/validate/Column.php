<?php

namespace app\sns\validate;

use think\Validate;

/**
 * 栏目验证器
 */
class Column extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|栏目名称'   => 'require|unique:sns_column',
        'title|栏目标题'   => 'require',
    ];
}
