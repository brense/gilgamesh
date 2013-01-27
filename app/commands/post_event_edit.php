<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Event as Event;
use \models\EventQueue as EventQueue;

class post_event_edit implements Command {
	
	public function execute(){
		set_time_limit(0);
		
		if(isset($_POST['id'])){
			$event = Event::find_by_id($_POST['id']);
			$event->name = $_POST['name'];
			$event->type = $_POST['type'];
			$event->sub_type = $_POST['sub_type'];
			$event->save();
			
			if(isset($_FILES['csv']['tmp_name'])){
				$fp = fopen($_FILES['csv']['tmp_name'], 'r');
				$counter = 0;
				while(($line = fgets($fp)) !== false){
					if($counter > 0){
						$items = explode(';', trim($line));
						$queue = new EventQueue();
						$queue->event = $event->id;
						$queue->foreign_id = $items[0];
						$queue->save();
					}
					$counter++;
				}
			}
		}
		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}
	
}