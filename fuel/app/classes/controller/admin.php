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

        // マスタデータ取得
        $grades   = Model_Grade::find('all', array(
            'order_by' => array('id' => 'asc'),
        ));
        $subjects = Model_Subject::find('all', array(
            'order_by' => array('id' => 'asc'),
        ));

        $error   = null;
        $success = null;

        // 画面再描画用の入力値
        $input = array(
            'target_type' => Input::post('target_type', 'teacher'), // student / teacher
            'last_name'   => Input::post('last_name', ''),
            'first_name'  => Input::post('first_name', ''),
            'grade_id'    => Input::post('grade_id', ''),
            'subject_ids' => (array) Input::post('subject_ids', array()),
        );

        if (Input::method() === 'POST')
        {
            try
            {
                $target_type = Input::post('target_type');
                $last_name   = trim(Input::post('last_name'));
                $first_name  = trim(Input::post('first_name'));
                $grade_id    = Input::post('grade_id');
                $subject_ids = (array) Input::post('subject_ids', array());

                // 簡易バリデーション
                if ( ! in_array($target_type, array('student', 'teacher'), true))
                {
                    throw new \Exception('対象を選択してください。');
                }
                if ($last_name === '' || $first_name === '')
                {
                    throw new \Exception('氏名を入力してください。');
                }
                if ($target_type === 'student' && empty($grade_id))
                {
                    throw new \Exception('学年を選択してください。');
                }

                \DB::start_transaction();

                // ユーザー作成
                $user                = Model_User::forge();
                $user->role_id       = ($target_type === 'student') ? 3 : 2;
                $user->first_name    = $first_name;
                $user->last_name     = $last_name;

                // ユーザー名は一意になるよう自動採番（役割 + タイムスタンプ）
                $username_prefix     = ($target_type === 'student') ? 'stu' : 'tch';
                $user->username      = $username_prefix.'_'.time();

                // 初期パスワードをハッシュ化して保存
                $raw_password        = 'password123';
                $user->password      = password_hash($raw_password, PASSWORD_DEFAULT);

                $user->save();

                // 生徒の場合のみ students / student_subjects を作成
                if ($target_type === 'student')
                {
                    $student            = Model_Student::forge();
                    $student->user_id   = $user->id;
                    $student->grade_id  = (int) $grade_id;
                    $student->save();

                    foreach ($subject_ids as $subject_id)
                    {
                        if ( ! $subject_id)
                        {
                            continue;
                        }
                        $student_subject                  = Model_Student_Subject::forge();
                        $student_subject->student_user_id = $user->id;
                        $student_subject->subject_id      = (int) $subject_id;
                        $student_subject->save();
                    }
                }

                \DB::commit_transaction();

                $success = 'ユーザーを登録しました。';

                // フォームの初期化（講師に戻す）
                $input = array(
                    'target_type' => 'teacher',
                    'last_name'   => '',
                    'first_name'  => '',
                    'grade_id'    => '',
                    'subject_ids' => array(),
                );
            }
            catch (\Exception $e)
            {
                if (\DB::in_transaction())
                {
                    \DB::rollback_transaction();
                }
                $error = $e->getMessage();
            }
        }

        $this->template->content = View::forge('admin/user_create', array(
            'grades'   => $grades,
            'subjects' => $subjects,
            'error'    => $error,
            'success'  => $success,
            'input'    => $input,
        ));
    }
}
