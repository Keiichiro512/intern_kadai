<?php

class Model_Parent_Student_Relation extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'parent_user_id',
        'student_user_id',
    );

    protected static $_belongs_to = array(
        'parent' => array(
            'model_to' => 'Model_User',
            'key_from' => 'parent_user_id',
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
    );
}

