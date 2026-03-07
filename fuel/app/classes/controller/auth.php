<?php

class Controller_Auth extends Controller_Template
{
    public $template = 'template';

    public function before()
    {
        return parent::before();
    }

    public function action_login()
    {
        $this->template->title       = 'ログイン';
        $this->template->style_sheet = 'auth.css';
        $error = false;

        if (Input::method() === 'POST') {
            $username = trim((string) Input::post('username', ''));
            $password = (string) Input::post('password', '');

            if ($password === '') {
                $error = true;
            } else {
                $user = Model_User::find('first', array(
                    'where' => array(
                        array('username', $username),
                    ),
                ));

                $stored_hash = $user ? (string) $user->password : '';

                $query = \DB::select('password')
                    ->from('users')
                    ->where('username', $username)
                    ->execute();
                $raw_hash = $query->get('password');

                $clean_hash = trim((string) $raw_hash);
                $is_valid = password_verify($password, $clean_hash);

                $verify_ok = $this->verify_password($password, $stored_hash);
                $verify_ok = ($verify_ok || $is_valid);

                if (empty($user) || ! $verify_ok) {
                    $error = true;
                } else {
                    Session::set('user_id', (int) $user->id);
                    Session::set('role_id', (int) $user->role_id);
                    Session::set('username', (string) $user->username);

                    switch ((int) $user->role_id) {
                        case 1: // 塾長
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
                            Session::destroy();
                            $error = true;
                            break;
                    }
                }
            }
        }

        $this->template->content = View::forge('auth/login', [
            'error'    => $error,
            'username' => Input::post('username', ''),
        ]);
    }

    public function action_logout()
    {
        Session::destroy();
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

        return password_verify($input_password, $stored_hash);
    }
}
