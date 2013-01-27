<?php

namespace views;

use \models\Event as Event;

class EventListView extends AbstractView {
	
	protected $_template = 'event/list';
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		$event_id = Event::getID();
		
		if(isset($this->_vars['size']) && $this->_vars['size'] == 'small'){
			$limit = 5;
		} else {
			$limit = 20;
		}
		
		$models = Event::find_all(array($limit, 0));
		if(!is_array($models)){
			$models = array();
		}
		
		$events = array();
		foreach ($models as $event){
			$events[$event->start . $event->end . $event->id] = $event;
		}
		
		krsort($events);
		
		$this->_vars['events'] = $events;
		$this->_vars['selected_event'] = $event_id;
	}
	
}