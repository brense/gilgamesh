<?php

// TODO: this script currently only excepts tweets, switch need to be expanded

use models\Config as Config;
use background_process\BackgroundProcess as BackgroundProcess;
use storage\Storage as Storage;
use request\Request as Request;
use models\Update as Update;
use services\TwitterService as TwitterService;

define('FILE_ROOT', str_replace('processes' . DIRECTORY_SEPARATOR . 'empty_buffer.php', '',  __FILE__));
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
$process = new BackgroundProcess('empty_buffer.php', Config::$file_root, $php_exec);

while(!$process->stop){
	
	$start = microtime(true);
	
	$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
	
	// read the buffer
	if($handle = opendir(VOLUME . Config::$updates_buffer)){
		$message_docs = array();
		while(false !== ($entry = readdir($handle))){
			if(!is_dir(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $entry)){
				$contents = file_get_contents(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $entry);
				$json = json_decode($contents);
				if($json){
					$source = '';
					if(isset($json->created_at) && isset($json->id_str)){
						$source = 'twitter';
					}
					// TODO: make if's for other services
					
					switch($source){
						case 'twitter':
							// NOTICE: only collecting data from dutch speaking users is unreliable: $json->user->lang == 'nl'
							if((!isset($json->user->protected) || $json->user->protected != '1')/* && $json->user->lang == 'nl'*/){
								$user = $json->user;
								unset($user->_id, $user->type, $user->_rev, $user->profile_background_color, $user->profile_background_image_url, $user->profile_background_image_url_https, $user->profile_background_tile, $user->profile_link_color, $user->profile_sidebar_border_color, $user->profile_sidebar_fill_color, $user->profile_text_color, $user->profile_use_background_image, $user->default_profile, $user->default_profile_image);
								$json->user = $user;
								$json->_id = 'twitterupdate/' . $json->id_str;
								$json->type = 'TwitterUpdate';
								$json->timestamp = strtotime($json->created_at);
								$json->service = 'service/twitter';
								$message_docs[] = $json;
							} else {
								unlink(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $entry);
							}
							break;
						// TODO: make cases for other services
					}
				} else {
					unlink(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $entry);
				}
			}
		}
		closedir($handle);
		unset($handle);
	}
	
	// submit the docs to the couch
	if(isset($message_docs)){
		$response = $db->bulkCreate($message_docs);
	}
	
	// remove the submitted docs from the buffer
	$tweet_counter = 0;
	if(isset($response) && is_array($response)){
		foreach($response as $entry){
			$id = @array_pop(@explode('/', $entry));
			if(file_exists(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $id . '.txt')){
				unlink(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $id . '.txt');
				$tweet_counter++;
			}
		}
	}
	
	// calculate sleep time (10 seconds is the target)
	$end = microtime(true);
	usleep(10000000 - ($end - $start));
}

$process->restart();

exit;