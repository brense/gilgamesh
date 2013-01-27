<?php

use models\Config as Config;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;
use request\Request as Request;

define('FILE_ROOT', str_replace('app\processes\data_enrichment.php', '',  __FILE__));
set_time_limit(0);

require_once(FILE_ROOT . 'app\Application.php');
require_once(FILE_ROOT . 'config.php');

$app = new Application(array(
	'debug' => true,
	'query_caching' => false,
	'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
));

$process = new BackgroundProcess('data_enrichment.php', Config::$file_root, PHP_EXEC);

data_enrichment($process);

function data_enrichment(BackgroundProcess $process){
	$sleep = 10;
	
	$queuedRequests = array();
	
	for($n = 0; $n >= 0; $n++){
		$start = time();
		
		if($process->checkRestart($sleep)){
			break;
		}
		
		$couch = Storage::database('CouchDB', Config::$db['couchdb']);
		$response = $couch->query('GET', 'searches/_all_docs?include_docs=true', array(), 'json');
		$searches = $response->rows;
		
		foreach($searches as $search){
			if(isset($queuedRequests[$search->id])){
				$search = $queuedRequests[$search->id];
			}
			if(!isset($search->doc->started) || $search->doc->started == 0){
				$search->doc->started = time();
				$doc = $search->doc;
				$doc->_id = $search->id;
				$doc->_rev = $search->value->rev;
				$response = $couch->query('POST', 'searches/_bulk_docs', '{"docs":' . json_encode(array($doc)) . '}', 'json');
			}
			$response = file_get_contents('http://' . $search->doc->request->host . $search->doc->request->path . '?' . http_build_query($search->doc->request->params));
			
			switch($search->doc->service){
				case 'Flickr':
					$photos = array();
					$json = json_decode($response);
					if($json && isset($json->photos)){
						// create new queuedRequest if there are multiple result pages
						if($json->photos->pages > 1 && $json->photos->page < $json->photos->pages){
							$search->doc->request->params->page = ($json->photos->page + 1);
							$queuedRequests[$search->id] = $search;
						}
						// process photos
						foreach($json->photos->photo as $photo){
							$photo->_id = 'flickr_' . $photo->id;
							$photo->url = 'http://www.flickr.com/photos/' . $photo->owner . '/' . $photo->id . '/';
							$photos[] = $photo;
						}
						// insert photos in database
						$response = $couch->query('POST', 'enrichment/_bulk_docs', '{"docs":' . json_encode($photos) . '}', 'json');
						// update search when all results are processed
						if($json->photos->page == $json->photos->pages){
							$search->doc->request->params->min_uploaded_date = $search->doc->started;
							$search->doc->started = 0;
							$doc = $search->doc;
							$doc->_id = $search->id;
							$doc->_rev = $search->value->rev;
							$response = $couch->query('POST', 'searches/_bulk_docs', '{"docs":' . json_encode(array($doc)) . '}', 'json');
							unset($queuedRequests[$search->id]);
						}
					}
					break;
			}
		}
		//Twitter Search
		//Facebook Search
		//Youtube Search
		//Flickr Search
		
		$process->sleep($start, time());
	}
	
	if($sleep > 0){
		sleep($sleep);
		data_enrichment($process); // restart
	}
}

$process->stop();

exit;