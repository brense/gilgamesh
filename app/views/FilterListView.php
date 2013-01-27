<?php

namespace views;

use \models\Event as Event;
use \models\Filter as Filter;
use \storage\Storage as Storage;
use \models\Config as Config;

class FilterListView extends AbstractView {
	
	protected $_template = 'filter/list';
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		$event_id = @array_pop(@explode('/', Event::getID()));
		
		$selected = array();
		if(isset($_SESSION['filters'][$event_id])) $selected = $_SESSION['filters'][$event_id];
		
		$event = Event::find_by_key('event/' . $event_id);
		
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
					$q[] = '(' . $filter['field'] . ':' . $filter['value'] . ')';
				}
			}
		}
		
		$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
		
		$filters = array();
		foreach($event->filters as $filter){
			$query = $q;
			if(!isset($selected[(string)$filter]) || $selected[(string)$filter]['active'] != 1){
				if(is_array($filter->value)){
					$vals = array();
					foreach($filter->value as $val){
						$vals[] = $filter->field . ':' . $val;
					}
					$query[] = '(' . implode(' OR ', $vals) . ')';
				} else {
					$query[] = '(' . $filter->field . ':' . $filter->value . ')';
				}
			}
			
			$result = $db->search(rawurlencode(implode(' AND ', $query)), null, array(0,0), 'json');
			if(isset($result->rows->total)){
				$filter->updates = $result->rows->total;
			}
		}
		
		$this->_vars['filters'] = $event->filters;
		$this->_vars['selected'] = $selected;
	}
	
}