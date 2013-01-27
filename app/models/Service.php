<?php

namespace models;

use \Oauth as Oauth;

abstract class Service extends Model {
	
	protected $_oauth;
	
	protected $_oauth_version;
	protected $_prefix;
	
	protected $_consumer_key;
	protected $_consumer_secret;
	protected $_callback;
	
	protected $_access_token;
	protected $_access_token_secret;
	
	protected $_authorize_url;
	protected $_access_token_url;
	protected $_request_token_url;
	
	public function __construct(){
		$this->_oauth = new Oauth($this->_consumer_key, $this->_consumer_secret, $this->_callback, $this->_oauth_version, $this->_prefix);
		if(isset($this->_access_token) && isset($this->_access_token_secret)){
			$this->_oauth->setAccessToken($this->_access_token);
			$this->_oauth->setAccessTokenSecret($this->_access_token_secret);
		}
	}
	
	public function createRequest($method, $url, Array $parameters = array()){
		return $this->_oauth->createRequest($method, $url, $parameters);
	}
	
	public function makeRequest($method, $url, Array $parameters = array(), $returnType = 'json'){
		return $this->_oauth->makeRequest($method, $url, $parameters, $returnType);
	}
	
	public function signRequest(\Request &$request, $return = false, $includeCallback = false){
		return $this->_oauth->signRequest($request, $return, $includeCallback);
	}
	
	public function validateAccessToken(){
		// check if current token has expired
		if(isset($_SESSION[$this->_prefix]['expires']) && $_SESSION[$this->_prefix]['expires'] < time()){
			unset($_SESSION[$this->_prefix]);
			$this->authorize($this->_scope);
			return false;
		}
		
		// return true if access token is found
		if(isset($_SESSION[$this->_prefix]['access_token']) || (isset($this->_access_token) && strlen($this->_access_token) > 0)){
			if(isset($_SESSION[$this->_prefix]['access_token'])){
				$this->_oauth->setAccessToken($_SESSION[$this->_prefix]['access_token']);
			}
			if(isset($_SESSION[$this->_prefix]['access_token_secret'])){
				$this->_oauth->setAccessTokenSecret($_SESSION[$this->_prefix]['access_token_secret']);
			}
			return true;
		}
		
		// authorize app if no token is found
		if(!$this->_oauth->findAccessToken()){
			// handle oauth 1.0 flow
			if($this->_oauth_version == '1.0'){
				// request token and authorize app
				if(!isset($_GET['oauth_token']) && !isset($_GET['oauth_verifier'])){
					$this->requestToken();
					$this->authorize();
					return false;
				}
				// request access token
				else {
					if($_GET['oauth_token'] != $_SESSION[$this->_prefix]['token']){
						unset($_SESSION[$this->_prefix]['token'], $_SESSION[$this->_prefix]['token_secret']);
						return false;
					} else {
						$this->requestAccessToken();
						unset($_SESSION[$this->_prefix]['token'], $_SESSION[$this->_prefix]['token_secret']);
						return true;
					}
				}
			}
			// handle oauth 2.0 flow
			else {
				// authorize app
				if(!isset($_GET['state']) && !isset($_GET['code'])){
					$this->authorize($this->_scope);
					return false;
				}
				// request access token
				else {
					if($_GET['state'] != $_SESSION[$this->_prefix]['state']){
						unset($_SESSION[$this->_prefix]['state']);
						return false;
					} else {
						unset($_SESSION[$this->_prefix]['state']);
						$this->requestAccessToken();
						return true;
					}
				}
			}
		}
	}
	
	protected function requestToken($returnType = 'flat', Array $values = array('oauth_token', 'oauth_token_secret')){
		$request = $this->createRequest($method, $url, $parameters);
		$this->signRequest($request, false, true);
		$response = $this->makeRequest($request, $returnType);
		
		// get the correct parameters from the response
		$params = $this->getParametersFromResponse($response, $returnType);
		
		// add the token and token secret to the session
		if(isset($params[$values[0]]) && isset($params[$values[1]])){
			$_SESSION[$this->_prefix]['token'] = $params[$values[0]];
			$_SESSION[$this->_prefix]['token_secret'] = $params[$values[1]];
		}
		// throw exception if incorrect parameters were returned
		else {
			$s = '';
			foreach($params as $k => $v){$s = $k . '=' . $v;}
			throw new Exception('incorrect access token parameters returned: ' . implode('&', $s));
		}
	}
	
	protected function requestAccessToken($method = 'GET', Array $params = array(), $returnType = 'flat', Array $values = array('access_token', 'expires')){
		// add oauth verifier to parameters for oauth 1.0 request
		if($this->_oauth_version == '1.0'){
			$parameters = array('oauth_verifier' => $_GET['oauth_verifier']);
			$parameters = array_merge($parameters, $params);
		}
		// set parameters for oauth 2.0 request
		else {
			$parameters = array(
				'client_id' => $this->_consumer_key,
				'redirect_uri' => $this->_callback,
				'client_secret' => $this->_consumer_secret,
				'code' => $_GET['code']
			);
			$parameters = array_merge($parameters, $params);
		}
		
		// make the request
		$response = $this->makeRequest($method, $this->_access_token_url, $parameters, $returnType);
		
		// get the correct parameters from the response
		$params = $this->getParametersFromResponse($response, $returnType);
		
		// add the token to the session
		if(isset($params[$values[0]]) && isset($params[$values[1]])){
			if(isset($this->_request_token_url) && strlen($this->_request_token_url) > 0){
				$_SESSION[$this->_prefix]['access_token'] = $params[$values[0]];
				$_SESSION[$this->_prefix]['access_token_secret'] = $params[$values[1]];
			} else {
				$_SESSION[$this->_prefix]['access_token'] = $params[$values[0]];
				$_SESSION[$this->_prefix]['expires'] = time() + $params[$values[1]];
			}
		}
		// throw exception if incorrect parameters were returned
		else {
			$s = '';
			foreach($params as $k => $v){$s = $k . '=' . $v;}
			throw new Exception('incorrect access token parameters returned: ' . implode('&', $s));
		}
	}
	
	protected function authorize(Array $scope = array(), $scope_seperator = ',', $attach = null){
		// build authorize url for oauth 1.0 requests
		if($this->_oauth_version == '1.0'){
			$this->_authorize_url .= '?oauth_token=' . $_SESSION[$this->_prefix]['token'];
		}
		// build authorize url for oauth 2.0 requests
		else {
			$this->_authorize_url .= '?client_id=' . $this->_consumer_key . '&redirect_uri=' . $this->_callback;
			$state = md5(time() . mt_rand());
			$_SESSION[$this->_prefix]['state'] = $state;
			$this->_authorize_url .= '&state=' . $state . '&scope=' . implode($scope_seperator, $scope) . $attach;
		}
		// redirect
		header('Location: ' . $this->_authorize_url);exit;
	}
	
	private function getParametersFromResponse($response, $returnType){
		if($returnType != 'json'){
			$r = explode('&', $response);
			$params = array();
			foreach($r as $v){
				$param = explode('=', $v);
				$params[$param[0]] = $param[1];
			}
		} else {
			$params = $response;
		}
		return $params;
	}
	
}