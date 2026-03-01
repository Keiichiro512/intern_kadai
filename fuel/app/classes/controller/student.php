<?php

class Controller_Student extends Controller_Base
{
    public $template = 'template';

    public function action_home()
    {
        $this->template->title = '生徒ホーム';

        $this->template->content = View::forge('student/home', array(
            'username' => Session::get('username', ''),
        ));
    }
}

