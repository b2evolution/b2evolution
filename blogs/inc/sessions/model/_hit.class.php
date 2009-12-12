<?php
/**
 * This file implements the Hit class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @todo dh> Lazily handle properties through getters (and do not detect/do much in the constructor)!
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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
	 * The type of referer.
	 *
	 * Note: "spam" referers do not get logged.
	 * 'search'|'blacklist'|'referer'|'direct'|'spam'
	 *
	 * @var string
	 */
	var $referer_type;

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
	 * Extracted from search referers:
	 */
	var $_extracted_keyphrase = false;
	var $_keyphrase = NULL;
	var $_extracted_serprank = false;
	var $_serprank = NULL;


	/**
	 * Constructor
	 *
	 * This may INSERT a basedomain and a useragent but NOT the HIT itself!
	 */
	function Hit()
	{
		global $debug;

		// Get the first IP in the list of REMOTE_ADDR and HTTP_X_FORWARDED_FOR
		$this->IP = get_ip_list( true );

		// Check the REFERER and determine referer_type:
		// TODO: dh> move this out of here, too, only if "antispam_block_spam_referers" is true,
		//           do something about it (here or somewhere else, but early).
		$this->detect_referer(); // May EXIT if we are set up to block referer spam.

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

		if( is_admin_page() )
		{	// We are inside of admin, this supersedes 'direct' access
			// NOTE: this is not really a referer type but more a hit type
			$Debuglog->add( 'Hit: Referer is admin page.', 'request' );
			$this->referer_type = 'admin';
			return true;
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
	function detect_referer()
	{
		global $Debuglog, $debug;
		global $self_referer_list, $blackList, $search_engines;  // used to detect $referer_type
		global $skins_path;
		global $Settings;

		$this->referer = $this->get_referer();

		if( empty($this->referer) )
		{	// NO referer
			// This type may be superseeded and set to 'admin'
			if( ! $this->detect_admin_page() )
			{	// Not an admin page:
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
				return;
			}
		}


		// Check blacklist, see {@link $blackList}
		// NOTE: This is NOT the antispam!!
		// fplanque: we log these (again), because if we didn't we woudln't detect
		// reloads on these... and that would be a problem!
		foreach( $blackList as $lBlacklist )
		{
			$pos = strpos( $this->referer, $lBlacklist );
			// If not starting within in the first 12 chars it's probably an url param as in &url=http://this_blog.com
			if( $pos !== false && $pos <= 12 )
			{
				// This type may be superseeded by admin page
				// fp> 2009-05-10: because of the 12 char limit above the following is probably no longer needed. Please enable it back if anyone has a problem with admin being detected as blacklist
				// if( ! $this->detect_admin_page() )
				{	// Not an admin page:
					$Debuglog->add( 'Hit: detect_referer(): blacklist ('.$lBlacklist.')', 'request' );
					$this->referer_type = 'blacklist';
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
				require $skins_path.'_403_referer_spam.main.php';	// error & exit
				exit(0); // just in case.
				// THIS IS THE END!!
			}

			return; // type "spam"
		}


		// Is the referer a search engine?
		// Note: for debug simulation, you may need to add sth like $search_engines[] = '/credits.html'; into the conf
		// fp>dh: why add the code, but no matching data to stats.conf ?
		// dh> I wanted to refactor it, so that the data could contain the default encoding, too. Then I stopped this. I've left this though, since I thought it could turn out to be useful in the future.
		foreach( $search_engines as $search_engine_name => $lSearchEngine )
		{
			if( stristr($this->referer, $lSearchEngine) ) // search simulation
			{
				$Debuglog->add( 'Hit: detect_referer(): search engine ('.$lSearchEngine.')', 'request' );
				$this->referer_type = 'search';

				if( ctype_digit($search_engine_name) )
				{ // no name defined in $search_engines
					$this->search_engine = $lSearchEngine;
				}
				else
				{
					$this->search_engine = $search_engine_name;
				}
				return;
			}
		}
		$this->search_engine = 'unknown';

		$this->referer_type = 'referer';
	}


	/**
	 * Get name of search engine.
	 * This is only useful for referer_type="search".
	 *
	 * @return string Search engine name (or "pattern") from $search_engines. "unknown" if not detected.
	 */
	function get_search_engine()
	{
		if( ! isset($this->search_engine) )
			$this->detect_referer();
		return $this->search_engine;
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
					$DB->save_error_state();

					if( $DB->query( '
						INSERT INTO T_basedomains( dom_name )
							VALUES( '.$DB->quote($referer_basedomain).' )' ) )
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
		if( isset($this->agent_type) )
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

		$this->agent_type = 'unknown';
		$this->agent_name = '';
		$this->agent_platform = '';

		$user_agent = $this->get_user_agent();

		if( ! empty($user_agent) )
		{ // detect browser
			if( strpos($user_agent, 'Win') !== false)
			{
				$this->agent_platform = 'win';
			}
			elseif( strpos($user_agent, 'Mac') !== false)
			{
				$this->agent_platform = 'mac';
			}
			elseif( strpos($user_agent, 'Linux') !== false)
			{
				$this->agent_platform = 'linux';
			}
			elseif( $browscap = $this->get_browser_caps() )
			{
				$Debuglog->add( 'Hit:detect_useragent(): Trying to detect platform using browscap', 'request' );
				$Debuglog->add( 'Hit:detect_useragent(): Raw platform string: '.$browscap->platform, 'request' );

				$platform = strtolower( $browscap->platform );
				if( $platform == 'linux' || in_array( substr( $platform, 0, 3 ), array( 'win', 'mac' ) ) )
				{
					$this->agent_platform = $platform;
				}
			}

			if(strpos($user_agent, 'Lynx') !== false)
			{
				$this->is_lynx = 1;
				$this->agent_name = 'lynx';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'Firefox/') !== false)
			{
				$this->is_firefox = 1;
				$this->agent_name = 'firefox';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'Gecko/') !== false)	// We don't want to see Safari as Gecko
			{	// Tblue> Note: Gecko is only a rendering engine, not a real browser!
				$this->is_gecko = 1;
				$this->agent_name = 'gecko';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'MSIE') !== false && $this->agent_platform == 'win' )
			{
				$this->is_IE = true;
				$this->is_winIE = 1;
				$this->agent_name = 'msie';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'MSIE') !== false && $this->agent_platform == 'mac' )
			{
				$this->is_IE = true;
				$this->is_macIE = 1;
				$this->agent_name = 'msie';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'Chrome/') !== false)
			{
				$this->is_chrome = true;
				$this->agent_name = 'chrome';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'Safari/') !== false)
			{
				$this->is_safari = true;
				$this->agent_name = 'safari';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'Opera') !== false)
			{
				$this->is_opera = 1;
				$this->agent_name = 'opera';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'Nav') !== false || preg_match('/Mozilla\/4\./', $user_agent))
			{
				$this->is_NS4 = 1;
				$this->agent_name = 'nav4';
				$this->agent_type = 'browser';
			}
		}

		$Debuglog->add( 'Hit:detect_useragent(): Agent name: '.$this->agent_name, 'request' );
		$Debuglog->add( 'Hit:detect_useragent(): Agent platform: '.$this->agent_platform, 'request' );

		/*
		 * Detect requests for XML feeds by $skin / $tempskin param.
		 * fp> TODO: this is WEAK! Do we really need to know before going into the skin?
		 * dh> not necessary, but only where ->agent_type gets used (logging).
		 */
		if( isset( $Skin ) && $Skin->type == 'feed' )
		{
			$Debuglog->add( 'Hit: detect_useragent(): RSS', 'request' );
			$this->agent_type = 'rss';
		}
		else
		{ // Lookup robots
			$match = false;
			foreach( $user_agents as $lUserAgent )
			{
				if( $lUserAgent[0] == 'robot' && strpos( $this->user_agent, $lUserAgent[1] ) !== false )
				{
					$Debuglog->add( 'Hit:detect_useragent(): robot', 'request' );
					$this->agent_type = 'robot';
					$match = true;
					break;
				}
			}

			if( ! $match && ($browscap = $this->get_browser_caps()) && $browscap->crawler )
			{
				$Debuglog->add( 'Hit:detect_useragent(): robot (through browscap)', 'request' );
				$this->agent_type = 'robot';
			}
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
	 * @return boolean true if the hit gets logged; false if not
	 */
	function log()
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
		$this->record_the_hit();

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

		if( $is_admin_page && ! $Settings->get('log_admin_hits') )
		{	// We don't want to log admin hits:
			$Debuglog->add( 'Hit: Hit NOT logged, (Admin page logging is disabled)', 'request' );
			return false;
		}

		if( ! $is_admin_page && ! $Settings->get('log_public_hits') )
		{	// We don't want to log public hits:
			$Debuglog->add( 'Hit: Hit NOT logged, (Public page logging is disabled)', 'request' );
			return false;
		}

		if( $this->referer_type == 'spam' && ! $Settings->get('log_spam_hits') )
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
	 */
	function record_the_hit()
	{
		global $DB, $Session, $ReqURI, $Blog, $blog, $localtimenow, $Debuglog;

		$Debuglog->add( 'Hit: Recording the hit.', 'request' );

		if( !empty($Blog) )
		{
			$blog_ID = $Blog->ID;
		}
		elseif( !empty( $blog ) )
		{
			if( ! is_numeric($blog) )
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

		$hit_uri = substr($ReqURI, 0, 250); // VARCHAR(250) and likely to be longer
		$hit_referer = substr($this->referer, 0, 250); // VARCHAR(250) and likely to be longer

		// Extract the keyphrase from search referers:
		$keyphrase = $this->get_keyphrase();

		$keyp_ID = NULL;
		if( !empty( $keyphrase ) )
		{
			$DB->begin();

			$sql = 'SELECT keyp_ID
			          FROM T_track__keyphrase
			         WHERE keyp_phrase = '.$DB->quote($keyphrase);
			$keyp_ID = $DB->get_var( $sql, 0, 0, 'Get keyphrase ID' );

			if( empty( $keyp_ID ) )
			{
				$sql = 'INSERT INTO T_track__keyphrase( keyp_phrase )
					VALUES ('.$DB->quote($keyphrase).')';
				$DB->query( $sql, 'Add new keyphrase' );
				$keyp_ID = $DB->insert_id;
			}
		}

		// Extract the serprank from search referers:
		$serprank = $this->get_serprank();

		// insert hit into DB table:
		$sql = "
			INSERT INTO T_hitlog(
				hit_sess_ID, hit_datetime, hit_uri, hit_referer_type,
				hit_referer, hit_referer_dom_ID, hit_keyphrase_keyp_ID, hit_serprank, hit_blog_ID, hit_remote_addr, hit_agent_type )
			VALUES( '".$Session->ID."', FROM_UNIXTIME(".$localtimenow."), '".$DB->escape($hit_uri)."', '".$this->referer_type
				."', '".$DB->escape($hit_referer)."', ".$DB->null($this->get_referer_domain_ID()).', '.$DB->null($keyp_ID)
				.', '.$DB->null($serprank).', '.$DB->null($blog_ID).", '".$DB->escape( $this->IP )."', '".$this->get_agent_type()."'
			)";

		$DB->query( $sql, 'Record the hit' );

		if( !empty( $keyphrase ) )
		{
			$DB->commit();
		}

		$this->ID = $DB->insert_id;
	}



	/**
	 * Get the keyphrase from the referer
	 */
	function get_keyphrase()
	{
		if( !empty( $this->_extracted_keyphrase ) )
		{
			return $this->_keyphrase;
		}

		if( $this->referer_type == 'search' )
		{
			$this->_keyphrase = Hit::extract_keyphrase_from_referer( $this->referer );
		}

		$this->_extracted_keyphrase = true;

		return $this->_keyphrase;
	}


	/**
	 * Get the serprank from the referer
	 */
	function get_serprank()
	{
		if( !empty( $this->_extracted_serprank ) )
		{
			return $this->_extracted_serprank;
		}

		if( $this->referer_type == 'search' )
		{
			$this->_serprank = Hit::extract_serprank_from_referer( $this->referer );
		}

		$this->_extracted_serprank = true;

		return $this->_serprank;
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
	 * Get the User agent's name.
	 *
	 * @return string
	 */
	function get_agent_name()
	{
		if( ! isset($this->agent_name) )
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
		if( ! isset($this->agent_platform) )
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
		if( ! isset($this->agent_type) )
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

			// Restrict to current user if logged in:
			if( ! empty($current_User->ID) )
			{ // select by user ID: one user counts really just once. May be even faster than the anonymous query below..!?
				$sql = "
					SELECT SQL_NO_CACHE hit_ID FROM T_hitlog INNER JOIN T_sessions ON hit_sess_ID = sess_ID
					 WHERE sess_user_ID = ".$current_User->ID."
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


	/**
	 * Extract the keyphrase from a search engine referer url
	 *
	 * Typically http://google.com?s=keyphraz returns keyphraz
	 *
	 * @static
	 * @param string referer
	 * @return string keyphrase
	 */
	function extract_keyphrase_from_referer( $ref )
	{
		global $evo_charset, $known_search_params;

		// Parse URL.
		$pu = @parse_url($ref);
		if( ! isset($pu['query']) )
		{
			return NULL;
		}

		// Parse query string into associate array.
		parse_str($pu['query'], $ref_params);

		// Special handling for images.google.*:
		if( substr($pu['host'], 0, 14) == 'images.google.' && isset($ref_params['prev']) )
		{
			$prev = parse_url($ref_params['prev']);
			parse_str($prev['query'], $prev_params);

			$ie = isset($ref_params['ie']) ? $ref_params['ie'] : 'utf-8';
			$q = convert_charset($prev_params['q'], $evo_charset, $ie);
			return $q;
		}

		foreach( $known_search_params as $search_param )
		{
			if( isset($ref_params[$search_param]) )
			{ // found the keyphrase query parameter
				$q = trim(urldecode($ref_params[$search_param]));

			/* fp> what's that? when do we need that?
			 * Tblue> I think the problem is this: yandex.ru uses the text
			 *        parameter for the keyphrase and the p parameter for
			 *        the serprank, but some other search engine uses p
			 *        for the keyphrase. If yandex.ru puts the p param
			 *        before the text param in the URL, we would get the
			 *        serprank instead of the keyphrase, so if p appears
			 *        to be the serprank, we skip it. This may work for
			 *        yandex.ru, but if somebody searches for a numeric
			 *        value using the search engine which uses the p param
			 *        for the keyphrase, we won't get the correct result!
			 *        Conclusion: We need a better fix for yandex.ru.
			 * fp> What we need is to merge definitions for search engine sig + keyword param + position param into a single array or a single database table

				if( ctype_digit( $q ) && $param_parts[0] == 'p' )
				{	// ?p=5&text=keyword
					continue;
				}
			*/

				// convert from "input encoding":
				if( isset($ref_params['ie']) )
				{ // input encoding provided (Google does)
					$ie = $ref_params['ie'];
				}
				else
				{ // no input encoding provided, try to autodetect...
					$ie = 'utf-8'; // default

					if( can_check_encoding() )
					{
						foreach( array('utf-8', 'iso-8859-15') as $test_encoding )
						{
							if( check_encoding($q, $test_encoding) )
							{
								$ie = $test_encoding;
								break;
							}
						}
					}
				}
				$q = convert_charset($q, $evo_charset, $ie);

				return $q;
			}
		}

		return NULL;
	}


	/**
	 * Extract the "serp rank" from a search engine referer url
	 *
	 * Typically http://google.com?s=keyphraz&start=18 returns 18
	 *
	 * @static
	 * @param string referer
	 * @return string keyphrase
	 */
	function extract_serprank_from_referer( $ref )
	{
		// Note: The param names cannot contain special RegExp (PCRE)
		// characters (they must be escaped).
		static $serprank_params = array(
				'start',	// google
				'cd',		// google
				'b',		// yahoo
				'page',		// aol
				'page2',	// lycos
				'first',	// bing
				'sf',		// mail.ru
				'p',		// yandex.ru
			);
		static $regexp = '';

		if( $regexp === '' )
		{	// Generate RegExp:
			$regexp = '~[&?](?:'.implode( '|', $serprank_params ).')=([0-9]+)~i';
		}

		if( ! preg_match( $regexp, $ref, $serprank ) )
		{	// Could not extract serp rank:
			return NULL;
		}

		return $serprank[1];
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
}


/*
 * $Log$
 * Revision 1.56  2009/12/12 22:43:02  sam2kb
 * Detect Google Chrome. $Hit->is_chrome()
 *
 * Revision 1.55  2009/12/12 02:04:10  blueyed
 * doc
 *
 * Revision 1.54  2009/12/11 23:55:48  fplanque
 * doc
 *
 * Revision 1.53  2009/12/09 22:05:46  blueyed
 * Hit: extract_keyphrase_from_referer: test for utf-8 and iso-8859-15 in query string, where the referer contains no input encoding ("ie") hint.
 *
 * Revision 1.52  2009/12/09 22:04:37  blueyed
 * Hit: add get_search_engine. This will return the matched search engine name (if defined as key in $search_engines) or the matched pattern.
 *
 * Revision 1.51  2009/12/09 22:02:30  blueyed
 * Hit refactoring: add get_referer method.
 *
 * Revision 1.50  2009/12/09 20:09:32  blueyed
 * indent
 *
 * Revision 1.49  2009/12/08 22:38:13  fplanque
 * User agent type is now saved directly into the hits table instead of a costly lookup in user agents table
 *
 * Revision 1.48  2009/11/30 00:22:05  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.47  2009/11/24 01:03:00  blueyed
 * Fix transformation of duplicate entries in T_ user agents.
 *
 * Revision 1.46  2009/11/15 19:05:44  fplanque
 * no message
 *
 * Revision 1.45  2009/11/08 23:01:52  blueyed
 * Fix encoding/decoding of keyphrases from referers. Also, make the images.google code only apply to images.google.*
 *
 * Revision 1.44  2009/10/29 20:42:34  blueyed
 * Handle conversion of agnt_type from 'unknown' to a known type.
 *
 * Revision 1.43  2009/10/20 20:07:47  blueyed
 * Hit: init is_IE
 *
 * Revision 1.42  2009/10/13 10:02:01  tblue246
 * Hit::get_browser_caps(): Cache result.
 *
 * Revision 1.41  2009/10/13 09:39:03  tblue246
 * Bugfix
 *
 * Revision 1.40  2009/10/13 09:35:01  tblue246
 * Correctly detect Linux using browscap
 *
 * Revision 1.39  2009/10/12 22:51:58  blueyed
 * Hit: detect 'linux' as platform, too. Make the call to get_browser() more lazy.
 *
 * Revision 1.38  2009/10/03 20:43:40  tblue246
 * Commit message cleanup...
 *
 * Revision 1.37  2009/10/03 20:07:51  tblue246
 * - Hit::detect_user_agent():
 * 	- Try to use get_browser() to get platform information or detect robots if "normal" detection failed.
 * 	- Use Skin::type to detect RSS readers.
 * - Removed unneeded functions.
 * - translate_user_agent(): Use get_browser() if translation failed.
 *
 * Revision 1.36  2009/10/01 18:50:16  tblue246
 * convert_charset(): Trying to remove unreliable charset detection and modify all calls accordingly -- needs testing to ensure all charset conversions work as expected.
 *
 * Revision 1.35  2009/09/13 21:26:50  blueyed
 * SQL_NO_CACHE for SELECT queries using T_hitlog
 *
 * Revision 1.34  2009/08/31 21:47:02  fplanque
 * no message
 *
 * Revision 1.33  2009/08/31 01:45:28  sam2kb
 * $known_search_params definitions moved to conf/_stats.php
 * added "wd" param for baidu.com
 *
 * Revision 1.32  2009/08/30 15:35:51  tblue246
 * doc
 *
 * Revision 1.31  2009/08/30 14:00:13  fplanque
 * minor
 *
 * Revision 1.30  2009/08/22 15:27:38  tblue246
 * - FileRoot::FileRoot():
 * 	- Only try to create shared dir if enabled.
 * - Hit::extract_serprank_from_referer():
 * 	- Do not explode() $ref string, but use a (dynamically generated) RegExp instead. Tested and should work.
 *
 * Revision 1.29  2009/08/15 06:16:05  sam2kb
 * Better serp rank extraction
 *
 * Revision 1.28  2009/07/29 23:49:15  sam2kb
 * Better keywords extraction for google image search
 *
 * Revision 1.27  2009/07/27 18:58:31  blueyed
 * Fix E_FATAL in Hit::get_user_agent
 *
 * Revision 1.26  2009/07/08 02:38:55  sam2kb
 * Replaced strlen & substr with their mbstring wrappers evo_strlen & evo_substr when needed
 */
?>
