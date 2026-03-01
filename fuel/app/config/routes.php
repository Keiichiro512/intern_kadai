<?php
/**
 * ルーティング: 左がマッチするURIパス、右がコントローラ/アクション。
 * ログイン: URI「auth/login」→ Controller_Auth::action_login()
 * フォーム送信先は views/auth/login.php の Form::open(['action' => Uri::create('auth/login')]) で
 * base_url + "auth/login" になる。このURIがここで定義した 'auth/login' と一致する必要がある。
 */
return array(
    // デフォルトはログイン画面
    '_root_'       => 'auth/login',
    '_404_'        => 'welcome/404',

    // 認証（URI auth/login → auth/login = Controller_Auth::action_login）
    'auth/login'        => 'auth/login',
    'auth/logout'       => 'auth/logout',
    'auth/access_denied' => 'auth/access_denied',

    // 塾長（マスタ）画面（URL は admin/ 始まりに統一し、Controller_Base の権限チェックを有効にする）
    'admin/home'                  => 'admin/home',
    'admin/schedule'              => 'admin/schedule_home',
    'admin/schedule/save'         => 'admin/schedule_save',
    'admin/schedule/delete'       => 'admin/schedule_delete',
    'admin/user_create'           => 'admin/create',
    'admin/user_create_complete'  => 'admin/create_complete',
    'admin/users/edit'            => 'admin/user_list',
    'admin/edit_user/(:id)'       => 'admin/edit_user/$1',
    'admin/force_test_password/(:id)' => 'admin/force_test_password/$1',
    'admin/delete_user/(:id)'     => 'admin/delete_user/$1',
    'admin/masters_home'          => 'admin/home',

    // 各ロールのトップページ
    'teacher/home' => 'teacher/home',
    'student/home' => 'student/home',
    'parent/home'  => 'parent/home',

    // 権限チェックが必要なURLは、必ず admin / teacher / student / parent のいずれかで始めること。
    // 例: 'admin/reports' => 'admin/report/list',
    // 例: 'teacher/schedule' => 'teacher/schedule/index',
    // 例: 'student/my-schedule' => 'student/schedule/index',
);
