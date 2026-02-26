<?php

class Controller_Admin extends Controller_Template
{
    public $template = 'template';

    public function action_home()
    {
        $this->template->title       = '塾長ホーム';
        $this->template->style_sheet = 'admin.css';

        $this->template->content = View::forge('admin/home');
    }

    /**
     * 講師・生徒 追加画面
     */
    public function action_create()
    {
        $this->template->title       = 'ユーザー登録';
        $this->template->style_sheet = 'admin.css';

        // マスタデータ取得（全件）※ null の場合でも必ず配列を渡す
        $grades_result   = Model_Grade::find('all');
        $subjects_result = Model_Subject::find('all');

        $grades   = $grades_result   ?: array();
        $subjects = $subjects_result ?: array();

        // 初期表示用の入力値
        $input = array(
            'target_type' => 'teacher',
            'last_name'   => '',
            'first_name'  => '',
            'grade_id'    => '',
            'subject_ids' => array(),
        );

        $data = array(
            'grades'   => $grades,
            'subjects' => $subjects,
            // null を渡すと Security::htmlentities 内で get_class(null) が実行され警告になるため、空文字にしておく
            'error'    => '',
            'success'  => '',
            'input'    => $input,
        );

        $this->template->content = View::forge('admin/user_create', $data);
    }
}
