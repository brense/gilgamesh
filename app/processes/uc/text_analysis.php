<?php

use models\Config as Config;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;

define('FILE_ROOT', str_replace('app\processes\text_analysis.php', '',  __FILE__));
set_time_limit(0);

require_once(FILE_ROOT . 'app\Application.php');
require_once(FILE_ROOT . 'config.php');

$app = new Application(array(
	'debug' => true,
	'query_caching' => false,
	'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
));

$process = new BackgroundProcess('text_analysis.php', Config::$file_root, PHP_EXEC);

text_analysis($process);

function text_analysis(BackgroundProcess $process){
	$sleep = 10;
	
	$couch = Storage::database('CouchDB', Config::$db['couchdb']);
	
	for($n = 0; $n >= 0; $n++){
		$start = time();
		
		if($process->checkRestart($sleep)){
			break;
		}
		
		// TODO: check out apache OpenNLP http://opennlp.apache.org/cgi-bin/download.cgi
		// TODO: alchemy api 1000 calls per day http://www.alchemyapi.com/api/sentiment/urls.html
		
		// get 2 unanalyzed records from profiles database (1 records every 10 seconds = 6 records per minute = ? records per day
		$response = $couch->query('GET', 'updates/_design/views/_view/unanalyzed?skip=' . ($n * 1) . '&limit=2&reduce=false&include_docs=true&startkey=[0]&endkey=[0,{}]', null, 'json');
		$updates = array();
		if(isset($response->rows) && is_array($response->rows)){
			$updates = $response->rows;
		}
		foreach($updates as &$doc){
			$update = $doc->doc;
			
			// zemanta api 10000 calls per day http://developer.zemanta.com/docs/
			$url = 'http://api.zemanta.com/services/rest/0.0/';
			$params = array('method'=> 'zemanta.suggest', 'api_key'=> ZEMANTA_KEY, 'text'=> $update->text, 'format'=> 'json');
			$body = '';
			foreach($args as $k => $v){
				$body .= ($body != '')?'&':'';
				$body .= urlencode($k) . '=' . urlencode($v);
			}
			$req = new Request('POST', $url, null, $body);
			$req->ssl = false;
			$req->execute();
			$response = $req->response;
			print_r(json_decode($response));exit;
			
			// open calais api 50000 calls per day http://www.opencalais.com/documentation/calais-web-service-api
			// TODO: talk to open calais api
			
			$doc = $update;
		}
		
		// save the updated profiles
		$body = '{"docs":' . json_encode($updates) . '}';
		$response = $couch->query('POST', 'updates/_bulk_docs', $body, 'json');
		
		$process->sleep($start, time());
	}
	
	if($sleep > 0){
		sleep($sleep);
		text_analysis($process); // restart
	}
}

$process->stop();

exit;