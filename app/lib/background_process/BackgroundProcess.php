<?php

namespace background_process;

class BackgroundProcess {
	
	private $_file_root;
	private $_php_exec;
	
	private $_script;
	private $_error_file;
	private $_output_file;
	private $_restart_file;
	private $_stop = false;
	
	public function __construct($script, $file_root, $php_exec = null){
		$path = 'app' . DIRECTORY_SEPARATOR . 'processes' . DIRECTORY_SEPARATOR;
		if(file_exists($file_root . $path . $script)){
			$this->_script = $path . $script;
			$this->_error_file = $path . 'errors' . DIRECTORY_SEPARATOR . $script;
			$this->_output_file = $path . 'output' . DIRECTORY_SEPARATOR . $script;
			$this->_restart_file = $path . 'restart' . DIRECTORY_SEPARATOR . $script;
			
			$this->_file_root = $file_root;
			if(isset($php_exec)){
				$this->_php_exec = $php_exec;
			}
		} else {
			throw new \Exception('process script could not be found');
		}
	}
	
	public function execute(){
		if(file_exists($this->_file_root . $this->_restart_file)){
			return false;
		} else {
			$state = $this->createRestart(30);
			switch(DIRECTORY_SEPARATOR){
				case '/': // nix
					$pid = system('sh ' . $this->_file_root . $this->_script . ' > ' . $this->_file_root . $this->_output_file . ' 2>' . $this->_file_root . $this->_error_file . ' &');
					break;
				case '\\': // win
					$state = pclose(popen('start /B ' . $this->_php_exec . ' ' . $this->_file_root . $this->_script . ' > ' . $this->_file_root . $this->_output_file . ' 2> ' . $this->_file_root . $this->_error_file, 'r'));
					break;
			}
			return $state;
		}
	}
	
	public function restart($sleep){
		if(file_exists($this->_file_root . $this->_restart_file)){
			unlink($this->_file_root . $this->_restart_file);
		}
	}
	
	public function terminate(){
		file_put_contents($this->_file_root . $this->_restart_file, '');
	}
	
	public function sleep($start, $end){
		$sleep = 10 - ($end - $start);
		if($sleep < 0){
			$sleep = 0;
		}
		sleep($sleep);
	}
	
	private function createRestart($sleep){
		return file_put_contents($this->_file_root . $this->_restart_file, '{"restart":0,"sleep":' . $sleep . '}');
	}
	
	public function checkStop(){
		if(file_exists($this->_file_root . $this->_restart_file)){
			$json = json_decode(file_get_contents($this->_file_root . $this->_restart_file));
			if($json){
				return true;
			}
		}
		$this->_stop = true;
		return false;
	}
	
	public function __get($property){
		return $this->{'_' . $property};
	}
	
}