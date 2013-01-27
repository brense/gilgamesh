<?php

namespace models;

use \storage\Storage as Storage;
use \services\TwitterService as TwitterService;

class Update extends Saveable {
	
	protected $_id;
	protected $_foreign_id;
	protected $_id_str;
	protected $_text;
	protected $_user;
	protected $_event;
	protected $_type;
	protected $_entities;
	protected $_coordinates;
	protected $_place;
	protected $_retweet_count;
	protected $_source;
	protected $_in_reply_to_status_id_str;
	protected $_in_reply_to_user_id_str;
	protected $_in_reply_to_screen_name;
	protected $_created_at;
	protected $_time_ago;
	protected $_retweeted;
	protected $_rt;
	protected $_fav;
	protected $_flag;
	protected $_read;
	protected $_replies;
	
	protected $_table = 'update';
	
	private static $_database = 'updates';
	
	public function populate(){
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
	
	public static function find_by_id(){
		if(isset($_GET['id'])){
			$update = Update::find_by_key($_GET['id']);			
		}
		
		$arr = array();
		if(isset($update) && $update instanceof Update){
			$arr = array($update);
		}
		
		$template = new Template('message/list', array('messages' => $arr));
		$html = $template->render();
		
		echo $html;
	}
	
	public static function find_by_ids(){
		$upsates = array();
		if(isset($_GET['ids'])){
			$ids = explode(',', $_GET['ids']);
			
			$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'POST';
			$obj->url = '_all_docs?include_docs=true';
			$obj->body = json_encode(array('keys' => $ids));
			$updates = $db->query($obj, 'map');
		}
		
		$template = new Template('message/list', array('messages' => $updates));
		$html = $template->render();
		
		echo $html;
	}
	
	public static function geo(){
		$event_id = Event::getID();
		$couch = Storage::database('CouchDB', Config::$db['couchdb']);
		$couch->mapper->setObject(new Update());
		
		if($_GET['startlat'] == $_GET['endlat'] && $_GET['startlong'] == $_GET['endlong']){
			$_GET['lat'] = $_GET['startlat'];
			$_GET['long'] = $_GET['startlong'];
		} else {
			$_GET['startlat'] += 0.0001;
			$_GET['startlong'] += 0.0001;
		}
		
		$html = '';
		if(isset($_GET['lat']) && isset($_GET['long'])){
			$startlat = $_GET['lat'] - 0.0001;
			$startlong = $_GET['long'] - 0.0001;
			$endlat = $_GET['lat'] + 0.0001;
			$endlong = $_GET['long'] + 0.0001;
			$url = '_design/update/_view/geo?endkey=[' . $event_id . ',' . $startlat . ',' . $startlong . ']&startkey=[' . $event_id . ',' . $endlat . ',' . $endlong . ',{}]&stale=ok&reduce=false&include_docs=true&descending=true';
		} elseif(isset($_GET['startlat']) && isset($_GET['startlong']) && isset($_GET['endlat']) && isset($_GET['endlong'])){
			$url = '_design/update/_view/geo?startkey=[' . $event_id . ',' . $_GET['startlat'] . ',' . $_GET['startlong'] . ']&endkey=[' . $event_id . ',' . $_GET['endlat'] . ',' . $_GET['endlong'] . ']&stale=ok&reduce=false&include_docs=true&descending=true';
		}
		$updates = $couch->query('GET', $url, array(), 'map');
		
		if(!is_array($updates)){
			$updates = array();
		}
		$updates_by_user_ids = array();
		$user_ids = array();
		foreach($updates as $update){
			$updates_by_user_ids[$update->user] = $update;
			$user_ids[] = $update->user;
		}
		
		$body = json_encode(array('keys' => $user_ids));
		$couch->mapper->setObject(new Profile());
		$response = $couch->query('POST', 'profiles/_all_docs?include_docs=true', $body, 'map');
		
		if(is_array($response)){
			foreach($response as $profile){
				$updates_by_user_ids[$profile->id]->user = $profile;
			}
		}
		
		if(!is_array($updates_by_user_ids)){
			$updates_by_user_ids = array();
		}
		
		$template = new Template('message/list', array('messages' => $updates_by_user_ids));
		$html = $template->render();
		
		echo $html;
	}
	
	public static function user(){
		$html = '';
		if(isset($_GET['id'])){
			$event_id = Event::getID();
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = '_design/update/_view/user?endkey=[' . $event_id . ',' . $_GET['id'] . ']&startkey=[' . $event_id . ',' . $_GET['id'] . ',{}]&stale=ok&reduce=false&include_docs=true&descending=true';
			
			$updates = $couch->query($obj, 'map');
			
		}
			
		$template = new Template('message/list', array('messages' => $updates));
		echo $template->render();
	}
	
	public static function __callStatic($method, $parameters){
		$parameters['db'] = self::$_database;
		$parameters['obj'] = new self();
		return parent::__callStatic($method, $parameters);
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
	
	public function assignTo($params){
		print_r($params);
	}
	
	public function flag($params){
		if(isset($params['id'])){
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode($params['id']);
			$update = $couch->query($obj, 'json');
			if(isset($params['unflag'])){
				$update->flag = false;
			} else {
				$update->flag = true;
			}
			$obj = new \StdClass();
			$obj->method = 'POST';
			$obj->url = '_bulk_docs';
			$obj->body = json_encode(array('docs' => array($update)));
			$response = $couch->query($obj, 'json');
		}
	}
	
	public function markAsRead($params){
		if(isset($params['id'])){
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode($params['id']);
			$update = $couch->query($obj, 'json');
			if(isset($params['unread'])){
				$update->read = false;
			} else {
				$update->read = true;
			}
			$obj = new \StdClass();
			$obj->method = 'POST';
			$obj->url = '_bulk_docs';
			$obj->body = json_encode(array('docs' => array($update)));
			$response = $couch->query($obj, 'json');
		}
	}
	
}