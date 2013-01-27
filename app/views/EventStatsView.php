<?php

namespace views;

use \models\Event as Event;
use \models\Update as Update;
use \storage\Storage as Storage;
use \storage\ElasticSearch as ElasticSearch;
use \models\Config as Config;
use \models\Profile as Profile;

class EventStatsView extends AbstractView {
	
	protected $_template = 'event/stats';
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);		
		
		$options = array();
		if(isset($this->_vars['options'])){
			$options = $this->_vars['options'];
		}
		
		// set event id
		if(isset($options->event_id)){
			$event_id = $options->event_id;
		} else if(isset($options->home_timeline) && $options->home_timeline == true){
			$event_id = '*'; // ?
		} else {
			$event_id = Event::getID();
		}
		
		$filters = array();
		if(isset($_SESSION['filters'][$event_id])){
			$filters = $_SESSION['filters'][$event_id];
		}
		$q = array('event:' . $event_id);
		foreach($filters as $filter){
			if($filter['active'] == 1){
				$vals = explode(',', $filter['value']);
				if(count($vals) > 0){
					foreach($vals as &$val){
						$val = $filter['field'] . ':' . $val;
					}
					$q[] = '(' . implode(' OR ', $vals) . ')';
				} else {
					$q[] = $filter['field'] . ':' . $filter['value'];
				}
			}
		}
		
		if(count($q) == 1){
			$couch = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = '_design/update/_view/message_types?stale=ok&group_level=5&startkey=[' . $event_id . ']&endkey=[' . $event_id . ',{}]';
			$response = $couch->query($obj, 'json');
			$bpm = array();
			if(isset($response->rows)){
				foreach($response->rows as $row){
					$year = $row->key[1];
					$month = $row->key[2]+1;
					$day = $row->key[3];
					$hour = $row->key[4];
					
					$original = $row->value[0];
					$retweet = $row->value[1];
					$reply = $row->value[2];
					
					$date = strtotime(date('m/d/Y H:00:00', mktime($hour, 0, 0, $month, $day, $year)));
					$bpm[$date]['original'] = $original;
					$bpm[$date]['rt'] = $retweet;
					$bpm[$date]['reply'] = $reply;
				}
			}
			ksort($bpm);
			$this->_vars['bpm'] = $bpm;
			
			// TODO: implement this:
			$this->_vars['users'] = array();
			
		} else {
			// get messages types per hour
			$fields = array('_source.text', '_source.in_reply_to_status_id_str', '_source.created_at', '_source.user.screen_name');
			$bpm = array();
			
			$config = Config::$db->{Config::$db_type};
			$config->search_engine = new ElasticSearch((array)Config::$search);
			$couch = Storage::database(Config::$db_type, (array)$config);
			
			$json = $couch->search(rawurlencode(implode(' AND ', $q)) . '&fields=' . implode(',', $fields), 'id:desc', array(0, 30000), 'json');
			
			if(isset($json->rows->hits)){
				foreach($json->rows->hits as $update){
					$time = strtotime($update->fields->{'_source.created_at'});
					$date = strtotime(date('m/d/Y H:00:00', $time));
					if(!isset($bpm[$date]['original'])){
						$bpm[$date]['original'] = 0;
					}
					if(!isset($bpm[$date]['rt'])){
						$bpm[$date]['rt'] = 0;
					}
					if(!isset($bpm[$date]['reply'])){
						$bpm[$date]['reply'] = 0;
					}
					if(isset($update->fields->{'_source.in_reply_to_status_id_str'}) && strlen($update->fields->{'_source.in_reply_to_status_id_str'}) > 0){
						$bpm[$date]['reply']++;
					} else if(substr($update->fields->{'_source.text'}, 0, 2) == 'RT'){
						$bpm[$date]['rt']++;
					} else {
						$bpm[$date]['original']++;
					}
				}
			}
			
			ksort($bpm);
			
			$this->_vars['bpm'] = $bpm;
			
			unset($response, $fields, $bpm);
			
			// get users
			$users = array();
			foreach($json->rows->hits as $row){
				if(isset($users[$row->fields->{'_source.user.screen_name'}])){
					$users[$row->fields->{'_source.user.screen_name'}]++;
				} else {
					$users[$row->fields->{'_source.user.screen_name'}] = 1;
				}
			}
			
			unset($json);
			
			arsort($users);
			$this->_vars['users'] = array_slice($users, 0, 10);
			
			unset($users_by_names);
		}
	}
	
}