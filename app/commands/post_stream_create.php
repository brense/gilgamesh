<?php

namespace commands;

use \interfaces\Command as Command;

class post_stream_create implements Command {
	
	public function execute(){
		set_time_limit(0);
		for($i = 0; $i < 10; $i++){
			file_put_contents('test.txt', 'test' . implode('&', $_POST));
			echo implode('&', $_POST);
			echo 'test';
			sleep(1);
		}
	}
	
}