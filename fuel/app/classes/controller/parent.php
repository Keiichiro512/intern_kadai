<?php

class Controller_Parent extends Controller_Base
{
    public $template = 'template';

    /**
     * 保護者本人専用マイページ。自分の子供の授業・報告書のみ表示。
     * ログイン中ユーザーIDはセッションから取得（URLパラメータは使用しない）。
     */
    public function action_home()
    {
        $this->template->title       = '保護者ホーム';
        $this->template->style_sheet = 'admin.css';

        $user_id = (int) \Session::get('user_id');
        if ( ! $user_id) {
            $this->template->content = View::forge('parent/home', array(
                'username'   => '',
                'children'   => array(),
                'schedules'  => array(),
            ));
            return;
        }

        $relations = \Model_Parent_Student_Relation::find('all', array(
            'where'   => array(array('parent_user_id', $user_id)),
            'related' => array('student'),
        ));
        $relations = $relations ?: array();

        $student_user_ids = array();
        foreach ($relations as $r) {
            if ($r->student_user_id) {
                $student_user_ids[] = (int) $r->student_user_id;
            }
        }
        $student_user_ids = array_unique($student_user_ids);

        $schedules = array();
        if ( ! empty($student_user_ids)) {
            $schedules = \Model_Lesson_Schedule::find('all', array(
                'where'    => array(array('student_user_id', 'in', $student_user_ids)),
                'related'  => array('student', 'subject', 'report'),
                'order_by' => array('lesson_date' => 'desc', 'time_slot_id' => 'asc'),
            ));
            $schedules = $schedules ?: array();
        }

        $this->template->content = View::forge('parent/home', array(
            'username'  => \Session::get('username', ''),
            'children'  => $relations,
            'schedules' => $schedules,
        ));
    }
}
