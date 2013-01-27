<?php

namespace commands;

use \interfaces\Command as Command;
use \models\User as User;
use \models\Config as Config;

class post_user_logout implements Command {
	
	public function execute(){
		unset($_SESSION['user']);
		echo json_encode(array('referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
	}
	
}