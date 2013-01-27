<?php

namespace storage;

use \request\Request as Request;

class ElasticSearch extends SearchEngine {
	
	protected $_ssl;
	
	public function __construct($config){
		if(isset($config['host'])){
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
			$this->_handle = $protocol . '://' . $auth . $config['host'] . '/' . $config['index'];
		} else {
			throw new \Exception('incorrect configuration parameters for ElasticSearch');
		}
	}
	
	public function query($query, $limit = null, $sort = null){
		if(isset($limit) && isset($limit[0])){
			$from = '&from=' . $limit[0];
		} else {
			$from = '';
		}
		if(isset($limit) && isset($limit[1])){
			$size = '&size=' . $limit[1];
		} else {
			$size = '';
		}
		if(isset($sort)){
			$sort = '&sort=' . $sort;
		} else {
			$sort = '';
		}
		$url = '/_search/?q=' . $query . $from . $size . $sort;
		$req = new Request('GET', $this->_handle . str_replace(' ', '%20', $url));
		if($this->_ssl){
			$req->ssl = true;
		}
		$req->execute();
		
		$response = json_decode($req->response);
		unset($req);
		if(isset($response->hits)){
			return json_encode(array('rows' => $response->hits));
		} else {
			return json_encode(array('rows' => array()));
		}
	}
	
}