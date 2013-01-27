<?php

namespace models;

use \storage\Storage as Storage;
use \services\TwitterService as TwitterService;

class TwitterUpdate extends Update {
	
	protected $_id_str;
	protected $_user = array();
	protected $_entities;
	protected $_coordinates;
	protected $_place;
	protected $_retweet_count;
	protected $_in_reply_to_status_id_str;
	protected $_in_reply_to_user_id_str;
	protected $_in_reply_to_screen_name;
	protected $_created_at;
	protected $_time_ago;
	protected $_retweeted;
	protected $_rt;
	
	public function populate(){
		$this->_created = strtotime($this->_created_at);
		$this->_geolocation = $this->_coordinates;
		
		$profile = new TwitterProfile();
		foreach($this->_user as $k => $v){
			if(property_exists($profile, '_' . $k)){
				$profile->$k = $v;
			}
		}
		$profile->populate();
		$this->_profile = $profile;
		
		if(isset($this->_entities->hashtags)){
			foreach($this->_entities->hashtags as $hashtag){
				$this->_labels[] = $hashtag->text;
			}
		}
		
		if(isset($this->_entities->user_mentions)){
			foreach($this->_entities->user_mentions as $mention){
				$profile = new TwitterProfile();
				$profile->id = $mention->id_str;
				$profile->username = $mention->screen_name;
				$profile->full_name = $mention->name;
				$this->_mentions[] = $profile;
			}
		}
		
		$this->_created_at = strtotime($this->_created_at);
		$this->calculateTimeAgo(time());
		$this->parseJsonFields();
	}
	
	public function calculateTimeAgo($time){
		$diff = $time - $this->_created_at;
		if($diff < 60){
			if($diff == 1){
				$this->time_ago = '1 seconde';
			} else {
				$this->time_ago = $diff . ' seconden';
			}
		} else if($diff < 3600){
			$ceil = round($diff/60);
			if($ceil == 1){
				$this->time_ago = '1 minuut';
			} else {
				$this->time_ago = $ceil . ' minuten';
			}
		} else if($diff < 86400){
			$ceil = round($diff/3600);
			if($ceil == 1){
				$this->time_ago = '1 uur';
			} else {
				$this->time_ago = $ceil . ' uren';
			}
		} else if($diff < 604800){
			$ceil = round($diff/86400);
			if($ceil == 1){
				$this->time_ago = '1 dag';
			} else {
				$this->time_ago = $ceil . ' dagen';
			}
		} else if($diff < 2628000){
			$ceil = round(diff/604800);
			if($ceil == 1){
				$this->time_ago = '1 week';
			} else {
				$this->time_ago = $ceil . ' weken';
			}
		} else if($diff < 31536000){
			$ceil = round($diff/2628000);
			if($ceil == 1){
				$this->time_ago = '1 maand';
			} else {
				$this->time_ago = $ceil . ' maanden';
			}
		} else {
			$ceil = round($diff/31536000);
			if($ceil == 1){
				$this->time_ago = '1 jaar';
			} else {
				$this->time_ago = $ceil . ' jaar';
			}
		}
	}
	
	public function parseJsonFields(){
		if(isset($this->_user)){
			$user = new Profile();
			foreach($this->_user as $k => $v){
				$user->$k = $v;
			}
			$this->_user = $user;
		}
		
		if(isset($this->_entities->hashtags)){
			foreach($this->_entities->hashtags as $hashtag){
				$this->_text = str_replace('#' . $hashtag->text, '<a href="" class="hashtag">#' . $hashtag->text . '</a>', $this->_text);
			}
		}
		if(isset($this->_entities->user_mentions) && count($this->_entities->user_mentions) > 0){
			foreach($this->_entities->user_mentions as $mention){
				$this->_text = str_replace('@' . $mention->screen_name, '<a rel="' . $mention->id_str . '" class="show-user">@' . $mention->screen_name . '</a>', $this->_text);
			}
		}
		if(isset($this->_entities->urls) && count($this->_entities->urls) > 0){
			foreach($this->_entities->urls as $url){
				$this->_text = str_replace($url->url, '<a href="' . $url->expanded_url . '" class="url">' . $url->url . '</a>', $this->_text);
			}
		} else {
			$regex = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
			if(preg_match($regex, $this->_text, $url)){
				$this->_text = preg_replace($regex, '<a href="' . $url[0] . '" class="url">' . $url[0] . '</a>', $this->_text);
			}
		}
		if(isset($this->_entities->media) && count($this->_entities->media) > 0){
			foreach($this->_entities->media as $media){
				$this->_text = str_replace($media->url, '<a href="show_url?url=' . $media->expanded_url . '" class="url">' . $media->url . '</a>', $this->_text);
			}
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
			$obj->url = rawurlencode('service/twitter');
			$service = $couch->query($obj, 'json');
			
			$twitter = new TwitterService((array)$service->authentication);
			$twitter->reply($update, $params['reply']);
			
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
			$obj->url = rawurlencode('service/twitter');
			$service = $couch->query($obj, 'json');
			
			$twitter = new TwitterService((array)$service->authentication);
			if($params['original'] == $params['reply']){
				$twitter->reply($updateMapped, $params['reply']);
			} else {
				$twitter->share($updateMapped);
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