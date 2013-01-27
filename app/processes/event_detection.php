<?php

use models\Config as Config;
use models\Event as Event;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;

define('FILE_ROOT', str_replace('app\processes\event_detection.php', '',  __FILE__));
set_time_limit(0);

require_once(FILE_ROOT . 'app\Application.php');
require_once(FILE_ROOT . 'config.php');

$app = new Application(array(
	'debug' => true,
	'query_caching' => false,
	'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
));

$process = new BackgroundProcess('event_detection.php', Config::$file_root, PHP_EXEC);

$couch = Storage::database('CouchDB', Config::$db['couchdb']);

while(!$process->stop){
	$start = time();
	
	if(!$process->checkStop()){
		break;
	}
	
	// get followed profiles
	$response = $couch->query('GET', 'streams/_all_docs?include_docs=true&stale=ok', null, 'json');
	$streams = array();
	if(isset($response->rows) && is_array($response->rows)){
		$streams = $response->rows;
	}
	unset($response);
	
	$follow_ids = array();
	foreach($streams as $doc){
		$stream = $doc->doc;
		if(isset($stream->request->params->follow)){
			$ids = explode(',', $stream->request->params->follow);
			foreach($ids as $id){
				$follow_ids[] = $id;
			}
		}
	}
	unset($streams);
	
	$response = $couch->query('GET', 'updates/_design/views/_view/orphan?include_docs=true&limit=1000&reduce=false', null, 'json');
	
	$events = Event::find_all();
	
	$updates = array();
	if(isset($response->rows) && is_array($response->rows)){
		$updates = $response->rows;
		
		$keep = array();
		$delete = array();
		$hashtag_counts = array();
		$updates_keys = array();
		$updates_count = count($updates);
		
		foreach($updates as $doc){
			$update = $doc->doc;
			$updates_keys[$update->_id] = $update;
			
			// TODO: make this more dynamic? get detection filters from database?
			
			// populate hashtag counts
			foreach($update->entities->hashtags as $hashtag){
				if(!isset($hashtag_counts[strtolower($hashtag->text)])){
					$hashtag_counts[strtolower($hashtag->text)] = array();
				}
				$hashtag_counts[strtolower($hashtag->text)][] = $update->_id;
			}
			
			// TODO: detectie van grote en kleinere incidenten dmv HVgripNL en/of p2000?
			
			if(isset($update->user) && in_array($update->user, $follow_ids)){
				$update->event = 0;
				$keep[$update->_id] = $update;
			}
			
			if(!in_array($update->_id, $keep)){
				$delete[$update->_id] = array('_id' => $update->_id, '_rev' => $update->_rev, '_deleted' => true);;
			}
		}
		
		// TODO: check if message belongs in one of the existing events
		
		// keyword burst detection based on hashtags
		$counts = array();
		$total = 0;
		foreach($hashtag_counts as $hashtag => $values){
			$counts[$hashtag] = count($values);
		}
		arsort($counts);
		$sorted = array();
		foreach($counts as $hashtag => $count){
			$total += $count;
			$sorted[$hashtag] = $hashtag_counts[$hashtag];
		}
		$factor = 6; // play with the factor to influence the event detection
		$length = count($hashtag_count);
		$top = array_slice($sorted, 0, round($length / $factor));
		$bottom = array_slice($sorted, round($length / $factor));
		
		$top_count = 0;
		$hashtags = array();
		$filters = array();
		foreach($top as $k => $v){
			$hashtags[] = $k;
			$filters[] = array('name' => 'Hashtag', 'type' => 'term', 'field' => 'entities.hashtags.text', 'value' => $k);
			$top_count += count($v);
		}
		
		$bottom_count = 0;
		foreach($bottom as $k => $v){
			$bottom_count += count($v);
		}
		
		if(($top_count / ($factor * 1.5)) > ($bottom_count / $factor)){
			$event = json_decode('{}');
			$event->hashtags = $hashtags;
			$event->filters = $filters;
			$event->start = time();
			$event->peak = array('timestamp' => time(), 'messages' => $top_count);
			$event->type = 'onbekend'; // TODO: determine this dynamically?
			$event->sub_type = 'onbekend'; // TODO: determine this dynamically?
			$event->name = $event->type; // TODO: determine this dynamically?
			$event->location = array('lat' => 0, 'long' => 0, 'name' => 'onbekend'); // TODO: determine this dynamically?
			$event->total_messages = $top_count;
			$response = $couch->query('POST', 'events/', json_encode($event), 'json');
			if($response && isset($response->id)){
				$event_id = $response->id;
			}
			
			foreach($top as $ids){
				foreach($ids as $id){
					$updates_keys[$id]->event = $event_id;
					$keep[$id] = $updates_keys[$id];
					if(isset($delete[$id])){
						unset($delete[$id]);
					}
				}
			}
		}
		
		// delete "uninteresting" documents
		if(count($delete) > 0){
			$body = '{"docs":' . json_encode($delete) . '}';
			$response = $couch->query('POST', 'updates/_bulk_docs', $body, 'json');
		}
		
		// update "interesting" documents
		if(count($keep) > 0){
			$body = '{"docs":' . json_encode($keep) . '}';
			$response = $couch->query('POST', 'updates/_bulk_docs', $body, 'json');
		}
		
		unset($keep, $delete, $updates, $response);
		
	}
	$process->sleep($start, time());
}

$process->restart();

exit;