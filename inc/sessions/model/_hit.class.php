<?php
/**
 * This file implements the Hit class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @todo dh> Lazily handle properties through getters (and do not detect/do much in the constructor)!
 *
 * @package evocore
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * A hit to a blog.
 *
 * @package evocore
 */
class Hit
{
	/**
	 * ID in DB, gets set when {@link log()} was called and the hit was logged.
	 */
	var $ID;

	/**
	 * Is the hit already logged?
	 * @var boolean
	 */
	var $logged = false;

	/**
	 * The referer/referrer.
	 *
	 * @var string
	 */
	var $referer;


	/**
	 * The type of hit.
	 *
	 * 'standard'|'rss'|'admin'|'ajax'|'service'|'api'
	 *
	 * @var string
	 */
	var $hit_type;

	/**
	 * The type of referer.
	 *
	 * Note: "spam" referers do not get logged.
	 * 'search'|'special'|'referer'|'direct'|'spam'|'self'
	 *
	 * @var string
	 */
	var $referer_type;


	/**
	 * The dom_type of the referer's base domain in T_basedomains
	 * 'unknown'|'normal'|'searcheng'|'aggregator'|'email'
	 * @var string
	 */
	var $dom_type = 'unknown';
	/**
	 * The ID of the referer's base domain in T_basedomains
	 *
	 * @var integer
	 */
	var $referer_domain_ID;

	/**
	 * Is this a reload?
	 * This gets lazy-filled by {@link is_new_view()}.
	 * @var boolean
	 * @access protected
	 */
	var $_is_new_view;

	/**
	 * Ignore this hit?
	 * @var boolean
	 */
	var $ignore = false;

	/**
	 * Remote address (IP).
	 * @var string
	 */
	var $IP;

	/**
	 * The user agent.
	 * @see Hit::get_user_agent()
	 * @access protected
	 * @var string
	 */
	var $user_agent;

	/**
	 * The user agent ID, eg 1000
	 * @see Hit::get_agent_ID()
	 * @access protected
	 * @var integer
	 */
	var $agent_ID;

	/**
	 * The user agent name, eg "safari"
	 * @see Hit::get_agent_name()
	 * @access protected
	 * @var string
	 */
	var $agent_name;

	/**
	 * The user agent platform. Either "win", "mac" or "linux".
	 * @see Hit::get_agent_platform()
	 * @access protected
	 * @var string
	 */
	var $agent_platform;


	/**
	 * The user's remote host.
	 * Use {@link get_remote_host()} to access it (lazy filled).
	 * @var string
	 * @access protected
	 */
	var $_remoteHost;

	/**
	 * The user agent type.
	 *
	 * 'rss'|'robot'|'browser'|'unknown'
	 *
	 * @see Hit::get_agent_type()
	 * @access protected
	 * @var string
	 */
	var $agent_type;

	/**
	 * Array of 2 letter ISO country codes
	 * This gets lazy-filled by {@link get_country_codes()}.
	 * @var array
	 * @access protected
	 */
	var $country_codes;

	/**
	 * Array of known search engines in format( searchEngineName => URL )
	 * This gets lazy-filled by {@link get_search_engine_names()}.
	 * @var array
	 * @access protected
	 */
	var $search_engine_names;

	/**
	 * Extracted from search referers:
	 */
	var $_search_params_tried = false;
	var $_keyphrase = NULL;
	var $_serprank = NULL;
	var $_search_engine = NULL;

	/**
	 * Session ID
	 */
	var $session_id;

	/**
	 * Hit time
	 */
	var $hit_time;

	/**
	 * Hit_response_code
	 */
	var $hit_response_code = 200;

	/**
	 * Hit request method: 'unknown', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'HEAD', 'OPTIONS', 'LINK', 'UNLINK', 'PURGE', 'LOCK', 'UNLOCK', 'PROPFIND', 'VIEW'
	 * This is value of $_SERVER['REQUEST_METHOD']
	 */
	var $method;

	/**
	 * Hit action
	 */
	var $action;

	/**
	 * Test mode
	 * The mode used by geneartion of a fake statisctics. In this case test_mode = 1
	 * fp>vitaliy what is this for?
	 * Test what & when?
	 *
	 */
	var $test_mode;

	/**
	 * Test rss mode
	 * fp>vitaliy what is this for?
	 * Test_rss is used for geneartion of fake statistics. In the normal mode
	 * Hit class determines rss hit using $Skin->type == 'feed'. In the test mode
	 * skin type doesn't change and that is why the new variable was needed to emulate
	 * skin type.
	 */
	var $test_rss;

	/**
	 * Test URI
	 */
	var $test_uri;

	/**
	 * Browser version
	 * @var integer
	 */
	var $browser_version;

	/**
	 * Constructor
	 *
	 * This may INSERT a basedomain and a useragent but NOT the HIT itself!
	 */
	function __construct( $referer = NULL, $IP = NULL, $session_id= NULL, $hit_time = NULL, $test_mode = NULL , $test_uri = NULL, $user_agent = NULL, $test_admin = NULL, $test_rss = NULL)
	{
		global $debug;

		if( isset($IP) )
		{
			$this->IP = $IP;
		}
		else
		{	// Get the first IP in the list of REMOTE_ADDR and HTTP_X_FORWARDED_FOR
			$this->IP = get_ip_list( true );
		}

		if (!empty($session_id))
		{
			$this->session_id = $session_id;
		}

		if (!empty($hit_time))
		{
			$this->hit_time = $hit_time;
		}

		if (!empty($test_mode))
		{
			$this->test_mode = $test_mode;
		}

		if (!empty($test_uri))
		{
			$this->test_uri = $test_uri;
		}

		if (!empty($user_agent))
		{
			$this->user_agent = $user_agent;
		}

		if (!empty($test_admin))
		{
			$this->test_admin = $test_admin;
		}

		if (!empty($test_rss))
		{
			$this->test_rss = $test_rss;
		}

		$this->hit_type = $this->get_hit_type();

		// Check the REFERER and determine referer_type:
		// TODO: dh> move this out of here, too, only if "antispam_block_spam_referers" is true,
		//           do something about it (here or somewhere else, but early).
		$this->detect_referer( $referer ); // May EXIT if we are set up to block referer spam.

		if( $debug )
		{
			global $Debuglog;
			$Debuglog->add( 'Hit: IP: '.$this->IP, 'request' );
			$Debuglog->add( 'Hit: UserAgent: '.$this->get_user_agent(), 'request' );
			$Debuglog->add( 'Hit: Referer: '.var_export($this->referer, true).'; type='.$this->referer_type, 'request' );
			$Debuglog->add( 'Hit: Remote Host: '.$this->get_remote_host( false ), 'request' );
		}
	}


	/**
	 * Detect admin page
	 */
	function detect_admin_page()
	{
		global $Debuglog;
		if (empty($this->test_mode) || (!empty($this->test_mode) && !empty($this->test_admin)))
		{
			if( is_admin_page() )
			{	// We are inside of admin, this supersedes 'direct' access
				// NOTE: this is not really a referer type but more a hit type
				// $Debuglog->add( 'Hit: Referer is admin page.', 'request' );
				//$this->referer_type = 'admin';
				return true;
			}
		}
		return false;
	}


	/**
	 * Get HTTP referrer/referer.
	 *
	 * Due to potential non-thread safety with getenv() (fallback), we'd better do this early.
	 *
	 * @return string
	 */
	function get_referer()
	{
		global $HTTP_REFERER; // might be set by PHP (give highest priority)

		$referer = NULL;
		if( isset( $HTTP_REFERER ) )
		{ // Referer provided by PHP:
			$referer = $HTTP_REFERER;
		}
		else
		{
			if( isset($_SERVER['HTTP_REFERER']) )
			{
				$referer = $_SERVER['HTTP_REFERER'];
			}
			else
			{ // Fallback method (not thread safe :[[ ) - this function does not work in ISAPI mode.
				$referer = getenv('HTTP_REFERER');
			}
		}
		return $referer;
	}


	/**
	 * Get & Analyze referer
	 *
	 * Due to potential non-thread safety with getenv() (fallback), we'd better do this early.
	 *
	 * referer_type: enum('search', 'blacklist', 'referer', 'direct'); 'spam' gets used internally
	 */
	function detect_referer( $referer = NULL )
	{
		global $Debuglog, $debug;
		global $self_referer_list, $SpecialList;  // used to detect $referer_type
		global $skins_path, $siteskins_path;
		global $Settings;

		if( isset($referer) )
		{
			$this->referer = $referer;
		}
		else
		{	// Get referer from HTTP request
			$this->referer = $this->get_referer();
		}


		if( empty($this->referer) )
		{	// NO referer
			// This type may be superseeded and set to 'admin'
			if( ! $this->detect_admin_page() )
			{	// Not an admin page:
				$this->referer_type = 'direct';
			}
			else
			{
				if (empty($this->hit_type))
				{
					$this->hit_type = 'admin';
				}
				$this->referer_type = 'direct';
			}
			return;
		}


		// ANALYZE referer...


		// Check self referer list, see {@link $self_referer_list}
		// fplanque: we log these (again), because if we didn't we woudln't detect
		// reloads on these... and that would be a problem!
		foreach( $self_referer_list as $self_referer )
		{
			$pos = strpos( $this->referer, $self_referer );
			// If not starting within in the first 12 chars it's probably an url param as in &url=http://this_blog.com
			if( $pos !== false && $pos <= 12
				&& ! ($debug && strpos( $this->referer, '/search.html' ) ) ) // search simulation
			{
				// This type may be superseeded by admin page
				if( ! $this->detect_admin_page() )
				{	// Not an admin page:
					$Debuglog->add( 'Hit: detect_referer(): self referer ('.$self_referer.')', 'request' );
					$this->referer_type = 'self';
				}
				else
				{
					if (empty($this->hit_type))
					{
						$this->hit_type = 'admin';
					}
					$this->referer_type = 'self';
				}
				return;
			}
		}


		// Check Special list, see {@link $SpecialList}
		// NOTE: This is NOT the antispam!!
		// fplanque: we log these (again), because if we didn't we woudln't detect
		// reloads on these... and that would be a problem!
		foreach( $SpecialList as $lSpeciallist )
		{
			$pos = strpos( $this->referer, $lSpeciallist );
			// If not starting within in the first 12 chars it's probably an url param as in &url=http://this_blog.com
			if( $pos !== false && $pos <= 12 )
			{
				// This type may be superseeded by admin page
				// fp> 2009-05-10: because of the 12 char limit above the following is probably no longer needed. Please enable it back if anyone has a problem with admin being detected as blacklist
				// if( ! $this->detect_admin_page() )
				{	// Not an admin page:
					$Debuglog->add( 'Hit: detect_referer(): blacklist ('.$lSpeciallist.')', 'request' );
					$this->referer_type = 'special';
				}
				return;
			}
		}


		// Check if the referer is valid and does not match the antispam blacklist:
		// NOTE: requests to admin pages should not arrive here, because they should be matched above through $self_referer_list!
		load_funcs('_core/_url.funcs.php');
		if( $error = validate_url( $this->referer, 'commenting' ) )
		{ // This is most probably referer spam!!
			$Debuglog->add( 'Hit: detect_referer(): '.$error.' (SPAM)', 'hit');
			$this->referer_type = 'spam';

			if( $Settings->get('antispam_block_spam_referers') )
			{ // In order to preserve server resources, we're going to stop processing immediatly (no logging)!!
				require $siteskins_path.'_403_referer_spam.main.php';	// error & exit
				exit(0); // just in case.
				// THIS IS THE END!!
			}

			return; // type "spam"
		}


		// Is the referer a search engine?
		if( $this->is_search_referer($this->referer) )
		{
			$Debuglog->add( 'Hit: detect_referer(): search engine', 'request' );
			$this->referer_type = 'search';
			return;
		}

		$this->referer_type = 'referer';
	}


	/**
	 * Get referer_domain_ID (ID of the referer in T_basedomains).
	 *
	 * @return integer (may be NULL, but should never).
	 */
	function get_referer_domain_ID()
	{
		if( ! isset($this->referer_domain_ID) )
		{
			global $DB;
			// Check if we know the base domain:
			$referer_basedomain = get_base_domain($this->referer);
			if( $referer_basedomain )
			{	// This referer has a base domain
				// Check if we have met it before:
				$hit_basedomain = $DB->get_row( '
					SELECT dom_ID
						FROM T_basedomains
					 WHERE dom_name = '.$DB->quote($referer_basedomain) );
				if( !empty( $hit_basedomain->dom_ID ) )
				{	// This basedomain has visited before:
					$this->referer_domain_ID = $hit_basedomain->dom_ID;
					// fp> The blacklist handling that was here made no sense.
				}
				else
				{	// This is the first time this base domain visits:

					// The INSERT below can fail, probably if we get two simultaneous hits (seen in the demo logfiles)
					if ($this->agent_type == 'robot' || $this->hit_type == 'rss')
					{
						$this->dom_type = 'aggregator';
					}
					elseif($this->referer_type == 'search')
					{
						$this->dom_type = 'searcheng';
					}
					elseif($this->referer_type == 'referer' || $this->referer_type == 'self')
					{
						$this->dom_type = 'normal';
					}


					$DB->save_error_state();

					if( $DB->query( '
						INSERT INTO T_basedomains( dom_name, dom_type)
							VALUES( '.$DB->quote($referer_basedomain).', '.$DB->quote($this->dom_type).' )' ) )
					{ // INSERTed ok:
						$this->referer_domain_ID = $DB->insert_id;
					}
					else
					{ // INSERT failed: see, try to select again (may become/stay NULL)
						$this->referer_domain_ID = $DB->get_var( '
							SELECT dom_ID
								FROM T_basedomains
							 WHERE dom_name = '.$DB->quote($referer_basedomain) );
					}

					$DB->restore_error_state();
				}
			}
		}
		return $this->referer_domain_ID;
	}


	/**
	 * Set {@link $user_agent} and detect the browser.
	 * This function also sets {@link $agent_type}.
	 */
	function detect_useragent()
	{
		if( isset( $this->agent_type ) )
		{ // already detected.
			return;
		}

		global $DB, $Debuglog;
		global $user_agents;
		global $Skin; // to detect agent_type (gets set in /xmlsrv/atom.php for example)


		// Init is_* members.
		$this->is_lynx = false;
		$this->is_firefox = false;
		$this->is_gecko = false;
		$this->is_IE = false;
		$this->is_winIE = false;
		$this->is_macIE = false;
		$this->is_chrome = false;
		$this->is_safari = false;
		$this->is_opera = false;
		$this->is_NS4 = false;

		$this->agent_ID = NULL;
		$this->agent_type = 'unknown';
		$this->agent_name = '';
		$this->agent_platform = '';

		$this->browser_version = 0;

		$user_agent = $this->get_user_agent();

		if( ! empty( $user_agent ) )
		{ // detect browser
			if( strpos( $user_agent, 'Win' ) !== false )
			{
				$this->agent_platform = 'win';
			}
			elseif( strpos( $user_agent, 'Mac' ) !== false )
			{
				$this->agent_platform = 'mac';
			}
			elseif( strpos( $user_agent, 'Linux' ) !== false )
			{
				$this->agent_platform = 'linux';
			}
			elseif( $browscap = $this->get_browser_caps() )
			{
				$platform = isset( $browscap->platform) ? $browscap->platform : '';

				$Debuglog->add( 'Hit:detect_useragent(): Trying to detect platform using browscap', 'request' );
				$Debuglog->add( 'Hit:detect_useragent(): Raw platform string: "'.$platform.'"', 'request' );

				$platform = strtolower( $platform );
				if( $platform == 'linux' || in_array( substr( $platform, 0, 3 ), array( 'win', 'mac' ) ) )
				{
					$this->agent_platform = $platform;
				}
			}

			if( strpos( $user_agent, 'Lynx' ) !== false )
			{
				$this->is_lynx = 1;
				$this->agent_name = 'lynx';
				$this->agent_type = 'browser';
			}
			elseif( strpos( $user_agent, 'Firefox/' ) !== false )
			{
				$this->is_firefox = 1;
				$this->agent_name = 'firefox';
				$this->agent_type = 'browser';
			}
			elseif( strpos( $user_agent, 'Gecko/' ) !== false )	// We don't want to see Safari as Gecko
			{ // Tblue> Note: Gecko is only a rendering engine, not a real browser!
				$this->is_gecko = 1;
				$this->agent_name = 'gecko';
				$this->agent_type = 'browser';
			}
			elseif( strpos( $user_agent, 'MSIE' ) !== false )
			{
				if( preg_match( '/MSIE (\d+)\./', $user_agent, $browser_version ) )
				{ // Detect version of IE browser
					$this->browser_version = (int)$browser_version[1];
				}
				$this->is_IE = true;
				if( $this->agent_platform == 'win' )
				{ // Win IE
					$this->is_winIE = 1;
				}
				if( $this->agent_platform == 'mac' )
				{ // Mac IE
					$this->is_macIE = 1;
				}
				$this->agent_name = 'msie';
				$this->agent_type = 'browser';
			}
			elseif( strpos( $user_agent, 'Chrome/' ) !== false )
			{
				$this->is_chrome = true;
				$this->agent_name = 'chrome';
				$this->agent_type = 'browser';
			}
			elseif( strpos( $user_agent, 'Safari/' ) !== false )
			{
				$this->is_safari = true;
				$this->agent_name = 'safari';
				$this->agent_type = 'browser';
			}
			elseif( strpos( $user_agent, 'Opera' ) !== false )
			{
				$this->is_opera = 1;
				$this->agent_name = 'opera';
				$this->agent_type = 'browser';
			}
			elseif( strpos( $user_agent, 'Nav' ) !== false || preg_match( '/Mozilla\/4\./', $user_agent ) )
			{
				$this->is_NS4 = 1;
				$this->agent_name = 'nav4';
				$this->agent_type = 'browser';
			}
		}

		$Debuglog->add( 'Hit:detect_useragent(): Agent name: '.$this->agent_name, 'request' );
		$Debuglog->add( 'Hit:detect_useragent(): Agent platform: '.$this->agent_platform, 'request' );


		// Lookup robots
		$match = false;
		foreach( $user_agents as $agent_ID => $lUserAgent )
		{
			if( strpos( $this->user_agent, $lUserAgent[1] ) !== false )
			{
				$Debuglog->add( 'Hit:detect_useragent(): '.$lUserAgent[0], 'request' );
				$Debuglog->add( 'Hit:detect_useragent(): Agent ID: '.$agent_ID, 'request' );
				$this->agent_type = ( $lUserAgent[0] == 'robot' ) ? 'robot' : 'browser';
				$this->agent_ID = $agent_ID;
				$match = true;
				break;
			}
		}

		if( ! $match && ( $browscap = $this->get_browser_caps() ) &&
		    isset( $browscap->crawler ) && $browscap->crawler )
		{
			$Debuglog->add( 'Hit:detect_useragent(): robot (through browscap)', 'request' );
			$this->agent_type = 'robot';
		}

	}


	/**
	 * Get browser capabilities through {@link get_browser()}.
	 *
	 * @return false|object The return value of get_browser().
	 */
	function get_browser_caps()
	{
		static $caps = NULL;

		if( $caps === NULL )
		{
			$caps = @get_browser( $this->get_user_agent() );
		}

		return $caps;
	}


	/**
	 * Log a hit on a blog page / rss feed.
	 *
	 * This function should be called at the end of the page, otherwise if the page
	 * is displaying previous hits, it may display the current one too.
	 *
	 * The hit will not be logged in special occasions, see {@link $ignore} and {@link is_good_hit()}.
	 *
	 * It will call {@link Hitlist::dbprune()} to do the automatic pruning of old hits in case
	 * of auto_prune_stats_mode == "page".
	 *
	 * @param boolean Hit saving in the database can be delayed or not because of the hit_ID is required
	 *
	 * @return boolean true if the hit gets logged; false if not
	 */
	function log( $delayed = false )
	{
		global $Plugins;

		if( $this->logged )
		{	// Already logged, don't log twice:
			return false;
		}

		// Remember we have already attempted to log:
		$this->logged = true;

		// Check if this hit should be logged:
		if( ! $this->should_be_logged() )
		{
			return false;
		}

		if( $Plugins->trigger_event_first_true('AppendHitLog', array( 'Hit' => &$this ) ) )
		{	// Plugin has handled recording
			return true;
		}

		// Check if this hit should STILL be logged after plugin call:
		if( ! $this->should_be_logged() )
		{
			return false;
		}

		// Record the HIT now:
		$this->record_the_hit( $delayed );

		return true;
	}


	/**
	 * Tell if a HIT should be logged:
	 *
	 * @return boolean
	 */
	function should_be_logged()
	{
		global $Settings, $Debuglog, $is_admin_page;

		if( $is_admin_page && ! $Settings->get('log_admin_hits') && empty($this->test_mode))
		{	// We don't want to log admin hits:
			$Debuglog->add( 'Hit: Hit NOT logged, (Admin page logging is disabled)', 'request' );
			return false;
		}

		if( ! $is_admin_page && ! $Settings->get('log_public_hits') )
		{	// We don't want to log public hits:
			$Debuglog->add( 'Hit: Hit NOT logged, (Public page logging is disabled)', 'request' );
			return false;
		}

		if( ($this->referer_type == 'spam' && ! $Settings->get('log_spam_hits')) && empty($this->test_mode) )
		{	// We don't want to log referer spam hits:
			$Debuglog->add( 'Hit: Hit NOT logged, (Referer spam)', 'request' );
			return false;
		}

		return true;
	}


	/**
	 * This records the hit. You should not call this directly, but {@link Hit::log()} instead!
	 *
	 * However, if a Plugin registers the {@link Plugin::AppendHitLog() AppendHitLog event}, it
	 * could be necessary to call this as a shutdown function.
	 *
	 * @param boolean Hit saving in the database can be delayed or not because of the hit_ID is required
	 */
	function record_the_hit( $delayed = false )
	{
		global $DB, $Session, $ReqURI, $Collection, $Blog, $blog, $localtimenow, $Debuglog, $disp, $ctrl, $http_response_code;

		// To log current display and controller the global variables $disp and $ctrl are used. They can be setup while calling of some controller
		// or while forming a page. In case if these variables aren't setup, NULL is recorded to the DB.
		$Debuglog->add( 'Hit: Recording the hit.', 'request' );

		$this->action = $this->get_action();

		if( empty( $this->test_uri ) )
		{
			if( ! empty( $Blog ) )
			{
				$blog_ID = $Blog->ID;
			}
			elseif( ! empty( $blog ) )
			{
				if( ! is_numeric( $blog ) )
				{ // this can be anything given by URL param "blog"! (because it's called on shutdown)
				  // see todo in param().
					$blog = NULL;
				}
				$blog_ID = $blog;
			}
			else
			{
				$blog_ID = NULL;
			}
		}
		else
		{
			$blog_ID = isset( $this->test_uri['blog_id'] ) ? $this->test_uri['blog_id'] : NULL;
			$ReqURI = $this->test_uri['link'];
		}

		$hit_uri = substr( $ReqURI, 0, 250 ); // VARCHAR(250) and likely to be longer
		$hit_referer = substr( $this->referer, 0, 250 ); // VARCHAR(250) and likely to be longer

		// Extract the keyphrase from search referers:
		$keyphrase = $this->get_keyphrase();

		$keyp_ID = NULL;

		if ( empty( $keyphrase ) )
		{	// No search hit
			if ( ! empty( $this->test_mode ) && ! empty( $this->test_uri['s'] ) )
			{
				$s = $this->test_uri['s'];
			}
			else
			{
				$s = get_param( 's' );
			}
			if( ! empty( $s ) && ! empty( $blog_ID ) )
			{ // Record Internal Search:
				$keyphrase  = $s;
			}
		}

		// Extract the serprank from search referers:
		$serprank = $this->get_serprank();

		if( ! empty( $http_response_code ) )
		{ //  in some cases $http_response_code not set and we can use value by default
			$this->hit_response_code = $http_response_code;
		}

		if( empty( $this->hit_type ) )
		{
			global $Skin, $is_api_request;

			if( ! empty( $is_api_request ) )
			{	// This is an API request:
				$this->hit_type = 'api';
			}
			elseif( ( isset( $Skin ) && $Skin->type == 'feed' ) || ! empty( $this->test_rss ) )
			{
				$this->hit_type = 'rss';
			}
			else
			{
				if( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && ( strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) )
				{
					$this->hit_type = 'ajax';
				}
				else
				{
					$this->hit_type = 'standard';
				}
			}
		}

		if( empty( $this->method ) )
		{	// Initialize a request method:
			if( isset( $_SERVER['REQUEST_METHOD'] ) && in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'HEAD', 'OPTIONS', 'LINK', 'UNLINK', 'PURGE', 'LOCK', 'UNLOCK', 'PROPFIND', 'VIEW' ) ) )
			{	// Current request method is allowed
				$this->method = $_SERVER['REQUEST_METHOD'];
			}
			else
			{	// Unknown method
				$this->method = 'unknown';
			}
		}

		// Insert a hit into DB table:
		$sql_insert_fields = array(
				'hit_datetime'          => 'FROM_UNIXTIME( '.$localtimenow.' )',
				'hit_uri'               => $DB->quote( $hit_uri ),
				'hit_disp'              => $DB->quote( $disp ),
				'hit_ctrl'              => $DB->quote( $ctrl ),
				'hit_action'            => $DB->quote( $this->action ),
				'hit_type'              => $DB->quote( $this->hit_type ),
				'hit_referer_type'      => $DB->quote( $this->referer_type ),
				'hit_referer'           => $DB->quote( $hit_referer ),
				'hit_referer_dom_ID'    => $DB->quote( $this->get_referer_domain_ID() ),
				'hit_keyphrase_keyp_ID' => $DB->quote( $keyp_ID ),
				'hit_keyphrase'         => $DB->quote( $keyphrase ),
				'hit_serprank'          => $DB->quote( $serprank ),
				'hit_coll_ID'           => $DB->quote( $blog_ID ),
				'hit_remote_addr'       => $DB->quote( $this->IP ),
				'hit_agent_type'        => $DB->quote( $this->get_agent_type() ),
				'hit_agent_ID'          => $DB->quote( $this->get_agent_ID() ),
				'hit_response_code'     => $DB->quote( $this->hit_response_code ),
				'hit_method'            => $DB->quote( $this->method )
			);

		if( empty( $this->test_mode ) )
		{ // Normal mode
			$sql_insert_fields['hit_sess_ID']  = $DB->quote( $Session->ID );
		}
		else
		{ // Test mode
			$sql_insert_fields['hit_sess_ID']  = $DB->quote( $this->session_id );
			$sql_insert_fields['hit_datetime'] = 'FROM_UNIXTIME( '.$this->hit_time.' )';
			$sql_insert_fields['hit_disp']     = $DB->quote( isset( $this->test_uri['disp'] ) ? $this->test_uri['disp'] : NULL );
			$sql_insert_fields['hit_ctrl']     = $DB->quote( isset( $this->test_uri['ctrl'] ) ? $this->test_uri['ctrl'] : NULL );
			$sql_insert_fields['hit_action']   = 'NULL';
		}

		$sql = $delayed ? 'INSERT DELAYED INTO' : 'INSERT INTO';
		$sql = $sql.' T_hitlog ( '.implode( ', ', array_keys( $sql_insert_fields ) ).' )'.
		                    ' VALUES ( '.implode( ', ', $sql_insert_fields ).' )';

		$DB->query( $sql, 'Record the hit' );
		$hit_ID = $DB->insert_id;

		if( ! empty( $keyphrase ) )
		{
			$DB->commit();
		}

		$this->ID = $hit_ID;
	}


	/**
	 * Get hit type from the uri
	 * @return mixed Hit type
	 */
	function get_hit_type()
	{
		global $ReqURI;
		if( $this->hit_type )
		{
			return $this->hit_type;
		}

		$ajax_array = array('htsrv/async.php', 'htsrv/anon_async.php');

		foreach ($ajax_array as $ajax)
		{
			if (strstr($ReqURI,$ajax))
			{
				return 'ajax';
			}
		}

		if (strstr($ReqURI,'htsrv/'))
		{
			return 'service';
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Get hit action
	 * @return mixed Hit action
	 */
	function get_action()
	{
		global $ReqURI, $action;
		if( $this->action )
		{
			return $this->action;
		}

		if (strstr($ReqURI,'htsrv/getfile.php'))
		{ // special case
			return 'getfile';
		}
		if (!empty ($action))
		{
			return $action;
		}
		else
		{
			return NULL;
		}
	}



	/**
	 * Get the keyphrase from the referer
	 */
	function get_keyphrase()
	{
		if( $this->_search_params_tried )
		{
			return $this->_keyphrase;
		}

		if( $this->referer_type == 'search' )
		{
			$this->extract_params_from_referer( $this->referer );
		}

		return $this->_keyphrase;
	}


	/**
	 * Get the serprank from the referer
	 */
	function get_serprank()
	{
		if( $this->_search_params_tried )
		{
			return $this->_serprank;
		}

		if( $this->referer_type == 'search' )
		{
			$this->extract_params_from_referer( $this->referer );
		}

		return $this->_serprank;
	}


	/**
	 * Get name of search engine
	 */
	function get_search_engine()
	{
		if( $this->_search_params_tried )
		{
			return $this->_search_engine;
		}

		if( $this->referer_type == 'search' )
		{
			$this->extract_params_from_referer( $this->referer );
		}

		return $this->_search_engine;
	}


	/**
	 * Get the User agent's signature.
	 *
	 * @return string False, if not provided or empty, if it included tags.
	 */
	function get_user_agent()
	{
		if( ! isset($this->user_agent) )
		{
			global $HTTP_USER_AGENT; // might be set by PHP, give highest priority

			if( isset($HTTP_USER_AGENT) )
			{
				$this->user_agent = $HTTP_USER_AGENT;
			}
			elseif( isset($_SERVER['HTTP_USER_AGENT']) )
			{
				$this->user_agent = $_SERVER['HTTP_USER_AGENT'];
			}
			else
			{
				$this->user_agent = false;
			}

			if( $this->user_agent != strip_tags($this->user_agent) )
			{ // then they have tried something funky, putting HTML or PHP into the user agent
				global $Debuglog;
				$Debuglog->add( 'Hit: detect_useragent(): '.T_('bad char in User Agent'), 'hit');
				$this->user_agent = '';
			}
			else
			{
				$this->user_agent = substr( $this->user_agent, 0, 250 );
			}
		}
		return $this->user_agent;
	}


	/**
	 * Get the User agent's ID.
	 *
	 * @return integer
	 */
	function get_agent_ID()
	{
		if( ! isset( $this->agent_ID ) )
		{
			$this->detect_useragent();
		}
		return $this->agent_ID;
	}


	/**
	 * Get the User agent's name.
	 *
	 * @return string
	 */
	function get_agent_name()
	{
		if( ! isset( $this->agent_name ) )
		{
			$this->detect_useragent();
		}
		return $this->agent_name;
	}


	/**
	 * Get the User agent's platform.
	 *
	 * @return string
	 */
	function get_agent_platform()
	{
		if( ! isset( $this->agent_platform ) )
		{
			$this->detect_useragent();
		}
		return $this->agent_platform;
	}


	/**
	 * Get the User agent's type.
	 *
	 * @return string
	 */
	function get_agent_type()
	{
		if( ! isset( $this->agent_type ) )
		{
			$this->detect_useragent();
		}
		return $this->agent_type;
	}


	/**
	 * Get the remote hostname.
	 *
	 * @return string
	 */
	function get_remote_host( $allow_nslookup = false )
	{
		global $Timer;

		$Timer->resume( 'Hit::get_remote_host' );

		if( is_null($this->_remoteHost) )
		{
			if( isset( $_SERVER['REMOTE_HOST'] ) )
			{
				$this->_remoteHost = $_SERVER['REMOTE_HOST'];
			}
			elseif( $allow_nslookup )
			{ // We allowed reverse DNS lookup:
				// This can be terribly time consuming (4/5 seconds!) when there is no reverse dns available!
				// This is the case on many intranets and many users' first time installs!!!
				// Some people end up considering evocore is very slow just because of this line!
				// This cannot be enabled by default.
				$this->_remoteHost = @gethostbyaddr($this->IP);
			}
			else
			{
				$this->_remoteHost = '';
			}
		}

		$Timer->pause( 'Hit::get_remote_host' );

		return $this->_remoteHost;
	}


	function get_param_from_string( $string, $param )
	{
		// Make sure the string does not start with "?", otherwise parse_str adds the question mark to the first param
		$string = preg_replace( '~^\?~', '', $string );

		parse_str($string, $array);

		$value = NULL;
		if( isset($array[$param]) && trim($array[$param]) != '' )
		{
			$value = $array[$param];
		}
		return $value;
	}


	/**
	 * Get array of 2 letter ISO country codes
	 *
	 * @return array
	 */
	function get_country_codes()
	{
		global $DB;

		if( is_null( $this->country_codes ) )
		{
			// sam2kb> Save one DB query on every page load
			// $this->country_codes = $DB->get_col('SELECT ctry_code FROM T_regional__country', 0, 'get 2 letter ISO country codes' );

			$countries = 'ad, ae, af, ag, ai, al, am, an, ao, aq, ar, as, at, au, aw, ax, az, ba, bb, bd, be, bf, bg, bh, bi,
							bj, bl, bm, bn, bo, br, bs, bt, bv, bw, by, bz, ca, cc, cd, cf, cg, ch, ci, ck, cl, cm, cn, co, cr,
							cu, cv, cx, cy, cz, de, dj, dk, dm, do, dz, ec, ee, eg, eh, er, es, et, fi, fj, fk, fm, fo, fr, ga,
							gb, gd, ge, gf, gg, gh, gi, gl, gm, gn, gp, gq, gr, gs, gt, gu, gw, gy, hk, hm, hn, hr, ht, hu, id,
							ie, il, im, in, io, iq, ir, is, it, je, jm, jo, jp, ke, kg, kh, ki, km, kn, kp, kr, kw, ky, kz, la,
							lb, lc, li, lk, lr, ls, lt, lu, lv, ly, ma, mc, md, me, mf, mg, mh, mk, ml, mm, mn, mo, mp, mq, mr,
							ms, mt, mu, mv, mw, mx, my, mz, na, nc, ne, nf, ng, ni, nl, no, np, nr, nu, nz, om, pa, pe, pf, pg,
							ph, pk, pl, pm, pn, pr, ps, pt, pw, py, qa, re, ro, rs, ru, rw, sa, sb, sc, sd, se, sg, sh, si, sj,
							sk, sl, sm, sn, so, sr, st, sv, sy, sz, tc, td, tf, tg, th, tj, tk, tl, tm, tn, to, tr, tt, tv, tw,
							tz, ua, ug, um, us, uy, uz, va, vc, ve, vg, vi, vn, vu, wf, ws, ye, yt, za, zm, zw';

			$this->country_codes = array_map( 'trim', explode(',', $countries) );
		}
		return $this->country_codes;
	}


	/**
	 * @return array Array of ( searchEngineName => URL )
	 */
	function get_search_engine_names()
	{
		global $search_engine_params;

		if( is_null( $this->search_engine_names ) )
		{
			$this->search_engine_names = array();
			foreach( $search_engine_params as $url => $info )
			{
				if( !isset($this->search_engine_names[$info[0]]) )
				{	// Do not overwrite existing keys
					$this->search_engine_names[$info[0]] = $url;
				}
			}
		}
		return $this->search_engine_names;
	}


	/**
	 * Reduce URL to more minimal form.  2 letter country codes are
	 * replaced by '{}', while other parts are simply removed.
	 *
	 * Examples:
	 *   www.example.com -> example.com
	 *   search.example.com -> example.com
	 *   m.example.com -> example.com
	 *   de.example.com -> {}.example.com
	 *   example.de -> example.{}
	 *   example.co.uk -> example.{}
	 *
	 * @param string $url
	 * @return string
	 */
	function get_lossy_url( $url )
	{
		static $countries;

		if( !isset($countries) )
		{	// Load 2 letter ISO country codes
			$countries = implode( '|', $this->get_country_codes() );
		}

		// Add not ISO 3166-1 country code top level domains
		$other_ccTLDs = ', uk, eu';

		return preg_replace(
			array(
				'/^(w+[0-9]*|search)\./',
				'/(^|\.)m\./',
				'/(\.(com|org|net|co|it|edu))?\.('.$countries.$other_ccTLDs.')(\/|$)/',
				'/^('.$countries.$other_ccTLDs.')\./',
			),
			array(
				'',
				'$1',
				'.{}$4',
				'{}.',
			),
			$url);
	}


	/**
	 * Determine if a hit is a new view (not reloaded or from a robot).
	 *
	 * 'Reloaded' means: visited before from the same user (in a session) or from same IP/user_agent in the
	 * last {@link $Settings reloadpage_timeout} seconds.
	 *
	 * This gets queried by the Item objects before incrementing its view count (if the Item gets viewed
	 * in total ({@link $dispmore})).
	 *
	 * @todo fplanque>> if this is only useful to display who's online or view counts, provide option to disable all those resource consuming gadgets. (Those gadgets should be plugins actually, and they should enable this query only if needed)
	 *        blueyed>> Move functionality to Plugin (with a hook in Item::content())?!
	 * @return boolean
	 */
	function is_new_view()
	{
		if( $this->get_agent_type() == 'robot' )
		{	// Robot requests are not considered as (new) views:
			return false;
		}

		if( ! isset($this->_is_new_view) )
		{
			global $current_User;
			global $DB, $Debuglog, $Settings, $ReqURI, $localtimenow;
			global $Session;

			// Restrict to current user if logged in:
			if( ! empty($current_User->ID) )
			{ // select by user ID: one user counts really just once. May be even faster than the anonymous query below..!?
				$sql = "
					SELECT SQL_NO_CACHE hit_ID FROM T_hitlog
					 WHERE hit_sess_ID = ".$Session->ID."
						 AND hit_uri = '".$DB->escape( substr($ReqURI, 0, 250) )."'
					 LIMIT 1";
			}
			else
			{ // select by remote_addr/hit_agent_type:
				$sql = "
					SELECT SQL_NO_CACHE hit_ID
					  FROM T_hitlog
					 WHERE hit_datetime > '".date( 'Y-m-d H:i:s', $localtimenow - $Settings->get('reloadpage_timeout') )."'
					   AND hit_remote_addr = ".$DB->quote( $this->IP )."
					   AND hit_uri = '".$DB->escape( substr($ReqURI, 0, 250) )."'
					   AND hit_agent_type = ".$DB->quote($this->get_agent_type())."
					 LIMIT 1";
			}
			if( $DB->get_var( $sql, 0, 0, 'Hit: Check for reload' ) )
			{
				$Debuglog->add( 'Hit: No new view!', 'request' );
				$this->_is_new_view = false;  // We don't want to log this hit again
			}
			else
			{
				$this->_is_new_view = true;
			}
		}

		return $this->_is_new_view;
	}


	/**
	 * Is this a browser reload (F5)?
	 *
	 * @return boolean true on reload, false if not.
	 */
	function is_browser_reload()
	{
		if( ( isset( $_SERVER['HTTP_CACHE_CONTROL'] ) && strpos( $_SERVER['HTTP_CACHE_CONTROL'], 'max-age=0' ) !== false )
			|| ( isset( $_SERVER['HTTP_PRAGMA'] ) && $_SERVER['HTTP_PRAGMA'] == 'no-cache' ) )
		{ // Reload
			return true;
		}

		return false;
	}


	/*
	 * Is this a search referer hit?
	 *
	 * Note: in some situations it is not possible to detect search keywords (like google redirect URLs),
	 * and in stats you may see [n.a.] in 'Search keywords' column.
	 *
	 * @param string Hit referer
	 * @param boolean true to return an array, false to return boolean
	 * @return boolean|array of normalized referer parts: (host, path, query, fragment)
	 */
	function is_search_referer( $referer, $return_params = false )
	{
		global $search_engine_params;

		// Load search engine definitions
		require_once dirname(__FILE__).'/_search_engines.php';

		// Parse referer
		$pu = @parse_url($referer);

		if( ! isset( $pu['host'] ) )
		{
			return false;
		}

		$ref_host = $pu['host'];
		$ref_query = isset($pu['query']) ? $pu['query'] : '';
		$ref_fragment = isset($pu['fragment']) ? $pu['fragment'] : '';

		// Some search engines (eg. Bing Images) use the same domain
		// as an existing search engine (eg. Bing), we must also use the url path
		$ref_path = isset($pu['path']) ? $pu['path'] : '';

		$host_pattern = $this->get_lossy_url($ref_host);

		if( array_key_exists($ref_host.$ref_path, $search_engine_params) )
		{
			$ref_host = $ref_host.$ref_path;
		}
		elseif( array_key_exists($host_pattern.$ref_path, $search_engine_params) )
		{
			$ref_host = $host_pattern.$ref_path;
		}
		elseif( array_key_exists($host_pattern, $search_engine_params) )
		{
			$ref_host = $host_pattern;
		}
		elseif( !array_key_exists($ref_host, $search_engine_params) )
		{
			if( !strncmp($ref_query, 'cx=partner-pub-', 15) )
			{	// Google custom search engine
				$ref_host = 'www.google.com/cse';
			}
			elseif( !strncmp($ref_path, '/pemonitorhosted/ws/results/', 28) )
			{	// Private-label search powered by InfoSpace Metasearch
				$ref_host = 'infospace.com';
			}
			else
			{	// Not a search referer
				return false;
			}
		}

		if( $return_params )
		{
			return array( $ref_host, $ref_path, $ref_query, $ref_fragment );
		}
		return true;
	}


	/**
	 * Extracts a keyword from a raw not encoded URL.
	 * Will only extract keyword if a known search engine has been detected.
	 * Returns the keyword:
	 * - in UTF8: automatically converted from other charsets when applicable
	 * - strtolowered: "QUErY test!" will return "query test!"
	 * - trimmed: extra spaces before and after are removed
	 *
	 * A list of supported search engines can be found in /inc/sessions/model/_search_engines.php
	 * The function returns false when a keyword couldn't be found.
	 * 	 eg. if the url is "http://www.google.com/partners.html" this will return false,
	 *       as the google keyword parameter couldn't be found.
	 *
	 * @param string URL referer
	 * @return array|false false if a keyword couldn't be extracted,
	 * 						or array(
	 * 							'engine_name' => 'Google',
	 * 							'keywords' => 'my searched keywords',
	 *							'serprank' => 4)
	 */
	function extract_params_from_referer( $ref )
	{
		global $Debuglog, $search_engine_params, $evo_charset, $current_charset;

		// Make sure we don't try params extraction twice
		$this->_search_params_tried = true;

		@list($ref_host, $ref_path, $query, $fragment) = $this->is_search_referer($ref, true);

		if( empty($ref_host) )
		{	// Not a search referer
			return false;
		}

		$search_engine_name = $search_engine_params[$ref_host][0];

		$keyword_param = NULL;
		if( !empty($search_engine_params[$ref_host][1]) )
		{
			$keyword_param = $search_engine_params[$ref_host][1];
		}
		if( is_null($keyword_param) )
		{	// Get settings from first item in group
			$search_engine_names = $this->get_search_engine_names();

			$url = $search_engine_names[$search_engine_name];
			$keyword_param = $search_engine_params[$url][1];
		}
		if( !is_array($keyword_param) )
		{
			$keyword_param = array($keyword_param);
		}

		if( $search_engine_name == 'Google Images' || ($search_engine_name == 'Google' && strpos($ref, '/imgres') !== false) )
		{	// Google image search
			$search_engine_name = 'Google Images';

			$query = urldecode(trim( $this->get_param_from_string($query, 'prev') ));
			$query = str_replace( '&', '&amp;', strstr($query, '?') );
		}
		elseif( $search_engine_name == 'Google' && (strpos($query, '&as_') !== false || strpos($query, 'as_') === 0) )
		{ // Google with "as_" param
			$keys = array();

			if( $key = $this->get_param_from_string($query, 'as_q') )
			{
				array_push($keys, $key);
			}
			if( $key = $this->get_param_from_string($query, 'as_oq') )
			{
				array_push($keys, str_replace('+', ' OR ', $key));
			}
			if( $key = $this->get_param_from_string($query, 'as_epq') )
			{
				array_push($keys, "\"$key\"");
			}
			if( $key = $this->get_param_from_string($query, 'as_eq') )
			{
				array_push($keys, "-$key");
			}
			$key = trim(urldecode(implode(' ', $keys)));
		}

		if( empty( $key ) && ! empty( $keyword_param ) )
		{	// we haven't extracted a search key with the special cases above...
			foreach( $keyword_param as $param )
			{
				if( $param[0] == '/' )
				{	// regular expression match
					if( @preg_match($param, $ref, $matches) )
					{
						$key = trim(urldecode($matches[1]));
						break;
					}
				}
				else
				{	// search for keywords now &vname=keyword
					if( $key = $this->get_param_from_string($query, $param) )
					{
						$key = trim(urldecode($key));
						if( !empty($key) ) break;
					}
				}
			}
		}

		$key_param_in_query = false;
		if( empty( $key ) && ! empty( $keyword_param ) )
		{	// Check if empty key param exists in query, e.g. "/search?q=&other_param=text"
			// OR search engine supports urls without query param like Google:
			foreach( $keyword_param as $k_param )
			{
				if( $k_param === NULL || strpos( $query, '&'.$k_param.'=' ) !== false || strpos( $query, $k_param.'=' ) === 0 )
				{	// Search engine supports urls without param OR Key param with empty value exists in query, We can decide this referer url as from search engine:
					$key_param_in_query = true;
					break;
				}
			}
		}

		if( empty( $key ) && ! $key_param_in_query )
		{ // Not a search referer
			if( $this->referer_type == 'search' )
			{ // If the referer was detected as 'search' we need to change it to 'special'
				// to keep search stats clean.
				$this->referer_type = 'special';
				$Debuglog->add( 'Hit: extract_params_from_referer() overrides referer type set by detect_referer(): "search" -> "special"', 'request' );
			}

			return false;
		}


		// Convert encoding
		if( !empty($search_engine_params[$ref_host][3]) )
		{
			$ie = $search_engine_params[$ref_host][3];
		}
		elseif( isset($url) && !empty($search_engine_params[$url][3]) )
		{
			$ie = $search_engine_params[$url][3];
		}
		else
		{	// Fallback to default encoding
			$ie = array('utf-8', 'iso-8859-15');
		}

		if( is_array($ie) )
		{
			if( can_check_encoding() )
			{
				foreach( $ie as $test_encoding )
				{
					if( check_encoding($key, $test_encoding) )
					{
						$ie = $test_encoding;
						break;
					}
				}
			}
			else
			{
				$ie = $ie[0];
			}
		}

		$key = convert_charset($key, $evo_charset, $ie);
		// convert to lower string but keep in evo_charset
		$saved_charset = $current_charset;
		$current_charset = $evo_charset;
		$key = utf8_strtolower($key);
		$current_charset = $saved_charset;

		// Extract the "serp rank"
		// Typically http://google.com?s=keyphraz&start=18 returns 18
		if( !empty($search_engine_params[$ref_host][4]) )
		{
			$serp_param = $search_engine_params[$ref_host][4];
		}
		elseif( isset($url) && !empty($search_engine_params[$url][4]) )
		{
			$serp_param = $search_engine_params[$url][4];
		}
		else
		{	// Fallback to default params
			$serp_param = array('offset','page','start');
		}

		if( !is_array($serp_param) )
		{
			$serp_param = array($serp_param);
		}

		if( strpos($search_engine_name, 'Google') !== false )
		{	// Append fragment which Google uses in instant search
			$query .= '&'.$fragment;
		}

		foreach( $serp_param as $param )
		{
			if( $var = $this->get_param_from_string($query, $param) )
			{
				if( ctype_digit($var) )
				{
					$serprank = $var;
					break;
				}
			}
		}

		$this->_search_engine = $search_engine_name;
		$this->_keyphrase = $key;
		$this->_serprank = isset($serprank) ? $serprank : NULL;

		return array(
				'engine_name'	=> $this->_search_engine,
				'keyphrase'		=> $this->_keyphrase,
				'serprank'		=> $this->_serprank
			);
	}


	/**
	 * Is this Lynx?
	 * @return boolean
	 */
	function is_lynx()
	{
		if( ! isset($this->is_lynx) )
			$this->detect_useragent();
		return $this->is_lynx;
	}

	/**
	 * Is this Firefox?
	 * @return boolean
	 */
	function is_firefox()
	{
		if( ! isset($this->is_firefox) )
			$this->detect_useragent();
		return $this->is_firefox;
	}

	/**
	 * Is this Gecko?
	 * @return boolean
	 */
	function is_gecko()
	{
		if( ! isset($this->is_gecko) )
			$this->detect_useragent();
		return $this->is_gecko;
	}

	/**
	 * Is this WinIE?
	 * @return boolean
	 */
	function is_winIE()
	{
		if( ! isset($this->is_winIE) )
			$this->detect_useragent();
		return $this->is_winIE;
	}

	/**
	 * Is this MacIE?
	 * @return boolean
	 */
	function is_macIE()
	{
		if( ! isset($this->is_macIE) )
			$this->detect_useragent();
		return $this->is_macIE;
	}

	/**
	 * Is this Internet Explorer?
	 *
	 * @param integer Version of IE, NULL to don't check a version
	 * @param string Operator to compare a version: '=', '<', '>', '<=', '>=', '!='
	 * @return boolean
	 */
	function is_IE( $version = NULL, $operator = '=' )
	{
		if( ! isset( $this->is_IE ) )
		{
			$this->detect_useragent();
		}

		$result = $this->is_IE;

		if( ! $result )
		{ // No IE
			return false;
		}

		if( ! is_null( $version ) )
		{ // Check version of IE
			switch( $operator )
			{
				case '=':
					return $this->get_browser_version() == $version;
				case '>':
					return $this->get_browser_version() > $version;
				case '<':
					return $this->get_browser_version() < $version;
				case '>=':
					return $this->get_browser_version() >= $version;
				case '<=':
					return $this->get_browser_version() <= $version;
				case '!=':
					return $this->get_browser_version() != $version;
				default:
					// Incorrect operator
					return false;
			}
		}

		return true;
	}

	/**
	 * Is this Chrome?
	 * @return boolean
	 */
	function is_chrome()
	{
		if( ! isset($this->is_chrome) )
			$this->detect_useragent();
		return $this->is_chrome;
	}

	/**
	 * Is this Safari?
	 * @return boolean
	 */
	function is_safari()
	{
		if( ! isset($this->is_safari) )
			$this->detect_useragent();
		return $this->is_safari;
	}

	/**
	 * Is this Opera?
	 * @return boolean
	 */
	function is_opera()
	{
		if( ! isset($this->is_opera) )
			$this->detect_useragent();
		return $this->is_opera;
	}

	/**
	 * Is this Netscape4?
	 * @return boolean
	 */
	function is_NS4()
	{
		if( ! isset($this->NS4) )
			$this->detect_useragent();
		return $this->is_NS4;
	}


	/**
	 * Is this a browser hit?
	 * @return boolean
	 */
	function is_browser()
	{
		if( ! isset($this->is_browser) )
			$this->is_browser = ($this->get_agent_type() == 'browser');
		return $this->is_browser;
	}


	/**
	 * Is this a robot hit?
	 * @return boolean
	 */
	function is_robot()
	{
		if( ! isset($this->is_robot) )
			$this->is_robot = ($this->get_agent_type() == 'robot');
		return $this->is_robot;
	}


	/**
	 * Get a version of browser
	 *
	 * @return integer Browser version
	 */
	function get_browser_version()
	{
		if( is_null( $this->browser_version ) )
		{	// Initialize browser version on first calling:
			$this->detect_useragent();
		}

		return intval( $this->browser_version );
	}
}

?>