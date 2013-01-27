<?php

namespace views;

use \models\Update as Update;
use \models\Event as Event;
use \storage\Storage as Storage;
use \models\Config as Config;

class MessageMapView extends AbstractView {
	
	protected $_template = 'message/map';
	protected $_vars;
	
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
		
		$event = Event::find_by_key($event_id);
		
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
		
		$couch = Storage::database('CouchDB', Config::$db['couchdb']);
		$couch->mapper->setObject(new Update());
		
		$url = 'updates/_design/views/_view/geo?startkey=[' . $event_id . ']&endkey=[' . $event_id . ',{}]&stale=ok&group_level=3';
		
		$updates = $couch->query('GET', $url, array(), 'json');
		
		$this->_vars['coordinates'] = array();
		if(isset($updates->rows)){
			$this->_vars['coordinates'] = $updates->rows;
		}
		$this->_vars['event'] = $event;
	}
	
}