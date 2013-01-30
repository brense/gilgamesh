<?php

namespace views;

use \models\Update as Update;
use \models\Profile as Profile;
use \models\Event as Event;
use \storage\ElasticSearch as ElasticSearch;
use \models\Config as Config;
use \storage\Storage as Storage;

class MessageListView extends AbstractView {
	
	protected $_template = 'message/list';
	
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
		
		// set limit and skip
		$skip = 0;
		$limit = 20;
		if(isset($options->skip)){
			$skip = $options->skip;
		}
		if(isset($_GET['skip'])){
			$skip = $_GET['skip'];
		}
		if(isset($options->limit)){
			$limit = $options->limit;
		}
		if(isset($_GET['append']) && $_GET['append'] == 'true'){
			$this->_vars['append'] = true;
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
		
		if(isset($_GET['timestamp'])){
			$this->_vars['append'] = true;
			$q[] = 'timestamp:[' . ((int)$_GET['timestamp'] + 1) . ' TO ' . time() . ']';
		}
		
		$config = Config::$db->{Config::$db_type};
		$config->search_engine = new ElasticSearch((array)Config::$search);
		$couch = Storage::database('CouchDB', (array)$config);
		
		$updates = $couch->search(rawurlencode(implode(' AND ', $q)), 'timestamp:desc,_score:desc', array($skip, $limit), 'map');
		
		if(!is_array($updates)){
			$updates = array();
		}
		
		foreach($updates as $k => $update){
			if(!($update->user instanceof Profile)){
				unset($updates[$k]);
			}
		}
		
		$this->_vars['messages'] = $updates;
		if(!isset($_GET['timestamp'])){
			$this->_vars['next'] = $skip + $limit;
		}
	}
	
}