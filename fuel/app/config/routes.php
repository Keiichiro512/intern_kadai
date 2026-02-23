<?php
return array(
	'_root_'  => 'welcome/index',  // The default route
	'_404_'   => 'welcome/404',    // The main 404 route
	'auth/login'  => 'auth/login',
	'admin/home'  => 'admin/home',
	'employee/request(/:name)?' => array('employee/request', 'name' => 'name'),
	'employee/hello(/:name)?' => array('employee/welcome', 'name' => 'hello'),
	'hello(/:name)?' => array('welcome/hello', 'name' => 'hello'),
);
