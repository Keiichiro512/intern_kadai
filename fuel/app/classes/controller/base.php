<?php

/**
 * すべての認可が必要なコントローラーの親クラス。
 * 未ログイン時は auth/login へ、ロール不一致時は auth/access_denied へリダイレクトする。
 */
class Controller_Base extends Controller_Template
{
    public $template = 'template';

    public function before()
    {
        if ( ! $this->check_access()) {
            return false;
        }
        return parent::before();
    }

    /**
     * ログイン状態とURLプレフィックスに応じたアクセス可否を判定する。
     * 未ログインなら auth/login へ、権限不足なら auth/access_denied へリダイレクトする。
     *
     * @return bool アクセス許可する場合 true、リダイレクトした場合 false
     */
    protected function check_access()
    {
        if ( ! \Session::get('user_id')) {
            \Response::redirect('auth/login');
            return false;
        }

        $prefix = \Uri::segment(1);
        if ($prefix === null || $prefix === '') {
            return true;
        }

        $prefix = strtolower($prefix);
        $role_id = (int) \Session::get('role_id', 0);

        $required_role_by_prefix = array(
            'admin'   => 1,
            'teacher' => 2,
            'student' => 3,
            'parent'  => 4,
        );

        if (isset($required_role_by_prefix[$prefix]) && $role_id !== $required_role_by_prefix[$prefix]) {
            \Response::redirect('auth/access_denied');
            return false;
        }

        return true;
    }
}
