<?php

namespace services;

use \oauth\Oauth as Oauth;
use \models\Update as Update;
use \request\OauthRequest as OauthRequest;

abstract class Service {
	
	protected $_oauth;
	
	public function __construct(Array $config){
		if(isset($config['consumer_key']) && isset($config['consumer_secret']) && isset($config['callback'])){
			$this->_oauth = new Oauth($config['consumer_key'], $config['consumer_secret'], $config['callback']);
			if(isset($config['access_token']) && isset($config['access_token_secret'])){
				$this->_oauth->setAccessToken($config['access_token']);
				$this->_oauth->setAccessTokenSecret($config['access_token_secret']);
			} else {
				$this->getToken();
			}
		} else {
			throw new \Exception('invalid configuration for service');
		}
	}
	
	private function getToken(){
		// TODO: implement this
	}
	
	abstract public function reply(Update $update, $reply);
	
	abstract public function share(Update $update, $reply = null);
	
	abstract public function like(Update $update);
	
	abstract public function search($parameters);
	
	public function __get($property){
		if(property_exists($this, '_' . $property)){
			return $this->{'_' . $property};
		}
	}
	
	public function __set($property, $value){
		if(property_exists($this, '_' . $property)){
			$this->{'_' . $property} = $value;
		}
	}
	
}