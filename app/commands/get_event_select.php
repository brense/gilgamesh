<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Config as Config;

class get_event_select implements Command {
	
	public function execute(){
		if(isset($_GET['id'])){
			$_SESSION['event_id'] = $_GET['id'];
			echo json_encode(array('referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
		}
	}
	
}