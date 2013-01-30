<?php

namespace models;

use interfaces\Renderable as Renderable;

class Template extends Model implements renderable {
	
	protected $_file;
	protected $_vars;
	protected $_children = array();
	
	public function __construct($file, Array $vars = array()){
		$this->_file = $file;
		$this->_vars = $vars;
	}
	
	public function render(Array $options = array()){
		$html = '';
		foreach($this->_children as $child){
			$html .= $child->render();
		}
		if(isset($options['content'])){
			$this->_vars['content'] = $options['content'];
		} else {
			$this->_vars['content'] = $html;
		}
		ob_start();
		if(file_exists(Config::$file_root . 'templates\\' . $this->_file . '.php')){
			include(Config::$file_root . 'templates\\' . $this->_file . '.php');
		}
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	
	public static function parseArrayToContent(Array $arr){
		$contents = '';
		foreach($arr as $item){
			if(isset($item->type)){
				// create an array with template variables
				if(!isset($item->vars)){
					$item->vars = array();
				}
				// parse content based on type
				switch($item->type){
					case 'view':
						$class_name  = 'views\\' . $item->view;
						$child = new $class_name((array)$item->vars);
						break;
					case 'template':
						$child = new Template($item->template, (array)$item->vars);
						break;
					case 'wrapper':
						// TODO: wrappers are not supported right now
						break 2;
				}
				$contents .= $child->render();
			}
		}
		return $contents;
	}
	
	public function addChild(Renderable $template){
		$this->_children[] = $template;
	}
	
}