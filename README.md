# riot-api-data
A PHP class to handle Riot's (League Of Legends) API.

I am very nooby php self-taught programmer, so this class may not be very well optimized. I put this up because I needed one  class to simplify my api requests and all libraries I found were a bit too complex, just had too many features that I didn't really need. Also I found it fun to make and improved my programming skills.

# Methods
- getData($request, $data); // makes a api request. $data must be an associative array
- getStaticData($request, $data); // makes an static data api request. $data must be an associative array
- getShards($region); // get region information. Will retrieve all in no parameter is specified
- setRegion($region); // sets the region to make the requests
- response(); // access the response from the last api call
 - response()->code // gives the header response code. Such as 200 for success or 429 for Rate Limit Exceeded
 - response()->header // returns the full header as a string
 - response()->retryAfter // seconds to wait until it's OK to make anoother request in case 429 was returned
 - response()->body // the actual response. returns an stdClass()

For header codes and the body responses check developer.riotgames.com
# Configuration
Change this values to suit your needs

	private $region = "euw";
	private $key = "";
	private $autoRetry = true; // if rate limit exceeded, should this the script auto retry after "Retry After" (from response header) seconds
	private $memcacheEnabled = false; // rate limiter with memcache to avoid getting your key blacklisted. Recommended!
	private $callsTenSeconds = 10; // max calls per ten seconds (if memcache enabled)
	private $callsTenMinutes = 500; // max calls per ten minutes (if memcache enabled)
	private $timeout = 2; // time to sleep if memcached reached its limit
	private $debugMode = false;
# Requests available
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
	
# #ToDo
- Complete requests/static requests list with all parameters
- Add dynamic configuration on class constructor
