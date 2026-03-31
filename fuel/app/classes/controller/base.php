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

        // \Log::debug('[Base::before] enter ' . get_class($this) . ' uri=' . \Uri::main());
        // // ... check_access ...
        // \Log::debug('[Base::before] after check_access OK');
        // $ret = parent::before();
        // \Log::debug('[Base::before] after parent::before ret=' . var_export($ret, true));
        // return $ret;
        
        // アプリの動きを「後から読める文章」としてログファイルに残すための関数です。標準出力（ブラウザ）には出ず、fuel/app/logs/ 以下の日付ログなどに追記されます。
        // 先頭の \ について：PHP ではクラス名の前の \ はグローバル名前空間の意味です。FuelPHP 1.x ではアプリ側が名前空間なしのことも多いですが、\Log と書くと **Fuel\Core\Log のエイリアス（グローバルの Log）**を確実に指せます。

        // [Base::before] のような 固定文字列を先頭に付けると、grep '[Base::before]' fuel/app/logs/ のように ログを横断検索しやすくなります。
        // get_class($this) は「どの子クラスか」、URI は「どの URL か」を同一行で関連づける役です。
        // Controller::before() は 何も return しないので、PHP では戻り値は null になります。

        if ( ! $this->check_access()) {
            return false;
        }
        // FuelPHPのルール：before() が false を返すと そのリクエストでは action_* が実行されないから
        // 親が false を返した場合も、それをちゃんと上に伝えるために return している。
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
        // URLの最初のセグメントを取得（http://localhost/admin/home なら admin）
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
        
        // 「このURLのエリアに、このユーザーは入っていいか」（認可）
        if (isset($required_role_by_prefix[$prefix]) && $role_id !== $required_role_by_prefix[$prefix]) {
            \Response::redirect('auth/access_denied');
            return false;
        }

        return true;
    }
}
