<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Event as Event;
use \models\Config as Config;
use \controllers\PdoController as PdoController;

class post_event_delete implements Command {
	
	public function execute(){
		$event = new Event();
		$event->id = $_POST['id'];
		$event->delete();
		
		$db = new PdoController(Config::$db['sql']);
		$db->query('DELETE FROM `models_message` WHERE `event` = :event', array(':event' => $_POST['id']));
		
		echo json_encode(array('referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
	}
	
}