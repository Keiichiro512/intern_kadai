<?php

class Model_Student_Subject extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'student_user_id',
        'subject_id',
    );

    protected static $_belongs_to = array(
        'student_user' => array(
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
}

