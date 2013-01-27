<?php

namespace views;

use \models\Event as Event;

class EventEditView extends AbstractView {
	
	protected $_template = 'event/add';
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		if(isset($_GET['id'])){
			$event_id = $_GET['id'];
		} else {
			$event_id = Event::getID();
		}
		
		$model = Event::find_by_id($event_id);
		if($model instanceof Event){
			$model->peak = json_decode($model->peak);
			$model->hashtags = json_decode($model->hashtags);
			$model->location = json_decode($model->location);
			if(strlen($model->name) == 0){
				$model->name = $model->type . '/' . $model->sub_type;
			}
			$this->_vars['event'] = $model;
		}
		
	}
	
}