<?php

class Model_Homework extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'report_id',
        'student_user_id',
        'content',
        'due_date',
        'is_completed',
        'completed_at',
        'created_at',
        'updated_at',
    );

    protected static $_belongs_to = array(
        'report' => array(
            'model_to' => 'Model_Report',
            'key_from' => 'report_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'student_user' => array(
            'model_to' => 'Model_User',
            'key_from' => 'student_user_id',
            'key_to' => 'id',
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

