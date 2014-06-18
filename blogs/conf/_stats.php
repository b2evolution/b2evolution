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
	1000 => array('robot', 'Googlebot', 'Google (Googlebot)' ), // removed slash in order to also match "Googlebot-Image", "Googlebot-Mobile", "Googlebot-Sitemaps"
	1001 => array('robot', 'Slurp/', 'Inktomi (Slurp)' ),
	1002 => array('robot', 'Yahoo! Slurp', 'Yahoo (Slurp)' ), // removed ; to also match "Yahoo! Slurp China"
	1003 => array('robot', 'msnbot', 'MSN Search (msnbot)' ), // removed slash in order to also match "msnbot-media"
	1004 => array('robot', 'Frontier/', 'Userland (Frontier)' ),
	1005 => array('robot', 'ping.blo.gs/', 'blo.gs' ),
	1006 => array('robot', 'organica/', 'Organica' ),
	1007 => array('robot', 'Blogosphere/', 'Blogosphere' ),
	1008 => array('robot', 'blogging ecosystem crawler', 'Blogging ecosystem'),
	1009 => array('robot', 'FAST-WebCrawler/', 'Fast' ),			// http://fast.no/support/crawler.asp
	1010 => array('robot', 'timboBot/', 'Breaking Blogs (timboBot)' ),
	1011 => array('robot', 'NITLE Blog Spider/', 'NITLE' ),
	1012 => array('robot', 'The World as a Blog ', 'The World as a Blog' ),
	1013 => array('robot', 'daypopbot/ ', 'DayPop' ),
	1014 => array('robot', 'Bitacle bot/', 'Bitacle' ),
	1015 => array('robot', 'Sphere Scout', 'Sphere Scout' ),
	1016 => array('robot', 'Gigabot/', 'Gigablast (Gigabot)' ),
	1017 => array('robot', 'Yandex', 'Yandex' ),
	1018 => array('robot', 'Mail.RU/', 'Mail.Ru' ),
	1019 => array('robot', 'Baiduspider', 'Baidu spider' ),
	1020 => array('robot', 'infometrics-bot', 'Infometrics Bot' ),
	1021 => array('robot', 'DotBot/', 'DotBot' ),
	1022 => array('robot', 'Twiceler-', 'Cuil (Twiceler)' ),
	1023 => array('robot', 'discobot/', 'Discovery Engine' ),
	1024 => array('robot', 'Speedy Spider', 'Entireweb (Speedy Spider)' ),
	1025 => array('robot', 'monit/', 'Monit'),
	1026 => array('robot', 'Sogou web spider', 'Sogou'),
	1027 => array('robot', 'Tagoobot/', 'Tagoobot'),
	1028 => array('robot', 'MJ12bot/', 'Majestic-12'),
	1029 => array('robot', 'ia_archiver', 'Alexa crawler'),
	1030 => array('robot', 'KaloogaBot', 'Kalooga'),
	1031 => array('robot', 'Flexum/', 'Flexum'),
	1032 => array('robot', 'OOZBOT/', 'OOZBOT'),
	1033 => array('robot', 'ApptusBot', 'Apptus'),
	1034 => array('robot', 'Purebot', 'Pure Search'),
	1035 => array('robot', 'Sosospider', 'Sosospider'),
	1036 => array('robot', 'TopBlogsInfo', 'TopBlogsInfo'),
	1037 => array('robot', 'spbot/', 'SEOprofiler'),
	1038 => array('robot', 'StackRambler', 'Rambler' ),
	1039 => array('robot', 'AportWorm', 'Aport.ru' ),
	1040 => array('robot', 'ScoutJet', 'ScoutJet' ),
	1041 => array('robot', 'bingbot/', 'Bing' ),
	1042 => array('robot', 'Nigma.ru/', 'Nigma.ru' ),
	1043 => array('robot', 'ichiro/', 'Ichiro' ),
	1044 => array('robot', 'YoudaoBot/', 'Youdao' ),
	1045 => array('robot', 'Sogou web spider/', 'Sogou web spider' ),
	1046 => array('robot', 'findfiles.net', 'findfiles.net' ),
	1047 => array('robot', 'SiteBot/', 'SiteBot' ),
	1048 => array('robot', 'Nutch-', 'Apache Nutch' ),
	1049 => array('robot', 'DoCoMo/', 'DoCoMo' ),
	1050 => array('robot', 'findlinks/', 'FindLinks' ),
	1051 => array('robot', 'MLBot', 'MLBot' ),
	1052 => array('robot', 'facebookexternalhit', 'Facebook' ),
	1053 => array('robot', ' oBot/', 'IBM Bot' ),
	1054 => array('robot', 'GarlikCrawler/', 'Garlik' ),
	1055 => array('robot', 'Yeti/', 'Naver' ),
	1056 => array('robot', 'TurnitinBot/', 'Turnitin' ),
	1057 => array('robot', 'NerdByNature.Bot', 'NerdByNature' ),
	1058 => array('robot', 'SeznamBot/', 'SeznamBot' ),
	1059 => array('robot', 'Nymesis/', 'Nymesis' ),
	1060 => array('robot', 'YodaoBot/', 'YodaoBot' ),
	1061 => array('robot', 'Exabot/', 'Exabot' ),
	1062 => array('robot', 'AhrefsBot/', 'AhrefsBot' ),
	1063 => array('robot', 'SISTRIX Crawler', 'SISTRIX' ),
	1064 => array('robot', 'AcoonBot/', 'AcoonBot' ),
	1065 => array('robot', 'VoilaBot', 'VoilaBot' ),
	1066 => array('robot', 'SiteExplorer', 'SiteExplorer' ),
	1067 => array('robot', 'IstellaBot/', 'IstellaBot' ),
	1068 => array('robot', 'exb.de/crawler', 'ExB Language Crawler' ),
	1069 => array('robot', 'SemrushBot', 'SemrushBot' ),
	// Unknown robots:
	5000 => array('robot', 'psycheclone', 'Psycheclone' ),
	// Aggregators:
	10000 => array('aggregator', 'AppleSyndication/', 'Safari RSS (AppleSyndication)' ),
	10001 => array('aggregator', 'Feedreader', 'Feedreader' ),
	10002 => array('aggregator', 'Syndirella/', 'Syndirella' ),
	10003 => array('aggregator', 'rssSearch Harvester/', 'rssSearch Harvester' ),
	10004 => array('aggregator', 'Newz Crawler',	'Newz Crawler' ),
	10005 => array('aggregator', 'MagpieRSS/', 'Magpie RSS' ),
	10006 => array('aggregator', 'CoologFeedSpider', 'CoologFeedSpider' ),
	10007 => array('aggregator', 'Pompos/', 'Pompos' ),
	10008 => array('aggregator', 'SharpReader/', 'SharpReader'),
	10009 => array('aggregator', 'Straw ', 'Straw'),
	10010 => array('aggregator', 'YandexBlog', 'YandexBlog'),
	10011 => array('aggregator', ' Planet/', 'Planet Feed Reader'),
	10012 => array('aggregator', 'UniversalFeedParser/', 'Universal Feed Parser'),
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