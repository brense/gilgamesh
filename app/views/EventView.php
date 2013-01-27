<?php

namespace views;

use \models\Event as Event;

class EventView extends AbstractView {
	
	protected $_template = 'event/details';
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		$event_id = Event::getID();
		
		$model = Event::find_by_key($event_id);
		if($model instanceof Event){
			//$model->peak = json_decode($model->peak);
			//$model->hashtags = json_decode($model->hashtags);
			//$model->location = json_decode($model->location);
			if(strlen($model->name) == 0){
				$model->name = $model->type . '/' . $model->sub_type;
			}
			$this->_vars['event'] = $model;
		}
		
	}
	
}