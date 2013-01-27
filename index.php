<?php

use models\Page as Page;
use controllers\CommandController as CommandController;
use models\Config as Config;

require_once('app/Application.php');

// init application and set options
$app = new Application(function(){
		// config init function
		$json = json_decode(file_get_contents('http://127.0.0.1:5984/afstuderen/_design/config/_view/all?key="config/default"&include_docs=true&reduce=false&stale=ok'));
		if(isset($json->rows) && is_array($json->rows) && isset($json->rows[0]) && isset($json->rows[0]->doc)){
			$doc = $json->rows[0]->doc;
			foreach($json->rows[0]->doc as $k => $v){
				if($k != '_id' && $k != '_rev' && $k != 'type'){
					Config::$$k = $v;
				}
			}
		}
	}, array(
		'debug' => true,
		'query_caching' => false
	)
);

// set request routes
$app->get('do/:uri', function($uri){
	$parts = explode('/', $uri);
	$function = array_pop($parts);
	$class = '\\' . implode('\\', $parts);
	if(substr($class, -4, 4) == 'View'){
		$view = new $class();
		echo $view->$function($_GET);
	} else {
		$obj = new $class();
		echo $obj->$function($_GET);
	}
});
$app->post('do/:uri', function($uri){
	$parts = explode('/', $uri);
	$function = array_pop($parts);
	$class = '\\' . implode('\\', $parts);
	if(substr($class, -4, 4) == 'View'){
		$view = new $class();
		echo $view->$function($_POST);
	} else {
		$obj = new $class();
		echo $obj->$function($_POST);
	}
});

$app->get(':uri', function($uri){
	$page = Page::find_by_uri($uri);
	echo $page->render();
});

// handle incomming requests
$app->handleRequest();