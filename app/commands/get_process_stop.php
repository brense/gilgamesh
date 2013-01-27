<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Config as Config;
use background_process\BackgroundProcess as BackgroundProcess;

class get_process_stop implements Command {
	
	public function execute(){
		if(isset($_GET['process'])){
			$process = new BackgroundProcess($_GET['process'], Config::$file_root, PHP_EXEC);
			$process->restart(0);
		} else {
			// TODO: stop all processes
		}
	}
	
}