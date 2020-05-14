<?php

use nulastudio\Middleware;

// 主页
Router::get('/', function () {echo 'mclone is good';});

Router::post('clone', 'MCloneController@mclone');
Router::post('status', 'MCloneController@status');
Router::post('drop', 'MCloneController@drop');
Router::get('clean', 'MCloneController@clean');

// 404处理
// Router::error(function(){});

// 模板渲染
Router::dispatch('Dispatcher@dispatch');

function callback(callable $callback)
{
    return (new Middleware)->send()->to($callback)->finish(function ($origin, $data) {
        Dispatcher::dispatch($data);
    })->pack();
}
