<?php

use models\Config as Config;
use background_process\BackgroundProcess as BackgroundProcess;
use storage\Storage as Storage;
use request\Request as Request;
use models\Update as Update;

define('FILE_ROOT', str_replace('processes' . DIRECTORY_SEPARATOR . 'text_analysis.php', '',  __FILE__));
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
$process = new BackgroundProcess('text_analysis.php', Config::$file_root, $php_exec);

$n = 0;
while(!$process->stop){
	
	$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
	
	$obj = new \StdClass();
	$obj->method = 'GET';
	$obj->url = rawurlencode('text_analysis');
	$json = $db->query($obj, 'json');
	
	if(isset($json->ww)){ $ww = $json->ww; } else { $ww = array(); }
	if(isset($json->pers)){ $pers = $json->pers; } else { $pers = array(); }
	if(isset($json->q)){ $q = $json->q; } else { $q = array(); }
	if(isset($json->have)){ $have = $json->have; } else { $have = array(); }
	if(isset($json->damage)){ $damage_words = $json->damage; } else { $damage_words = array(); }
	if(isset($json->offer)){ $offer_words = $json->offer; } else { $offer_words = array(); }
	if(isset($json->need)){ $need_word = $json->need; } else { $need_word = ''; }
	if(isset($json->not)){ $not_words = $json->not; } else { $not_words = array(); }
	if(isset($json->positive)){ $positive_words = $json->positive; } else { $positive_words = array(); }
	if(isset($json->negative)){ $negative_words = $json->negative; } else { $negative_words = array(); }
	
	$limit = 100;
	$skip = 0;
	
	$obj = new \StdClass();
	$obj->method = 'GET';
	$obj->url = '_design/update/_view/unanalyzed?include_docs=true&reduce=false' . '&limit=' . $limit. '&skip=' . $skip;
	$source_rows = $db->query($obj, 'json');
	
	if(isset($source_rows->rows) && is_array($source_rows->rows) && count($source_rows->rows) > 0){
		$docs = array();
		foreach($source_rows->rows as $row){
			$doc = $row->doc;
			
			$text = '';
			// NOTE: this needs to be extended for different services (doc.text = Twitter, doc.message = Facebook)
			if(isset($doc->text)){
				$text = $doc->text;
			} else if(isset($doc->message)){
				$text = $doc->message;
			}
			
			// remove urls from text
			$nourls = preg_replace("#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#iS", '', $text);
			
			// split text into sentences
			$regex = '/(?<=[.!?])\s+/';
			$sentences = preg_split($regex, $nourls, -1, PREG_SPLIT_NO_EMPTY);
			
			// set message classifiers
			$info = false;
			$offer = false;
			$need = false;
			$damage = false;
			$emotion = 0; // 0 = unknown, 1 = negative, 2 = positive
			
			$pos = 0;
			$neg = 0;
			
			// loop through all the words
			foreach($sentences as $sentence){
				// set sentence classifiers
				$is_question = false;
				$offer_prob_non_question = false;
				$offer_prob_question = false;
				$need_prob_non_question = false;
				$has_have = false;
				
				// simplest check if message is a question
				if(strpos($sentence, '?') !== false){
					$is_question = true;
				}
				
				// split sentence into words
				preg_match_all('/\w+/', $sentence, $words);
				if(isset($words[0]) && is_array($words[0]) && !isset($words[1])){
					$words = $words[0];
				}
				
				foreach($words as $i => $word){
					// complicated question check
					$x = $i + 1;
					$y = $i - 1;
					if(
						(isset($words[$x]) && in_array(strtolower($word), $ww) && in_array(strtolower($words[$x]), $pers)) ||
						(isset($words[$x]) && in_array(strtolower($word), $q) && in_array(strtolower($words[$x]), $ww))
					){
						$is_question = true;
					}
					
					// classify as damage ("schade en slachtoffers")
					if(in_array(strtolower($word), $damage_words)){
						$damage = true;
					}
					
					// check if sentence contains "have"
					if(in_array(strtolower($word), $have)){
						$has_have = true;
					}
					
					// check probability of an offer or need ("hulp aanbod/vraag")
					if(in_array(strtolower($word), $offer_words)){
						$offer_prob_non_question = true;
					}
					if(strtolower($word) == $need_word){
						$offer_prob_question = true;
					}
					if(strtolower($word) == $need_word && !in_array(strtolower($words[$y]), $not_words)){
						$need_prob_non_question = true;
					}
					
					// check mood (pos/neg)
					if(in_array(strtolower($word), $positive_words)){
						$pos++;
					}
					if(in_array(strtolower($word), $negative_words)){
						$neg++;
						
					}
					
				}
				
				// classify message as offer ("hulp aanbod")
				if(($has_have && !$is_question && $offer_prob_non_question) || ($is_question && $offer_prob_question)){
					$offer = true;
				}
				
				// classify message as need ("hulp vraag")
				if(($has_have && $is_question && $offer_prob_non_question) || (!$is_question && $need_prob_non_question)){
					$need = true;
				}
				
				// classify as info ("informatie behoefte")
				if($is_question && !$offer && !$need){
					$info = true;
				}
				
			}
			
			// classify as positive or negative
			if($pos > $neg){
				$emotion = 2;
			} else if($neg > $pos){
				$emotion = 1;
			} else {
				$emotion = 0;
			}
			
			// load words into one array
			preg_match_all('/\w+/', $nourls, $words);
			
			// set new document parameters
			$doc->filters = array('info' => $info, 'damage' => $damage, 'need' => $need, 'offer' => $offer, 'emotion' => $emotion);
			$doc->words = $words;
			$doc->sentences = $sentences;
			$doc->analyzed = time();
			
			$docs[] = $doc;
		}
	} else {
		// 
	}
	
	$r = $db->bulkCreate($docs);
	
	$n++;
	
}

$process->restart(30);

exit;