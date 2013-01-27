<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Page as Page;
use \models\Template as Template;

class get_page_find implements Command {
	
	public function execute(){
		if(isset($_GET['uri'])){
			$uri = explode('?', $_GET['uri']);
			if(substr($uri[0], -1, 1) == '/'){
				$uri[0] = substr($uri[0], 0, -1);
			}
			if(isset($uri[1])){
				$params = explode('&', $uri[1]);
				foreach($params as $param){
					$parts = explode('=', $param);
					if(isset($parts[0]) && isset($parts[1])){
						$_GET[$parts[0]] = $parts[1];
					}
				}
			}
			$page = Page::find_by_uri($uri[0]);
			$contents = array();
			foreach(json_decode($page->content) as $content){
				$template = new Template($content->template, (array)$content->vars);
				$cts = Template::parseArrayToContent($content->content);
				$contents[str_replace('/', '_', $content->template)] = $template->render(array('content' => $cts));
			}
			$contents['page_title'] = $page->title;
			$contents['page_uri'] = $page->uri;
			echo json_encode($contents);
		}
	}
	
}