<?php

namespace Fuel\Migrations;

class Create_lesson_schedule_students_and_modify_schedules
{
    public function up()
    {
        \DBUtil::create_table('lesson_schedule_students', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'lesson_schedule_id' => array('type' => 'int', 'constraint' => 11),
            'student_user_id' => array('type' => 'int', 'constraint' => 11),
        ), array('id'), true, 'InnoDB', null, array(
            array(
                'constraint' => 'fk_lss_lesson_schedule',
                'key' => 'lesson_schedule_id',
                'reference' => array(
                    'table' => 'lesson_schedules',
                    'column' => 'id',
                ),
                'on_delete' => 'CASCADE',
            ),
            array(
                'constraint' => 'fk_lss_student_user',
                'key' => 'student_user_id',
                'reference' => array(
                    'table' => 'users',
                    'column' => 'id',
                ),
                'on_delete' => 'CASCADE',
            ),
        ));

        \DBUtil::modify_fields('lesson_schedules', array(
            'student_user_id' => array(
                'type' => 'int',
                'constraint' => 11,
                'null' => true,
            ),
        ));

        // 既存データのバックフィル: lesson_schedules の student_user_id を lesson_schedule_students に投入
        $rows = \DB::select('id', 'student_user_id')
            ->from('lesson_schedules')
            ->where('student_user_id', '!=', null)
            ->execute()
            ->as_array();
        foreach ($rows as $row) {
            \DB::insert('lesson_schedule_students')->set(array(
                'lesson_schedule_id' => $row['id'],
                'student_user_id'    => $row['student_user_id'],
            ))->execute();
        }
    }

    public function down()
    {
        \DBUtil::drop_table('lesson_schedule_students');

        \DBUtil::modify_fields('lesson_schedules', array(
            'student_user_id' => array(
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
            ),
        ));
    }
}
