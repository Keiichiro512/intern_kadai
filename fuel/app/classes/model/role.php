<?php

class Model_Role extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'role_name',
    );

    protected static $_has_many = array(
        'users' => array(
            'model_to' => 'Model_User',
            'key_from' => 'id',
            'key_to' => 'role_id',
            'cascade_save' => false,
            'cascade_delete' => false,
        ),
    );
}

