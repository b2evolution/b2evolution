<?php
/*
 * b2evolution stats config
 * Version of this file: 0.9
 *
 * Reminder: everything that starts with #, /* or // is a comment
 */


/*
 * Stat filters:
 */

# Blacklist: domains that should not be logged for stats
# The following substrings will be looked up in the referer http header
# THIS IS NOT FOR SPAM! Use the Antispam features in the admin section to control spam
$blackList = Array(
	$baseurl.'/',
	'localhost',
	'127.0.0.1',
	// stat services
	'sitemeter.com/',
	// add your own...
);



# Search engines for statistics
# The following substrings will be looked up in the referer http header
$search_engines = Array(
	'.google.',
	'.hotbot.',
	'.altavista.',
	'.excite.',
	'.voila.fr/',
	'http://search',
	'search.',
	'search2.',
	'http://recherche',
	'recherche.',
	'recherches.',
	'vachercher.',
	'feedster.com/',
	'alltheweb.com/',
	'daypop.com/',
	'feedster.com/',
	'technorati.com/',
	'weblogs.com/',
	'exalead.com/',
	'killou.com/',
	'buscador.terra.es',
	'web.toile.com',
	'metacrawler.com/', 
	'.mamma.com/', 
	'.dogpile.com/', 
	'search1-1.free.fr', 
	'search1-2.free.fr', 
	'overture.com', 
	'startium.com', 
	'2020search.com', 
	'bestsearchonearth.info', 
	'mysearch.com', 
	'popdex.com', 
	'64.233.167.104', 
	'seek.3721.com', 
);


# UserAgent identifiers for statistics
# The following substrings will be looked up in the user_agent http header
$user_agents = array(
	// Robots:
	array('robot', 'Googlebot/', 'Google (Googlebot)' ),
	array('robot', 'Slurp/', 'Inktomi (Slurp)' ),
	array('robot', 'Frontier/',	'Userland (Frontier)' ),
	array('robot', 'ping.blo.gs/', 'blo.gs' ),
	array('robot', 'organica/',	'Organica' ),
	array('robot', 'Blogosphere/', 'Blogosphere' ),
	array('robot', 'blogging ecosystem crawler',	'Blogging ecosystem'),
	array('robot', 'FAST-WebCrawler/', 'Fast' ),			// http://fast.no/support/crawler.asp
	array('robot', 'timboBot/', 'Breaking Blogs (timboBot)' ),
	array('robot', 'NITLE Blog Spider/', 'NITLE' ),
	array('robot', 'The World as a Blog ', 'The World as a Blog' ),
	array('robot', 'daypopbot/ ', 'DayPop' ),
	// Aggregators:
	array('aggregator', 'Feedreader', 'Feedreader' ),
	array('aggregator', 'Syndirella/',	'Syndirella' ),
	array('aggregator', 'rssSearch Harvester/', 'rssSearch Harvester' ),
	array('aggregator', 'Newz Crawler',	'Newz Crawler' ),
	array('aggregator', 'MagpieRSS/', 'Magpie RSS' ),
	array('aggregator', 'CoologFeedSpider', 'CoologFeedSpider' ),
	array('aggregator', 'Pompos/', 'Pompos' ),
	array('aggregator', 'SharpReader/',	'SharpReader'),
	array('aggregator', 'Straw ',	'Straw'),
);


# Do you want to check if referers really do refer to you before logging them
# WARNING: this is very time consuming!
$doubleCheckReferers = 0;		// Set to 1 to enable double checking


# Do not change the following unless you know what you're doing...
# Due to potential non-thread safety, we'd better do this early
if( !isset( $HTTP_REFERER ) )
{	// If this magic variable is not already set:
	if( isset($_SERVER['HTTP_REFERER']) )
	{	// This would be the best way to get the referrer,
		// unfortunatly, it's not always avilable!! :[
		// If someone has a clue about this, I'd like to hear about it ;)
		$HTTP_REFERER = $_SERVER['HTTP_REFERER'];
	}
	else
	{	// Fallback method (not thread safe :[[ )
		$HTTP_REFERER = getenv('HTTP_REFERER');
	}
}


?>