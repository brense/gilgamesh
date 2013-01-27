<?php

namespace storage;

use \request\Request as Request;

// TODO: the sort parameter is ignored in all functions

class CouchDB extends Database {
	
	protected $_defaultMapper = 'CouchDBMapper';
	protected $_ssl = true;
		
	public function __construct(Array $config){
		if(isset($config['host']) && isset($config['db'])){
			parent::__construct($config);
			$auth = '';
			if(isset($config['user']) && strlen($config['user']) > 0 && isset($config['password']) && strlen($config['password']) > 0){
				$auth = $config['user'] . ':' . $config['password'] . '@';
			}
			$protocol = 'http';
			if(isset($config['ssl'])){
				$this->_ssl = $config['ssl'];
				if($config['ssl']){
					$protocol = 'https';
				}
			}
			$this->_handle = $protocol . '://' . $auth . $config['host'] . '/' . $config['db'] . '/';
		} else {
			throw new \Exception('incorrect configuration parameters for CouchDB');
		}
	}
	
	private function execute($req, $return = null){
		if($this->_ssl){
			$req->ssl = true;
		}
		$req->execute();
		$response = $req->response;
		switch($return){
			case 'lastInsertId':
				return $this->_mapper->lastInsertId($response);
				break;
			case 'map':
				return $this->_mapper->map($response);
				break;
			case 'json':
				return json_decode($response);
				break;
			default:
				return $req->success();
				break;
		}
	}
	
	private function bulkQuery($docs){
		$obj = json_decode('{}');
		$obj->method = 'POST';
		$obj->url = '_bulk_docs';
		$obj->body = json_encode(array('docs' => $docs));
		return $this->query($obj, 'lastInsertId');
	}
	
	public function query($obj, $return = null){
		if(isset($obj->method) && isset($obj->url)){
			$body = '';
			if(isset($obj->body)){
				$body = $obj->body;
			}
			$parameters = array();
			if(isset($obj->parameters) && is_array($obj->parameters)){
				$parameters = $obj->parameters;
			}
			$req = new Request(strtoupper($obj->method), $this->_handle . $obj->url, $parameters, $body);
			return $this->execute($req, $return);
		} else {
			throw new \Exception('invalid query object');
		}
	}
	
	public function search($query, $sort = array(), $limit = array(), $return = array()){
		$response = $this->_search->query($query, $limit, $sort);
		if($return == 'map'){
			$json = json_decode($response);
			if($json && isset($json->rows)){
				return $this->_mapper->map(json_encode(array('rows' => $json->rows->hits)));
			} else {
				return array();
			}
		} else if($return == 'json'){
			return json_decode($response);
		} else {
			return $response;
		}
	}
	
	public function bulkCreate(Array $docs){
		return $this->bulkQuery($docs);
	}
	
	public function bulkUpdate(Array $docs, Array $criteria){
		foreach($docs as $k => &$doc){
			if(isset($criteria[$k]) && isset($criteria[$k]['_id']) && isset($criteria[$k]['_rev'])){
				$doc = array_merge($doc, $criteria[$k]);
			}
		}
		return $this->bulkQuery($docs);
	}
	
	public function bulkDelete(Array $criteria){
		$docs = array();
		foreach($criteria as $doc){
			if(isset($doc['_id']) && isset($doc['_delete'])){
				$docs[] = $doc;
			}
		}
		return $this->bulkQuery($docs);
	}
	
	public function create(Array $values){
		$obj = json_decode('{}');
		$obj->method = 'PUT';
		$obj->url = '';
		$obj->body = json_encode($values);
		return $this->query($obj, 'lastInsertId');
	}
	
	public function read(Array $criteria = array(), Array $columns = array(), Array $sort = array(), Array $limit = array()){
		$obj = json_decode('{}');
		$obj->method = 'GET';
		
		if(isset($criteria['key'])){
			$obj->url = rawurlencode($criteria['key']);
		} else if(count($criteria) <= 1){
			$c = '';
			if(count($criteria) == 0){
				$view = 'all';
			} else {
				foreach($criteria as $view => $v){
					if(is_array($v) && isset($v['from']) && isset($v['to'])){
						$c = '&startkey=' . $v['from'] . '&endkey=' . $v['to'];
					} else {
						if(strlen($v) == 0){
							$v = '""';
						}
						$c = '&key=' . $v;
					}
					break;
				}
			}
			$obj->url = '_design/' . $this->_table . '/_view/' . $view . '?include_docs=true&reduce=false&stale=ok&descending=true' . $c;
			if(count($limit) > 0 && isset($limit[0]) && isset($limit[1])){
				$obj->url .= '&skip=' . $limit[0] . '&limit=' . $limit[1];
			}
		}
		
		return $this->query($obj, 'map');
	}
	
	public function update(Array $values, Array $criteria){
		$obj = json_decode('{}');
		$obj->method = 'PUT';
		$obj->url = '';
		$obj->body = json_encode(array_merge($values, $criteria));
		return $this->query($obj);
	}
	
	public function delete(Array $criteria){
		$obj = json_decode('{}');
		$obj->method = 'DELETE';
		foreach($criteria as $k => $key){
			if($k == 'id'){
				$obj->url = $key;
				break;
			}
		}
		return $this->query($obj);
	}
	
}