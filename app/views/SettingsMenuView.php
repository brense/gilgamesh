<?php

namespace views;

class SettingsMenuView extends AbstractView {
	
	protected $_template = 'settings/menu';
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
	}
	
}