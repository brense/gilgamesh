<?php

namespace storage;

class Buffer {
	
	private $_buffer_path;
	
	public function __construct(Array $config){
		if(isset($config['buffer_path'])){
			$this->_buffer_path = $config['buffer_path'];
		} else {
			throw new \Exception('invallid configuration for buffer');
		}
	}
	
	public function writeBuffer($entry, $data){
		return file_put_contents($this->_buffer_path . DIRECTORY_SEPARATOR . $entry, $data);
	}
	
	public function readBuffer(){
		$entries = array();
		if($handle = opendir($this->_buffer_path)){
			while(false !== ($entry = readdir($handle))){
				if($entry != "." && $entry != ".." && $entry != '_notes'){
					$entries[] = file_get_contents($this->_buffer_path . DIRECTORY_SEPARATOR . $entry);
				}
			}
			closedir($handle);
			unset($handle);
		}
		return $entries;
	}
	
	public function emptyBuffer($entries){
		$counter = 0;
		foreach($entries as $entry){
			if(file_exists($this->_buffer_path . DIRECTORY_SEPARATOR . $entry . '.txt')){
				unlink($this->_buffer_path . DIRECTORY_SEPARATOR . $entry . '.txt');
				$counter++;
			}
		}
		return $counter;
	}
	
}