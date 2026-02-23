<?php
/**
 * The development database settings. These get merged with the global settings.
 */

return [
    'default' => [
        'connection'  => [
            // ポイント: hostは 127.0.0.1 ではなく、docker-composeのサービス名「db」にする
            'dsn'        => 'mysql:host=db;dbname=student_management;charset=utf8mb4',
            'username'   => 'root',
            'password'   => 'root',
        ],
        // 実行されたSQLを画面下部で確認できるようにする（デバッグに便利！）
        'profiling' => true,
    ],
];