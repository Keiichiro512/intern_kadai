<?php

class Model_Report extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'lesson_schedule_id',
        'unit_name',
        'homework_achievement_rate',
        'homework_accuracy_rate',
        'lesson_report',
        'next_homework',
        'parent_message',
        'created_at',
        'updated_at',
    );

    protected static $_belongs_to = array(
        'lesson_schedule' => array(
            'model_to' => 'Model_Lesson_Schedule',
            'key_from' => 'lesson_schedule_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );

    protected static $_has_many = array(
        'homeworks' => array(
            'model_to' => 'Model_Homework',
            'key_from' => 'id',
            'key_to' => 'report_id',
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

