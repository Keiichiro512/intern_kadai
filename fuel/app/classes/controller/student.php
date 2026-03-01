<?php

class Controller_Student extends Controller_Base
{
    public $template = 'template';

    /**
     * 生徒本人専用マイページ。自分の授業・報告書のみ表示。
     * ログイン中ユーザーIDはセッションから取得（URLパラメータは使用しない）。
     */
    public function action_home()
    {
        $this->template->title       = '生徒ホーム';
        $this->template->style_sheet = 'admin.css';

        $user_id = (int) \Session::get('user_id');
        if ( ! $user_id) {
            $this->template->content = View::forge('student/home', array(
                'username'  => '',
                'schedules' => array(),
            ));
            return;
        }

        $schedules = \Model_Lesson_Schedule::find('all', array(
            'where'    => array(array('student_user_id', $user_id)),
            'related'  => array('teacher', 'subject', 'report'),
            'order_by' => array('lesson_date' => 'desc', 'time_slot_id' => 'asc'),
        ));
        $schedules = $schedules ?: array();

        $this->template->content = View::forge('student/home', array(
            'username'  => \Session::get('username', ''),
            'schedules' => $schedules,
        ));
    }
}
