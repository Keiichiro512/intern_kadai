<?php

class Model_Subject extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'subject_name',
    );

    protected static $_has_many = array(
        'student_subjects' => array(
            'model_to' => 'Model_Student_Subject',
            'key_from' => 'id',
            'key_to' => 'subject_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
        'lesson_schedules' => array(
            'model_to' => 'Model_Lesson_Schedule',
            'key_from' => 'id',
            'key_to' => 'subject_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );
}

