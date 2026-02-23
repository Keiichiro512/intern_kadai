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
            $username = Input::post('username');
            $password = Input::post('password');

            // TODO: 実際の認証処理に置き換える
            if ($username !== 'admin' || $password !== 'password') {
                $error = true;
            } else {
                Response::redirect('/');
            }
        }

        $this->template->content = View::forge('auth/login', [
            'error'    => $error,
            'username' => Input::post('username', ''),
        ]);
    }
}
