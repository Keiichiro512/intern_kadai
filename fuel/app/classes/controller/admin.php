<?php

class Controller_Admin extends Controller_Base
{
    public $template = 'template';

    public function action_home()
    {
        $this->template->title       = '塾長ホーム';
        $this->template->style_sheet = 'admin.css';

        $this->template->content = View::forge('admin/home');
    }

    /**
     * スケジュール管理画面。講師・生徒・時間枠・指定日の授業・カレンダーを取得してViewに渡す。
     */
    public function action_schedule_home()
    {
        $this->template->title       = '授業スケジュール';
        $this->template->style_sheet = 'admin.css';

        $year  = (int) \Input::get('year', date('Y'));
        $month = (int) \Input::get('month', date('n'));
        $month = max(1, min(12, $month));
        $date_str = \Input::get('date');
        if ($date_str) {
            $display_dt = \DateTime::createFromFormat('Y-m-d', $date_str);
            if ( ! $display_dt) {
                $display_dt = new \DateTime($year . '-' . $month . '-01');
            }
        } else {
            $today = new \DateTime();
            if ($year === (int) $today->format('Y') && $month === (int) $today->format('n')) {
                $display_dt = clone $today;
            } else {
                $display_dt = new \DateTime($year . '-' . $month . '-01');
            }
        }

        $teachers = \Model_User::find('all', array(
            'where'    => array(array('role_id', 2)),
            'order_by' => array('last_name' => 'asc', 'first_name' => 'asc'),
        ));
        $teachers = $teachers ?: array();

        $grades = \Model_Grade::find('all', array(
            'order_by' => array('id' => 'asc'),
            'related'  => array('students' => array('related' => array('user'))),
        ));
        $grades = $grades ?: array();
        $students_by_grade = array();
        foreach ($grades as $grade) {
            $students_by_grade[] = array(
                'grade'   => $grade,
                'students' => $grade->students ?: array(),
            );
        }

        $time_slots = \Model_Time_Slot::find('all', array('order_by' => array('id' => 'asc')));
        $time_slots = $time_slots ?: array();

        $lesson_date = $display_dt->format('Y-m-d');
        $schedules = \Model_Lesson_Schedule::find('all', array(
            'where'   => array(array('lesson_date', $lesson_date)),
            'related' => array('teacher', 'student', 'subject'),
            'order_by' => array('time_slot_id' => 'asc'),
        ));
        $schedules = $schedules ?: array();

        $lesson_slots = array();
        foreach ($time_slots as $ts) {
            $lesson_slots[$ts->id] = array();
        }
        foreach ($schedules as $ls) {
            $tid = (int) $ls->time_slot_id;
            if ( ! isset($lesson_slots[$tid])) {
                $lesson_slots[$tid] = array();
            }
            $teacher_name = ($ls->teacher) ? ($ls->teacher->last_name . ' ' . $ls->teacher->first_name) : '';
            $student_name = ($ls->student) ? ($ls->student->last_name . ' ' . $ls->student->first_name) : '';
            $subject_name = ($ls->subject) ? $ls->subject->subject_name : '';
            $lesson_slots[$tid][] = array(
                'id'      => (int) $ls->id,
                'teacher' => $teacher_name,
                'student' => $student_name,
                'subject' => $subject_name,
                'teacher_user_id' => (int) $ls->teacher_user_id,
                'student_user_id' => (int) $ls->student_user_id,
                'subject_id'      => (int) $ls->subject_id,
            );
        }

        $weekday_ja = array('日', '月', '火', '水', '木', '金', '土');
        $display_date = $display_dt->format('n/j') . '(' . $weekday_ja[(int) $display_dt->format('w')] . ')';

        $cal_year  = (int) $display_dt->format('Y');
        $cal_month = (int) $display_dt->format('n');
        $calendar_month = $cal_month . '月';
        $calendar_year  = $cal_year;

        $first = new \DateTime($cal_year . '-' . $cal_month . '-01');
        $last  = clone $first;
        $last->modify('last day of this month');
        $last_day = (int) $last->format('j');
        $start_w = (int) $first->format('w');

        $calendar_weeks = array();
        $week = array();
        for ($i = 0; $i < $start_w; $i++) {
            $week[] = '';
        }
        for ($d = 1; $d <= $last_day; $d++) {
            $week[] = (string) $d;
            if (count($week) === 7) {
                $calendar_weeks[] = $week;
                $week = array();
            }
        }
        if (count($week) > 0) {
            while (count($week) < 7) {
                $week[] = '';
            }
            $calendar_weeks[] = $week;
        }

        $first_str = $first->format('Y-m-d');
        $last_str  = $last->format('Y-m-d');
        $month_schedules = \Model_Lesson_Schedule::find('all', array(
            'where' => array(
                array('lesson_date', '>=', $first_str),
                array('lesson_date', '<=', $last_str),
            ),
            'order_by' => array('lesson_date' => 'asc', 'time_slot_id' => 'asc'),
        ));
        $month_schedules = $month_schedules ?: array();

        $slot_labels = array();
        foreach ($time_slots as $ts) {
            $slot_labels[(int) $ts->id] = mb_substr($ts->slot_name, 0, 1) ?: (string) $ts->id;
        }
        $monthly_lessons = array();
        foreach ($month_schedules as $ls) {
            $date = $ls->lesson_date;
            $tid = (int) $ls->time_slot_id;
            if ( ! isset($monthly_lessons[$date])) {
                $monthly_lessons[$date] = array();
            }
            $monthly_lessons[$date][$tid] = isset($slot_labels[$tid]) ? $slot_labels[$tid] : (string) $tid;
        }

        $selected_day = (int) $display_dt->format('j');

        $cal_dt = new \DateTime($cal_year . '-' . $cal_month . '-01');
        $prev_dt = clone $cal_dt;
        $prev_dt->modify('first day of previous month');
        $next_dt = clone $cal_dt;
        $next_dt->modify('first day of next month');
        $prev_url = \Uri::create('admin/schedule', array(), array(
            'year'  => $prev_dt->format('Y'),
            'month' => $prev_dt->format('n'),
        ));
        $next_url = \Uri::create('admin/schedule', array(), array(
            'year'  => $next_dt->format('Y'),
            'month' => $next_dt->format('n'),
        ));

        $subjects = \Model_Subject::find('all', array('order_by' => array('id' => 'asc')));
        $subjects = $subjects ?: array();

        $students_flat = array();
        foreach ($grades as $grade) {
            if (empty($grade->students)) continue;
            foreach ($grade->students as $st) {
                if ($st->user) {
                    $students_flat[] = array(
                        'id'   => (int) $st->user_id,
                        'name' => $st->user->last_name . ' ' . $st->user->first_name,
                    );
                }
            }
        }

        $this->template->content = View::forge('admin/schedule/home', array(
            'teachers'          => $teachers,
            'students_by_grade' => $students_by_grade,
            'display_date'      => $display_date,
            'time_slots'        => $time_slots,
            'lesson_slots'      => $lesson_slots,
            'calendar_month'    => $calendar_month,
            'calendar_year'     => $calendar_year,
            'calendar_weeks'    => $calendar_weeks,
            'selected_day'      => $selected_day,
            'calendar_month_num'=> $cal_month,
            'calendar_year_num' => $cal_year,
            'prev_url'            => $prev_url,
            'next_url'            => $next_url,
            'schedule_lesson_date' => $lesson_date,
            'subjects'            => $subjects,
            'students_flat'       => $students_flat,
            'monthly_lessons'     => $monthly_lessons,
        ));
    }

    /**
     * スケジュール保存（新規 or 更新）。POST: lesson_schedule_id, lesson_date, time_slot_id, teacher_user_id, student_user_id, subject_id
     */
    public function action_schedule_save()
    {
        if (\Input::method() !== 'POST') {
            \Response::redirect('admin/schedule');
            return;
        }

        $id = (int) \Input::post('lesson_schedule_id', 0);
        $lesson_date = trim((string) \Input::post('lesson_date', ''));
        $time_slot_id = (int) \Input::post('time_slot_id', 0);
        $teacher_user_id = (int) \Input::post('teacher_user_id', 0);
        $student_user_id = (int) \Input::post('student_user_id', 0);
        $subject_id = (int) \Input::post('subject_id', 0);

        $errors = array();
        if ($lesson_date === '') $errors[] = '日付を指定してください。';
        if ($time_slot_id <= 0) $errors[] = '時間枠を指定してください。';
        if ($teacher_user_id <= 0) $errors[] = '講師を選択してください。';
        if ($student_user_id <= 0) $errors[] = '生徒を選択してください。';
        if ($subject_id <= 0) $errors[] = '科目を選択してください。';

        if ( ! empty($errors)) {
            \Session::set_flash('error', implode(' ', $errors));
            \Response::redirect(\Uri::create('admin/schedule', array(), \Input::get()));
            return;
        }

        try {
            if ($id > 0) {
                $schedule = \Model_Lesson_Schedule::find($id);
                if ( ! $schedule) {
                    \Session::set_flash('error', '指定された授業が見つかりません。');
                    \Response::redirect('admin/schedule');
                    return;
                }
                $schedule->lesson_date = $lesson_date;
                $schedule->time_slot_id = $time_slot_id;
                $schedule->teacher_user_id = $teacher_user_id;
                $schedule->student_user_id = $student_user_id;
                $schedule->subject_id = $subject_id;
                $schedule->save();
            } else {
                \Model_Lesson_Schedule::forge(array(
                    'lesson_date'      => $lesson_date,
                    'time_slot_id'     => $time_slot_id,
                    'teacher_user_id'  => $teacher_user_id,
                    'student_user_id'  => $student_user_id,
                    'subject_id'       => $subject_id,
                ))->save();
            }
            \Session::set_flash('success', '保存しました。');
        } catch (\Exception $e) {
            \Session::set_flash('error', '保存に失敗しました。');
        }

        $redirect_params = array();
        $ry = \Input::post('redirect_year', \Input::get('year'));
        $rm = \Input::post('redirect_month', \Input::get('month'));
        $rd = trim((string) \Input::post('redirect_date', ''));
        if ($ry) $redirect_params['year'] = $ry;
        if ($rm) $redirect_params['month'] = $rm;
        if ($rd !== '') $redirect_params['date'] = $rd;
        \Response::redirect(\Uri::create('admin/schedule', array(), $redirect_params));
    }

    /**
     * スケジュール削除。POST: lesson_schedule_id
     */
    public function action_schedule_delete()
    {
        if (\Input::method() !== 'POST') {
            \Response::redirect('admin/schedule');
            return;
        }

        $id = (int) \Input::post('lesson_schedule_id', 0);
        if ($id <= 0) {
            \Session::set_flash('error', '不正なリクエストです。');
            \Response::redirect('admin/schedule');
            return;
        }

        $schedule = \Model_Lesson_Schedule::find($id);
        if ($schedule) {
            try {
                $schedule->delete();
                \Session::set_flash('success', '削除しました。');
            } catch (\Exception $e) {
                \Session::set_flash('error', '削除に失敗しました。');
            }
        }

        $redirect_params = array();
        $ry = \Input::post('redirect_year', \Input::get('year'));
        $rm = \Input::post('redirect_month', \Input::get('month'));
        $rd = trim((string) \Input::post('redirect_date', ''));
        if ($ry) $redirect_params['year'] = $ry;
        if ($rm) $redirect_params['month'] = $rm;
        if ($rd !== '') $redirect_params['date'] = $rd;
        \Response::redirect(\Uri::create('admin/schedule', array(), $redirect_params));
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
            'target_type'        => 'teacher',
            'last_name'          => '',
            'first_name'         => '',
            'grade_id'           => '',
            'subject_ids'        => array(),
            'parent_last_name'   => '',
            'parent_first_name'  => '',
            'parent_password'    => '',
        );

        $errors = '';

        if (\Input::method() === 'POST') {
            $input['target_type']       = \Input::post('target_type', 'teacher');
            $input['last_name']         = trim((string) \Input::post('last_name', ''));
            $input['first_name']        = trim((string) \Input::post('first_name', ''));
            $input['grade_id']          = (string) \Input::post('grade_id', '');
            $input['subject_ids']       = (array) \Input::post('subject_ids', array());
            $input['parent_last_name']  = trim((string) \Input::post('parent_last_name', ''));
            $input['parent_first_name'] = trim((string) \Input::post('parent_first_name', ''));
            $input['parent_password']   = trim((string) \Input::post('parent_password', ''));

            $raw_password = (string) \Input::post('password', '');

            $has_parent_input = false;
            if ($input['target_type'] === 'student') {
                if ($input['parent_last_name'] !== '' || $input['parent_first_name'] !== '' || $input['parent_password'] !== '') {
                    $has_parent_input = true;
                }
            }

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

                if ($has_parent_input) {
                    $val->add('parent_last_name', '保護者氏')
                        ->add_rule('required')
                        ->add_rule('max_length', 50);
                    $val->add('parent_first_name', '保護者名')
                        ->add_rule('required')
                        ->add_rule('max_length', 50);
                    $val->add('parent_password', '保護者パスワード')
                        ->add_rule('required')
                        ->add_rule('min_length', 6);
                }
            }

            if ( ! $val->run($input)) {
                $errors = $val->show_errors();
            } else {
                try {
                    \DB::start_transaction();

                    // ログイン照合（password_verify）と整合させるため、必ず password_hash(PASSWORD_DEFAULT) を使用
                    $hashed_password        = password_hash($raw_password, PASSWORD_DEFAULT);
                    $hashed_parent_password = $has_parent_input ? password_hash($input['parent_password'], PASSWORD_DEFAULT) : null;

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

                        if ($has_parent_input && $hashed_parent_password !== null) {
                            $parent_username = $input['parent_last_name'] . $input['parent_first_name'];

                            $parent_user = Model_User::forge(array(
                                'role_id'    => 4,
                                'username'   => $parent_username,
                                'password'   => $hashed_parent_password,
                                'first_name' => $input['parent_first_name'],
                                'last_name'  => $input['parent_last_name'],
                            ));
                            $parent_user->save();

                            $relation = Model_Parent_Student_Relation::forge(array(
                                'parent_user_id'  => (int) $parent_user->id,
                                'student_user_id' => (int) $user->id,
                            ));
                            $relation->save();
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

    /**
     * 講師・生徒を編集する一覧（学年別生徒・講師リスト）
     */
    public function action_user_list()
    {
        $this->template->title       = '講師・生徒を編集する';
        $this->template->style_sheet = 'admin.css';

        $grades = Model_Grade::find('all', array('order_by' => array('id' => 'asc')));
        $students_by_grade = array();
        foreach ($grades as $grade) {
            $students = Model_Student::find('all', array(
                'where'   => array('grade_id' => $grade->id),
                'related' => array('user'),
            ));
            $students = $students ?: array();
            usort($students, function ($a, $b) {
                $na = $a->user ? $a->user->last_name . $a->user->first_name : '';
                $nb = $b->user ? $b->user->last_name . $b->user->first_name : '';
                return strcmp($na, $nb);
            });
            $students_by_grade[] = array('grade' => $grade, 'students' => $students);
        }

        $teachers = Model_User::find('all', array(
            'where'    => array('role_id' => 2),
            'order_by' => array('last_name' => 'asc', 'first_name' => 'asc'),
        ));

        $this->template->content = View::forge('admin/user_list', array(
            'students_by_grade' => $students_by_grade,
            'teachers'          => $teachers,
        ));
    }

    /**
     * ユーザー編集画面（一覧からの遷移先）
     */
    public function action_edit_user($id)
    {
        $this->template->title       = 'ユーザー編集';
        $this->template->style_sheet = 'admin.css';

        $user = Model_User::find((int) $id);
        if (empty($user)) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }

        $is_student = ((int) $user->role_id === 3);
        $grades     = $is_student ? (Model_Grade::find('all', array('order_by' => array('id' => 'asc'))) ?: array()) : array();
        $subjects   = $is_student ? (Model_Subject::find('all', array('order_by' => array('id' => 'asc'))) ?: array()) : array();

        $student = null;
        $subject_ids = array();
        $grade_id = '';

        if ($is_student) {
            $student = Model_Student::find('first', array(
                'where' => array(
                    array('user_id', (int) $user->id),
                ),
            ));
            $grade_id = $student ? (string) $student->grade_id : '';

            $rels = Model_Student_Subject::find('all', array(
                'where' => array(
                    array('student_user_id', (int) $user->id),
                ),
            )) ?: array();
            foreach ($rels as $rel) {
                $subject_ids[] = (int) $rel->subject_id;
            }
        }

        $input = array(
            'last_name'   => (string) $user->last_name,
            'first_name'  => (string) $user->first_name,
            'grade_id'    => (string) $grade_id,
            'subject_ids' => $subject_ids,
            'password'    => '',
        );

        $errors = '';

        if (\Input::method() === 'POST') {
            $input['last_name']   = trim((string) \Input::post('last_name', ''));
            $input['first_name']  = trim((string) \Input::post('first_name', ''));
            $input['grade_id']    = (string) \Input::post('grade_id', '');
            $input['subject_ids'] = (array) \Input::post('subject_ids', array());
            $raw_password         = (string) \Input::post('password', '');
            $input['password']    = $raw_password;

            $val = \Validation::forge();
            $val->add('last_name', '氏')->add_rule('required')->add_rule('max_length', 50);
            $val->add('first_name', '名')->add_rule('required')->add_rule('max_length', 50);

            $val->add('password', 'パスワード')->add_rule('min_length', 6);

            if ($is_student) {
                $val->add('grade_id', '学年')->add_rule('required');
                $val->add('subject_ids', '受講科目')->add_rule('required');
            }

            if ( ! $val->run($input)) {
                $errors = $val->show_errors();
            } else {
                try {
                    \DB::start_transaction();

                    $user->last_name  = $input['last_name'];
                    $user->first_name = $input['first_name'];

                    if ($raw_password !== '') {
                        // ログイン照合（password_verify）と整合させるため、必ず password_hash(PASSWORD_DEFAULT)
                        $user->password = password_hash($raw_password, PASSWORD_DEFAULT);
                    }

                    $user->save();

                    if ($is_student) {
                        if (empty($student)) {
                            $student = Model_Student::forge(array(
                                'user_id'  => (int) $user->id,
                                'grade_id' => (int) $input['grade_id'],
                            ));
                        } else {
                            $student->grade_id = (int) $input['grade_id'];
                        }
                        $student->save();

                        $existing = Model_Student_Subject::find('all', array(
                            'where' => array(
                                array('student_user_id', (int) $user->id),
                            ),
                        )) ?: array();

                        foreach ($existing as $ex) {
                            $ex->delete();
                        }

                        foreach ($input['subject_ids'] as $sid) {
                            $sid = (int) $sid;
                            if ($sid <= 0) {
                                continue;
                            }
                            $ss = Model_Student_Subject::forge(array(
                                'student_user_id' => (int) $user->id,
                                'subject_id'      => $sid,
                            ));
                            $ss->save();
                        }
                    }

                    \DB::commit_transaction();

                    \Session::set_flash('success', '更新しました');
                    \Response::redirect('admin/edit_user/' . (int) $user->id);
                    return;
                } catch (\Exception $e) {
                    \DB::rollback_transaction();
                    $errors = $e->getMessage();
                }
            }
        }

        $this->template->content = View::forge('admin/user_edit', array(
            'user'     => $user,
            'is_student' => $is_student,
            'grades'   => $grades,
            'subjects' => $subjects,
            'errors'   => (string) $errors,
            'input'    => $input,
        ));
    }

    /**
     * 【テスト用】指定ユーザーのパスワードを強制的に "testpass123" に変更する。
     * ログイン照合の検証用。本番では削除すること。
     */
    public function action_force_test_password($id)
    {
        $user = Model_User::find((int) $id);
        if (empty($user)) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }
        $user->password = password_hash('testpass123', PASSWORD_DEFAULT);
        $user->save();
        \Session::set_flash('success', 'このユーザーのパスワードを "testpass123" に強制変更しました。ログイン検証後に元に戻してください。');
        \Response::redirect('admin/edit_user/' . (int) $user->id);
    }

    /**
     * ユーザー削除
     */
    public function action_delete_user($id)
    {
        if (\Input::method() !== 'POST') {
            \Response::redirect('admin/users/edit');
            return;
        }

        $user = Model_User::find((int) $id);
        if (empty($user)) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }

        $deleted_parent_count = 0;

        try {
            \DB::start_transaction();

            if ((int) $user->role_id === 3) {
                // 1. 生徒関連：受講科目 → 生徒詳細の順で削除
                $existing = Model_Student_Subject::find('all', array(
                    'where' => array(
                        array('student_user_id', (int) $user->id),
                    ),
                )) ?: array();
                foreach ($existing as $ex) {
                    $ex->delete();
                }

                $student = Model_Student::find('first', array(
                    'where' => array(
                        array('user_id', (int) $user->id),
                    ),
                ));
                if ($student) {
                    $student->delete();
                }

                // 2. この生徒に紐づく親子関係から parent_user_id を取得
                $child_relations = Model_Parent_Student_Relation::find('all', array(
                    'where' => array(
                        array('student_user_id', (int) $user->id),
                    ),
                )) ?: array();

                $parent_ids = array();
                foreach ($child_relations as $rel) {
                    $parent_ids[(int) $rel->parent_user_id] = true;
                }
                $parent_ids = array_keys($parent_ids);

                // 3. 他に紐付いている生徒がいない親のみ users を削除
                foreach ($parent_ids as $parent_id) {
                    $other_relations = Model_Parent_Student_Relation::find('all', array(
                        'where' => array(
                            array('parent_user_id', $parent_id),
                            array('student_user_id', '!=', (int) $user->id),
                        ),
                    )) ?: array();

                    if (count($other_relations) === 0) {
                        $parent_rels = Model_Parent_Student_Relation::find('all', array(
                            'where' => array(
                                array('parent_user_id', $parent_id),
                            ),
                        )) ?: array();
                        foreach ($parent_rels as $pr) {
                            $pr->delete();
                        }
                        $parent_user = Model_User::find($parent_id);
                        if ($parent_user) {
                            $parent_user->delete();
                            $deleted_parent_count++;
                        }
                    }
                }

                // 4. 削除対象ユーザーに紐づく親子関係を削除（生徒側として残っている分）
                $relations_child = Model_Parent_Student_Relation::find('all', array(
                    'where' => array(
                        array('student_user_id', (int) $user->id),
                    ),
                )) ?: array();
                foreach ($relations_child as $rel) {
                    $rel->delete();
                }
            }

            // 5. 削除対象が親または講師等の場合：自身を親とする紐付けを削除
            $relations_parent = Model_Parent_Student_Relation::find('all', array(
                'where' => array(
                    array('parent_user_id', (int) $user->id),
                ),
            )) ?: array();
            foreach ($relations_parent as $rel) {
                $rel->delete();
            }

            $user->delete();

            \DB::commit_transaction();

            if ((int) $user->role_id === 3 && $deleted_parent_count > 0) {
                \Session::set_flash('success', '生徒および（該当する場合は）親御さんのデータを削除しました');
            } elseif ((int) $user->role_id === 3) {
                \Session::set_flash('success', '生徒のデータを削除しました');
            } else {
                \Session::set_flash('success', '削除しました');
            }
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Session::set_flash('error', $e->getMessage());
        }

        \Response::redirect('admin/users/edit');
        return;
    }
}
