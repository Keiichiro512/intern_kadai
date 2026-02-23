<?php

class Model_Student extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'user_id',
        'grade_id',
        'created_at',
        'updated_at',
    );

    protected static $_belongs_to = array(
        'user' => array(
            'model_to' => 'Model_User',
            'key_from' => 'user_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'grade' => array(
            'model_to' => 'Model_Grade',
            'key_from' => 'grade_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );

    protected static $_has_many = array(
        'student_subjects' => array(
            'model_to' => 'Model_Student_Subject',
            'key_from' => 'user_id',
            'key_to' => 'student_user_id',
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

