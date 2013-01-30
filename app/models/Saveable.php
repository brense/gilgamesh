<?php

namespace models;

use \storage\Storage as Storage;

abstract class Saveable extends Model {
	
	protected $_id;
	protected $_rev;
	
	public static function __callStatic($method, $parameters){
		$class = get_called_class();
		$obj = new $class();
		
		// "find all" method
		if($method == 'find_all'){
			return $obj->read();
		}
		// "find by" method
		else if(substr($method, 0, 8) == 'find_by_' && isset($parameters[0])){
			$criteria = array(substr($method, 8) => $parameters[0]);
			$results = $obj->read($criteria);
			if(is_array($results) || $results instanceof Saveable){
				return $results;
			} else {
				return $obj;
			}
		}
		// "find" method
		else if($method == 'find'){
			$criteria = array();
			if(isset($parameters[0]) && is_array($parameters[0])){
				$criteria = $parameters[0];
			}
			$sort = array();
			if(isset($parameters[1]) && is_array($parameters[1])){
				$sort = $parameters[1];
			}
			$limit = array();
			if(isset($parameters[2]) && is_array($parameters[2])){
				$limit = $parameters[2];
			}
			return $obj->read($criteria, $sort, $limit);
		}
	}
	
	public function save(){
		if($this->id > 0){
			return $this->update();
		} else {
			return $this->create();
		}
	}
	
	private function create(){
		return $this->db()->mapper->create($this);
	}
	
	private function read(Array $criteria = array(), $sort = array(), $limit = array()){
		$results = $this->db()->mapper->read($criteria, $sort, $limit);
		
		if(is_array($results) && count($results) > 1){
			return $results;
		} else if(is_array($results) && count($results) == 1 && isset($results[0])) {
			return $results[0];
		} else if($results instanceof Saveable){
			return $results;
		} else {
			return false;
		}
	}
	
	private function update(){
		return $this->db()->mapper->update($this);
	}
	
	public function delete(){
		return $this->db()->mapper->delete($this);
	}
	
	private function db(){
		$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
		$db->table = $this->_table;
		return $db;
	}
	
	public function getProperties(){
		$arr = array();
		foreach($this as $k => $v){
			if(is_object($v)){
				$v = json_encode($v);
			}
			$arr[substr($k, 1)] = $v;
		}
		return $arr;
	}
	
	public function populate(){
		return true;
	}
	
	public function __toString(){
		$arr = array('_id' => $this->_id, '_rev' => $this->_rev);
		return json_encode($arr);
	}
	
}