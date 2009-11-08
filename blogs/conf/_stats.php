<?php
/**
 * This is b2evolution's stats config file.
 *
 * @deprecated TODO: It holds now just things that should be move around due to hitlog refactoring.
 *
 * This file sets how b2evolution will log hits and stats
 * Last significant changes to this file: version 1.6
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Self referers that should not be considered as "real" referers in stats.
 * This should typically include this site and maybe other subdomains of this site.
 *
 * The following substrings will be looked up in the referer http header
 * in order to identify referers to hide in the logs.
 *
 * The string must start within the 12 FIRST CHARS of the referer or it will be ignored.
 * note: http://abc.com is already 14 chars. 12 for safety.
 *
 * WARNING: you should *NOT* use a slash at the end of simple domain names, as
 * older Netscape browsers will not send these. For example you should list
 * http://www.example.com instead of http://www.example.com/ .
 *
 * @todo move to admin interface (T_basedomains list editor), but use for upgrading
 * @todo handle multiple blog roots.
 * @todo If $basehost already begins with "www.", the pure domain will not
 *       be counted as a self referrer. Possible solution: Strip the "www."
 *       from $basehost - for "www.example.com" this would give "://example.com"
 *       and "://www.example.com", which would be correct (currently it
 *       gives "://www.example.com" and "://www.www.example.com").
 *
 * @global array
 */
$self_referer_list = array(
	'://'.$basehost,			// This line will match all pages from the host of your $baseurl
	'://www.'.$basehost,	// This line will also match www.you_base_host in case you have no www. on your basehost
	'http://localhost',
	'http://127.0.0.1',
);


/**
 * Blacklist: referrers that should not be considered as "real" referers in stats.
 * This should typically include stat services, online email services, online aggregators, etc.
 *
 * The following substrings will be looked up in the referer http header
 * in order to identify referers to hide in the logs
 *
 * THIS IS NOT FOR SPAM! Use the Antispam features in the admin section to control spam!
 *
 * The string must start within the 12 FIRST CHARS of the referer or it will be ignored.
 * note: http://abc.com is already 14 chars. 12 for safety.
 *
 * WARNING: you should *NOT* use a slash at the end of simple domain names, as
 * older Netscape browsers will not send these. For example you should list
 * http://www.example.com instead of http://www.example.com/ .
 *
 * @todo move to admin interface (T_basedomains list editor), but use for upgrading
 *
 * @global array
 */
$blackList = array(
	// webmails
	'.mail.yahoo.com/',
	'//mail.google.com/',
	'//webmail.aol.com/',
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
 * @todo move to admin interface (specific list editor), include query params
 *
 * @global array $search_engines
 */
$search_engines = array(
	'//www.google.', // q=  and optional start= or cd= when using ajax
	'ask.com/web', // q=
	'.hotbot.',
	'.altavista.',
	'.excite.',
	'.voila.fr/',
	'http://search',
	'://suche.',
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
	'http://www.netscape.',
	'/searchresults/',
	'/websearch?',
	'http://results.',
	'baidu.com/',
	'reacteur.com/',
	'http://www.lmi.fr/',
	'kartoo.com/',
	'icq.com/search',
	'alexa.com/search',
	'att.net/s/', // string=
	'blingo.com/search',  //q=
	'crawler.com/search/',	// q
	'inbox.com/search/', // q
	'scroogle.org/', // GW=
	'cuil.com/',
	'yandex.ru/yandsearch',
	'go.mail.ru/search',
	'//www.bing.com', //q=
	'//cc.bingj.com', // q=
	'.qip.ru/', // query=
);


/**
 * Search params needed to extract keywords from a search engine referer url
 *
 * Typically http://google.com?s=keyphraz returns keyphraz
 *
 * fp> TODO: merge with above table
 *           dh> Piwik might have good data to build upon.
 * fp> TODO: put into configurable database table
 *
 * @global array $known_search_params
 */
$known_search_params =  array(
	'q',
	'as_q',         // Google Advanced Search Query
	'as_epq',       // Google Advanced Search Query
	'query',
	'search',
	's',            // google.co.uk
	'p',
	'kw',
	'qs',
	'searchfor',    // mysearch.myway.com
	'r',
	'rdata',        // search.ke.voila.fr
	'string',       // att.net
	'su',           // suche.web.de
	'Gw',           // scroogle.org
	'text',         // yandex.ru
	'search_query',	// search.ukr.net
	'wd',			// baidu.com
	'keywords',		// gde.ru
);


/**
 * UserAgent identifiers for logging/statistics
 *
 * The following substrings will be looked up in the user_agent http header
 *
 * @todo move to admin interface (T_useragents list editor)
 *
 * 'type' aggregator currently gets only used to "translate" user agent strings.
 * An aggregator hit gets detected by accessing the feed.
 *
 * @global array $user_agents
 */
$user_agents = array(
	// Robots:
	array('robot', 'Googlebot', 'Google (Googlebot)' ), // removed slash in order to also match "Googlebot-Image", "Googlebot-Mobile", "Googlebot-Sitemaps"
	array('robot', 'Slurp/', 'Inktomi (Slurp)' ),
	array('robot', 'Yahoo! Slurp;', 'Yahoo (Slurp)' ),
	array('robot', 'msnbot/', 'MSN Search (msnbot)' ),
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
	array('robot', 'Bitacle bot/', 'Bitacle' ),
	array('robot', 'Sphere Scout', 'Sphere Scout' ),
	array('robot', 'Gigabot/', 'Gigablast (Gigabot)' ),
	array('robot', 'Yandex/', 'Yandex' ),
	array('robot', 'Mail.Ru/', 'Mail.Ru' ),
	array('robot', 'Baiduspider', 'Baidu spider' ),
	array('robot', 'infometrics-bot', 'Infometrics Bot' ),
	array('robot', 'DotBot/', 'DotBot' ),
	array('robot', 'Twiceler-', 'Cuil (Twiceler)' ),
	array('robot', 'discobot/', 'Discovery Engine' ),
	array('robot', 'Speedy Spider', 'Entireweb (Speedy Spider)' ),
	array('robot', 'monit/', 'Monit'),
	// Unknown robots:
	array('robot', 'psycheclone', 'Psycheclone' ),
	// Aggregators:
	array('aggregator', 'AppleSyndication/', 'Safari RSS (AppleSyndication)' ),
	array('aggregator', 'Feedreader', 'Feedreader' ),
	array('aggregator', 'Syndirella/',	'Syndirella' ),
	array('aggregator', 'rssSearch Harvester/', 'rssSearch Harvester' ),
	array('aggregator', 'Newz Crawler',	'Newz Crawler' ),
	array('aggregator', 'MagpieRSS/', 'Magpie RSS' ),
	array('aggregator', 'CoologFeedSpider', 'CoologFeedSpider' ),
	array('aggregator', 'Pompos/', 'Pompos' ),
	array('aggregator', 'SharpReader/',	'SharpReader'),
	array('aggregator', 'Straw ',	'Straw'),
	array('aggregator', 'YandexBlog', 'YandexBlog'),
	array('aggregator', ' Planet/', 'Planet Feed Reader'),
	array('aggregator', 'UniversalFeedParser/', 'Universal Feed Parser'),
);


?>
