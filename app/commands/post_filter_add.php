<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Filter as Filter;
use \models\Event as Event;
use \models\Config as Config;

class post_filter_add implements Command {
	
	public function execute(){
		
		$event_id = Event::getID();
		$referer = str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER']);
		
		$check = Filter::read(array('event' => $event_id, 'type' => $_POST['type'], 'value' => $_POST['value']));
		if(!$check){
			switch($_POST['type']){
				case 'location':
					$name = 'Locatie';
					break;
				case 'term':
					$name = 'Hashtag';
					break;
				case 'period':
					$name = 'Periode';
					break;
				case 'source':
					$name = 'Bron';
					break;
			}
			
			$filter = new Filter();
			$filter->event = $event_id;
			$filter->type = $_POST['type'];
			$filter->name = $name;
			$filter->value = $_POST['value'];
			
			if($filter->save()){
				echo json_encode(array('success' => true, 'referer' => $referer));
			} else {
				echo json_encode(array('success' => false, 'referer' => $referer));
			}
		} else {
			if($check instanceof Filter){
				$filter = $check;
			}
			echo json_encode(array('success' => true, 'referer' => $referer));
		}
		$_SESSION['filters'][$event_id][$filter->id] = 1;
	}
	
}