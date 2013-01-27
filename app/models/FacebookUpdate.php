<?php

namespace models;

use \storage\Storage as Storage;
use \services\FacebookService as FacebookService;

class FacebookUpdate extends Update {
	
	protected $_message;
	protected $_created_time;
	protected $_message_tags = array();
	protected $_from;
	protected $_shares;
	protected $_likes;
	protected $_updated_time;
	protected $_actions;
	protected $_privacy;
	
	public function populate(){
		$this->_text = $this->_message;
		$this->_created = strtotime($this->_created_time);
		
		$profile = new FacebookProfile();
		foreach($this->_from as $k => $v){
			if(property_exists($profile, $k)){
				$profile->$k = $v;
			}
		}
		$profile->populate();
		$this->_profile = $profile;
		
		foreach($this->_message_tags as $mention){
			$profile = new FacebookProfile();
			$profile->id = $mention->id;
			$profile->username = $mention->name;
			$profile->full_name = $mention->name;
			$this->_mentions[] = $profile;
		}
	}
	
	public function reply($params){
		if(isset($params['id']) && isset($params['reply']) && strlen($params['reply']) > 0){
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode($params['id']);
			$update = $couch->query($obj, 'map');
			
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode('service/facebook');
			$service = $couch->query($obj, 'json');
			
			$fb = new FacebookService((array)$service->authentication);
			$fb->reply($update, $params['reply']);
			
			echo json_encode(array('state' => 'reply', 'id' => $params['id']));
		}
	}
	
	public function retweet($params){
		if(isset($params['id'])){
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode($params['id']);
			$update = $couch->query($obj, 'json');
			$updateMapped = $couch->query($obj, 'map');
			$update->rt = true;
			$obj->method = 'POST';
			$obj->url = '_bulk_docs';
			$obj->body = json_encode(array('docs' => array($update)));
			$response = $couch->query($obj, 'json');
			
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode('service/facebook');
			$service = $couch->query($obj, 'json');
			
			$fb = new FacebookService((array)$service->authentication);
			if($params['original'] == $params['reply']){
				$fb->reply($updateMapped, $params['reply']);
			} else {
				$fb->share($updateMapped);
			}
			
			echo json_encode(array('state' => 'retweet', 'id' => $params['id']));
		}
	}
	
	public function favorite($params){
		if(isset($params['id'])){
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode($params['id']);
			$update = $couch->query($obj, 'json');
			
			if(isset($params['unfav'])){
				$update->fav = false;
			} else {
				$update->fav = true;
			}
			$obj = new \StdClass();
			$obj->method = 'POST';
			$obj->url = '_bulk_docs';
			$obj->body = json_encode(array('docs' => array($update)));
			$response = $couch->query($obj, 'json');
		}
	}
	
}