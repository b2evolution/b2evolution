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
	// site status services
	'host-tracker.com',
	// add your own...
);


/**
 * UserAgent identifiers for logging/statistics
 *
 * The following substrings will be looked up in the user_agent http header
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
	array('robot', 'Yahoo! Slurp', 'Yahoo (Slurp)' ), // removed ; to also match "Yahoo! Slurp China"
	array('robot', 'msnbot', 'MSN Search (msnbot)' ), // removed slash in order to also match "msnbot-media"
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
	array('robot', 'Yandex', 'Yandex' ),
	array('robot', 'Mail.Ru', 'Mail.Ru' ),
	array('robot', 'Baiduspider', 'Baidu spider' ),
	array('robot', 'infometrics-bot', 'Infometrics Bot' ),
	array('robot', 'DotBot/', 'DotBot' ),
	array('robot', 'Twiceler-', 'Cuil (Twiceler)' ),
	array('robot', 'discobot/', 'Discovery Engine' ),
	array('robot', 'Speedy Spider', 'Entireweb (Speedy Spider)' ),
	array('robot', 'monit/', 'Monit'),
	array('robot', 'Sogou web spider', 'Sogou'),
	array('robot', 'Tagoobot/', 'Tagoobot'),
	array('robot', 'MJ12bot/', 'Majestic-12'),
	array('robot', 'ia_archiver', 'Alexa crawler'),
	array('robot', 'KaloogaBot', 'Kalooga'),
	array('robot', 'Flexum/', 'Flexum'),
	array('robot', 'OOZBOT/', 'OOZBOT'),
	array('robot', 'ApptusBot', 'Apptus'),
	array('robot', 'Purebot', 'Pure Search'),
	array('robot', 'Sosospider', 'Sosospider'),
	array('robot', 'TopBlogsInfo', 'TopBlogsInfo'),
	array('robot', 'spbot/', 'SEOprofiler'),
	array('robot', 'StackRambler', 'Rambler' ),
	array('robot', 'AportWorm', 'Aport.ru' ),
	array('robot', 'ScoutJet', 'ScoutJet' ),
	array('robot', 'bingbot/', 'Bing' ),
	array('robot', 'Nigma.ru/', 'Nigma.ru' ),
	array('robot', 'ichiro/', 'Ichiro' ),
	array('robot', 'YoudaoBot/', 'Youdao' ),
	array('robot', 'Sogou web spider/', 'Sogou web spider' ),
	array('robot', 'findfiles.net', 'findfiles.net' ),
	array('robot', 'SiteBot/', 'SiteBot' ),
	array('robot', 'Nutch-', 'Apache Nutch' ),
	array('robot', 'DoCoMo/', 'DoCoMo' ),
	array('robot', 'findlinks/', 'FindLinks' ),
	array('robot', 'MLBot', 'MLBot' ),
	array('robot', 'facebookexternalhit', 'Facebook' ),
	array('robot', ' oBot/', 'IBM Bot' ),
	array('robot', 'GarlikCrawler/', 'Garlik' ),
	array('robot', 'Yeti/', 'Naver' ),
	array('robot', 'TurnitinBot/', 'Turnitin' ),
	array('robot', 'NerdByNature.Bot', 'NerdByNature' ),
	array('robot', 'SeznamBot/', 'SeznamBot' ),
	array('robot', 'Nymesis/', 'Nymesis' ),
	array('robot', 'YodaoBot/', 'YodaoBot' ),
	array('robot', 'Exabot/', 'Exabot' ),
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

$referer_type_array = array (
	'0'			=> 'All',
	'search'	=> 'Search',
	'referer'	=> 'Referer',
	'direct'	=> 'Direct',
	'self'		=> 'Self',
	'blacklist' => 'Blacklist',
	'spam'		=> 'Spam',
	'admin'		=> 'Admin'
							);

$referer_type_color = array(
	'search'	=> '0099FF',
	'blacklist' => 'ff00ff',
	'referer'	=> '00CCFF',
	'direct'	=> '00FFCC',
	'spam'		=> 'FF0000',
	'self'		=> '00FF99',
	'admin'		=> '999999'
	);

$agent_type_array = array (
	'0'			=> 'All',
	'rss'		=> 'RSS',
	'robot'		=> 'Robot',
	'browser'	=> 'Browser',
	'unknown'	=> 'Unknown',
	);


$agent_type_color = array(
	'rss'		=> 'FF6600',
	'robot'		=> 'FF9900',
	'browser'	=> 'FFCC00',
	'unknown'	=> 'cccccc'
);
?>