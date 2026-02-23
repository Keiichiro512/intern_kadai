<?php

class Model_User extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'role_id',
        'username',
        'password',
        'first_name',
        'last_name',
        'created_at',
        'updated_at',
    );

    protected static $_belongs_to = array(
        'role' => array(
            'model_to' => 'Model_Role',
            'key_from' => 'role_id',
            'key_to' => 'id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );

    protected static $_has_one = array(
        'student' => array(
            'model_to' => 'Model_Student',
            'key_from' => 'id',
            'key_to' => 'user_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );

    protected static $_has_many = array(
        'parent_relations' => array(
            'model_to' => 'Model_Parent_Student_Relation',
            'key_from' => 'id',
            'key_to' => 'parent_user_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'child_relations' => array(
            'model_to' => 'Model_Parent_Student_Relation',
            'key_from' => 'id',
            'key_to' => 'student_user_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'student_subjects' => array(
            'model_to' => 'Model_Student_Subject',
            'key_from' => 'id',
            'key_to' => 'student_user_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'teacher_schedules' => array(
            'model_to' => 'Model_Lesson_Schedule',
            'key_from' => 'id',
            'key_to' => 'teacher_user_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'student_schedules' => array(
            'model_to' => 'Model_Lesson_Schedule',
            'key_from' => 'id',
            'key_to' => 'student_user_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'homeworks' => array(
            'model_to' => 'Model_Homework',
            'key_from' => 'id',
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

