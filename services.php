<?php

use models\Config as Config;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;
use services\TwitterService as TwitterService;
use request\OauthRequest as OauthRequest;
use models\ServiceRequest as ServiceRequest;

define('FILE_ROOT', str_replace('services.php', '',  __FILE__));

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

$couch = Storage::database('CouchDB', Config::$db['couchdb']);
$use = 'enrichment';

$sreqs = ServiceRequest::find_by_use(array('startkey' => '["' . $use . '"]', 'endkey' => '["' . $use . '",{}]', 'group_level' => 1));
$reqs = array();
foreach($sreqs as $sreq){
	$reqs = array_merge($reqs, $sreq->enrichment());
}
echo '<pre>';
print_r($reqs);
exit;