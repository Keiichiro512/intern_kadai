<?php

class Controller_Auth extends Controller_Template
{
    public $template = 'template';

    public function action_login()
    {
        $this->template->title       = 'ログイン';
        $this->template->style_sheet = 'auth.css';

        $error = false;

        if (Input::method() === 'POST') {
            $username = trim((string) Input::post('username', ''));
            $password = (string) Input::post('password', '');

            $user = Model_User::find('first', array(
                'where' => array(
                    array('username', $username),
                ),
            ));

            if (empty($user) || ! $this->verify_password($password, (string) $user->password)) {
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

    private function verify_password($input_password, $stored_password)
    {
        if ($stored_password === '') {
            return false;
        }

        // FuelPHP Auth(SimpleAuth) で保存されたハッシュであれば validate_password を優先して使う
        try {
            $auth = \Auth::instance();
            if ($auth->validate_password($input_password, $stored_password)) {
                return true;
            }
        } catch (\Exception $e) {
            // Auth が利用できない場合は後続のロジックにフォールバック
        }

        // bcrypt / password_hash 形式なら verify、そうでなければ平文比較
        if (strpos($stored_password, '$2y$') === 0 || strpos($stored_password, '$2a$') === 0 || strpos($stored_password, '$2b$') === 0) {
            return password_verify($input_password, $stored_password);
        }

        return hash_equals($stored_password, $input_password);
    }
}
