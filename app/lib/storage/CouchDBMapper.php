<?php

namespace storage;

use \models\Saveable as Saveable;
use \models\Config as Config;

class CouchDBMapper extends Mapper {
	
	public function map($results){
		$json = json_decode($results);
		if($json){
			if(isset($json->rows)){
				$objects = array();
				foreach($json->rows as $result){
					$doc = $result;
					if(isset($result->doc)){
						$doc = $result->doc;
					} else if(isset($result->_source)){
						$doc = $result->_source;
					}
					
					$obj = $this->populate($doc, $result);
						
					$objects[] = $obj;
				}
				return $objects;
			} else {
				return $this->populate($json, $json);
			}
		}
	}
	
	public function create(Saveable $obj){
		$values = $this->flatten($obj);
		$this->_db->create($values);
	}
	
	public function read(Array $criteria = array(), $sort = array(), $limit = array()){
		return $this->_db->read($criteria, array(), $sort, $limit);
	}
	
	public function update(Saveable $obj){
		$values = $this->flatten($obj);
		$this->_db->update($values, array());
	}
	
	public function delete(Saveable $obj){
		$this->_db->delete(array('id' => $obj->id));
	}
	
	private function populate($doc, $result){
		if(isset($doc->type)){
			$model = '\models\\' . $doc->type;
			$obj = new $model();
		} else if(isset($this->_object)) {
			$obj = clone($this->_object);
		} else {
			$obj = json_decode('{}');
		}
		
		foreach($doc as $k => $v){
			$obj->$k = $v;
		}
		
		if(isset($doc->_id)){
			$obj->id = $doc->_id;
			$obj->rev = $doc->_rev;
		} else if(isset($result->_id)){
			$obj->id = $result->_id;
			$obj->rev = $result->_rev;
		}
		
		if(method_exists($obj, 'populate')){
			$obj->populate();
		}
		
		return $obj;
	}
	
	public function flatten($objects){
		if(is_array($objects)){
			foreach($objects as &$object){
				$object = $this->makeFlat($object);
			}
		} else {
			$objects = $this->makeFlat($objects);
		}
		return $objects;
	}
	
	public function lastInsertId($response){
		$json = json_decode($response);
		if(isset($json->id)){
			return $json->id;
		} else if(is_array($json) && isset($json[0]->id)){
			$ids = array();
			foreach($json as $id){
				if(isset($id->id)){
					$ids[] = $id->id;
				}
			}
			return $ids;
		}
	}
	
	private function makeFlat(Saveable $object){
		$type = @array_pop(@explode('\\', get_class($object)));
		$object = (object)$object->getProperties();
		$object->type = $type;
				
		return $object;
	}
	
}