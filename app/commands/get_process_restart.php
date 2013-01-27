<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Process as Process;

class get_process_restart implements Command {
	
	public function execute(){
		if(isset($_GET['id'])){
			// start selected process
			$process = Process::find_by_id($_GET['id']);
			if($process instanceof Process){
				$process->restart(30);
			}
		} else {
			// start all processes
			$processes = Process::read(array());
			if($processes instanceof Process){
				$processes = array($processes);
			}
			if(is_array($processes)){
				foreach($processes as $process){
					if($process instanceof Process){
						$process->restart(30);
					}
				}
			}
		}
	}
	
}