<?php

namespace models;

use storage\Storage as Storage;
use storage\ElasticSearch as ElasticSearch;

class Event extends Saveable {
	
	protected $_name;
	protected $_peak;
	protected $_event_type;
	protected $_hashtags;
	protected $_location;
	protected $_total_messages;
	protected $_start;
	protected $_end;
	protected $_filters = array();
	
	protected $_table = 'event';
	
	private static $_database = 'events';
	
	public static function getID(){
		$event_id = 1;
		if(isset($_SESSION['event_id'])) $event_id = $_SESSION['event_id'];
		if(isset($_GET['event_id'])) $event_id = $_GET['event_id'];
		return $event_id;
	}
	
	public function getDisplayName(){
		$display_name = $this->_name;
		if(strlen($display_name) == 0){
			$display_name = $this->_type . '/' . $this->_sub_type;
			if(strlen($display_name) > 20){
				$display_name = $this->_sub_type;
			}
		}
		if(strlen($display_name) > 20){
			$display_name = substr($display_name, 0, 17) . '...';
		}
		return $display_name;
	}
	
	public static function select(){
		if(isset($_GET['eventId'])){
			$_SESSION['event_id'] = $_GET['eventId'];
			echo json_encode(array('referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
		}
	}
	
	public static function __callStatic($method, $parameters){
		$parameters['db'] = self::$_database;
		$parameters['obj'] = new self();
		return parent::__callStatic($method, $parameters);
	}
	
	public function populate(){		
		foreach($this->_filters as &$filter){
			$f = $filter;
			$filter = new Filter();
			$filter->type = $f->type;
			$filter->name = $f->name;
			$filter->value = $f->value;
			$filter->field = $f->field;
		}
	}
	
	public static function geoData(){
		if(isset($_GET['event'])){
			// set the correct event_id
			if($_GET['event'] == 0){
				$event_id = @array_pop(@explode('/', Event::getID()));
			} else {
				$_SESSION['event_id'] = $_GET['event'];
				$event_id = $_GET['event'];
			}
			
			$filters = array();
			if(isset($_SESSION['filters'][$event_id])){
				$filters = $_SESSION['filters'][$event_id];
			}
			$q = array('event:' . $event_id, '(geo.type:Point OR coordinates.type:Point)');
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
			
			$fields = array('fields=_source.geo', '_source.coordinates');
			
			$config = Config::$db->{Config::$db_type};
			$config->search_engine = new ElasticSearch((array)Config::$search);
			$couch = Storage::database('CouchDB', (array)$config);
			
			$json = $couch->search(rawurlencode(implode(' AND ', $q)) . '&' . implode(',', $fields), '_score:desc,timestamp:desc', array(0, 2000), 'json');
			
			$geo_data = array();
			if($json && isset($json->rows)){
				foreach($json->rows->hits as $doc){
					if(isset($doc->fields->{'_source.coordinates'}->coordinates) && isset($doc->_id)){
						$geo_data[$doc->_id] = array($doc->fields->{'_source.coordinates'}->coordinates[1], $doc->fields->{'_source.coordinates'}->coordinates[0]);
					}
					if(isset($doc->fields->{'_source.geo'}->coordinates) && isset($doc->_id)){
						$geo_data[$doc->_id] = array($doc->fields->{'_source.geo'}->coordinates[0], $doc->fields->{'_source.geo'}->coordinates[1]);
					}
				}
			}
			
			echo json_encode(array('data' => $geo_data));
		}
	}
	
}