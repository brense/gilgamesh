<?php

namespace oauth;

use \request\Request as Request;

class Oauth {
	
	protected $_consumer_key;
	protected $_consumer_secret;
	protected $_callback;
	
	protected $_access_token;
	protected $_access_token_secret;
	
	protected $_version;
	
	public function __construct($consumer_key, $consumer_secret, $callback, $version = '1.0'){
		$this->_consumer_key = $consumer_key;
		$this->_consumer_secret = $consumer_secret;
		$this->_callback = $callback;
		$this->_version = $version;
	}
	
	public function setAccessToken($access_token){
		$this->_access_token = $access_token;
	}
	
	public function setAccessTokenSecret($access_token_secret){
		$this->_access_token_secret = $access_token_secret;
	}
	
	public function findAccessToken(){
		if(!isset($this->_access_token) || strlen($this->_access_token) == 0){
			return false;
		} else {
			return true;
		}
	}
	
	public function signRequest(Request &$request, $includeCallback = false){
		// merge request parameters
		$oauth = $this->getOauthParameters($includeCallback);
		if(is_array($request->parameters)){
			$merged_params = array_merge($oauth, $request->parameters);
		} else {
			$merged_params = $oauth;
		}
		
		// create oauth base string
		$parameter_string = $this->buildParameterString($merged_params);
		$base_string = strtoupper($request->method) . '&' . rawurlencode($request->url) . '&' . rawurlencode($parameter_string);
		
		// create signature and build authorization headers
		$this->generateOauthSignature($oauth, $base_string);
		$header = $this->buildAuthorizationHeader($oauth);
		
		$request->oauth = $oauth;
		// TODO: no headers for oauth 2.0 request?
		$request->headers = $header;
	}
	
	private function buildParameterString(Array $parameters = array()){
		$encoded_params = array();
		foreach($parameters as $k => $v){
			$encoded_params[rawurlencode($k)] = rawurlencode($k) . '=' . rawurlencode($v);
		}
		ksort($encoded_params);
		return implode('&', $encoded_params);
	}
	
	private function getOauthParameters(){
		$oauth = array(
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => md5(time()),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_version' => $this->_version
		);
		if(isset($this->_access_token) && strlen($this->_access_token) > 0){
			$oauth['oauth_token'] = $this->_access_token;
		}
		// TODO: check if callback needs to be included or oauth verifier???
		return $oauth;
	}
	
	private function generateOauthSignature(Array &$oauth, $base_string){
		$signing_key = rawurlencode($this->_consumer_secret) . '&';
		if(isset($this->_access_token_secret) && strlen($this->_access_token_secret) > 0){
			$signing_key .= rawurlencode($this->_access_token_secret);
		}
		$oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
	}
	
	private function buildAuthorizationHeader(Array $oauth){
		$r = 'Authorization: OAuth ';
		$values = array();
		foreach($oauth as $key => $value){
			$values[] = $key . '="' . rawurlencode($value) . '"';
		}
		$r .= implode(', ', $values);
		return array($r, 'Expect:');
	}
	
	private function createQueryString(Array $parameters){
		$p = array();
		foreach($parameters as $k => $v){
			$p[] = $k . '=' . $v;
		}
		return implode('&', $p);
	}
	
}