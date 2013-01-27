<?php

namespace controllers;

use \storage\Storage as Storage;
use \models\Config as Config;
use \models\Event as Event;

class EventController {
	
	public static function find($query = null){
		$couch = Storage::database('CouchDB', Config::$db['couchdb']);
		$couch->mapper->setObject(new Event());
		$events = $couch->query('GET', 'events/_all_docs?include_docs=true', null, 'map');
		$events = $couch->mapper->flatten($events);
		echo json_encode($events);
	}
	
}