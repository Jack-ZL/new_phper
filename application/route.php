<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

Route::rule('post/add','Post/add');

Route::rule('post/edit/:id','Post/edit');

Route::post('post/reply','Post/reply');

Route::get('post/msgtoreply/:id','Post/msgToReply');

Route::get('post/:id','Post/detail');

Route::get('u/:id','User/home');

Route::get('jump','Index/jump');

Route::get('column/:name/[:map]','Index/column');

//第三方授权
Route::get('sns/auth/:name/[:state]','Sns/auth');

// 第三方授权回调
Route::get('sns/callback/:name/[:state]','Sns/callback');

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

];
