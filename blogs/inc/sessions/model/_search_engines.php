<?php
/**
 * Search Engine parameters
 *
 * Based on Piwik - Open source web analytics - {@link http://piwik.org/}
 * Latest version: http://dev.piwik.org/svn/trunk/core/DataFiles/SearchEngines.php
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Detail of a line:
 * Url => array( SearchEngineName, KeywordParameter, [path containing the keyword], [charset used by the search engine], [search rank parameter])
 *
 * The main search engine URL has to be at the top of the list for the given
 * search Engine.  This serves as the master record so additional URLs
 * don't have to duplicate all the information, but can override when needed.
 *
 * The URL, "example.com", will match "example.com", "m.example.com",
 * "www.example.com", and "search.example.com".
 *
 * For region-specific search engines, the URL, "{}.example.com" will match
 * any ISO3166-1 alpha2 country code against "{}".  Similarly, "example.{}"
 * will match against valid country TLDs, but should be used sparingly to
 * avoid false positives.
 *
 * The charset should be an encoding supported by mbstring.  If unspecified,
 * we'll assume it's UTF-8.
 * Reference: http://www.php.net/manual/en/mbstring.encodings.php
 *
 * To help us link directly the search engine result page for the keyword,
 * specify the third entry in the array using the macro {k} that will
 * automatically be replaced by the keyword.
 *
 * A simple example is:
 *  'www.google.com'		=> array('Google', 'q', 'search?q={k}'),
 *
 * A more complicated example, with an array of possible variable names, custom charset and a serp rank param:
 *  'www.baidu.com'			=> array('Baidu', array('wd', 'word', 'kw'), 's?wd={k}', 'gb2312', 'start'),
 *
 * Another example using a regular expression to parse the path for keywords:
 *  'infospace.com'         => array('InfoSpace', array('/dir1\/(pattern)\/dir2/'), '/dir1/{k}/dir2/stuff/'),
 */
$search_engine_params = array(
	// 1
	'1.cz'						=> array('1.cz', 'q', 'index.php?q={k}', 'iso-8859-2'),

	// 123people
	'www.123people.com'			=> array('123people', array('/s\/([^\/]+)/', 'search_term'), 's/{k}'),
	'123people.{}'				=> array('123people'),

	// 1und1
	'search.1und1.de'			=> array('1und1', 'su', 'search/web/?su={k}'),

	// Abacho
	'www.abacho.de'				=> array('Abacho', 'q', 'suche?q={k}'),
	'www.abacho.com'			=> array('Abacho'),
	'www.abacho.co.uk'			=> array('Abacho'),
	'www.se.abacho.com'			=> array('Abacho'),
	'www.tr.abacho.com'			=> array('Abacho'),
	'www.abacho.at'				=> array('Abacho'),
	'www.abacho.fr'				=> array('Abacho'),
	'www.abacho.es'				=> array('Abacho'),
	'www.abacho.ch'				=> array('Abacho'),
	'www.abacho.it'				=> array('Abacho'),

	// ABCsok
	'abcsok.no'					=> array('ABCsok', 'q', '?q={k}'),
	'verden.abcsok.no'			=> array('ABCsok'),

	// Acoon
	'www.acoon.de'				=> array('Acoon', 'begriff', 'cgi-bin/search.exe?begriff={k}'),

	// Alexa
	'alexa.com'					=> array('Alexa', 'q', 'search?q={k}'),
	'search.toolbars.alexa.com'	=> array('Alexa'),

	// Alice Adsl
	'rechercher.aliceadsl.fr'	=> array('Alice Adsl', 'qs', 'google.pl?qs={k}'),

	// Allesklar
	'www.allesklar.de'			=> array('Allesklar', 'words', '?words={k}'),
	'www.allesklar.at'			=> array('Allesklar'),
	'www.allesklar.ch'			=> array('Allesklar'),

	// AllTheWeb
	'www.alltheweb.com'			=> array('AllTheWeb', 'q', 'search?q={k}'),

	// All.by
	'all.by'					=> array('All.by', 'query', 'cgi-bin/search.cgi?mode=by&query={k}'),

	// Altavista
	'www.altavista.com'			=> array('AltaVista', 'q', 'web/results?q={k}', '', 'b'),
	'search.altavista.com'		=> array('AltaVista'),
	'listings.altavista.com'	=> array('AltaVista'),
	'altavista.de'				=> array('AltaVista'),
	'altavista.fr'				=> array('AltaVista'),
	'{}.altavista.com'			=> array('AltaVista'),
	'be-nl.altavista.com'		=> array('AltaVista'),
	'be-fr.altavista.com'		=> array('AltaVista'),

	// Apollo Latvia
	'apollo.lv/portal/search/'	=> array('Apollo lv', 'q', '?cof=FORID%3A11&q={k}&search_where=www'),

	// APOLLO7
	'apollo7.de'				=> array('Apollo7', 'query', 'a7db/index.php?query={k}&de_sharelook=true&de_bing=true&de_witch=true&de_google=true&de_yahoo=true&de_lycos=true'),

	// AOL
	'search.aol.com'			=> array('AOL', array('query', 'q', 'userQuery', 'as_q'), 'aol/search?q={k}'),
	'search.aol.{}'				=> array('AOL'),
	'aol.{}'					=> array('AOL'),
	'aolsearch.aol.com'			=> array('AOL'),
	'www.aolrecherche.aol.fr'	=> array('AOL'),
	'www.aolrecherches.aol.fr'	=> array('AOL'),
	'www.aolimages.aol.fr'		=> array('AOL'),
	'aim.search.aol.com'		=> array('AOL'),
	'www.recherche.aol.fr'		=> array('AOL'),
	'find.web.aol.com'			=> array('AOL'),
	'recherche.aol.ca'			=> array('AOL'),
	'aolsearch.aol.co.uk'		=> array('AOL'),
	'search.aol.co.uk'			=> array('AOL'),
	'aolrecherche.aol.fr'		=> array('AOL'),
	'sucheaol.aol.de'			=> array('AOL'),
	'suche.aol.de'				=> array('AOL'),
	'suche.aolsvc.de'			=> array('AOL'),
	'aolbusqueda.aol.com.mx'	=> array('AOL'),
	'alicesuche.aol.de'			=> array('AOL'),
	'alicesuchet.aol.de'		=> array('AOL'),
	'alicesuche.aol.de'			=> array('AOL'),
	'suchet2.aol.de'			=> array('AOL'),
	'search.hp.my.aol.com.au'	=> array('AOL'),
	'search.hp.my.aol.de'		=> array('AOL'),
	'search.hp.my.aol.it'		=> array('AOL'),
	'search-intl.netscape.com'	=> array('AOL'),

	// Aport
	'sm.aport.ru'				=> array('Aport', 'r', 'search?r={k}', 'windows-1251', 'p'),

	// Arama
	'arama.com'					=> array('Arama', 'q', 'search.php3?q={k}'),

	// Arcor
	'www.arcor.de'				=> array('Arcor', 'Keywords', 'content/searchresult.jsp?Keywords={k}'),

	// Arianna (Libero.it)
	'arianna.libero.it'			=> array('Arianna', 'query', 'search/abin/integrata.cgi?query={k}'),
	'www.arianna.com'			=> array('Arianna'),

	// Ask (IAC Search & Media)
	'ask.com'					=> array('Ask', array('ask', 'q', 'searchfor'), 'web?q={k}'),
	'web.ask.com'				=> array('Ask'),
	'int.ask.com'				=> array('Ask'),
	'mws.ask.com'				=> array('Ask'),
	'images.ask.com'			=> array('Ask'),
	'images.{}.ask.com'			=> array('Ask'),
	'ask.reference.com'			=> array('Ask'),
	'www.askkids.com'			=> array('Ask'),
	'iwon.ask.com'				=> array('Ask'),
	'www.ask.co.uk'				=> array('Ask'),
	'{}.ask.com'				=> array('Ask'),
	'www.qbyrd.com'				=> array('Ask'),
	'{}.qbyrd.com'				=> array('Ask'),
	'www.search-results.com'	=> array('Ask'),
	'{}.search-results.com'		=> array('Ask'),

	// Atlas
	'searchatlas.centrum.cz'	=> array('Atlas', 'q', '?q={k}'),

	// Austronaut
	'www2.austronaut.at'		=> array('Austronaut', 'q'),
	'www1.austronaut.at'		=> array('Austronaut'),

	// Babylon (Enhanced by Google)
	'search.babylon.com'		=> array('Babylon', array('q', '/\/web\/(.*)/'), '?q={k}'),
	'searchassist.babylon.com'	=> array('Babylon'),

	// Baidu
	'www.baidu.com'				=> array('Baidu', array('wd', 'word', 'kw'), 's?wd={k}', 'gb2312', 'pn'),
	'www1.baidu.com'			=> array('Baidu'),
	'zhidao.baidu.com'			=> array('Baidu'),
	'tieba.baidu.com'			=> array('Baidu'),
	'news.baidu.com'			=> array('Baidu'),
	'web.gougou.com'			=> array('Baidu', 'search', 'search?search={k}'), // uses baidu search

	// Bellnet
	'www.suchmaschine.com'		=> array('Bellnet', 'suchstr', 'cgi-bin/bellnet.cgi?suchstr={k}'),

	// Biglobe
	'cgi.search.biglobe.ne.jp'	=> array('Biglobe', 'q', 'cgi-bin/search-st?q={k}'),

	// Bing
	'bing.com'					=> array('Bing', array('q', 'Q'), 'search?q={k}', '', 'first'),
	'{}.bing.com'				=> array('Bing'),
	'msnbc.msn.com'				=> array('Bing'),
	'm.bing.com'				=> array('Bing'),

	// Bing Cache
	'cc.bingj.com'				=> array('Bing'),

	// Bing Images
	'bing.com/images/search'	=> array('Bing Images', array('q', 'Q'), '?q={k}'),
	'{}.bing.com/images/search'	=> array('Bing Images'),

	// blekko
	'blekko.com'				=> array('blekko', array('q', '/\/ws\/(.*)/'), 'ws/{k}'),

	// Blogdigger
	'www.blogdigger.com'		=> array('Blogdigger', 'q'),

	// Blogpulse
	'www.blogpulse.com'			=> array('Blogpulse', 'query', 'search?query={k}'),

	// Bluewin
	'search.bluewin.ch'			=> array('Bluewin', 'searchTerm', '?searchTerm={k}'),

	// Canoe.ca
	'web.canoe.ca'				=> array('Canoe.ca', 'q', 'search?q={k}'),

	// Centrum
	'search.centrum.cz'			=> array('Centrum', 'q', '?q={k}'),
	'morfeo.centrum.cz'			=> array('Centrum'),

	// Charter
	'www.charter.net'			=> array('Charter', 'q', 'search/index.php?q={k}'),

	// Clix (Enhanced by Google)
	'pesquisa.clix.pt'			=> array('Clix', 'question', 'resultado.html?in=Mundial&question={k}'),

	// Conduit
	'search.conduit.com'		=> array('Conduit.com', 'q', 'Results.aspx?q={k}'),

	// Comcast
	'search.comcast.net'		=> array('Comcast', 'q', '?q={k}'),

	// Compuserve
	'websearch.cs.com'			=> array('Compuserve.com (Enhanced by Google)', 'query', 'cs/search?query={k}'),

	// Crawler
	'crawler.com'				=> array('Crawler.com', 'a', 'search/results1.aspx?q={k}'),

	// Cuil
	'www.cuil.com'				=> array('Cuil', 'q', 'search?q={k}'),

	// Daemon search
	'daemon-search.com'			=> array('Daemon search', 'q', 'explore/web?q={k}'),
	'my.daemon-search.com'		=> array('Daemon search'),

	// DasOertliche
	'www.dasoertliche.de'		=> array('DasOertliche', 'kw'),

	// DasTelefonbuch
	'www1.dastelefonbuch.de'	=> array('DasTelefonbuch', 'kw'),

	// Daum
	'search.daum.net'			=> array('Daum', 'q', 'search?q={k}', 'EUC-KR'),

	// Delfi Latvia
	'smart.delfi.lv'			=> array('Delfi lv', 'q', 'find?q={k}'),

	// Delfi
	'otsing.delfi.ee'			=> array('Delfi EE', 'q', 'find?q={k}'),

	// Digg
	'digg.com'					=> array('Digg', 's', 'search?s={k}'),

	// Dir.com
	'fr.dir.com'				=> array('Dir.com', 'req'),

	// Dmoz
	'dmoz.org'					=> array('Dmoz', 'search', '', '', 'start'),
	'editors.dmoz.org'			=> array('Dmoz'),

	// DuckDuckGo
	'duckduckgo.com'			=> array('DuckDuckGo', 'q', '?q={k}'),

	// Earthlink
	'search.earthlink.net'		=> array('Earthlink', 'q', 'search?q={k}'),

	// Ecosia (powered by Bing)
	'ecosia.org'				=> array('Ecosia', 'q', 'search.php?q={k}'),

	// Eniro
	'www.eniro.se'				=> array('Eniro', array('q', 'search_word'), 'query?q={k}'),

	// Eurip
	'www.eurip.com'				=> array('Eurip', 'q', 'search/?q={k}'),

	// Euroseek
	'www.euroseek.com'			=> array('Euroseek', 'string', 'system/search.cgi?string={k}'),

	// Everyclick
	'www.everyclick.com'		=> array('Everyclick', 'keyword'),

	// Excite
	'search.excite.it'			=> array('Excite', 'q', 'web/?q={k}'),
	'search.excite.fr'			=> array('Excite'),
	'search.excite.de'			=> array('Excite'),
	'search.excite.co.uk'		=> array('Excite'),
	'search.excite.es'			=> array('Excite'),
	'search.excite.nl'			=> array('Excite'),
	'msxml.excite.com'			=> array('Excite', '/\/[^\/]+\/ws\/results\/[^\/]+\/([^\/]+)/'),
	'www.excite.co.jp'			=> array('Excite', 'search', 'search.gw?search={k}', 'SHIFT_JIS'),

	// Exalead
	'www.exalead.fr'			=> array('Exalead', 'q', 'search/results?q={k}'),
	'www.exalead.com'			=> array('Exalead'),

	// eo
	'eo.st'						=> array('eo', 'x_query', 'cgi-bin/eolost.cgi?x_query={k}'),

	// Facebook
	'www.facebook.com'			=> array('Facebook', 'q', 'search/?q={k}'),

	// Fast Browser Search
	'www.fastbrowsersearch.com'	=> array('Fast Browser Search', 'q', 'results/results.aspx?q={k}'),

	// Francite
	'recherche.francite.com'	=> array('Francite', 'name'),

	// Fireball
	'www.fireball.de'			=> array('Fireball', 'q', 'ajax.asp?q={k}'),

	// Firstfind
	'www.firstsfind.com'		=> array('Firstsfind', 'qry'),

	// Fixsuche
	'www.fixsuche.de'			=> array('Fixsuche', 'q'),

	// Flix
	'www.flix.de'				=> array('Flix.de', 'keyword'),

	// Forestle
	'forestle.org'				=> array('Forestle', 'q', 'search.php?q={k}'),
	'{}.forestle.org'			=> array('Forestle'),
	'forestle.mobi'				=> array('Forestle'),

	// Free
	'search.free.fr'			=> array('Free', 'q'),
	'search1-2.free.fr'			=> array('Free'),
	'search1-1.free.fr'			=> array('Free'),

	// Freecause
	'search.freecause.com'		=> array('FreeCause', 'p', '?p={k}'),

	// Freenet
	'suche.freenet.de'			=> array('Freenet', array('query', 'Keywords'), 'suche/?query={k}'),

	// FriendFeed
	'friendfeed.com'			=> array('FriendFeed', 'q', 'search?q={k}'),

	// GAIS
	'gais.cs.ccu.edu.tw'		=> array('GAIS', 'q', 'search.php?q={k}'),

	// Geona
	'geona.net'					=> array('Geona', 'q', 'search?q={k}'),

	// Gde
	'gde.ru'					=> array('Gde.ru', 'keywords', '', 'windows-1251'),

	// Gigablast
	'www.gigablast.com'			=> array('Gigablast', 'q', 'search?q={k}'),
	'dir.gigablast.com'			=> array('Gigablast (Directory)', 'q'),

	// GMX
	'suche.gmx.net'				=> array('GMX', 'su', 'search/web/?su={k}'),

	// Gnadenmeer
	'www.gnadenmeer.de'			=> array('Gnadenmeer', 'keyword'),

	// Goo
	'search.goo.ne.jp'			=> array('goo', 'MT', 'web.jsp?MT={k}'),
	'ocnsearch.goo.ne.jp'		=> array('goo'),

	// Google
	'google.com'				=> array('Google', 'q', 'search?q={k}', '', array('start','cd')),
	'google.{}'					=> array('Google'),
	'www2.google.com'			=> array('Google'),
	'ipv6.google.com'			=> array('Google'),
	'go.google.com'				=> array('Google'),

	// Google vs typo squatters
	'wwwgoogle.com'				=> array('Google'),
	'wwwgoogle.{}'				=> array('Google'),
	'gogole.com'				=> array('Google'),
	'gogole.{}'					=> array('Google'),
	'gppgle.com'				=> array('Google'),
	'gppgle.{}'					=> array('Google'),
	'googel.com'				=> array('Google'),
	'googel.{}'					=> array('Google'),

	// Powered by Google
	'search.avg.com'			=> array('Google'),
	'isearch.avg.com'			=> array('Google'),
	'www.cnn.com'				=> array('Google', 'query'),
	'darkoogle.com'				=> array('Google'),
	'search.darkoogle.com'		=> array('Google'),
	'search.foxtab.com'			=> array('Google'),
	'www.gooofullsearch.com'	=> array('Google', 'Keywords'),
	'search.hiyo.com'			=> array('Google'),
	'search.incredimail.com'	=> array('Google'),
	'search1.incredimail.com'	=> array('Google'),
	'search2.incredimail.com'	=> array('Google'),
	'search3.incredimail.com'	=> array('Google'),
	'search4.incredimail.com'	=> array('Google'),
	'search.sweetim.com'		=> array('Google'),
	'www.fastweb.it'			=> array('Google'),
	'search.juno.com'			=> array('Google', 'query'),
	'searchresults.verizon.com'	=> array('Google'),
	'homepage.am'				=> array('Google'),
	'search.homepage.am'		=> array('Google'),
	'search.alot.com'			=> array('Google'),

	// Google Earth
	// - 2010-09-13: are these redirects now?
	'www.googleearth.de'		=> array('Google'),
	'www.googleearth.fr'		=> array('Google'),

	// Google Cache
	'webcache.googleusercontent.com'=> array('Google', '/\/search\?q=cache:[A-Za-z0-9]+:[^+]+([^&]+)/', 'search?q={k}'),

	// Google SSL
	'encrypted.google.com'		=> array('Google SSL', 'q', 'search?q={k}', '', array('start','cd')),

	// Google Blogsearch
	'blogsearch.google.com'		=> array('Google Blogsearch', 'q', 'blogsearch?q={k}'),
	'blogsearch.google.{}'		=> array('Google Blogsearch'),

	// Google Custom Search
	'google.com/cse'			=> array('Google Custom Search', array('q', 'query')),
	'google.{}/cse'				=> array('Google Custom Search'),
	'google.com/custom'			=> array('Google Custom Search'),
	'google.{}/custom'			=> array('Google Custom Search'),

	// Google Translation
	'translate.google.com'		=> array('Google Translations', 'q'),

	// Google Images
	'images.google.com'			=> array('Google Images', 'q', 'images?q={k}'),
	'images.google.{}'			=> array('Google Images'),

	// Google News
	'news.google.com'			=> array('Google News', 'q'),
	'news.google.{}'			=> array('Google News'),

	// Google Shopping
	'google.com/products'		=> array('Google Shopping', 'q', '?q={k}&tbm=shop'),
	'google.{}/products'		=> array('Google Shopping'),

	// Google syndicated search
	'googlesyndicatedsearch.com'=> array('Google syndicated search', 'q'),

	// Google Video
	'video.google.com'			=> array('Google Video', 'q', 'search?q={k}&tbm=vid'),

	// Google Wireless Transcoder
	// - does not appear to execute JavaScript
	//'google.com/gwt/n'			=> array('Google Wireless Transcoder'),

	// Goyellow.de
	'www.goyellow.de'			=> array('GoYellow.de', 'MDN'),

	// Gule Sider
	'www.gulesider.no'			=> array('Gule Sider', 'q'),

	// HighBeam
	'www.highbeam.com'			=> array('HighBeam', 'q', 'Search.aspx?q={k}'),

	// Hit-Parade
	'req.hit-parade.com'		=> array('Hit-Parade', 'p7', 'general/recherche.asp?p7={k}'),
	'class.hit-parade.com'		=> array('Hit-Parade'),
	'www.hit-parade.com'		=> array('Hit-Parade'),

	// Holmes.ge
	'holmes.ge'					=> array('Holmes', 'q', 'search.htm?q={k}'),

	// Hooseek.com
	'www.hooseek.com'			=> array('Hooseek', 'recherche', 'web?recherche={k}'),

	// Hotbot
	'www.hotbot.com'			=> array('Hotbot', 'query'),

	// Icerocket
	'blogs.icerocket.com'		=> array('Icerocket', 'q', 'search?q={k}'),

	// ICQ
	'www.icq.com'				=> array('ICQ', 'q', 'search/results.php?q={k}', '', 'start'),
	'search.icq.com'			=> array('ICQ'),

	// Ilse
	'www.ilse.nl'				=> array('Ilse NL', 'search_for', '?search_for={k}'),

	// InfoSpace (and related web properties)
	'infospace.com'				=> array('InfoSpace', '/\/[^\/]+\/ws\/results\/[^\/]+\/([^\/]+)/', 'ispace/ws/results/Web/{k}/1/1/content-top-left/Relevance/'),
	'dogpile.com'				=> array('InfoSpace'),
	'nbci.dogpile.com'			=> array('InfoSpace'),
	'search.nation.com'			=> array('InfoSpace'),
	'search.go2net.com'			=> array('InfoSpace'),
	'metacrawler.com'			=> array('InfoSpace'),
	'webfetch.com'				=> array('InfoSpace'),
	'webcrawler.com'			=> array('InfoSpace'),
	'search.dogreatgood.com'	=> array('InfoSpace'),

	// Powered by InfoSpace
	'isearch.babylon.com'		=> array('InfoSpace', 'q'),
	'start.facemoods.com'		=> array('InfoSpace', 's'),
	'search.magentic.com'		=> array('InfoSpace', 'q'),
	'search.searchcompletion.com'=> array('InfoSpace', 'q'),

	/*
	 * Other InfoSpace powered metasearches are handled in Piwik_Common::extractSearchEngineInformationFromUrl()
	 *
	 * This includes sites such as:
	 * - search.kiwee.com
	 * - ws.copernic.com
	 * - result.iminent.com
	 */

	// Interia
	'www.google.interia.pl'		=> array('Interia', 'q', 'szukaj?q={k}'),

	// I-play
	'start.iplay.com'			=> array('I-play', 'q', 'searchresults.aspx?q={k}'),

	// Ixquick
	'ixquick.com'				=> array('Ixquick', 'query', '', '', 'startat'),
	'www.eu.ixquick.com'		=> array('Ixquick'),
	'ixquick.de'				=> array('Ixquick'),
	'www.ixquick.de'			=> array('Ixquick'),
	'us.ixquick.com'			=> array('Ixquick'),
	's1.us.ixquick.com'			=> array('Ixquick'),
	's2.us.ixquick.com'			=> array('Ixquick'),
	's3.us.ixquick.com'			=> array('Ixquick'),
	's4.us.ixquick.com'			=> array('Ixquick'),
	's5.us.ixquick.com'			=> array('Ixquick'),
	'eu.ixquick.com'			=> array('Ixquick'),
	's8-eu.ixquick.com'			=> array('Ixquick'),
	's1-eu.ixquick.de'			=> array('Ixquick'),

	// Jyxo
	'jyxo.1188.cz'				=> array('Jyxo', 'q', 's?q={k}'),

	// Jungle Spider
	'www.jungle-spider.de'		=> array('Jungle Spider', 'q'),

	// Jungle key
	'junglekey.com'				=> array('Jungle Key', 'query', 'search.php?query={k}&type=web&lang=en'),
	'junglekey.fr'				=> array('Jungle Key'),

	// Kataweb
	'www.kataweb.it'			=> array('Kataweb', 'q'),

	// Kvasir
	'www.kvasir.no'				=> array('Kvasir', 'q', 'alle?q={k}'),

	// Latne
	'www.latne.lv'				=> array('Latne', 'q', 'siets.php?q={k}'),

	// La Toile Du Quebec via Google
	'www.toile.com'				=> array('La Toile Du Quebec (Google)', 'q', 'search?q={k}'),
	'web.toile.com'				=> array('La Toile Du Quebec (Google)'),

	// LiveInternet.ru
	'www.liveinternet.ru'		=> array('LiveInternet.ru', 'q', 'q/?q={k}'),

	// LiveTool
	'search.livetool.ru'		=> array('LiveTool', 'text', '', array('utf-8','windows-1251'), 'text'),

	// Looksmart
	'www.looksmart.com'			=> array('Looksmart', 'key'),

	// Lo.st (Enhanced by Google)
	'lo.st'						=> array('Lo.st', 'x_query', 'cgi-bin/eolost.cgi?x_query={k}'),

	// Lycos
	'search.lycos.com'			=> array('Lycos', 'query', '?query={k}', '', 'page2'),
	'lycos.{}'					=> array('Lycos'),

	// maailm.com
	'www.maailm.com'			=> array('maailm.com', 'tekst'),

	// Mail.ru
	'go.mail.ru'				=> array('Mail.ru', 'q', 'search?q={k}', array('utf-8','windows-1251'), 'sf'),
	'search.list.mail.ru'		=> array('Mail.ru'),
	'searchru.icq.com'			=> array('Mail.ru'), // It's powered my go.mail.ru search engine!

	// Mamma
	'www.mamma.com'				=> array('Mamma', 'query', 'result.php?q={k}'),
	'mamma75.mamma.com'			=> array('Mamma'),

	// Meta
	'meta.ua'					=> array('Meta.ua', 'q', 'search.asp?q={k}'),

	// MetaCrawler.de
	's1.metacrawler.de'			=> array('MetaCrawler DE', 'qry', '?qry={k}'),
	's2.metacrawler.de'			=> array('MetaCrawler DE'),
	's3.metacrawler.de'			=> array('MetaCrawler DE'),

	// Metager
	'meta.rrzn.uni-hannover.de'	=> array('Metager', 'eingabe', 'meta/cgi-bin/meta.ger1?eingabe={k}'),
	'www.metager.de'			=> array('Metager'),

	// Metager2
	'metager2.de'				=> array('Metager2', 'q', 'search/index.php?q={k}'),

	// Meinestadt
	'www.meinestadt.de'			=> array('Meinestadt.de', 'words'),

	// Mister Wong
	'www.mister-wong.com'		=> array('Mister Wong', 'keywords', 'search/?keywords={k}'),
	'www.mister-wong.de'		=> array('Mister Wong'),

	// Monstercrawler
	'www.monstercrawler.com'	=> array('Monstercrawler', 'qry'),

	// Mozbot
	'www.mozbot.fr'				=> array('mozbot', 'q', 'results.php?q={k}'),
	'www.mozbot.co.uk'			=> array('mozbot'),
	'www.mozbot.com'			=> array('mozbot'),

	// El Mundo
	'ariadna.elmundo.es'		=> array('El Mundo', 'q'),

	// Mynet
	'arama.mynet.com' => array('Mynet', 'query', 'result?query={k}'),

	// MySpace
	'searchservice.myspace.com'	=> array('MySpace', 'qry', 'index.cfm?fuseaction=sitesearch.results&type=Web&qry={k}'),

	// MySearch / MyWay / MyWebSearch (default: powered by Ask.com)
	'www.mysearch.com'			=> array('MyWebSearch', array('searchfor', 'searchFor'), 'search/Ajmain.jhtml?searchfor={k}', '', 'pn'),
	'ms114.mysearch.com'		=> array('MyWebSearch'),
	'ms146.mysearch.com'		=> array('MyWebSearch'),
	'kf.mysearch.myway.com'		=> array('MyWebSearch'),
	'ki.mysearch.myway.com'		=> array('MyWebSearch'),
	'search.myway.com'			=> array('MyWebSearch'),
	'search.mywebsearch.com'	=> array('MyWebSearch'),


	// Najdi
	'www.najdi.si'				=> array('Najdi.si', 'q', 'search.jsp?q={k}'),

	// Nate
	'search.nate.com'			=> array('Nate', 'q', 'search/all.html?q={k}', 'EUC-KR'),

	// Naver
	'search.naver.com'			=> array('Naver', 'query', 'search.naver?query={k}', 'EUC-KR'),

	// Needtofind
	'ko.search.need2find.com'	=> array('Needtofind', 'searchfor', 'search/AJmain.jhtml?searchfor={k}'),

	// Neti
	'www.neti.ee'				=> array('Neti', 'query', 'cgi-bin/otsing?query={k}', 'iso-8859-1'),

	// Nifty
	'search.nifty.com'			=> array('Nifty', 'q', 'websearch/search?q={k}'),

	// Nigma
	'nigma.ru'					=> array('Nigma.ru', 's', 'index.php?s={k}', '', 'startpos'),
	'www.nigma.ru'				=> array('Nigma.ru'),

	// Onet
	'szukaj.onet.pl'			=> array('Onet.pl', 'qt', 'query.html?qt={k}'),

	// Online.no
	'online.no'					=> array('Online.no', 'q', 'google/index.jsp?q={k}'),

	// OpenDNS
	'guide.opendns.com'			=> array('OpenDNS', 'q', 'main?q={k}'),

	// Opplysningen 1881
	'www.1881.no'				=> array('Opplysningen 1881', 'Query', 'Multi/?Query={k}'),

	// Orange
	'busca.orange.es'			=> array('Orange', 'q', 'search?q={k}'),

	// Paperball
	'www.paperball.de'			=> array('Paperball', 'q', 'suche/s/?q={k}'),

	// PeoplePC
	'search.peoplepc.com'		=> array('PeoplePC', 'q', 'search?q={k}'),

	// Picsearch
	'www.picsearch.com'			=> array('Picsearch', 'q', 'index.cgi?q={k}'),

	// Plazoo
	'www.plazoo.com'			=> array('Plazoo', 'q'),

	// Poisk.Ru
	'poisk.ru'					=> array('Poisk.Ru', 'text', 'cgi-bin/poisk?text={k}', 'windows-1251'),

	// QIP
	'search.qip.ru'				=> array('QIP.ru', 'query', 'search?query={k}'),
	'magna.qip.ru'				=> array('QIP.ru', 'q', '', 'utf-8', 'page'),

	// Qualigo
	'www.qualigo.at'			=> array('Qualigo', 'q'),
	'www.qualigo.ch'			=> array('Qualigo'),
	'www.qualigo.de'			=> array('Qualigo'),
	'www.qualigo.nl'			=> array('Qualigo'),

	// Rakuten
	'websearch.rakuten.co.jp'	=> array('Rakuten', 'qt', 'WebIS?qt={k}'),

	// Rambler
	'rambler.ru'				=> array('Rambler', array('query', 'words'), 'search?query={k}'),
	'nova.rambler.ru'			=> array('Rambler'),
	'news.rambler.ru'			=> array('Rambler'),
	'images.rambler.ru'			=> array('Rambler'),
	'm.rambler.ru'				=> array('Rambler'),

	// Reddit
	'www.reddit.com'			=> array('Reddit', 'q', '', 'utf-8', 'count'),

	// RPMFind
	'rpmfind.net'				=> array('rpmfind', 'query', 'linux/rpm2html/search.php?query={k}'),
	'fr2.rpmfind.net'			=> array('rpmfind'),

	// Road Runner Search
	'search.rr.com'				=> array('Road Runner', 'q', '?q={k}', '', 'offset'),

	// Sapo
	'pesquisa.sapo.pt'			=> array('Sapo', 'q', '?q={k}'),

	// scour.com
	'scour.com'					=> array('Scour.com', '/search\/[^\/]+\/(.*)/', 'search/web/{k}'),

	// Search.com
	'www.search.com'			=> array('Search.com', 'q', 'search?q={k}'),

	// Search.ch
	'www.search.ch'				=> array('Search.ch', 'q', '?q={k}'),

	// Search-results.com
	'www.search-results.com'	=> array('Search-results.com', 'q'),

	// Searchalot
	'searchalot.com'			=> array('Searchalot', 'q', '?q={k}'),

	// SearchCanvas
	'www.searchcanvas.com'		=> array('SearchCanvas', 'q', 'web?q={k}'),

	// Search.ukr.net
	'search.ukr.net'			=> array('search.ukr.net', 'search_query'),

	// Searchy
	'www.searchy.co.uk'			=> array('Searchy', 'q', 'index.html?q={k}'),

	// Setooz
	// 2010-09-13: the mismatches are because subdomains are language codes
	//             (not country codes)
	'bg.setooz.com'				=> array('Setooz', 'query', 'search?query={k}', '', 'pager.offset'),
	'da.setooz.com'				=> array('Setooz'),
	'el.setooz.com'				=> array('Setooz'),
	'fa.setooz.com'				=> array('Setooz'),
	'ur.setooz.com'				=> array('Setooz'),
	'{}.setooz.com'				=> array('Setooz'),

	// Seznam
	'search.seznam.cz'			=> array('Seznam', 'q', '?q={k}'),

	// Sharelook
	'www.sharelook.fr'			=> array('Sharelook', 'keyword'),

	// Skynet
	'www.skynet.be'				=> array('Skynet', 'q', 'services/recherche/google?q={k}'),

	// Sogou
	'www.sogou.com'				=> array('Sogou', 'query', 'web?query={k}'),

	// soso.com
	'www.soso.com'				=> array('Soso', 'w', 'q?w={k}', 'gb2312'),

	// Startpagina
	'startgoogle.startpagina.nl'=> array('Startpagina (Google)', 'q', '?q={k}'),

	// Suche.info
	'suche.info'				=> array('Suche.info', 'Keywords', 'suche.php?Keywords={k}'),

	// Suchmaschine.com
	'www.suchmaschine.com'		=> array('Suchmaschine.com', 'suchstr', 'cgi-bin/wo.cgi?suchstr={k}'),

	// Suchnase
	'www.suchnase.de'			=> array('Suchnase', 'q'),

	// TalkTalk
	'www.talktalk.co.uk'		=> array('TalkTalk', 'query', 'search/results.html?query={k}'),

	// Technorati
	'technorati.com'			=> array('Technorati', 'q', 'search?return=sites&authority=all&q={k}'),

	// Teoma
	'www.teoma.com'				=> array('Teoma', 'q', 'web?q={k}'),

	// Terra -- referer does not contain search phrase (keywords)
	'buscador.terra.es'			=> array('Terra', 'query', 'Default.aspx?source=Search&query={k}'),
	'buscador.terra.cl'			=> array('Terra'),
	'buscador.terra.com.br'		=> array('Terra'),

	// Tiscali
	'search.tiscali.it'			=> array('Tiscali', array('q', 'key'), '?q={k}'),
	'search-dyn.tiscali.it'		=> array('Tiscali'),
	'hledani.tiscali.cz'		=> array('Tiscali', 'query'),

	// Tixuma
	'www.tixuma.de'				=> array('Tixuma', 'sc', 'index.php?mp=search&stp=&sc={k}&tg=0'),

	// T-Online
	'suche.t-online.de'			=> array('T-Online', 'q', 'fast-cgi/tsc?mandant=toi&context=internet-tab&q={k}'),
	'brisbane.t-online.de'		=> array('T-Online'),
	'navigationshilfe.t-online.de'=> array('T-Online', 'q', 'dtag/dns/results?mode=search_top&q={k}'),

	// Toolbarhome
	'www.toolbarhome.com'		=> array('Toolbarhome', 'q', 'search.aspx?q={k}'),

	'vshare.toolbarhome.com'	=> array('Toolbarhome'),

	// Top-page.ru
	'www.top-page.ru'			=> array('Top-page.ru', array('q', 'query'), '', array('utf-8','windows-1251'), array('page', 'start') ),

	// Trouvez.com
	'www.trouvez.com'			=> array('Trouvez.com', 'query'),

	// TrovaRapido
	'www.trovarapido.com'		=> array('TrovaRapido', 'q', 'result.php?q={k}'),

	// Trusted-Search
	'www.trusted--search.com'	=> array('Trusted Search', 'w', 'search?w={k}'),

	// Tut.by
	'search.tut.by'				=> array('Tut.by', 'query', 'query?w={k}', array('utf-8','windows-1251') ),

	// Twingly
	'www.twingly.com'			=> array('Twingly', 'q', 'search?q={k}'),

	// URL.ORGanzier
	'www.url.org'				=> array('URL.ORGanzier', 'q', '?l=de&q={k}'),

	// Vinden
	'www.vinden.nl'				=> array('Vinden', 'q', '?q={k}'),

	// Vindex
	'www.vindex.nl'				=> array('Vindex', 'search_for', '/web?search_for={k}'),
	'search.vindex.nl'			=> array('Vindex'),

	// Virgilio
	'ricerca.virgilio.it'		=> array('Virgilio', 'qs', 'ricerca?qs={k}', '', 'offset'),
	'ricercaimmagini.virgilio.it'=> array('Virgilio'),
	'ricercavideo.virgilio.it'	=> array('Virgilio'),
	'ricercanews.virgilio.it'	=> array('Virgilio'),
	'mobile.virgilio.it'		=> array('Virgilio', 'qrs'),

	// Voila
	'search.ke.voila.fr'		=> array('Voila', 'rdata', 'S/voila?rdata={k}'),
	'www.lemoteur.fr'			=> array('Voila'), // uses voila search

	// Volny
	'web.volny.cz'				=> array('Volny', 'search', 'fulltext/?search={k}', 'windows-1250'),

	// Walhello
	'www.walhello.info'			=> array('Walhello', 'key', 'search?key={k}'),
	'www.walhello.com'			=> array('Walhello'),
	'www.walhello.de'			=> array('Walhello'),
	'www.walhello.nl'			=> array('Walhello'),

	// Web.de
	'suche.web.de'				=> array('Web.de', 'su', 'search/web/?su={k}'),

	// Web.nl
	'www.web.nl'				=> array('Web.nl', 'zoekwoord'),

	// Webalta.ru
	'webalta.ru'				=> array('Webalta.ru', 'q', 'search?q={k}'),

	// Weborama
	'www.weborama.fr'			=> array('weborama', 'QUERY'),

	// WebSearch
	'www.websearch.com'			=> array('WebSearch', array('qkw', 'q'), 'search/results2.aspx?q={k}'),

	// Wedoo
	// 2011-02-15 - keyword no longer appears to be in Referer URL; candidate for removal?
	'fr.wedoo.com'				=> array('Wedoo', 'keyword'),
	'en.wedoo.com'				=> array('Wedoo'),
	'es.wedoo.com'				=> array('Wedoo'),

	// Winamp (Enhanced by Google)
	'search.winamp.com'			=> array('Winamp', 'q', 'search/search?q={k}'),

	// Witch
	'www.witch.de'				=> array('Witch', 'search', 'search-result.php?cn=0&search={k}'),

	// Wirtualna Polska
	'szukaj.wp.pl'				=> array('Wirtualna Polska', 'szukaj', 'http://szukaj.wp.pl/szukaj.html?szukaj={k}', 'ISO-8859-2', 'offset'),

	// WWW
	'search.www.ee'				=> array('www varav', 'query'),

	// X-recherche
	'www.x-recherche.com'		=> array('X-Recherche', 'MOTS', 'cgi-bin/websearch?MOTS={k}'),

	// Yahoo
	'search.yahoo.com'			=> array('Yahoo!', array('p', 'q'), 'search?p={k}', '', 'b'),
	'yahoo.com'					=> array('Yahoo!'),
	'yahoo.{}'					=> array('Yahoo!'),
	'm.yahoo.{}'				=> array('Yahoo!'),
	'{}.m2.yahoo.{}'			=> array('Yahoo!'),
	'{}.search.yahoo.com'		=> array('Yahoo!'),
	'cade.search.yahoo.com'		=> array('Yahoo!'),
	'espanol.search.yahoo.com'	=> array('Yahoo!'),
	'qc.search.yahoo.com'		=> array('Yahoo!'),
	'{}.yahoo.com'				=> array('Yahoo!'),
	'cade.yahoo.com'			=> array('Yahoo!'),
	'espanol.yahoo.com'			=> array('Yahoo!'),
	'qc.yahoo.com'				=> array('Yahoo!'),
	'{}.yhs.search.yahoo.com'	=> array('Yahoo!'),
	'one.cn.yahoo.com'			=> array('Yahoo!'),
	'siteexplorer.search.yahoo.com'	=> array('Yahoo!'),

	// Powered by Yahoo APIs
	'www.cercato.it'			=> array('Yahoo!', 'q'),
	'search.offerbox.com'		=> array('Yahoo!', 'q'),

	// Powered by Yahoo! Search Marketing (Overture)
	'ys.mirostart.com'			=> array('Yahoo!', 'q'),

	// Yahoo! Directory
	'search.yahoo.com/search/dir' => array('Yahoo! Directory', 'p', '?p={k}'),
	//'{}.dir.yahoo.com'			=> array('Yahoo! Directory'),

	// Yahoo! Images
	'images.search.yahoo.com'	=> array('Yahoo! Images', 'p', 'search/images?p={k}'),
	'{}.images.search.yahoo.com'=> array('Yahoo! Images'),
	'cade.images.search.yahoo.com'=> array('Yahoo! Images'),
	'espanol.images.search.yahoo.com'=> array('Yahoo! Images'),
	'qc.images.search.yahoo.com'=> array('Yahoo! Images'),
	'images.yahoo.com'			=> array('Yahoo! Images'),
	'{}.images.yahoo.com'		=> array('Yahoo! Images'),
	'cade.images.yahoo.com'		=> array('Yahoo! Images'),
	'espanol.images.yahoo.com'	=> array('Yahoo! Images'),
	'qc.images.yahoo.com'		=> array('Yahoo! Images'),

	// Yam
	'search.yam.com'			=> array('Yam', 'k', 'Search/Web/?SearchType=web&k={k}'),

	// Yandex
	'yandex.com'				=> array('Yandex', array('text', 'q', 'query'), 'yandsearch?text={k}', array('utf-8','windows-1251'), 'p'),
	'yandex.{}'					=> array('Yandex'),
	'images.yandex.{}'			=> array('Yandex'),
	'hghltd.yandex.{}'			=> array('Yandex'),
	'yaca.yandex.{}'			=> array('Yandex'),

	// Powered by Yandex
	'search.i.ua'				=> array('Yandex'),

	// Yasni
	'www.yasni.de'				=> array('Yasni', 'query'),
	'www.yasni.com'				=> array('Yasni'),
	'www.yasni.co.uk'			=> array('Yasni'),
	'www.yasni.ch'				=> array('Yasni'),
	'www.yasni.at'				=> array('Yasni'),

	// Yellowmap
	'yellowmap.de'				=> array('Yellowmap', ' '),

	// Yippy
	'search.yippy.com'			=> array('Yippy', 'query', 'search?query={k}'),

	// YouGoo
	'www.yougoo.fr'				=> array('YouGoo', 'q', '?cx=search&q={k}'),

	// Zapmeta
	'www.zapmeta.com'			=> array('Zapmeta', array('q', 'query'), '?q={k}'),
	'www.zapmeta.nl'			=> array('Zapmeta'),
	'www.zapmeta.de'			=> array('Zapmeta'),
	'uk.zapmeta.com'			=> array('Zapmeta'),

	// Zoek
	'www3.zoek.nl'				=> array('Zoek', 'q'),

	// Zhongsou
	'p.zhongsou.com'			=> array('Zhongsou', 'w', 'p?w={k}'),

	// Zoeken
	'www.zoeken.nl'				=> array('Zoeken', 'q', '?q={k}'),

	// Zoohoo
	'zoohoo.cz'					=> array('Zoohoo', 'q', '?q={k}', 'windows-1250'),

	// Zoznam
	'www.zoznam.sk'				=> array('Zoznam', 's', 'hladaj.fcgi?s={k}&co=svet'),
);

?>