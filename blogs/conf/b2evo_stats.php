<?php
/*
 * b2evolution stats config
 * Version of this file: 0.8.3
 *
 * Reminder: everything that starts with #, /* or // is a comment
 */


/*
 * Stat filters:
 */

# Blacklist: domains that should not be logged for stats
# The following substrings will be looked up in the referer http header
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

?>