<?php

namespace storage;

class MySQLMapper extends Mapper {
	
	public function map($results){
		$objects = array();
		foreach($results as $result){
			$obj = clone($this->_object);
			foreach($result as $k => $v){
				$obj->$k = $v;
			}
			$objects[] = $obj;
		}
		return $objects;
	}
	
}