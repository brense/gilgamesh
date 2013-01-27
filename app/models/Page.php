<?php

namespace models;

use models\Template as Template;

class Page extends Saveable {
	
	protected $_id;
	protected $_title;
	protected $_uri;
	protected $_template;
	protected $_style;
	protected $_content;
	
	protected $_table = 'page';
	
	private static $_database = 'pages';
	
	public function render(Array $options = array()){
		$template = new Template($this->_template, array('style' => $this->_style, 'root' => Config::$root_url, 'title' => $this->_title, 'page' => $this->_uri));
		$this->parseContentIntoTemplate($template);
		echo $template->render();
	}
	
	private function parseContentIntoTemplate(Template $template, $content = null){
		if(!isset($content)){
			$content = $this->_content;
		}
		if(is_array($content)){
			foreach($content as $item){
				if(isset($item->type)){
					$child;
					// create an array with template variables
					$vars = array();
					if(isset($item->vars)){
						foreach($item->vars as $k => $v){
							$vars[$k] = $v;
						}
					}
					// parse content based on type
					switch($item->type){
						case 'view':
							$class_name  = 'views\\' . $item->view;
							$child = new $class_name($vars);
							break;
						case 'template':
							$child = new Template($item->template, $vars);
							break;
						case 'wrapper':
							$child = new Template($item->template, $vars);
							$this->parseContentIntoTemplate($child, $item->content);
							break;
					}
					$template->addChild($child);
				}
			}
		}
	}
	
	public static function __callStatic($method, $parameters){
		$parameters['db'] = self::$_database;
		$parameters['obj'] = new self();
		
		switch($method){
			case 'find':
				$method = 'find_by_uri';
				$parts = explode('?', $parameters[0]);
				$parameters[0] = $parts[0];
				if(substr($parameters[0], -1, 1) == '/'){
					$parameters[0] = substr($parameters[0], 0, -1);
				}
				$query = array();
				if(isset($parts[1])){
					$query = explode('&', $parts[1]);
				}
				foreach($query as $param){
					$parts = explode('=', $param);
					$_GET[$parts[0]] = $parts[1];
				}
				$page = parent::__callStatic($method, $parameters);
				$content = $page[0]->content;
				$return = array('page_title' => $page[0]->title, 'page_uri' => $page[0]->uri);
				foreach($content as $item){
					$template = new Template($item->template, (array)$item->vars);
					$return[str_replace('/', '_', $item->template)] = $template->render(array('content' => Template::parseArrayToContent($item->content)));
				}
				echo json_encode($return);
				break;
			default:
				$return = parent::__callStatic($method, $parameters);
				if($return){
					return $return;
				} else {
					throw new \Exception('page not found');
				}
				break;
		}
	}
	
}