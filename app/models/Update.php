<?php

namespace models;

use \storage\Storage as Storage;

class Update extends Saveable {
	
	protected $_id;
	protected $_text;
	protected $_profile;
	protected $_event;
	protected $_service;
	protected $_labels = array();
	protected $_mentions = array();
	protected $_geolocation;
	protected $_source;
	protected $_created;
	protected $_timestamp;
	protected $_fav;
	protected $_flag;
	protected $_read;
	protected $_replies;
	
	protected $_table = 'update';
	
	public function populate(){
		return true;
	}
	
	public static function find_by_id(){
		if(isset($_GET['id'])){
			$update = Update::find_by_key($_GET['id']);			
		}
		
		$arr = array();
		if(isset($update) && $update instanceof Update){
			$arr = array($update);
		}
		
		$template = new Template('message/list', array('messages' => $arr, 'no_update' => true));
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
		
		$template = new Template('message/list', array('messages' => $updates, 'no_update' => true));
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
		
		$template = new Template('message/list', array('messages' => $updates_by_user_ids, 'no_update' => true));
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
			
		$template = new Template('message/list', array('messages' => $updates, 'no_update' => true));
		echo $template->render();
	}
	
	public static function __callStatic($method, $parameters){
		$parameters['obj'] = new self();
		return parent::__callStatic($method, $parameters);
	}
	
	public function assignTo($params){
		// TODO: implement this
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