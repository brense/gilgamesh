<?php

namespace storage;

use \models\Saveable as Saveable;
use \models\Config as Config;

abstract class Mapper {
	
	protected $_object;
	protected $_db;
	
	public function __construct(Database $db, Saveable $obj = null){
		$this->_db = $db;
		if(isset($obj)){
			$this->_object = $obj;
		}
	}
	
	abstract public function map($results);
	
	abstract public function create(Saveable $obj);
	
	abstract public function read(Array $criteria = array(), $sort = array(), $limit = array());
	
	abstract public function update(Saveable $obj);
	
	abstract public function delete(Saveable $obj);
	
	abstract public function flatten($objects);
	
	abstract public function lastInsertId($response);
	
	public function __get($property){
		switch($property){
			case 'object':
				return $this->_object;
				break;
		}
	}
	
	public function __set($property, $value){
		switch($property){
			case 'object':
				$this->_object = $value;
				break;
		}
	}
	
}