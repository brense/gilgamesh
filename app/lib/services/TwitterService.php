<?php

namespace services;

use \models\TwitterUpdate as TwitterUpdate;
use \models\Update as Update;
use \request\OauthRequest as OauthRequest;

class TwitterService extends Service {
	
	protected $_oauth_version = '1.0';
	protected $_prefix = 'twitter';
	protected $_streaming_endpoint;
	
	protected $_authorize_url = 'https://api.twitter.com/oauth/authorize';
	protected $_access_token_url = 'https://api.twitter.com/oauth/access_token';
	protected $_request_token_url = 'https://api.twitter.com/oauth/request_token';
	
	public function reply(Update $update, $reply){
		$parameters = array(
			'status' => rawurlencode($reply),
			'in_reply_to_status_id' => @array_pop(@explode('/', $update->id))
		);
		$req = new OauthRequest('POST', 'https://api.twitter.com/1.1/statuses/update.json', $parameters);
		$req->sign($this->_oauth, array('include_callback' => true));
		$req->execute();
	}
	
	public function share(Update $update, $reply = null){
		if(isset($reply)){
			$this->reply($update, $reply);
		} else {
			$req = new OauthRequest('POST', 'https://api.twitter.com/1.1/statuses/retweet/' . @array_pop(@explode('/', $update->id)) . '.json');
			$req->sign($this->_oauth, array('include_callback' => true));
			$req->execute();
		}
	}
	
	public function like(Update $update){
		$parameters = array(
			'id' => $update->id
		);
		$req = new OauthRequest('POST', 'https://api.twitter.com/1.1/favorites/create.json', $parameters);
		$req->sign($this->_oauth, array('include_callback' => true));
		$req->execute();
	}
	
}