<?php

namespace app\sns\model;

use think\Model as ThinkModel;

/**
 * 栏目模型
 * @package app\sns\model
 */
class Column extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__SNS_COLUMN__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}