<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/11
 * Time: 16:51
 */

namespace app\sns\admin;


use app\admin\controller\Admin;
use app\sns\model\Post as PostModel;
use app\common\builder\ZBuilder;

class Post extends Admin
{
    public function index()
    {

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = PostModel::where($map)->order('id desc')->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('帖子管理') // 设置页面标题
            ->setTableName('sns_post') // 设置数据表名
            ->setSearch(['id' => 'ID', 'title' => '标题']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'UID'],
                ['column_id', 'CID'],
                ['title', '标题'],
                ['top', '顶置','switch'],
                ['top_sort', '顶置排序','text.edit'],
                ['essence', '加精','switch'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('delete') // 批量添加顶部按钮
            ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}