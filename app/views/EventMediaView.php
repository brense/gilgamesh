<?php

namespace views;

use \models\Update as Update;
use \models\Profile as Profile;
use \models\Event as Event;
use \storage\Storage as Storage;
use \storage\ElasticSearch as ElasticSearch;
use \models\Config as Config;

class EventMediaView extends AbstractView {
	
	protected $_template = 'event/media';
	
	public function __construct(Array $vars = array()){
		parent::__construct($vars);
		
		$options = array();
		if(isset($this->_vars['options'])){
			$options = $this->_vars['options'];
		}
		
		$event_id = Event::getID();
		
		$filters = array();
		if(isset($_SESSION['filters'][$event_id])){
			$filters = $_SESSION['filters'][$event_id];
		}
		$q = array('event:' . $event_id);
		foreach($filters as $filter){
			if($filter['active'] == 1){
				$vals = explode(',', $filter['value']);
				if(count($vals) > 0){
					foreach($vals as &$val){
						$val = $filter['field'] . ':' . $val;
					}
					$q[] = '(' . implode(' OR ', $vals) . ')';
				} else {
					$q[] = $filter['field'] . ':' . $filter['value'];
				}
			}
		}
		
		$fields = array('_source.entities.media', '_source.entities.urls');
		
		$config = Config::$db->{Config::$db_type};
		$config->search_engine = new ElasticSearch((array)Config::$search);
		$couch = Storage::database(Config::$db_type, (array)$config);
		
		$json = $couch->search(rawurlencode(implode(' AND ', $q)) . '&fields=' . implode(',', $fields), '_score:desc,timestamp:desc', array(0, 1000), 'json');
		
		$media = array();
		foreach($json->rows->hits as $row){
			if(count($row->fields->{'_source.entities.urls'}) > 0){
				foreach($row->fields->{'_source.entities.urls'} as $url){
					$media[] = $url->expanded_url;
				}
			}
			if(isset($row->fields->{'_source.entities.media'})){
				foreach($row->fields->{'_source.entities.media'} as $url){
					$media[] = $url->media_url;
				}
			}
		}
		
		$rows = array_unique($media);
		
		$urls = array();
		foreach($rows as $media){
			$regex = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
			preg_match_all($regex, $media, $match);
			for($n = 0; $n < count($match[0]); $n++){
				$provider = substr($match[0][$n], strlen($match[1][$n])+3, -strlen($match[2][$n]));
				$url = array();
				switch($provider){
					case 'yfrog.com':
						$url['img'] = $match[0][$n] . ':iphone';
						$url['large'] = $match[0][$n] . ':medium';
						$url['type'] = 'image';
						break;
					case 'twitpic.com':
						$url['img'] = 'http://twitpic.com/show/thumb' . $match[2][$n] . '.jpg';
						$url['large'] = 'http://twitpic.com/show/thumb' . $match[2][$n] . '.jpg';
						$url['type'] = 'image';
						break;
					case 'moby.to':
						$url['img'] = $match[0][$n] . ':square';
						$url['large'] = $match[0][$n] . ':medium';
						$url['type'] = 'image';
						break;
					case 'pbs.twimg.com':
						$url['img'] = $match[0][$n] . ':small';
						$url['large'] = $match[0][$n] . ':large';
						$url['type'] = 'image';
						break;
					case 'lockerz.com':
						$url['img'] = 'http://api.plixi.com/api/tpapi.svc/imagefromurl?url=' . $match[0][$n] . '&size=small';
						$url['large'] = 'http://api.plixi.com/api/tpapi.svc/imagefromurl?url=' . $match[0][$n] . '&size=big';
						$url['type'] = 'image';
						break;
					case 'www.youtube.com':
						$url['video'] = 'http://www.youtube.com/embed/' . str_replace('/watch?v=', '', $match[2][$n]);
						$url['large'] = 'http://www.youtube.com/embed/' . str_replace('/watch?v=', '', $match[2][$n]);
						$url['type'] = 'video';
						break;
					case 'tmi.me':
					case 'twitter.com':
					case 'goo.gl':
					case 'www.rtvnoord.nl':
					case 'www.rtl.nl':
					case 'dlvr.it':
					default:
						//$url['link'] = $match[0][$n];
						//$url['type'] = 'link';
						break;
				}
				if(isset($url['type'])){
					$url['href'] = $match[0][$n];
					//$url['text'] = $doc->doc->text;
					$urls[] = $url;
				}
			}
		}
		$this->_vars['urls'] = array_slice($urls, 0, 20);
	}
	
}