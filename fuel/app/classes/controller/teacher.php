<?php

class Controller_Teacher extends Controller_Template
{
    public $template = 'template';

    public function action_home()
    {
        $this->template->title = '講師ホーム';

        $this->template->content = View::forge('teacher/home', array(
            'username' => Session::get('username', ''),
        ));
    }
}

