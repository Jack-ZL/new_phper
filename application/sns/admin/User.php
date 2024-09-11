<?php
/**
 * Created by PhpStorm.
 * User: Rafi
 * Date: 2018/12/11
 * Time: 16:51
 */

namespace app\sns\admin;


use app\admin\controller\Admin;
use app\sns\model\User as UserModel;
use app\common\builder\ZBuilder;

class User extends Admin
{
    public function index()
    {

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = UserModel::where($map)->order('id desc')->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('用户管理') // 设置页面标题
            ->setTableName('sns_user') // 设置数据表名
            ->setSearch(['id' => 'ID', 'mobile' => '手机号', 'email' => '邮箱']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['nickname', '昵称'],
                ['email', '邮箱'],
                ['mobile', '手机号'],
                ['authentication', '认证信息', 'text.edit'],
                ['score', '积分', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
//            ->addTopButtons('add') // 批量添加顶部按钮
            ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    /**
     * 赞赏码展示申请
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function appreciate()
    {

        // 获取查询条件
        $map = $this->getMap();
        $map['appreciate_code_status'] = 2;

        // 数据列表
        $data_list = UserModel::where($map)->order('appreciate_apply_time desc')->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('赞赏码展示申请') // 设置页面标题
            ->setTableName('sns_user') // 设置数据表名
            ->setSearch(['id' => 'ID', 'mobile' => '手机号', 'email' => '邮箱']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['nickname', '昵称'],
                ['email', '邮箱'],
                ['mobile', '手机号'],
                ['appreciate_code_status', '状态，1（通过）', 'text.edit'],
                ['appreciate_apply_time', '申请时间', 'datetime'],
            ])
//            ->addTopButtons('add') // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}