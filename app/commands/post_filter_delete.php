<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Filter as Filter;
use \models\Event as Event;
use \models\Config as Config;

class post_filter_delete implements Command {
	
	public function execute(){
		
		$event_id = Event::getID();
		$referer = str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER']);
		
		if(isset($_POST['id']) && $_POST['id'] > 0){
			$filter = new Filter();
			$filter->id = $_POST['id'];
			if($filter->delete()){
				unset($_SESSION['filters'][$event_id][$filter->id]);
				echo json_encode(array('success' => true, 'referer' => $referer));
			} else {
				echo json_encode(array('success' => false, 'referer' => $referer));
			}
		}
	}
	
}