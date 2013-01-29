<?php

use models\Config as Config;
use background_process\BackgroundProcess as BackgroundProcess;
use storage\Storage as Storage;
use request\Request as Request;
use request\OauthRequest as OauthRequest;
use models\Update as Update;
use services\TwitterService as TwitterService;

define('FILE_ROOT', str_replace('processes' . DIRECTORY_SEPARATOR . 'connect_stream.php', '',  __FILE__));
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
$process = new BackgroundProcess('connect_stream.php', Config::$file_root, $php_exec);

$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});

// get all streams
$obj = new \StdClass();
$obj->method = 'GET';
$obj->url = '_design/stream/_view/task?key="detection"&include_docs=true&reduce=false';
$streams = $db->query($obj, 'map');

// create a socket connection for each stream
$fp = array();
foreach($streams as $stream){
	$fp[] = connect($stream);
}

// loop through the connected streams and get their content
while(!$process->stop){
	while(!feof($fp[0])){
		foreach($fp as $conn){
			$data = fgets($conn);
			if($data){
				$updates = explode("\r\n", $data);
				foreach($updates as $update){
					$json = json_decode($update);
					if($json && isset($json->id_str)){
						file_put_contents(VOLUME . Config::$updates_buffer . DIRECTORY_SEPARATOR . $json->id_str . '.txt', $update);
					} else {
						// TODO: stall warning?
					}
				}
			}
		}
	}
}

// close connections if a stream is not responding
foreach($fp as $conn){
	fclose($conn);
}

$process->restart();

exit;

function connect($stream){	
	// create the oauth request and sign it
	$protocol = 'http';
	$scheme = '';
	$port = 80;
	$method = $stream->method;
	$url = parse_url($stream->service->streaming_endpoint);
	$host = $url['host'];
	$path = $url['path'] . $stream->path;
	$parameters = (array)$stream->parameters;
	
	if($stream->ssl){
		$protocol = 'https';
		$scheme = 'ssl://';
		$port = 443;
	}
	$request = new OauthRequest($method, $protocol . '://' . $host . $path,	$parameters);
	$request->sign($stream->service->oauth);
	
	// create a socket connection
	$fp = fsockopen($scheme . $host, $port, $errno, $errstr, 30);
	if(!$fp){
		throw new \Exception($errno . ':' . $errstr);
	} else {
		$req = $method . ' ' . $path . '?' . http_build_query($parameters) . " HTTP/1.1\r\n";
		$req .= "Host: " . $host . "\r\n";
		$req .= $request->headers[0] . "\r\n\r\n";
		fwrite($fp, $req);
	}
	
	return $fp;
}