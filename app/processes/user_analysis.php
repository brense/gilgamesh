<?php

use models\Config as Config;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;

define('FILE_ROOT', str_replace('app\processes\user_analysis.php', '',  __FILE__));
set_time_limit(0);

require_once(FILE_ROOT . 'app\Application.php');
require_once(FILE_ROOT . 'config.php');

$app = new Application(array(
	'debug' => true,
	'query_caching' => false,
	'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
));

$process = new BackgroundProcess('user_analysis.php', Config::$file_root, PHP_EXEC);

//TODO: infochimps influence api ? calls per day http://www.infochimps.com/datasets/twitter-census-influence-metrics#api-docs_tab
//TODO: whit.li api 5000 calls per day http://developer.whit.li/iodocs

$couch = Storage::database('CouchDB', Config::$db['couchdb']);

while(!$process->stop){
	$start = time();
	
	if(!$process->checkStop()){
		break;
	}
	
	// get 2 unanalyzed records from profiles database (2 records every 10 seconds = 12 records per minute = 17280 records per day
	// start and end key can be used to reanalyze old records past a certain date (klout says 5 days)
	$response = $couch->query('GET', 'profiles/_design/views/_view/unanalyzed?skip=' . ($n * 2) . '&limit=2&reduce=false&include_docs=true&startkey=[0]&endkey=[0,{}]', null, 'json');
	$profiles = array();
	if(isset($response->rows) && is_array($response->rows)){
		$profiles = $response->rows;
		foreach($profiles as &$doc){
			$profile = $doc->doc;
			
			// get kred score
			if(isset($profile->screen_name)){
				$url = 'http://api.kred.com/kredscore?source=combined&term=' . $profile->screen_name . '&app_id=' . KRED_ID . '&app_key=' . KRED_KEY;
			}
			if(isset($url)){
				$response = @json_decode(@file_get_contents($url));
			}
			if($response && isset($response->data) && isset($response->data[0]->influence)){
				$profile->kred = $response->data[0];
			}
			
			// get klout score
			if(!isset($profile->klout->topics) && isset($profile->klout->id)){
				$url = 'http://api.klout.com/v2/user.json/' . $profile->klout->id . '/topics?key=' . KLOUT_KEY;
			} else {
				if(isset($profile->screen_name)){
					$url = 'http://api.klout.com/v2/identity.json/twitter?screenName=' . $profile->screen_name . '&key=' . KLOUT_KEY;
				}
			}
			if(isset($url)){
				$response = @json_decode(@file_get_contents($url));
			}
			if($response && (isset($response->id) || is_array($response))){
				if(is_array($response)){
					$profile->klout->topics = $response;
				} else {
					$profile->klout = $response;
				}
			} else {
				$profile->analyzed = time();
			}
			
			// set the time the user was last analyzed
			if(isset($profile->klout->topics)){
				$profile->analyzed = time();
			}
			$doc = $profile;
		}
		
		// save the updated profiles
		$body = '{"docs":' . json_encode($profiles) . '}';
		$response = $couch->query('POST', 'profiles/_bulk_docs', $body, 'json');
	}
	$process->sleep($start, time());
}

$process->restart();

exit;