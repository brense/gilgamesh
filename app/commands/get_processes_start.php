<?php

namespace commands;

use \interfaces\Command as Command;
use \models\Process as Process;
use \storage\Storage as Storage;
use \models\Config as Config;

class get_processes_start implements Command {
	
	public function execute(){
		$couch = Storage::database('CouchDB', Config::$db['couchdb']);
		$response = json_decode($couch->query('GET', 'streams/_all_docs?include_docs=true', array(), 'json'));
		foreach($response->rows as $row){
			$url = 'http://127.0.0.1/afstuderen/gilgamesh2/do/stream/create/?id=' . $row->doc->_id;
			$info = parse_url($url);
			
			$fp = fsockopen($info['host'], 80, $errno, $errstr, 30);
			if($fp){
				$out = "POST " . $info['path'] . " HTTP/1.1\r\n";
				$out.= "Host: 127.0.0.1\r\n";
				$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
				$out.= "Content-Length: " . $info['query'] . "\r\n";
				$out.= "Connection: Close\r\n\r\n";
				$out .= $info['query'];
				fwrite($fp, $out);
				while(!feof($fp)){
					echo fgets($fp);
				}
				fclose($fp);
			}
		}
	}
	
}