<?php

class Controller_Parent extends Controller_Base
{
    public $template = 'template';

    public function action_home()
    {
        $this->template->title = '保護者ホーム';

        $this->template->content = View::forge('parent/home', array(
            'username' => Session::get('username', ''),
        ));
    }
}

