<?php

namespace views;

use \models\Process as Process;

class ProcessListView extends AbstractView {
	
	protected $_template = 'process/list';
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		$models = Process::read(array());
		if($models instanceof Process){
			$models = array($models);
		}
		if(!is_array($models)){
			$models = array();
		}
		foreach($models as &$process){
			if($process instanceof Process){
				$process->script = @array_pop(@explode('\\', $process->script));
				$process->error_file = @array_pop(@explode('\\', $process->error_file));
			}
		}
		$this->_vars['processes'] = $models;
	}
	
}