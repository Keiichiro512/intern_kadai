<?php

class Model_Grade extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'grade_name',
    );

    protected static $_has_many = array(
        'students' => array(
            'model_to' => 'Model_Student',
            'key_from' => 'id',
            'key_to' => 'grade_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );
}

