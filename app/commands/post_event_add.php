<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Event as Event;
use \models\EventQueue as EventQueue;

class post_event_add implements Command {
	
	public function execute(){
		set_time_limit(0);
		
		$event = new Event();
		$event->name = $_POST['name'];
		$event->peak = '{}';
		$event->type = $_POST['type'];
		$event->sub_type = $_POST['sub_type'];
		$event->hashtags = '{}';
		$event->location = '{}';
		$event->total_messages = 0;
		$event->start = time();
		$event->end = 0;
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
		header('Location: ' . $_SERVER['HTTP_REFERER']);
	}
	
}