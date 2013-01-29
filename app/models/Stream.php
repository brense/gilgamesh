<?php

namespace models;

use \storage\Storage as Storage;

class Stream extends Saveable {
	
	protected $_parameters;
	protected $_path;
	protected $_service;
	protected $_method;
	protected $_ssl = false;
	
	protected $_table = 'stream';
	
	public function populate(){
		// get the service belonging to the stream
		$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
		$obj = new \StdClass();
		$obj->method = 'GET';
		$obj->url = urlencode($this->_service);
		$json = $db->query($obj, 'json');
		$service_class = 'services\\' . $json->service;
		$service = new $service_class((array)$json->authentication);
		foreach($json as $k => $v){
			if(property_exists($service, '_' . $k)){
				$service->$k = $v;
			}
		}
		$this->_service = $service;
	}
	
}