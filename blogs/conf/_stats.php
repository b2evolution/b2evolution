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
 * Speciallist: referrers that should not be considered as "real" referers in stats.
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
$SpecialList = array(
	// webmails
	'.mail.yahoo.com/',
	'//mail.google.com/',
	'webmail.aol.com/',
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
	array('robot', 'Mail.RU/', 'Mail.Ru' ),
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
	array('robot', 'AhrefsBot/', 'AhrefsBot' ),
	array('robot', 'SISTRIX Crawler', 'SISTRIX' ),
	array('robot', 'AcoonBot/', 'AcoonBot' ),
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

/* Set user devices */
// MOBILE
$mobile_user_devices = array(
	'iphone'   => '(iphone|ipod)',
	'android'  => 'android.*mobile',
	'blkberry' => 'blackberry',
	'winphone' => 'windows phone os',
	'wince'    => 'windows ce; (iemobile|ppc|smartphone)',
	'palm'     => '(avantgo|blazer|elaine|hiptop|palm|plucker|xiino)',
	'gendvice' => '(kindle|mobile|mmp|midp|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap|opera mini)'
);

// TABLET
$tablet_user_devices = array(
	'ipad'     => '(ipad)',
	'andrtab'  => 'android(?!.*mobile)',
	'berrytab' => 'rim tablet os',
);

// PC
$pc_user_devices = array(
	'win311'   => 'win16',
	'win95'    => '(windows 95)|(win95)|(windows_95)',
	'win98'    => '(windows 98)|(win98)',
	'win2000'  => '(windows nt 5.0)|(windows 2000)',
	'winxp'    => '(windows nt 5.1)|(windows XP)',
	'win2003'  => '(windows nt 5.2)',
	'winvista' => '(windows nt 6.0)',
	'win7'     => '(windows nt 6.1)',
	'winnt40'  => '(windows nt 4.0)|(winnt4.0)|(winnt)|(windows nt)',
	'winme'    => '(windows me)|(win 9x 4.90)',
	'openbsd'  => 'openbsd',
	'sunos'    => 'sunos',
	'linux'    => '(linux)|(x11)',
	'ubuntu'   => 'ubuntu',
	'macosx'   => 'mac os x',
	'macos'    => '(mac_powerpc)|(macintosh)',
	'qnx'      => 'qnx',
	'beos'     => 'beos',
	'os2'      => 'os/2'
);

$user_devices = array_merge(
	$tablet_user_devices,
	$mobile_user_devices,
	$pc_user_devices
);

$user_devices_color = array(
	// Mobile
	'iphone'   => 'd8c1a1',
	'ipad'     => 'c5aa8c',
	'andrtab'  => 'cdba9c',
	'android'  => 'e0caa5',
	'berrytab' => 'b29575',
	'blkberry' => 'baa286',
	'winphone' => 'ceb28b',
	'wince'    => 'e4d6b9',
	'palm'     => 'c8ac84',
	'gendvice' => 'e6d4bf',
	// PC
	'win311'   => 'CCCCCC',
	'win95'    => '676767',
	'win98'    => 'ABABAB',
	'win2000'  => '898989',
	'winxp'    => 'DEDEDE',
	'win2003'  => 'A3A3A3',
	'winvista' => 'EEEEEE',
	'win7'     => '999999',
	'winnt40'  => 'B9B9B9',
	'winme'    => '7F7F7F',
	'openbsd'  => 'AFAFAF',
	'sunos'    => '808080',
	'linux'    => 'E0E0E0',
	'ubuntu'   => 'B4B4B4',
	'macosx'   => '9F9F9F',
	'macos'    => 'F0F0F0',
	'qnx'      => 'D0D0D0',
	'beos'     => '8F8F8F',
	'os2'      => 'C0C0C0'
	);

$referer_type_array = array (
	'0'       => 'All',
	'search'  => 'Search',
	'referer' => 'Referer',
	'direct'  => 'Direct',
	'self'    => 'Self',
	'special' => 'Special',
	'spam'    => 'Spam',
	'admin'   => 'Admin'
	);

$referer_type_color = array(
	'search'  => '0099FF',
	'special' => 'ff00ff',
	'referer' => '00CCFF',
	'direct'  => '00FFCC',
	'spam'    => 'FF0000',
	'self'    => '00FF99',
	'admin'   => '999999'
	);

$agent_type_array = array (
	'0'       => 'All',
	'robot'   => 'Robot',
	'browser' => 'Browser',
	'unknown' => 'Unknown',
	);

$agent_type_color = array(
	'rss'     => 'FF6600',
	'robot'   => 'FF9900',
	'browser' => 'FFCC00',
	'unknown' => 'cccccc'
);

$hit_type_array = array (
	'0'        => 'All',
	'rss'      => 'RSS',
	'standard' => 'Standard',
	'ajax'     => 'AJAX',
	'service'  => 'Service',
	'admin'    => 'Admin'
	);

$hit_type_color = array(
	'standard'         => 'FFBB00',
	'service'          => '0072FF',
	'rss'              => 'FF6600',
	'ajax'             => '009900',
	'admin'            => 'AAE0E0',
	'standard_robot'   => 'FF9900',
	'standard_browser' => 'FFCC00'
);

$user_gender_color = array(
	'women_active'       => '990066',
	'women_notactive'    => 'ff66cc',
	'men_active'         => '003399',
	'men_notactive'      => '6699ff',
	'nogender_active'    => '666666',
	'nogender_notactive' => 'cccccc'
);

?>