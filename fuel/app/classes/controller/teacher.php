<?php

class Controller_Teacher extends Controller_Base
{
    public $template = 'template';

    /**
     * 講師本人専用マイページ。担当スケジュール・担当生徒のみ表示。
     * ログイン中ユーザーIDはセッションから取得（URLパラメータは使用しない）。
     */
    public function action_home()
    {
        $this->template->title       = '講師ホーム';
        $this->template->style_sheet = 'admin.css';

        $user_id = (int) \Session::get('user_id');
        if ( ! $user_id) {
            $this->template->content = View::forge('teacher/home', array(
                'username'   => '',
                'schedules'  => array(),
                'my_students' => array(),
            ));
            return;
        }

        $schedules = \Model_Lesson_Schedule::find('all', array(
            'where'    => array(array('teacher_user_id', $user_id)),
            'related'  => array('student', 'subject'),
            'order_by' => array('lesson_date' => 'asc', 'time_slot_id' => 'asc'),
        ));
        $schedules = $schedules ?: array();

        $my_student_ids = array();
        foreach ($schedules as $s) {
            if ($s->student_user_id && ! in_array($s->student_user_id, $my_student_ids, true)) {
                $my_student_ids[] = $s->student_user_id;
            }
        }
        $my_students = array();
        if ( ! empty($my_student_ids)) {
            $users = \Model_User::find('all', array(
                'where' => array(array('id', 'in', $my_student_ids)),
            ));
            $my_students = $users ?: array();
        }

        $this->template->content = View::forge('teacher/home', array(
            'username'    => \Session::get('username', ''),
            'schedules'   => $schedules,
            'my_students' => $my_students,
        ));
    }
}
