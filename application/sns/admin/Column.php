<?php

namespace app\sns\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\sns\model\Column as ColumnModel;
use app\user\model\Role as RoleModel;
use util\Tree;
use util\File;

/**
 * 栏目控制器
 * @package app\cms\admin
 */
class Column extends Admin
{
    /**
     * 栏目列表
     * @return mixed
     * @throws \think\Exception
     */
    public function index()
    {
        // 查询
        $map = $this->getMap();

        // 数据列表
        $data_list = ColumnModel::where($map)->column(true);

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['name' => '栏目名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
               ['id', 'ID'],
               ['name', '栏目名称'],
               ['title', '栏目标题'],
               ['dot', '热门', 'switch'],
               ['create_time', '创建时间', 'datetime'],
               ['user_group', '用户组', 'text.edit'],
               ['sort', '排序', 'text.edit'],
               ['status', '状态', 'switch'],
               ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable') // 批量添加顶部按钮
            ->addRightButton('edit') // 添加右侧按钮
            ->addRightButton('delete', ['data-tips' => '删除栏目前，请确保无子栏目和文档！']) // 添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 新增栏目
     * @param int $pid
     * @return mixed
     * @throws \think\Exception
     */
    public function add($pid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Column');
            if(true !== $result) $this->error($result);

            if ($column = ColumnModel::create($data)) {
                cache('sns_column_list', null);
                // 记录行为
                action_log('sns_column_add', 'sns_column', $column['id'], UID, $data['title']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '栏目标题', '<span class="text-danger">必填</span>'],
                ['text', 'name', '栏目名称', '<span class="text-danger">必填</span>，英文标识'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
                ['text', 'sort', '排序', '', 100],
            ])
            ->fetch();
    }

    /**
     * 编辑栏目
     * @param string $id
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function edit($id = '')
    {
        if ($id === 0) $this->error('参数错误');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Column');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            if (ColumnModel::update($data)) {
                // 记录行为
                action_log('sns_column_edit', 'sns_column', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = ColumnModel::get($id);

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '栏目标题', '<span class="text-danger">必填</span>'],
                ['text', 'name', '栏目名称', '<span class="text-danger">必填</span>，英文标识'],
                ['radio', 'status', '立即启用', '', ['否', '是']],
                ['text', 'sort', '排序', ''],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除栏目
     * @param null $ids 栏目id
     * @return mixed
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('参数错误');

        // 检查是否有子栏目
        if (ColumnModel::where('pid', $ids)->find()) {
            $this->error('请先删除或移动该栏目下的子栏目');
        }

        // 检查是否有文档
        if (Document::where('cid', $ids)->find()) {
            $this->error('请先删除或移动该栏目下的所有文档');
        }

        // 删除并记录日志
        $column_name = get_column_name($ids);
        return parent::delete(['column_delete', 'cms_column', 0, UID, $column_name]);
    }

    /**
     * 启用栏目
     * @param array $record 行为日志
     * @return mixed
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用栏目
     * @param array $record 行为日志
     * @return mixed
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

    /**
     * 设置栏目状态：删除、禁用、启用
     * @param string $type 类型：enable/disable
     * @param array $record
     * @return mixed
     */
    public function setStatus($type = '', $record = [])
    {
        $ids           = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $column_delete = is_array($ids) ? '' : $ids;
        $column_names  = ColumnModel::where('id', 'in', $ids)->column('name');
        return parent::setStatus($type, ['sns_column_'.$type, 'sns_column', $column_delete, UID, implode('、', $column_names)]);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id      = input('post.pk', '');
        $field   = input('post.name', '');
        $value   = input('post.value', '');
        $column  = ColumnModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $column . ')，新值：(' . $value . ')';
        return parent::quickEdit(['sns_column_edit', 'sns_column', $id, UID, $details]);
    }
}