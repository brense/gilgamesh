<?php

use models\Config as Config;

define('FILE_ROOT', str_replace('image_cache.php', '',  __FILE__));
set_time_limit(0);

require_once(FILE_ROOT . 'app\Application.php');

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
		'query_caching' => false,
		'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
	)
);

if(isset($_GET['img'])){
	$ext = strtolower(array_pop(@explode('.', array_pop(@explode(':', $_GET['img'])))));
	if(in_array($ext, array('jpeg', 'jpg', 'gif', 'png'))){
		if($ext == 'jpeg' || $ext == 'jpg'){
			header('Content-Type: image/jpeg');
		} else {
			header('Content-type: image/' . $ext);
		}
		$cache_file = Config::$file_root . 'cache' . DIRECTORY_SEPARATOR . 'img' . md5($_GET['img']) . '.' . $ext;
		if(file_exists($cache_file)){
			echo file_get_contents($cache_file);
		} else {
			$contents = file_get_contents($_GET['img']);
			file_put_contents($cache_file, $contents);
			echo $contents;
		}
	} else {
		header('Content-Type: image/jpeg');
		echo file_get_contents($_GET['img']);
	}
}