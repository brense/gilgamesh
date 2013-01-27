<?php

namespace models;

use \models\Config as Config;

class Process extends Model {
	
	protected $_script;
	protected $_error_file;
	protected $_output_file;
	protected $_restart_file;
	protected $_status_file;
	
	public function construct($config_file){
		$json = json_decode(file_get_contents($config_file));
		if(isset($json->script) && isset($json->error_file) && isset($json->output_file) && isset($json->restart_file) && isset($json->status_file)){
			$this->_script = $json->script;
			$this->_error_file = $json->error_file;
			$this->_output_file = $json->output_file;
			$this->_restart_file = $json->restart_file;
			$this->_status_file = $json->status_file;
		}
	}
	
	public function start($system){
		if($this->checkStatus){
			switch($system){
				case 'nix':
					$pid = system('sh ' . Config::$file_root . $this->_script . ' > ' . Config::$file_root . $this->_output_file . ' 2>' . Config::$file_root . $this->_error_file . ' &');
					break;
				case 'win':
					pclose(popen('start /B ' . PHP_EXEC . ' ' . Config::$file_root . $this->_script . ' > ' . Config::$file_root . $this->_output_file . ' 2> ' . Config::$file_root . $this->_error_file, 'r'));
					break;
			}
		}
	}
	
	public function setStatus($status){
		file_put_contents(Config::$file_root . $this->_status_file, $status);
	}
	
	public function checkStatus(){
		if(file_exists(Config::$file_root . $this->_status_file)){
			$status = file_get_contents(Config::$file_root . $this->_status_file);
			if($status == 'running'){
				return false;
			}
		}
		return true;
	}
	
	public function sleep($start, $end){
		$sleep = 10 - ($end - $start);
		if($sleep < 0){
			$sleep = 0;
		}
		sleep($sleep);
	}
	
	public function restart($sleep){
		if($sleep == 0){
			$this->setStatus('stopped');
		}
		file_put_contents(Config::$file_root . $this->_restart_file, '{"restart":1,"sleep":' . $sleep . '}');
	}
	
	public function createRestart($sleep){
		file_put_contents(Config::$file_root . $this->_restart_file, '{"restart":0,"sleep":' . $sleep . '}');
	}
	
	public function checkRestart(&$sleep){
		$json = json_decode(file_get_contents(Config::$file_root . $this->_restart_file));
		if($json && $json->restart){
			$sleep = $json->sleep;
			return true;
		} else {
			return false;
		}
	}
	
}