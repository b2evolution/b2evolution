<?php
/**
 * This is b2evolution's stats config file
 *
 * This file sets how b2evolution will log hits and stats
 * Last significant changes to this file: version 0.9.1
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * How many days of stats do you want to keep before auto pruning them?
 *
 * Set to 0 to disable auto pruning
 *
 * @global int $stats_autoprune
 */
$stats_autoprune = 30; // Default: 30 days



/**
 * Blacklist: referrers that should be hidden in stats. This should typically include this
 * site as well as stat services, online email services, online aggregators, etc.
 *
 * The following substrings will be looked up in the referer http header
 * in order to identify referers to hide in the logs
 *
 * THIS IS NOT FOR SPAM! Use the Antispam features in the admin section to control spam!
 *
 * WARNING: you should *NOT* use a slash at the end of simple domain names, as
 * older Netscape browsers will not send these. For example you should list
 * http://www.example.com instead of http://www.example.com/ .
 *
 * TODO: handle multiple blog roots.
 *
 * @global array $blackList
 */
$blackList = array(
	substr( $baseurl, 0, strlen($baseurl)-1 ),	// Remove tailing slash
	'http://localhost',
	'http://127.0.0.1',
	// stat services
	'sitemeter.com/',
	// aggregators
	'bloglines.com/',
	// caches
	'/search?q=cache:',		// Google cache
	// redirectors
	'googlealert.com/',
	// add your own...
);



/**
 * Search engines for statistics
 *
 * The following substrings will be looked up in the referer http header
 * in order to identify search engines
 *
 * @global array $search_engines
 */
$search_engines = array(
	'google.',
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
	'http://netscape.',
	'/searchresults/',
	'/websearch?',
	'http://results.',
	'baidu.com/',
	'reacteur.com/',
	'http://www.lmi.fr/',
	'kartoo.com/',
);


/**
 * UserAgent identifiers for logging/statistics
 *
 * The following substrings will be looked up in the user_agent http header
 *
 * @global array $user_agents
 */
$user_agents = array(
	// Robots:
	array('robot', 'Googlebot/', 'Google (Googlebot)' ),
	array('robot', 'Slurp/', 'Inktomi (Slurp)' ),
	array('robot', 'Yahoo! Slurp;', 'Yahoo (Slurp)' ),
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


/**
 * Do you want to check if referers really do refer to you before logging them
 *
 * WARNING: this is very time consuming!
 *
 * @global boolean $doubleCheckReferers
 */
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