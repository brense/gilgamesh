<?php

namespace storage;

abstract class Database {
	
	protected $_handle;
	protected $_mapper;
	protected $_search;
	protected $_table;
	
	public function __construct(Array $config){
		// set search engine
		if(isset($config['search_engine'])){
			$this->_search = $config['search_engine'];
		}
		
		// set mapper
		if(isset($config['mapper'])){
			$mapper = 'storage\\' . $config['mapper'];
		} else {
			$mapper = 'storage\\' . $this->_defaultMapper;
		}
		$this->_mapper = new $mapper($this);
		
		// set table
		if(isset($config['table'])){
			$this->_table = $config['table'];
		}
	}
	
	abstract public function query($obj, $return = null);
	
	abstract public function search($query, $sort = null, $limit = null, $return = null);
	
	abstract public function bulkCreate(Array $docs);
	
	abstract public function bulkUpdate(Array $docs, Array $criteria);
	
	abstract public function bulkDelete(Array $criteria);
	
	abstract public function create(Array $values);
	
	abstract public function read(Array $criteria = array(), Array $columns = array(), Array $sort = array(), Array $limit = array());
	
	abstract public function update(Array $values, Array $criteria);
	
	abstract public function delete(Array $criteria);
	
	public function __get($property){
		switch($property){
			case 'mapper':
				return $this->_mapper;
				break;
			case 'table':
				return $this->_table;
				break;
		}
	}
	
	public function __set($property, $value){
		switch($property){
			case 'mapper':
				$this->_mapper = $value;
				break;
			case 'table':
				$this->_table = $value;
				break;
		}
	}
	
}