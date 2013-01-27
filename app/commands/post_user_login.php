<?php

namespace commands;

use \interfaces\Command as Command;
use \models\User as User;
use \models\Config as Config;

class post_user_login implements Command {
	
	public function execute(){
		if(isset($_POST['username']) && isset($_POST['password'])){
			$user = User::login($_POST['username'], $_POST['password']);
			if($user){
				$_SESSION['user'] = $user;
			}
		}
		echo json_encode(array('referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
	}
	
}