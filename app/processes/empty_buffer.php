<?php

use models\Config as Config;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;

define('FILE_ROOT', str_replace('app\processes\empty_buffer.php', '',  __FILE__));
set_time_limit(0);

require_once(FILE_ROOT . 'app\Application.php');
require_once(FILE_ROOT . 'config.php');

$app = new Application(array(
	'debug' => true,
	'query_caching' => false,
	'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
));

$process = new BackgroundProcess('empty_buffer.php', Config::$file_root, PHP_EXEC);

$couch = Storage::database('CouchDB', Config::$db['couchdb']);

while(!$process->stop){

	$start = time();
	
	if(!$process->checkStop()){
		break;
	}
	
	// read the buffer
	if($handle = opendir(TWITTER_BUFFER)){
		$message_docs = array();
		$user_docs = array();
		while(false !== ($entry = readdir($handle))){
			if($entry != "." && $entry != ".." && $entry != '_notes'){
				$contents = file_get_contents(TWITTER_BUFFER . '\\' . $entry);
				$json = json_decode($contents);
				if($json){
					// NOTICE: only collecting data from dutch users $json->user->lang == 'nl'
					if((!isset($json->user->protected) || $json->user->protected != '1')/* && $json->user->lang == 'nl'*/){
						$user = $json->user;
						unset($user->_id, $user->type, $user->_rev, $user->profile_background_color, $user->profile_background_image_url, $user->profile_background_image_url_https, $user->profile_background_tile, $user->profile_image_url, $user->profile_image_url_https, $user->profile_link_color, $user->profile_sidebar_border_color, $user->profile_sidebar_fill_color, $user->profile_text_color, $user->profile_use_background_image, $user->default_profile, $user->default_profile_image);
						$json->user = $user;
						$json->_id = 'twitterupdate/' . $json->id_str;
						$json->type = 'TwitterUpdate';
						$message_docs[] = $json;
					} else {
						unlink(TWITTER_BUFFER . '\\' . $entry);
					}
				} else {
					unlink(TWITTER_BUFFER . '\\' . $entry);
				}
			}
		}
		closedir($handle);
		unset($handle);
	}
	
	// submit the docs to the couch
	$response_mq = $couch->query('POST', 'afstuderen/_bulk_docs', '{"docs":' . json_encode($message_docs) . '}', 'json');
	
	// remove the submitted docs from the buffer
	$tweet_counter = 0;
	foreach($response_mq as $entry){
		if(isset($entry->id) && file_exists(TWITTER_BUFFER . '\\' . $entry->id . '.txt')){
			unlink(TWITTER_BUFFER . '\\' . $entry->id . '.txt');
			$tweet_counter++;
		}
	}
	
	$process->sleep($start, time());
}

$process->restart();

exit;