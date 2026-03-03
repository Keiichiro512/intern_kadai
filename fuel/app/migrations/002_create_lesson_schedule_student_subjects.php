<?php

namespace Fuel\Migrations;

class Create_lesson_schedule_student_subjects
{
    public function up()
    {
        \DBUtil::create_table('lesson_schedule_student_subjects', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'lesson_schedule_id' => array('type' => 'int', 'constraint' => 11),
            'student_user_id' => array('type' => 'int', 'constraint' => 11),
            'subject_id' => array('type' => 'int', 'constraint' => 11),
        ), array('id'), true, 'InnoDB', null, array(
            array(
                'constraint' => 'fk_lsss_lesson_schedule',
                'key' => 'lesson_schedule_id',
                'reference' => array('table' => 'lesson_schedules', 'column' => 'id'),
                'on_delete' => 'CASCADE',
            ),
            array(
                'constraint' => 'fk_lsss_student_user',
                'key' => 'student_user_id',
                'reference' => array('table' => 'users', 'column' => 'id'),
                'on_delete' => 'CASCADE',
            ),
            array(
                'constraint' => 'fk_lsss_subject',
                'key' => 'subject_id',
                'reference' => array('table' => 'subjects', 'column' => 'id'),
                'on_delete' => 'CASCADE',
            ),
        ));
    }

    public function down()
    {
        \DBUtil::drop_table('lesson_schedule_student_subjects');
    }
}
