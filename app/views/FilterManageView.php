<?php

namespace views;

use \models\Event as Event;

class FilterManageView extends AbstractView {
	
	protected $_template = 'filter/manage';
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		$event_id = @array_pop(@explode('/', Event::getID()));
		
		if(isset($this->_vars['size']) && $this->_vars['size'] == 'small'){
			$limit = 5;
		} else {
			$limit = 20;
		}
		
		$filters = array();
		$model = Event::find_by_key('event/' . $event_id);
		if($model instanceof Event){
			$filters = $model->filters;
		}
		
		$this->_vars['filters'] = $filters;
		$this->_vars['event'] = $event_id;
	}
	
}