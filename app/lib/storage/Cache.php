<?php

namespace storage;

class Cache {
	
	private $_cache_path;
	private $_cache_time = 0;
	
	public function __construct(Array $config){
		if(isset($config['cache_path']) && isset($config['cache_time'])){
			$this->_cache_path = $config['cache_path'];
			$this->_cache_time = $config['cache_time'];
		} else {
			throw new \Exception('invallid configuration for cache');
		}
	}
	
	public function test($entry){
		if(file_exists($this->_cache_path . DIRECTORY_SEPARATOR . $entry)){
			if($this->_cache_time == 0 || filemtime($this->_cache_path . DIRECTORY_SEPARATOR . $entry) > time() - $this->_cache_time){
				return true;
			}
		}
		return false;
	}
	
	public function find($entry){
		if(file_exists($this->_cache_path . DIRECTORY_SEPARATOR . $entry)){
			return file_get_contents($this->_cache_path . DIRECTORY_SEPARATOR . $entry);
		}
		return false;
	}
	
	public function write($entry, $contents){
		file_put_contents($this->_cache_path . DIRECTORY_SEPARATOR . $entry, $contents);
	}
	
}