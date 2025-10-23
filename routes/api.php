<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 注释掉闭包路由，避免 route:cache 失败
// 如果需要此功能，请创建控制器方法替代
// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
