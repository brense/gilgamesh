<?php

namespace views;

use \models\Template as Template;
use \interfaces\Renderable as Renderable;

abstract class AbstractView implements Renderable{
	
	protected $_template;
	protected $_vars;
	
	public function __construct(Array $vars = array()){
		$this->_vars = $vars;
	}
	
	public function render(){
		if(!isset($this->_vars)){
			$this->_vars = array();
		}
		$this->_template = str_replace('/', DIRECTORY_SEPARATOR, $this->_template);
		$template = new Template($this->_template, $this->_vars);
		return $template->render();
	}
	
}