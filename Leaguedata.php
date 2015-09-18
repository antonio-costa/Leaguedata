<?php

class Leaguedata {
	private $requests = array(
			"summoner" => "v1.4/summoner/{summonerIds}",
			"summoner-by-name" => "v1.4/summoner/by-name/{summonerNames}",
			"summoner-masteries" => "v1.4/summoner/{summonerIds}/masteries",
			"summoner-runes" => "v1.4/summoner/{summonerIds}/runes",
			"summoner-name" => "v1.4/summoner/{summonerIds}/name",
			"champion" => "v1.2/champion/{id}",
			"game" => "v1.3/game/by-summoner/{summonerId}/recent",
			"league" => "v2.5/league/by-summoner/{summonerIds}",
			"league-entry" => "v2.5/league/by-summoner/{summonerIds}/entry",
			"league-team" => "v2.5/league/by-team/{teamIds}",
			"league-entry-team" => "v2.5/league/by-team/{teamIds}/entry",
			"league-challenger" => "v2.5/league/challenger",
			"league-master" => "v2.5/league/master",
			"match" => "v2.2/match/{matchId}",
			"matchlist" => "v2.2/matchlist/by-summoner/{summonerId}?championIds={championId}&rankedQueues={rankedQueues}&seasons={seasons}&beginTime={beginTime}&endTime={endTime}&beginIndex={beginIndex}&endIndex={endIndex}",
			"stats-ranked" => "v1.3/stats/by-summoner/{summonerId}/ranked?season={season}",
			"stats-summary" => "v1.3/stats/by-summoner/{summonerId}/summary?season={season}"
		);
	private $staticRequests = array(
			"champion" => "v1.2/champion/{id}",
			"item" => "v1.2/item/{id}"
		);
	private $baseUrl = "https://{region}.api.pvp.net/api/lol/{region}/";
	private $staticBaseUrl = "https://global.api.pvp.net/api/lol/static-data/{region}/";
	private $ch;
	private $response;	
	private $memcache;

	private $region = "euw";
	private $key = "448ee896-f0fb-4f04-93e5-1a9cc1594683";
	private $autoRetry = true;
	private $memcacheEnabled = true;
	private $callsTenSeconds = 10;
	private $callsTenMinutes = 500;
	private $timeout = 2;
	private $debugMode = true;

	public function __construct() {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
		curl_setopt($this->ch, CURLOPT_HEADER, 1);

		$this->response = new stdClass();

		if($this->memcacheEnabled) {
			$this->startMemcache();
		}
	}

	public function __destruct() {
		curl_close($this->ch);
		$this->closeMemcache();
	}
	/* ----------------------------------------------------
	*	Gets shards
	*	
	*	@param string 	$region 		get shards of a specific region. Leave blank for all
	*
	------------------------------------------------------- */
	public function getShards($region = null) {
		$this->execRequest("http://status.leagueoflegends.com/shards".($region !== null ? "/".$region : ""));
	}
	/* ----------------------------------------------------
	*	Parses and executes an api static data request
	*	
	*	@param string 	$req 		request key from $this->requests. If not in array, use it as is
	*   @param array 	$data 		array with data to fill the request
	*
	------------------------------------------------------- */
	public function getStaticData($req, $data = null) {
		$r = $this->parseRequest($req, $data, true);
		$this->execRequest($r);
	}
	/* ----------------------------------------------------
	*	Parses and executes an api request
	*	
	*	@param string 	$req 		request key from $this->requests. If not in array, use it as is
	*   @param array 	$data 		array with data to fill the request
	*
	------------------------------------------------------- */
	public function getData($req, $data = null) {
		$r = $this->parseRequest($req, $data);
		$this->execRequest($r);
	}
	/* ----------------------------------------------------
	*	Parses a api request
	*	
	*	@param string 	$req 		request key from $this->requests. If not in array, use it as is
	*   @param array 	$data 		array with data to fill the request
	* 
	* 	@return string 	parsed request
	*
	------------------------------------------------------- */
	public function parseRequest($req, $data = null, $static = false) {
		if($static) $r = $this->staticBaseUrl;
		else $r = $this->baseUrl;

		if($static) $reqs = $this->staticRequests;
		else $reqs = $this->requests;

		if(isset($reqs[$req])) {
			$r .= $reqs[$req];
		}
		else {
			$r .= $req;
		}

		if(is_array($data)) {
			foreach($data as $key=>$value) {
				$r = str_replace("{".$key."}", $value, $r);		
			}
		}

		$r = str_replace("{region}", $this->region, $r);
		$r .= ( (strpos($r, "?") === false) ? "?" : "&") . "api_key=".$this->key;

		$r = preg_replace("/{(.*?)}/", "", $r);

		return $r;
	}
	/* ----------------------------------------------------
	*	Executes a request
	*	
	*	@param string 	$req 		request query
	*
	------------------------------------------------------- */
	public function execRequest($r) {
		if($this->memcacheEnabled) {
			$timeout = $this->memcacheRateLimit();		
			if($timeout) {
				$this->debug("Error #430 executing request: ".$r);
				sleep($timeout);
				if($this->autoRetry) {
					$this->execRequest($r);
				}
				return;
			}
		}
		// set up and execute the request
		curl_setopt($this->ch, CURLOPT_URL, $r);
		$response = curl_exec($this->ch);
		$header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);

		$this->response->code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->response->header = substr($response, 0, $header_size);
		$this->response->body = json_decode(substr($response, $header_size));

		// handle rate limit exceeding
		if($this->response->code == 429) {
			$this->debug("Error #429  executing request: ".$r);

			if($this->autoRetry) {
				$this->debug("(#429) retry after: ".$this->response->body->retryAfter);
				sleep($this->response->body->retryAfter);
				$this->execRequest($r);
				return;
			}
		}
		else if($this->response->code != 200) {
			$this->debug("Error #".$this->response->code." executing request: ".$r);
		}

		if($this->memcacheEnabled) {
			$this->memcache->add('tenSec', 1, false, 10);
			$this->memcache->add('tenMin', 1, false, 10*60);

			$this->memcache->increment('tenSec');
			$this->memcache->increment('tenMin');
		}
	}
	/* ----------------------------------------------------
	*	Returns current request's response
	*	
	*	@return stdClass
	*
	------------------------------------------------------- */
	public function response() {
		return $this->response;
	}

	/* ----------------------------------------------------
	*	Initializes memcache
	*
	------------------------------------------------------- */
	public function startMemcache() {
		$this->memcache = new Memcache();
		$this->memcache->connect('localhost', 11211);
	}
	/* ----------------------------------------------------
	*	Closes memcache
	*
	------------------------------------------------------- */
	public function closeMemcache() {
		if($this->memcache !== null)
			$this->memcache->close();
	}
	/* ----------------------------------------------------
	*	Enforces the rate limit with memcache
	*	
	*	@return int 	time in seconds to sleep (0 if limit hasn't been exceeded)
	*
	------------------------------------------------------- */
	public function memcacheRateLimit() {
		$tenSec = $this->memcache->get('tenSec');
		$tenMin = $this->memcache->get('tenMin');

		if($tenSec >= $this->callsTenSeconds
		|| $tenMin >= $this->callsTenMinutes) {
			$this->response->code = 430;
			return $this->timeout;
		}
		return 0;
	}
	/* ----------------------------------------------------
	*	Debug functions
	*
	------------------------------------------------------- */
	public function debug($msg) {
		if($this->debugMode)
			var_dump($msg);
	}
	public function debug_memcached() {
		if($this->debugMode)
			var_dump($this->memcache->get('tenSec'), $this->memcache->get('tenMin'));
	}
}
