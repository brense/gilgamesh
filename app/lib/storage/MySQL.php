<?php

namespace storage;

class MySQL extends Database {
	
	private static $_handles;
	
	protected function __construct(Array $config){
		parent::__construct($config);
		$dsn = 'mysql:dbname=' . $config['name'] . ';host=' . $config['host'];
		$this->_handle = new \PDO($dsn, $config['user'], $config['password']);
	}
	
	public static function connect(Array $config){
		if(isset($config['name']) && isset($config['user']) && isset($config['password']) && isset($config['host'])){
			// allows connecting to different databases while ensuring only one connection to each database
			if(empty(self::$_handles[$config['name']])){
				self::$_handles[$config['name']] = new self($config);
			}
			return self::$_handles[$config['name']];
		} else {
			throw new \Exception('incorrect configuration parameters for MySQL');
		}
	}
	
	public function query($query, Array $params = array(), $return = 'none'){
		// TODO: fix query caching with new Cache class
		if($return == 'fetchAll' && Config::$query_caching){
			$cache_file = Config::$file_root . '\\cache\\' . md5($query . serialize($params)) . '.txt';
			if(file_exists($cache_file)){
				$contents = file_get_contents($cache_file);
				$json = json_decode($contents);
				if($json->cachetime > time()){
					return $json->results;
				} else {
					unlink($cache_file);
				}
			}
		}
		$statement = self::$_handle->prepare($query);
		$statement->execute($params);
		switch($return){
			case 'fetchAll':
				$results = $statement->fetchAll(\PDO::FETCH_ASSOC);
				if(Config::$query_caching){
					$contents = array();
					$contents['cachetime'] = time() + Config::$query_cachetime;
					$contents['results'] = $results;
					file_put_contents($cache_file, json_encode($contents));
				}
				return $results;
				break;
			case 'lastInsertId':
				return self::$_handle->lastInsertId();
				break;
			default:
				return true;
				break;
		}
	}
	
	public function create(Array $values){
		if(count($values) > 0){
			$params = array();		
			$cols = array();
			foreach($values as $key => $value){
				$params[':' . $key] = $value;
				$cols[] = '`' . $key . '`';
				$vals[] = ':' . $key;
			}
			$query = 'INSERT INTO ' . $this->_table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
			return $this->query($query, $params, 'lastInsertId');
		} else {
			return false;
		}
	}
	
	public function read(Array $criteria = array(), Array $columns = array(), $sort = null, $limit = null){
		if(count($columns) == 0){
			$select = '*';
		} else {
			$select = '`' . implode('`, `', $columns) . '`';
		}
		$params = array();
		if(count($criteria) == 0){
			$where = '';
		} else {
			foreach($criteria as $key => $value){
				$params[':' . $key] = $value;
				$crits[] = '`' . $key . '` = :' . $key;
			}
			$where = 'WHERE ' . implode(' AND ', $crits);
		}
		$query = 'SELECT ' . $select . ' FROM ' . $this->_table . ' ' . $where . ' ' . $sort .  ' ' . $limit;
		return $this->query($query, $params, 'fetchAll');
	}
	
	public function update(Array $values, Array $criteria){
		if(count($values) > 0){
			$params = array();
			if(count($criteria) == 0){
				$where = '';
			} else {
				foreach($criteria as $key => $value){
					$params[':' . $key] = $value;
					$crits[] = '`' . $key . '` = :' . $key;
				}
				$where = ' WHERE ' . implode(' AND ', $crits);
			}
			$cols = array();
			foreach($values as $key => $value){
				$params[':' . $key] = $value;
				$cols[] = '`' . $key . '` = :' . $key;
			}
			$query = 'UPDATE ' . $this->_table . ' SET ' . implode(', ', $cols) . $where;
			$results = $this->query($query, $params);
			if(isset($this->_mapper)){
				return $this->_mapper->map($results);
			} else {
				return $results;
			}
		} else {
			return false;
		}
	}
	
	public function delete(Array $criteria){
		$params = array();
		if(count($criteria) == 0){
			$where = '';
		} else {
			foreach($criteria as $key => $value){
				$params[':' . $key] = $value;
				$crits[] = '`' . $key . '` = :' . $key;
			}
			$where = ' WHERE ' . implode(' AND ', $crits);
		}
		$query = 'DELETE FROM ' . $this->_table . $where;
		return $this->query($query, $params);
	}
	
}