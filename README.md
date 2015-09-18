# riot-api-data
A PHP class to handle Riot's (League Of Legends) API

# Methods
	getData($string); // makes a api request
	getStaticData($string); // makes an static data api request
	getShards($region); // get region information. Will retrieve all in no parameter is specified
	setRegion($region); // sets the region to make the requests
