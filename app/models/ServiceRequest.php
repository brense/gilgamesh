<?php

namespace models;

use \request\Request as Request;
use \request\OauthRequest as OauthRequest;

class ServiceRequest extends Saveable {
	
	protected $_id;
	protected $_authentication;
	protected $_oauth_parameters;
	protected $_basic_parameters;
	protected $_methods;
	protected $_api_endpoint;
	protected $_ssl;
	
	private static $_database = 'services';
	
	public static function __callStatic($method, $parameters){
		$parameters['db'] = self::$_database;
		$parameters['obj'] = new self();
		return parent::__callStatic($method, $parameters);
	}
	
	public function populate(){
		
		foreach($this->_methods as &$m){
			foreach($m as &$v){
				foreach($v->params as &$val){
					if(substr($val, 0, 1) == '%' && substr($val, -1, 1) == '%'){
						if($v->authentication == 'basic'){
							$p = substr($val, 1, -1);
							if(isset($this->_basic_parameters->$p)){
								$val = $this->_basic_parameters->$p;
							}
						}
					}
				}
			}
		}
	}
	
	public function __call($use, $parameters){
		$reqs = array();
		foreach($this->_methods as $method => $obj){
			foreach($obj as $path => $o){
				if(in_array($use, $o->use)){
					$url = $this->_api_endpoint . $path;
					if($o->authentication == 'basic'){
						$req = new Request($method, $url, (array)$o->params);
					}
					if($o->authentication == 'oauth 1.0.' || $o->authentication == 'oauth 2.0'){
						$req = new OauthRequest($method, $url, (array)$o->params);
					}
					$reqs[] = $req;
				}
			}
		}
		return $reqs;
	}
	
}