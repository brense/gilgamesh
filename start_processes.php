<?php

use models\Config as Config;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;
use services\TwitterService as TwitterService;
use request\OauthRequest as OauthRequest;

define('FILE_ROOT', str_replace('start_processes.php', '',  __FILE__));

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

$path = FILE_ROOT . 'app' . DIRECTORY_SEPARATOR . 'processes';
if($handle = opendir($path)){
    while(false !== ($entry = readdir($handle))){
        if(!is_dir($path . DIRECTORY_SEPARATOR . $entry)){
			$process = new BackgroundProcess($entry, FILE_ROOT, PHP_EXEC);
			$process->execute();
		}
    }
    closedir($handle);
}

exit;