<?php

namespace request;

use \models\Config as Config;

class Request {
	
	private $_method;
	private $_url;
	private $_parameters = array();
	private $_body;
	private $_oauth;
	private $_headers;
	private $_content_type;
	private $_ssl = false;
	private $_user_agent;
	private $_http_auth;
	
	private $_response;
	private $_info;
	
	public function __construct($method, $url, Array $parameters = array(), $body = null){
		$this->_method = strtoupper($method);
		$this->_url = $url;
		$this->_parameters = $parameters;
		if(isset($body)){
			$this->_body = $body;
		} else if($method == 'POST' && count($parameters) > 0){
			$this->_body = http_build_query($parameters);
		}
	}
		
	public function execute(){
		// check if we are connected to the internet
		$info = parse_url($this->_url);
		// TODO: make this more dynamic
		if($info['host'] == @gethostbyname($info['host']) && $info['host'] !== '127.0.0.1' && $info['host'] !== '83.84.133.218'){
			return false;
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_URL, $this->_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// TODO: not needed? curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Accept: ' . $this->_acceptType));
		if($this->_ssl){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		if(isset($this->_user_agent)){
			curl_setopt($ch, CURLOPT_USERAGENT, $this->_user_agent);
		}
		if(isset($this->_headers)){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
		}
		if(isset($this->_http_auth)){
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        	curl_setopt($ch, CURLOPT_USERPWD, $this->_http_auth);
		}
		
		switch($this->_method){
			case 'GET':
				curl_setopt($ch, CURLOPT_URL, $this->_url . http_build_query($this->_parameters));
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($this->_body)));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_body);
				curl_setopt($ch, CURLOPT_POST, true);
				break;
			case 'PUT':
				$put_file = Config::$file_root . 'temp' . DIRECTORY_SEPARATOR . 'put' . md5($this) . '.txt';
				file_put_contents($put_file, '');
				$fh = fopen($put_file, 'rw');
				fwrite($fh, $this->_body);
				rewind($fh);
				curl_setopt($ch, CURLOPT_INFILE, $fh);
				curl_setopt($ch, CURLOPT_INFILESIZE, strlen($this->_body));
				curl_setopt($ch, CURLOPT_PUT, true);
				break;
			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			default:
				throw new \Exception('Request type ' . $this->_method . ' not supported');
				break;
		}
		
		$this->_response = curl_exec($ch);
		$this->_info = curl_getinfo($ch);
		
		unset($ch);
		
		if(isset($fh) && isset($file)){
			fclose($fh);
			unlink($file);
		}
	}
	
	public function success(){
		if($this->_info['http_code'] == '200'){
			return true;
		} else {
			return false;
		}
	}
	
	public static function __callStatic($method, $parameters){
		// parameters are: $url, $parameters, $body
		$request = new self($method, $parameters[0], $parameters[1], $parameters[2]);
		$request->execute();
	}
	
	public function __get($property){
		if(property_exists($this, '_' . $property)){
			return $this->{'_' . $property};
		} else if(isset($this->_info[$property])){
			return $this->_info[$property];
		}
	}
	
	public function __set($property, $value){
		if(property_exists($this, '_' . $property)){
			$this->{'_' . $property} = $value;
		}
	}
	
	public function __toString(){
		return $this->_method . $this->_url . implode('', $this->_parameters);
	}

}