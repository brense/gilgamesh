<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Event as Event;
use \models\EventQueue as EventQueue;
use \models\Config as Config;

class post_event_start implements Command {
	
	public function execute(){
		if(isset($_POST['id'])){
			$event = Event::find_by_id($_POST['id']);
			$event->start = time();
			$event->end = 0;
			$event->save();
		}
		echo json_encode(array('referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
	}
	
}