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

        $errors = '';

        if (\Input::method() === 'POST') {
            $input['target_type'] = \Input::post('target_type', 'teacher');
            $input['last_name']   = trim((string) \Input::post('last_name', ''));
            $input['first_name']  = trim((string) \Input::post('first_name', ''));
            $input['grade_id']    = (string) \Input::post('grade_id', '');
            $input['subject_ids'] = (array) \Input::post('subject_ids', array());

            $raw_password = (string) \Input::post('password', '');

            $val = \Validation::forge();
            $val->add('target_type', '対象')
                ->add_rule('required')
                ->add_rule('in_array', array('teacher', 'student'));
            $val->add('last_name', '氏')
                ->add_rule('required')
                ->add_rule('max_length', 50);
            $val->add('first_name', '名')
                ->add_rule('required')
                ->add_rule('max_length', 50);

            $val->add('password', 'パスワード')
                ->add_rule('required')
                ->add_rule('min_length', 6);

            if ($input['target_type'] === 'student') {
                $val->add('grade_id', '学年')
                    ->add_rule('required');
                $val->add('subject_ids', '受講科目')
                    ->add_rule('required');
            }

            if ( ! $val->run($input)) {
                $errors = $val->show_errors();
            } else {
                try {
                    \DB::start_transaction();

                    $auth = \Auth::instance();
                    $hashed_password = $auth->hash_password($raw_password);

                    $role_id = ($input['target_type'] === 'teacher') ? 2 : 3;
                    $username = $input['last_name'] . $input['first_name'];

                    $user = Model_User::forge(array(
                        'role_id'    => $role_id,
                        'username'   => $username,
                        'password'   => $hashed_password,
                        'first_name' => $input['first_name'],
                        'last_name'  => $input['last_name'],
                    ));
                    $user->save();

                    if ($input['target_type'] === 'student') {
                        $student = Model_Student::forge(array(
                            'user_id'  => (int) $user->id,
                            'grade_id' => (int) $input['grade_id'],
                        ));
                        $student->save();

                        foreach ($input['subject_ids'] as $subject_id) {
                            $subject_id = (int) $subject_id;
                            if ($subject_id <= 0) {
                                continue;
                            }

                            $student_subject = Model_Student_Subject::forge(array(
                                'student_user_id' => (int) $user->id,
                                'subject_id'      => $subject_id,
                            ));
                            $student_subject->save();
                        }
                    }

                    \DB::commit_transaction();

                    \Response::redirect('admin/user_create_complete');
                } catch (\Exception $e) {
                    \DB::rollback_transaction();
                    $errors = $e->getMessage();
                }
            }
        }

        $data = array(
            'grades'   => $grades,
            'subjects' => $subjects,
            // null を渡すと Security::htmlentities 内で get_class(null) が実行され警告になるため、空文字にしておく
            'errors'   => (string) $errors,
            'input'    => $input,
        );

        $this->template->content = View::forge('admin/user_create', $data);
    }

    /**
     * ユーザー登録完了画面
     */
    public function action_create_complete()
    {
        $this->template->title       = '登録完了';
        $this->template->style_sheet = 'admin.css';

        $this->template->content = View::forge('admin/user_create_complete');
    }
}
