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
	
	// TODO: these should come from the database
	$ww = array('heb', 'heeft', 'hebben', 'ga', 'gaat', 'gaan', 'is', 'ben', 'zijn'); // TODO: <- incomplete
	$pers = array('je', 'jij', 'ik', 'wij', 'we', 'men', 'zij', 'hij', 'jullie', 'de', 'het', 'iemand'); // TODO: <- incomplete
	$q = array('hoe', 'wat', 'waar', 'waarom', 'wanneer', 'wie', 'welke');
	$have = array('heb', 'heeft', 'hebben');
	$damage_words = array('schade', 'kapot', 'beschadigd', 'gewonden', 'gewonde', 'slachtoffer', 'slachtoffers', 'dode', 'doden', 'letsel'); // TODO: <- incomplete
	$offer_words = array('aangeboden', 'aanbod', 'aanbieden', 'beschikbaar');
	$need_word = 'nodig';
	$not_words = array('niet', 'geen');
	$positive_words = array("aandacht","aandacht wetenschap","actie","actiebereidheid","aktie","alert zijn","anders","beheersbaar","behulpzaamheid","belemmering ","aterstroom","betrokkenheid","betrouwbare overheid","bevestiging","bewustzijn","bezining","bij het wereldnieuws","bijzonder","bijzondere situatie","blij te kunnen helpen","blijdschap","boeiend","buitendijks vrij","buitenland","collega's","communicatie","cool","coördinatie","daadkracht","dankbaarheid","deltaplan","deprie","dienstverlening","dijk breekt niet door","dijkverhoging","dijkverzwaring","donald duck","dynamiek","eendrachtmoed","eenheid","eensgezindheid","eigen initiatief","elkaar helpen","emoties","er voor gaan","er wordt over ons gewaakt","fris","gaaf","gastvrijheid","geen paniek","geevacueerde","geholpen","geinformeerd","geluk","gemeenschapszin","genegenheid","gereed","gezamenlijk","gezelligheid","goed en welbehagelijk","goed reddingswerk","goede afloop","goede opvang","helpen","herinneringen aan mijn vroegste jeugd","het samen zijn we sterk","hoop","huisartsen","hulp","hulp acties","hulp bieden","hulp door redders","hulp fam. vrienden","hulp geven","hulp krijgen","hulpvaardigheid","hulpvaardigheid Ned. bevolking","hulpverleners","hulpverlening","hulpverlening o.a.militairen","iets voor elkaar overhebben","improvisatie","improvisatievermogen van particulieren","indrukwekkend","informatie","informatieverwerving","ingelicht","inzet van militairen","inzicht","je meer kan dan je denkt","kind","kind van 2 jaar","know how","kracht van water","kwaliteit dijken","lachwekkende paniek","leerzaam","leren","leven met natuur","leven met water","liefde","lotsverbondenheid","macht over het water","macht van natuur","macht van water","Mede leven met betrokkene","medeleven","meegedaan","meehelpen","meeleven","mensen evacueren","mensen in huis genomen","mijn","moed","mooi","mooi natuurverschijnsel","mooi om te zien","mooie natuur","naastenhulp","naastenliefde","natuur","natuurkracht","nieuwe vrienden","nieuwsgierig","nog ok","onderdak bij mensen","ontzag voor natuurgeweld","opgewekt","opgewonden familie","ophogen dijken","opluchting","opofferingsgezindheid","optimistichs","opvang","opvang door burgers","opvang evacuees","opvang fam","opvang slachtoffers","opvang van daklozen thuis","opwinding","organisatie","overleven","overwinning","overzicht","prestatie","proberen te helpen","puur natuur","rampenfonds","rampenplan","redding","redelijk veilig","respect voor natuur","rode kruis eerste opvang","saamhorigheid","saamhorigheid bij dijkbewaking","saamhorigheid in die tijd","samen doen","samen ervoor gaan","samen zijn","samenbinding","samenwerken bij evacuatie waar nodig","samenwerking","schaatsen op natuurijs","schitterend gezicht 1993","schoon","schoonheid","sensatie","snelle acties","soldariteit","solidariteit","spannend","spannend als kind","spanning","spanning en sensatie","spontane hulpverlening","stormvloedkering","uitzicht","uniek","vakantie","valt mee","veel sneeuwval","veiligheid","verandering","verbazing","verbondenheid","Verbroedering","verrassend","verstandig","vertrouwen","vertrouwen in overheid","vervoer verzorgen","viel toch mee achteraf","vluchtplannen maken","voel me veilig","voorbereid","voorkomen","vriendschap","vrijheid","vrolijk","waakzaam","waarschuwing","warmte","was jong en spannend","waterstand","waterstof","wederopbouw","wel spannend","werkgelegenheid","werkzaamheden","yes","zekerheid","zorgzame overheid");
	$negative_words = array("achterstallig onderhoud","afgekalfd","afhankelijkheid","als kind goed","angst","bedreigend","bedreiging","bedrukt","belangen","belangenverstrengeling","bereikbaarheid","berusting","besluiteloosheid","bezorgd","bezorgd om huis","bezuinigen","boos","buikpijn","chagrijnig","chaos","communicatie","de velen doden","dieren","dierenleed","dierenleed en doden","dijken","dit kan toch niet meer","doden","dom","dood","dreiging","egoisme","ellende","evacuatie","evacueren","fatalisme","financ.nadeel","financieel","gebrek aan daadkracht","gedoe","geen info door waterschap","gespannen","gevaar","gezeur over geld","graaicultuur","hectisch","hel","helpen","herstel","hersteltijd","hevige regenperiode","hoe lang nog?","hoe ver kan dit gaan","hopeloos","hulp","hulp na ramp","hulpeloos","hulpeloosheid","hulpenloos","informatie","informatie/media","inlichtingen","is er nieuws","is ons waterschap voldoende voorbereid?","jammer","jammeren over godsoordeel","kans op ramptoerisme en berovingen","klimaat","klote","kneuterigheid","kostenaspect","kostenplaatje","kracht van het water","kut","kwam erg dichtbij","lage landen","lastig voor de bewoners","later slecht verdeelt","leugens","ligging","machteloosheid","medelijden","mens klein","milieu","milieubeweging","milieu-freaks","moeten oppassen","naïviteit","nalatige overheid","natuur maffen","natuurgeweld","natuurvernietiging","nietige dijkjes tov het water","nu hopen beter","omvang gevolgen","onbegrip","onbehagen","onderschatting","onduidelijk","onduidelijkheid","ongerust","ongerustheid","onheilspellend","onkunde dijkbewaking","onkunde overheid","onmacht overheid","onnodig","onrust","ontbreken rampenplan","ontheemd","ontpolderen","onveilig voor mensen","onveiligheid","onvoldoende","onvoldoende voorbereid","onvoorbereid","onvoorbereidheid","onvoorspelbaarheid","onwetend","onwetendheid","onwetenheid","onzekerheid","oorlogs gevoel","opgeblazen","overheid","overheid doet niets","overheid weet niets","overmacht","overrompeld","overweldiging","paniek","paniek personeel waterschap","pers","pijn","poolkappen","provincie","ramp","ramptoeristen","reddeloos","regelzucht","regering","rivieren","rommelig","saamhorigheid","schade","schade landbouw","schijnveiligheid","schrik","slachtoffers","slecht dijkonderhoud","slechte informatie","slechte organisatie evacuatie","slechte organistatie","somber","spannend","spanning","spanningen","staan tegen over het","stormangst boomomwaaien","stress","teleurstelling","toekomst","traag","tragisch","triest","troep uit de rivieren","uitbuiting","vader lang van huis","veel kosten","veel werk","veiligheid","verandering","verbaasd","verbazing","verbijstering","verdriet","verdriet voor de mensen","verdrinking","vergoedingsramp","verhuizing","verlatenheid","verlies","vernietiging","verrassend","verschrikking","verwacht hulp","verwarring","verwoesting","vluchten","voorlichting","waarheen","waarschuwingen te laat","wakker worden","wanbeleid","wanhoop","wanneer stopt het stijgen","wantrouwen","water machtig","waterschap","watervrees","werkkamp","woede","zielig voor de dieren","zorg om familie","zorgen");
	
	$db = Storage::database(Config::$db_type, (array)Config::$db->{Config::$db_type});
	
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