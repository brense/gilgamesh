<?php

namespace models;

use \storage\Storage as Storage;

class Filter extends Saveable {
	
	protected $_id;
	protected $_event;
	protected $_type;
	protected $_name;
	protected $_value;
	protected $_field;
	protected $_updates = 0;
	
	public function add($data){
		if(isset($data['event_id']) && isset($data['field']) && isset($data['value']) && strlen($data['field']) > 0){
			$filter = array();
			$filter['value'] = $data['value'];
			$filter['field'] = $data['field'];
			switch($data['field']){
				case 'text':
					$filter['type'] = 'term';
					$filter['name'] = 'Woord';
					break;
				case 'entities.hashtags.text':
					if(substr($data['value'], 0, 1) != '#'){
						$filter['value'] = '#' . $data['value'];
					}
					$filter['type'] = 'term';
					$filter['name'] = 'Hashtag';
					break;
				case 'filter.schade':
					$filter['type'] = 'complex';
					$filter['field'] = 'damage';
					$filter['value'] = 'true';
					/*
					$filter['field'] = 'text';
					$filter['value'] = array('schade', 'slachtoffers', 'slachtoffer', 'kapot', 'gewonden', 'gewonde', 'doden', 'letsel', 'beschadiging', 'beschadigd', 'verwoesting', 'ravage', 'NOT*\?');
					*/
					$filter['name'] = 'Schade en slachtoffers';
					break;
				case 'filter.info':
					$filter['type'] = 'complex';
					$filter['field'] = 'info';
					$filter['value'] = 'true';
					$filter['name'] = 'Informatiebehoefte';
					break;
				case 'filter.need':
					$filter['type'] = 'complex';
					$filter['field'] = 'need';
					$filter['value'] = 'true';
					$filter['name'] = 'Gevraagde hulp';
					break;
				case 'filter.offer':
					$filter['type'] = 'complex';
					$filter['field'] = 'offer';
					$filter['value'] = 'true';
					$filter['name'] = 'Aangeboden hulp';
					break;
				case 'filter.imago': // TODO speciaal filter
					$filter['type'] = 'complex';
					$filter['field'] = 'text';
					$filter['value'] = '((trots OR hoera OR respect NOT helpen) AND (waterschap hunze OR @hunzeenaas))';
					$filter['name'] = 'Positief imago';
					break;
				case 'filter.worries': // TODO speciaal filter
					$filter['type'] = 'complex';
					$filter['field'] = 'text';
					$filter['value'] = '(eng OR bang OR bezorgd)';
					$filter['name'] = 'Bezorgdheid';
					break;
				case 'filter.period':
					$filter['type'] = 'complex';
					$filter['field'] = 'timestamp';
					// TODO: make pretty with calendars to select dates etc.
					$filter['name'] = 'Periode';
					break;
				case 'user.screen_name':
					$filter['type'] = 'persoon';
					$filter['name'] = 'Persoon';
					break;
			}
			
			$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode('event/' . $data['event_id']);
			$model = $db->query($obj, 'json');
			if(isset($model->_rev)){
				$filters = array();
				foreach($model->filters as $f){
					$filters[] = $f;
				}
				$filters[] = $filter;
				$model->filters = $filters;
				
				$obj = new \StdClass();
				$obj->method = 'POST';
				$obj->url = '_bulk_docs';
				$obj->body = '{"docs":' . json_encode(array($model)) . '}';
				$results = $db->query($obj, 'json');
			}
		}
	}
	
	public function remove($data){
		if(isset($data['event']) && isset($data['field']) && isset($data['value'])){
			$filters = array();
			$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
			$obj = new \StdClass();
			$obj->method = 'GET';
			$obj->url = rawurlencode('event/' . $data['event']);
			$model = $db->query($obj, 'json');
			if(isset($model->_rev)){
				$filters = (array)$model->filters;
				foreach($filters as $n => $filter){
					if($filter->field == $data['field'] && ((is_array($filter->value) && implode(',', $filter->value) == $data['value']) || $filter->value == $data['value'])){
						unset($filters[$n]);
					}
				}
				$model->filters = array();
				foreach($filters as $filter){
					$model->filters[] = $filter;
				}
				
				if(isset($_SESSION['filters'][$data['event']]) && isset($_SESSION['filters'][$data['event']][md5($data['field'] . $data['value'])])){
					unset($_SESSION['filters'][$data['event']][md5($data['field'] . $data['value'])]);
				}
				
				$obj = new \StdClass();
				$obj->method = 'POST';
				$obj->url = '_bulk_docs';
				$obj->body = '{"docs":' . json_encode(array($model)) . '}';
				$results = $db->query($obj, 'json');
			}
		}
	}
	
	public static function toggle(){
		$event_id = @array_pop(@explode('/', Event::getID()));
		$toggle = '';
		if(isset($_SESSION['filters'][$event_id][$_GET['id']]) && $_SESSION['filters'][$event_id][$_GET['id']]['active'] == 1){
			$_SESSION['filters'][$event_id][$_GET['id']] = array('active' => 0, 'field' => $_GET['field'], 'value' => $_GET['value']);
			$toggle = 'deactivate';
		} else {
			$_SESSION['filters'][$event_id][$_GET['id']] = array('active' => 1, 'field' => $_GET['field'], 'value' => $_GET['value']);
			$toggle = 'activate';
		}
		echo json_encode(array('toggle' => $toggle, 'referer' => str_replace(Config::$root_url, '', $_SERVER['HTTP_REFERER'])));
	}
	
	public function __toString(){
		return md5($this->_field . $this->_value);
	}
}