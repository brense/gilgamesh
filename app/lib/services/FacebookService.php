<?php

namespace services;

class FacebookService extends Service {
	
	protected $_oauth_version = '2.0';
	protected $_prefix = 'facebook';
	
	protected $_authorize_url = 'https://www.facebook.com/dialog/oauth';
	protected $_access_token_url = 'https://graph.facebook.com/oauth/access_token';
	
	public function __construct(Array $config){
		if(isset($config['consumer_key']) && isset($config['consumer_secret']) && isset($config['callback'])){
			$this->_oauth = new Oauth($config['consumer_key'], $config['consumer_secret'], $config['callback'], '2.0');
		} else {
			throw new \Exception('invalid configuration for service');
		}
	}
	
	public function reply(Update $update, $reply){
		$parameters = array(
			'message' => $reply
		);
		$req = new OauthRequest('POST', 'https://graph.facebook.com/' . $update->id . '/comments', $parameters);
		$req->sign($this->_oauth);
		$req->execute();
	}
	
	public function share(Update $update, $reply = null){
		$parameters = array (
			'link' => 'http://www.facebook.com/' . $update->profile->name . 'posts/' . $update->id, // example: http://www.facebook.com/rense.bakker/posts/174252586046788
			'message' => $reply,
			'picture' => '',
			'name' => '',
			'caption' => '',
			'description' => ''
		);
		$req = new OauthRequest('POST', 'https://graph.facebook.com/' . $profile_id . '/likes', $parameters);
		$req->sign($this->_oauth);
		$req->execute();
	}
	
	public function like(Update $update){
		$req = new OauthRequest('POST', 'https://graph.facebook.com/' . $update->id . '/likes');
		$req->sign($this->_oauth);
		$req->execute();
	}
	
}