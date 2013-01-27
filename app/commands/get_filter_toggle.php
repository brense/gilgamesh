<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Event as Event;
use \models\Config as Config;

class get_filter_toggle implements Command {
	
	public function execute(){
		$event_id = Event::getID();
		$toggle = '';
		if($_SESSION['filters'][$event_id][$_GET['id']] == 1){
			$_SESSION['filters'][$event_id][$_GET['id']] = 0;
			$toggle = 'deactivate';
		} else {
			$_SESSION['filters'][$event_id][$_GET['id']] = 1;
			$toggle = 'activate';
		}
		echo json_encode(array('toggle' => $toggle, 'referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
	}
	
}