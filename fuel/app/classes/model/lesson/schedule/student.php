<?php
namespace Model;

class Lesson_Schedule_Student extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'lesson_schedule_id',
        'student_user_id',
    );

    protected static $_table_name = 'lesson_schedule_students';

    protected static $_belongs_to = array(
        'lesson_schedule' => array(
            'key_from' => 'lesson_schedule_id',
            'model_to' => 'Model_Lesson_Schedule',
            'key_to' => 'id',
        ),
        'student' => array(
            'key_from' => 'student_user_id',
            'model_to' => 'Model_User',
            'key_to' => 'id',
        ),
    );
}
