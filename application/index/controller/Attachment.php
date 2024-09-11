<?php

namespace app\index\controller;

use app\admin\model\Attachment as AttachmentModel;
use think\Image;
use think\Hook;

/**
 * 附件控制器
 * @package app\index\controller
 */
class Attachment extends Home
{

    /**
     * 上传附件
     * @param string $dir
     * @param string $module
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function upload($dir = 'images', $module = 'index')
    {
        // 临时取消执行时间限制
        set_time_limit(0);
        if ($dir == '') $this->error('没有指定上传目录');
        return $this->saveFile($dir, $module);
    }

    /**
     * 保存附件
     * @param string $dir
     * @param string $module
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    private function saveFile($dir = '', $module = '')
    {
        // 附件大小限制
        $size_limit = $dir == 'images' ? config('upload_image_size') : config('upload_file_size');
        $size_limit = $size_limit * 1024;
        // 附件类型限制
        $ext_limit = $dir == 'images' ? config('upload_image_ext') : config('upload_file_ext');
        $ext_limit = $ext_limit != '' ? parse_attr($ext_limit) : '';
        // 缩略图参数
        $thumb = $this->request->post('thumb', '');
        // 水印参数
        $watermark = $this->request->post('watermark', '');

        // 获取附件数据
        $callback = '';

        $file_input_name = 'file';

        $file = $this->request->file($file_input_name);

        // 判断附件是否已存在
        if ($file_exists = AttachmentModel::get(['md5' => $file->hash('md5')])) {
            if ($file_exists['driver'] == 'local') {
                $file_path = PUBLIC_PATH. $file_exists['path'];
            } else {
                $file_path = $file_exists['path'];
            }
            return json([
                'code'   => 1,
                'info'   => '上传成功',
                'class'  => 'success',
                'id'     => $file_exists['id'],
                'path'   => $file_path
            ]);
        }

        // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {
            return json([
                'code'   => 0,
                'class'  => 'danger',
                'info'   => '附件过大'
            ]);
        }

        // 判断附件格式是否符合
        $file_name = $file->getInfo('name');
        $file_ext  = strtolower(substr($file_name, strrpos($file_name, '.')+1));
        $error_msg = '';
        if ($ext_limit == '') {
            $error_msg = '获取文件信息失败！';
        }
        if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
            $error_msg = '禁止上传非法文件！';
        }
        if (preg_grep("/php/i", $ext_limit)) {
            $error_msg = '禁止上传非法文件！';
        }
        if (!preg_grep("/$file_ext/i", $ext_limit)) {
            $error_msg = '附件类型不正确！';
        }

        if ($error_msg != '') {
            return json([
                'code'   => 0,
                'class'  => 'danger',
                'info'   => $error_msg
            ]);
        }

        // 附件上传钩子，用于第三方文件上传扩展
        if (config('upload_driver') != 'local') {
            $hook_result = Hook::listen('upload_attachment', $file, ['module' => $module], true);
            if (false !== $hook_result) {
                return $hook_result;
            }
        }

        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->move(config('upload_path') . DS . $dir);
        if($info){
            // 缩略图路径
            $thumb_path_name = '';
            // 图片宽度
            $img_width = '';
            // 图片高度
            $img_height = '';
            if ($dir == 'images') {
                $img = Image::open($info);
                $img_width  = $img->width();
                $img_height = $img->height();
                // 水印功能
                if ($watermark == '') {
                    if (config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
                        $this->create_water($info->getRealPath(), config('upload_thumb_water_pic'));
                    }
                } else {
                    if (strtolower($watermark) != 'close') {
                        list($watermark_img, $watermark_pos, $watermark_alpha) = explode('|', $watermark);
                        $this->create_water($info->getRealPath(), $watermark_img, $watermark_pos, $watermark_alpha);
                    }
                }

                // 生成缩略图
                if ($thumb == '') {
                    if (config('upload_image_thumb') != '') {
                        $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename());
                    }
                } else {
                    if (strtolower($thumb) != 'close') {
                        list($thumb_size, $thumb_type) = explode('|', $thumb);
                        $thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename(), $thumb_size, $thumb_type);
                    }
                }
            }

            // 获取附件信息
            $file_info = [
                'uid'    => $this->user['id'],
                'name'   => $file->getInfo('name'),
                'mime'   => $file->getInfo('type'),
                'path'   => 'uploads/' . $dir . '/' . str_replace('\\', '/', $info->getSaveName()),
                'ext'    => $info->getExtension(),
                'size'   => $info->getSize(),
                'md5'    => $info->hash('md5'),
                'sha1'   => $info->hash('sha1'),
                'thumb'  => $thumb_path_name,
                'module' => $module,
                'width'  => $img_width,
                'height' => $img_height,
            ];

            // 写入数据库
            if ($file_add = AttachmentModel::create($file_info)) {
                $file_path = PUBLIC_PATH. $file_info['path'];
                return json([
                    'code'   => 1,
                    'info'   => '上传成功',
                    'class'  => 'success',
                    'id'     => $file_add['id'],
                    'path'   => $file_path
                ]);
            } else {
                return json(['code' => 0, 'class' => 'danger', 'info' => '上传失败']);
            }
        }else{
            return json(['code' => 0, 'class' => 'danger', 'info' => $file->getError()]);
        }
    }

    /**
     * 创建缩略图
     * @param string $file 目标文件，可以是文件对象或文件路径
     * @param string $dir 保存目录，即目标文件所在的目录名
     * @param string $save_name 缩略图名
     * @param string $thumb_size 尺寸
     * @param string $thumb_type 裁剪类型
     * @return string 缩略图路径
     */
    private function create_thumb($file = '', $dir = '', $save_name = '', $thumb_size = '', $thumb_type = '')
    {
        // 获取要生成的缩略图最大宽度和高度
        $thumb_size = $thumb_size == '' ? config('upload_image_thumb') : $thumb_size;
        list($thumb_max_width, $thumb_max_height) = explode(',', $thumb_size);
        // 读取图片
        $image = Image::open($file);
        // 生成缩略图
        $thumb_type = $thumb_type == '' ? config('upload_image_thumb_type') : $thumb_type;
        $image->thumb($thumb_max_width, $thumb_max_height, $thumb_type);
        // 保存缩略图
        $thumb_path = config('upload_path') . DS . 'images/' . $dir . '/thumb/';
        if (!is_dir($thumb_path)) {
            mkdir($thumb_path, 0766, true);
        }
        $thumb_path_name = $thumb_path. $save_name;
        $image->save($thumb_path_name);
        $thumb_path_name = 'uploads/images/' . $dir . '/thumb/' . $save_name;
        return $thumb_path_name;
    }

    /**
     * 添加水印
     * @param string $file 要添加水印的文件路径
     * @param string $watermark_img 水印图片id
     * @param string $watermark_pos 水印位置
     * @param string $watermark_alpha 水印透明度
     */
    private function create_water($file = '', $watermark_img = '', $watermark_pos = '', $watermark_alpha = '')
    {
        $path = model('admin/attachment')->getFilePath($watermark_img, 1);
        $thumb_water_pic = realpath(ROOT_PATH . 'public/' . $path);
        if (is_file($thumb_water_pic)) {
            // 读取图片
            $image = Image::open($file);
            // 添加水印
            $watermark_pos   = $watermark_pos   == '' ? config('upload_thumb_water_position') : $watermark_pos;
            $watermark_alpha = $watermark_alpha == '' ? config('upload_thumb_water_alpha') : $watermark_alpha;
            $image->water($thumb_water_pic, $watermark_pos, $watermark_alpha);
            // 保存水印图片，覆盖原图
            $image->save($file);
        }
    }

    /**
     * 遍历获取目录下的指定类型的附件
     * @param string $path 路径
     * @param string $allowFiles 允许查看的类型
     * @param array $files 文件列表
     * @return array|null
     */
    public function getfiles($path = '', $allowFiles = '', &$files = array())
    {
        if (!is_dir($path)) return null;
        if(substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                        $files[] = array(
                            'url'=> str_replace("\\", "/", substr($path2, strlen($_SERVER['DOCUMENT_ROOT']))),
                            'mtime'=> filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }
}