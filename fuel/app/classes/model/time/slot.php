<?php

class Model_Time_Slot extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'slot_name',
        'start_time',
        'end_time',
    );

    protected static $_has_many = array(
        'lesson_schedules' => array(
            'model_to' => 'Model_Lesson_Schedule',
            'key_from' => 'id',
            'key_to' => 'time_slot_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );
}
