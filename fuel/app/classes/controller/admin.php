<?php

class Controller_Admin extends Controller_Template
{
    public $template = 'template';

    public function action_home()
    {
        $this->template->title       = '塾長ホーム';
        $this->template->style_sheet = 'admin.css';

        $this->template->content = View::forge('admin/home');
    }
}
