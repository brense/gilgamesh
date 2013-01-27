<?php

namespace request;

use oauth\Oauth as Oauth;

class OauthRequest extends Request {
	public function sign(Oauth $oauth, Array $options = array()){
		$includeCallback = false;
		if(isset($options['include_callback']) && $options['include_callback'] === true){
			$includeCallback = true;
		}
		$oauth->signRequest($this, $includeCallback);
	}
}