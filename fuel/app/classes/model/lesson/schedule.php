<?php

class Model_Lesson_Schedule extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'lesson_date',
        'time_slot_id',
        'teacher_user_id',
        'student_user_id',
        'subject_id',
        'created_at',
        'updated_at',
    );

    protected static $_belongs_to = array(
        // time_slot は Model_Time_Slot 読み込みエラー対策のため一時無効化（time_slot_id はプロパティで参照可）
        // 'time_slot' => array(
        //     'model_to' => 'Model_Time_Slot',
        //     'key_from' => 'time_slot_id',
        //     'key_to' => 'id',
        //     'cascade_save' => false,
        //     'cascade_delete' => false,
        // ),
        'teacher' => array(
            'model_to' => 'Model_User',
            'key_from' => 'teacher_user_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'student' => array(
            'model_to' => 'Model_User',
            'key_from' => 'student_user_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'subject' => array(
            'model_to' => 'Model_Subject',
            'key_from' => 'subject_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );

    protected static $_has_one = array(
        'report' => array(
            'model_to' => 'Model_Report',
            'key_from' => 'id',
            'key_to' => 'lesson_schedule_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );

    protected static $_observers = array(
        'Orm\\Observer_CreatedAt' => array(
            'events' => array('before_insert'),
            'property' => 'created_at',
            'mysql_timestamp' => true,
        ),
        'Orm\\Observer_UpdatedAt' => array(
            'events' => array('before_save'),
            'property' => 'updated_at',
            'mysql_timestamp' => true,
        ),
    );
}

