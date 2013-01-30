<?php

// TODO: handle search results with multiple pages

use models\Config as Config;
use background_process\BackgroundProcess as BackgroundProcess;
use storage\Storage as Storage;

define('FILE_ROOT', str_replace('processes' . DIRECTORY_SEPARATOR . 'enrichment.php', '',  __FILE__));
define('VOLUME', @array_shift(@explode(':' . DIRECTORY_SEPARATOR, FILE_ROOT)) . ':' . DIRECTORY_SEPARATOR);

require_once(FILE_ROOT . 'app' . DIRECTORY_SEPARATOR . 'Application.php');

// init application and set options
$app = new Application(
	function(){
		$json = json_decode(file_get_contents('http://127.0.0.1:5984/afstuderen/_design/config/_view/all?key="config/default"&include_docs=true&reduce=false&stale=ok'));
		if(isset($json->rows) && is_array($json->rows) && isset($json->rows[0]) && isset($json->rows[0]->doc)){
			$doc = $json->rows[0]->doc;
			foreach($json->rows[0]->doc as $k => $v){
				if($k != '_id' && $k != '_rev' && $k != 'type'){
					Config::${$k} = $v;
				}
			}
		}
	}, array(
	'debug' => true,
	'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
));

set_time_limit(0);

$php_exec = substr($_SERVER['DOCUMENT_ROOT'], 0, -5) . 'bin' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php' . Config::$php_version . DIRECTORY_SEPARATOR . 'php.exe';
$process = new BackgroundProcess('enrichment.php', Config::$file_root, $php_exec);

$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});

// get all streams
$obj = new \StdClass();
$obj->method = 'GET';
$obj->url = '_design/stream/_view/task?key="enrichment"&include_docs=true&reduce=false';
$streams = $db->query($obj, 'map');

// get all events
$obj = new \StdClass();
$obj->method = 'GET';
$obj->url = '_design/event/_view/running?include_docs=true&reduce=false';
$events = $db->query($obj, 'map');

while(!$process->stop){
	
	// loop through all events keeping in mind the rate limits (1 second interval?)
	foreach($events as $event){
		foreach($event->hashtags as $hashtag => $count){
			foreach($streams as $stream){
				foreach($stream->parameters as &$param){
					if($param == '%labels%'){
						$param = $hashtag;
					}
				}
				$url = $stream->service->search_endpoint . $stream->path . '?' . http_build_query((array)$stream->parameters);
				$json = json_decode(file_get_contents($url));
				
				// facebook
				if(isset($json->data)){
					$updates = $json->data;
				}
				
				// twitter
				if(isset($json->statuses)){
					$updates = $json->statuses;
				}
				
				foreach($updates as $update){
					if(isset($update->id)){
						if(isset($update->id_str)){
							$id = $update->id_str;
						} else {
							$id = $update->id;
						}
						// insert updates in updates buffer with event id attached so they won't pass through event detection
						$update->event = $event->id;
						file_put_contents(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $id . '.txt', json_encode($update));
					}
				}
			}
			sleep(10); // 10 second interval to prevent breaking rate limits? might have to be longer for some services
		}
	}
	
}

$process->restart();

exit;