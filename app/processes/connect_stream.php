<?php

use models\Config as Config;
use storage\Storage as Storage;
use background_process\BackgroundProcess as BackgroundProcess;
use services\TwitterService as TwitterService;
use request\OauthRequest as OauthRequest;

define('FILE_ROOT', str_replace('app\processes\connect_stream.php', '',  __FILE__));
set_time_limit(0);

require_once(FILE_ROOT . 'app\Application.php');
require_once(FILE_ROOT . 'config.php');

$app = new Application(array(
	'debug' => true,
	'query_caching' => false,
	'bootstrap' => str_replace(FILE_ROOT, '', __FILE__)
));

$process = new BackgroundProcess('connect_stream.php', Config::$file_root, PHP_EXEC);

while(!$process->stop){

	$couch = Storage::database('CouchDB', Config::$db['couchdb']);
	$response = $couch->query('GET', 'streams/_all_docs?include_docs=true', array(), 'json');
	$streams = $response->rows;
	
	// create a socket connection for each stream
	$fp = array();
	$n = 0;
	foreach($streams as $stream){
		// create the twitter service
		$twitter = new TwitterService((array)$stream->doc->oauth_parameters);
		
		// create the oauth request and sign it
		$protocol = 'http';
		$scheme = '';
		$port = 80;
		$method = $stream->doc->request->method;
		$host = $stream->doc->request->host;
		$path = $stream->doc->request->path;
		$parameters = (array)$stream->doc->request->params;
		if(isset($stream->doc->request->ssl) && $stream->doc->request->ssl){
			$protocol = 'https';
			$scheme = 'ssl://';
			$port = 443;
		}
		$request = new OauthRequest($method, $protocol . '://' . $host . $path,	$parameters);
		$request->sign($twitter->oauth);
		
		// create a socket connection
		$fp[$n] = fsockopen($scheme . $host, $port, $errno, $errstr, 30);
		if(!$fp[$n]){
			throw new \Exception($errno . ':' . $errstr);
		} else {
			$req = $method . ' ' . $path . '?' . http_build_query($parameters) . " HTTP/1.1\r\n";
			$req .= "Host: " . $host . "\r\n";
			$req .= $request->headers[0] . "\r\n\r\n";
			fwrite($fp[$n], $req);
		}	
		$n++;
	}
	
	// loop through the streams and get their content
	while(!feof($fp[0])){
		foreach($fp as $conn){
			if(!$process->checkStop()){
				break 2;
			}
			
			$data = fgets($conn);
			if($data){
				$updates = explode("\r\n", $data);
				foreach($updates as $update){
					$json = json_decode($update);
					if($json && isset($json->id_str)){
						file_put_contents(TWITTER_BUFFER . DIRECTORY_SEPARATOR . $json->id_str . '.txt', $update);
					} else {
						// TODO: stall warning?
					}
				}
			}
		}
	}
	
	// close connections if a stream is not responding
	foreach($fp as $conn){
		fclose($conn);
	}
}

$process->restart();

exit;