<?php
/*
 * b2evolution stats config
 * Version of this file: 0.8.5.1
 *
 * Reminder: everything that starts with #, /* or // is a comment
 */


/*
 * Stat filters:
 */

# Blacklist: domains that should not be logged for stats
# The following substrings will be looked up in the referer http header
# However, you should report spammers in $block_urls[] in _antispam.php, this way they'll
# also be blocked from comments...
$blackList = Array(
	$baseurl.'/',
	'localhost',
	// add your own...
);



# Search engines for statistics
# The following substrings will be looked up in the referer http header
$search_engines = Array(
	'http://www.google.',
	'search.yahoo.com/',
	'.voila.fr/',										// kw =
	'http://www.alltheweb.com/',
	'http://www.daypop.com/',
	'http://www.feedster.com/',
	'http://www.technorati.com/',
	'http://www.weblogs.com/',
	'http://vachercher.lycos.fr/',
	'http://search.lycos.',
	'http://search.sli.sympatico.ca/',
	'http://buscador.terra.es/',			// query =
	'http://search1-1.free.fr/',
	'http://search1-2.free.fr/',
	'http://www.hotbot.com/',
	'http://search.canoe.ca/', 				// q =
	'http://recherche.globetrotter.net/',	//q=
	'search.msn.', 	//q=
	'http://cgi.search.biglobe.ne.jp/', 	//q=
	'aolrecherche.aol.fr/',  //q=
	'altavista.com/',		// q=
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