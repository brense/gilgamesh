<?php

namespace views;

use \models\Event as Event;

class EventEditView extends AbstractView {
	
	protected $_template = 'event/add';
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		
		if(isset($_GET['id'])){
			$event = Event::find(array('key' => $_GET['id']));
			$this->_vars['event'] = $event;
		}
		
	}
	
}