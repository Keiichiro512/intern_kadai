<?php

return array(
    // デフォルトはログイン画面
    '_root_'       => 'auth/login',
    '_404_'        => 'welcome/404',

    // 認証
    'auth/login'   => 'auth/login',
    'auth/logout'  => 'auth/logout',

    // 塾長（マスタ）画面
    'admin/home'        => 'admin/home',
    'admin/user_create' => 'admin/create',
    'masters_home'      => 'admin/home',

    // 各ロールのトップページ
    'teacher/home' => 'teacher/home',
    'student/home' => 'student/home',
    'parent/home'  => 'parent/home',
);
