<?php

use models\Config as Config;
use background_process\BackgroundProcess as BackgroundProcess;
use storage\Storage as Storage;
use request\Request as Request;
use models\Update as Update;

define('FILE_ROOT', str_replace('processes' . DIRECTORY_SEPARATOR . 'event_detection.php', '',  __FILE__));
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
$process = new BackgroundProcess('event_detection.php', Config::$file_root, $php_exec);

while(!$process->stop){
	$start = microtime(true);
	
	$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
	
	// get all "orphan" updates
	$obj = new \StdClass();
	$obj->method = 'GET';
	$obj->url = '_design/update/_view/orphan?include_docs=true&reduce=false';
	$updates = $db->query($obj, 'map');
	
	// get all events
	$obj = new \StdClass();
	$obj->method = 'GET';
	$obj->url = '_design/event/_view/all?include_docs=true&reduce=false';
	$events = $db->query($obj, 'map');
	
	// TODO: get these more dynamically
	// users to follow
	$users = array(
		'87394935', // ?
		'97639259',
		'21873132',
		'223792086',
		'103819591',
		'202991059',
		'127227875',
		'60863284',
		'111323058',
		'137290023',
		'87399607'
	);
	
	// special users
	$event_sources = array(
		'310806591', // HVGripNL
		'21873132',  // P2000 Groningen
		'451432440'  // P2000 Groningen 2
	);
	
	$keep = array();
	$delete = array();
	
	if(count($updates) == 0){
		sleep(10);
		break;
	}
	
	$keywords = array();
	
	foreach($updates as $k => &$update){
		if(fromUsers($update, $users) || linkToExistingEvent($update, $events)){
			$keep[] = $k;
		} else {
			$delete[] = $k;
		}
		foreach($update->labels as $hashtag){
			if(in_array(strtolower($hashtag), $keywords)){
				$keywords[strtolower($hashtag)]++;
			} else {
				$keywords[strtolower($hashtag)] = 1;
			}
		}
	}
	
	keywordsBurstDetection($keywords, $updates, $keep, $delete);
	
	// get full json documents for relevant updates
	$ids = array();
	$ids_assoc = array();
	foreach($keep as $k){
		$ids[] = $updates[$k]->id;
		$ids_assoc[$k] = $updates[$k]->id;
	}
	$obj = new \StdClass();
	$obj->method = 'POST';
	$obj->url = '_all_docs?include_docs=true';
	$obj->body = json_encode(array('keys' => $ids));
	$json = $db->query($obj, 'json');
	$keep = array();
	if($json && isset($json->rows)){
		foreach($json->rows as $row){
			$doc = $row->doc;
			$k = array_search($doc->_id, $ids_assoc);
			$keep[$k] = $doc;
		}
	}
	
	$docs = array();
	foreach($updates as $k => $doc){
		if(isset($keep[$k])){
			$obj = $keep[$k];
		} else {
			$obj = json_decode((string)$doc);
		}
		$obj->event = $doc->event;
		$docs[$k] = $obj;
	}
	
	// set non-relevant documents to be deleted
	foreach($delete as $k){
		$docs[$k]->_deleted = true;
	}
	
	// update the orphan documents
	$obj = new \StdClass();
	$obj->method = 'POST';
	$obj->url = '_bulk_docs';
	$obj->body = json_encode(array('docs' => $docs));
	$json = $db->query($obj, 'json');
	
	// calculate sleep time (10 seconds is the target)
	$end = microtime(true);
	usleep(10000000 - ($end - $start));
}

$process->restart(30);

exit;

function fromUsers(&$update, $users){
	if(in_array($update->profile->id, $users)){
		$update->event = '0';
		return true;
	} else {
		return false;
	}
}

function linkToExistingEvent(&$update, $events){
	$profiles = array();
	foreach($events as $event){
		foreach($event->hashtags as $hashtag){
			$profiles[strtolower($hashtag)] = $event->id;
		}
	}
	foreach($update->labels as $hashtag){
		$event_ids = array();
		$total = 0;
		foreach($profiles as $compare => $event_id){
			if(strtolower($hashtag) == $compare){
				if(isset($event_ids[$event_id])){
					$event_ids[$event_id]++;
				} else {
					$event_ids[$event_id] = 1;
				}
				$total++;
			}
		}
		arsort($event_ids);
		foreach($event_ids as $event_id => $count){
			$certainty = $count / $total;
			$update->event = $event_id;
			return true;
		}
	}
	return false;
}

function keywordsBurstDetection($keywords, &$updates, &$keep, &$delete){
	 // play with these values to adjust detection
	$target = 10; // 10 is pretty low value
	$top_percent = 10;
	
	$total = count($keywords);
	$top_slice = round(($total / 100) * $top_percent);
	$sum = 0;
	foreach($keywords as $k => $v){
		$sum += $v;
	}
	arsort($keywords);
	$top = array_slice($keywords, 0, $top_slice);
	$top_sum = 0;
	$top_keywords = array();
	foreach($top as $k => $v){
		$top_sum += $v;
		$top_keywords[] = $k;
	}
	
	if($sum == 0){
		$factor = 0;
	} else {
		$factor = (100 / $sum) * $top_sum;
	}
	
	if($factor >= $target){
		// create new event object
		$event = new \StdClass();
		$event->hashtags = array();
		$event->name = $top_keywords[0];
		$event->total_messages = 0;
		$event->event_type = 'onbekend';
		$event->end = 0;
		$event->start = 0;
		$event->location = array('lat' => 0, 'long' => 0, 'name' => 'onbekend');
		$event->filters = array();
		$event->peak = array('timestamp' => time(), 'messages' => 0);
		$event->type = 'Event';
		$event->_id = 'event/' . str_replace('.', '', (string)microtime(true));
		
		$n = 0;
		foreach($top_keywords as $keyword){
			$event->filters[] = array('type' => 'term', 'name' => 'Hashtag', 'value' => '#' . $keyword, 'field' => 'entities.hashtags.text');
			$n++;
			if($n >= 3){
				break;
			}
		}
		
		// find updates for event
		$timestamps = array();
		foreach($updates as $k => &$update){
			$link = false;
			foreach($update->labels as $hashtag){
				foreach($top_keywords as $keyword){
					if(strtolower($hashtag) == $keyword){
						if(isset($event->hashtags[$keyword])){
							$event->hashtags[$keyword]++;
						} else {
							$event->hashtags[$keyword] = 1;
						}
						$link = true;
					}
				}
			}
			if($link){
				$keep[] = $k;
				unset($delete[$k]);
				$event->total_messages++;
				$timestamps[] = strtotime($update->created);
				$update->event = str_replace('event/', '', $event->_id);
			}
		}
		
		sort($timestamps);
		
		$event->start = $timestamps[0];
		$event->peak = array('timestamp' => time(), 'messages' => $event->total_messages);
		
		// add event to database
		$obj = new \StdClass();
		$obj->method = 'POST';
		$obj->url = '_bulk_docs';
		$obj->body = json_encode(array('docs' => array($event)));
		$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
		$db->query($obj, 'json');
	}
}