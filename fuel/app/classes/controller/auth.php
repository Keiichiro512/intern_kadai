<?php

class Controller_Auth extends Controller_Template
{
    public $template = 'template';

    public function before()
    {
        return parent::before();
    }

    // 確認済み
    public function action_login()
    {
        // template.phpのタイトル（$title）を設定する；タブのタイトルを設定する
        $this->template->title       = 'ログイン';
        // template.phpのstyle_sheet（$style_sheet）を設定する；cssを読み込む
        $this->template->style_sheet = 'auth.css';
        // まだエラーなし」という初期状態
        $error = false;

        // 「今のリクエストが POST かどうか」 を調べて、POST のときだけ if の中の処理（ログイン試行）を実行
        if (Input::method() === 'POST') {
            // trim(...) … ユーザー名の前後の余分な空白を除きます。
            $username = trim((string) Input::post('username', ''));
            // 第一引数：'password' … <form name="password"> の password と同じ名前で探す
            // 第二引数：'' … 何も送られてこなかったときは 空文字 にする、という意味（エラーにしないため）
            $password = (string) Input::post('password', '');

            if ($password === '') {
                $error = true;
            } else {
                $row = \DB::select('id', 'role_id', 'username', 'password')
                    ->from('users')
                    ->where('username', $username)
                    ->execute()
                    ->current();

                    // PHP の 型キャスト（型を変換する書き方） で、連想配列 $row を「オブジェクト」に変換。配列のキーがそのままプロパティ名になる。
                $user = (is_array($row) && isset($row['id'])) ? (object) $row : null;
                // $user? オブジェクトなど中身がある値は真
                $stored_hash = $user ? trim((string) $user->password) : '';
                $verify_ok   = $this->verify_password($password, $stored_hash);

                if ($user === null || ! $verify_ok) {
                    $error = true;
                } else {
                    Session::set('user_id', (int) $user->id);
                    Session::set('role_id', (int) $user->role_id);
                    Session::set('username', (string) $user->username);

                    switch ((int) $user->role_id) {
                        case 1: // 塾長
                            // Uri::create は「URL の文字列を作る」だけで、「移動する」処理ではない。
                            // Response::redirect('admin/home') は「ブラウザに別ページへ行かせる」処理 
                            Response::redirect('admin/home');
                            break;
                        case 2: // 講師
                            Response::redirect('teacher/home');
                            break;
                        case 3: // 生徒
                            Response::redirect('student/home');
                            break;
                        case 4: // 保護者
                            Response::redirect('parent/home');
                            break;
                        default:
                            // セッションに保存されていたデータをまとめて消す。
                            Session::destroy();
                            $error = true;
                            break;
                    }
                }
            }
        }

        $this->template->content = View::forge('auth/login', [
            'error'    => $error,
            // 失敗後も 入力した ID をフォームに残す（空なら空文字）
            'username' => Input::post('username', ''),
        ]);
    }

    // 確認済み
    public function action_logout()
    {
        // セッションに保存されていたデータをまとめて消す。
        Session::destroy();
        // ログイン画面にリダイレクトする
        Response::redirect('auth/login');
    }

    /**
     * 404 Not Found ページ。
     */
    public function action_404()
    {
        $this->template->title       = 'ページが見つかりません';
        $this->template->style_sheet = 'auth.css';
        $this->template->content     = View::forge('auth/404');
        return Response::forge($this->template, 404);
    }

    /**
     * 権限不足時に表示するページ。
     */
    public function action_access_denied()
    {
        $this->template->title       = 'アクセス権限がありません';
        $this->template->style_sheet = 'auth.css';
        $this->template->content     = View::forge('auth/access_denied');
    }

    // 確認済み
    /**
     * 入力パスワードとDBのハッシュを照合する。
     * DBには password_hash($raw, PASSWORD_DEFAULT) で保存された値が入っている前提。
     * 必ず password_verify（平文・ハッシュのペア）で検証し、他方式は使わない。
     */
    private function verify_password($input_password, $stored_hash)
    {
        if ($stored_hash === '' || $input_password === '') {
            return false;
        }
        // password_verify (第1引数：入力された平文パスワード, 第2引数：保存しておいたハッシュ)を渡すと、
        // 「この平文からこのハッシュが作られたか」 を内部で判定し、合っていれば true、違えば false を返す。
        return password_verify($input_password, $stored_hash);

        // ハッシュ化せずにもDBに保存できる。DBの内容が漏れた時に、平文でパスワードが見られてしまうのを防ぐ。
        
    }

}
