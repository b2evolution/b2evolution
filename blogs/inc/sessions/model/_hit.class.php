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
	 * The user agent platform, eg "mac"
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
	 * The default setting ('unknown') is taken for new entries (into T_useragents),
	 * that are not detected as 'rss', 'robot' or 'browser'.
	 * 'rss'|'robot'|'browser'|'unknown'
	 *
	 * @see Hit::get_agent_type()
	 * @access protected
	 * @var string
	 */
	var $agent_type;

	/**
	 * The ID of the user agent entry in T_useragents.
	 * @see Hit::get_agent_ID()
	 * @access protected
	 * @var integer
	 */
	var $agent_ID;


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
			$Debuglog->add( 'IP: '.$this->IP, 'hit' );
			$Debuglog->add( 'UserAgent: '.$this->get_user_agent(), 'hit' );
			$Debuglog->add( 'Referer: '.var_export($this->referer, true).'; type='.$this->referer_type, 'hit' );
			$Debuglog->add( 'Remote Host: '.$this->get_remote_host( false ), 'hit' );
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
			$Debuglog->add( 'Referer is admin page.', 'hit' );
			$this->referer_type = 'admin';
			return true;
		}
		return false;
	}


	/**
	 * Detect Referer (sic!).
	 * Due to potential non-thread safety with getenv() (fallback), we'd better do this early.
	 *
	 * referer_type: enum('search', 'blacklist', 'referer', 'direct'); 'spam' gets used internally
	 */
	function detect_referer()
	{
		global $HTTP_REFERER; // might be set by PHP (give highest priority)
		global $Debuglog, $debug;
		global $self_referer_list, $blackList, $search_engines;  // used to detect $referer_type
		global $skins_path;
		global $Settings;

		if( isset( $HTTP_REFERER ) )
		{ // Referer provided by PHP:
			$this->referer = $HTTP_REFERER;
		}
		else
		{
			if( isset($_SERVER['HTTP_REFERER']) )
			{
				$this->referer = $_SERVER['HTTP_REFERER'];
			}
			else
			{ // Fallback method (not thread safe :[[ ) - this function does not work in ISAPI mode.
				$this->referer = getenv('HTTP_REFERER');
			}
		}

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
					$Debuglog->add( 'detect_referer(): self referer ('.$self_referer.')', 'hit' );
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
					$Debuglog->add( 'detect_referer(): blacklist ('.$lBlacklist.')', 'hit' );
					$this->referer_type = 'blacklist';
				}
				return;
			}
		}


		// Check if the referer is valid and does not match the antispam blacklist:
		// NOTE: requests to admin pages should not arrive here, because they should be matched above through $self_referer_list!
		if( $error = validate_url( $this->referer, 'commenting' ) )
		{ // This is most probably referer spam!!
			$Debuglog->add( 'detect_referer(): '.$error.' (SPAM)', 'hit');
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
		foreach( $search_engines as $lSearchEngine )
		{
			if( stristr($this->referer, $lSearchEngine) ) // search simulation
			{
				$Debuglog->add( 'detect_referer(): search engine ('.$lSearchEngine.')', 'hit' );
				$this->referer_type = 'search';
				return;
			}
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
	 * This function also handles the relations with T_useragents and sets {@link $agent_type}.
	 */
	function detect_useragent()
	{
		if( isset($this->agent_type) )
		{ // already detected.
			return;
		}

		global $DB, $Debuglog;
		global $user_agents;
		global $skin; // to detect agent_type (gets set in /xmlsrv/atom.php for example)


		// Init is_* members.
		$this->is_lynx = false;
		$this->is_firefox = false;
		$this->is_gecko = false;
		$this->is_winIE = false;
		$this->is_macIE = false;
		$this->is_safari = false;
		$this->is_opera = false;
		$this->is_NS4 = false;

		$this->agent_type = 'unknown';
		$this->agent_name = '';
		$this->agent_platform = '';

		$user_agent = $this->get_user_agent();

		if( ! empty($user_agent) )
		{ // detect browser
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
			{
				$this->is_gecko = 1;
				$this->agent_name = 'gecko';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'MSIE') !== false && strpos($user_agent, 'Win') !== false)
			{
				$this->is_winIE = 1;
				$this->agent_name = 'msie';
				$this->agent_type = 'browser';
			}
			elseif(strpos($user_agent, 'MSIE') !== false && strpos($user_agent, 'Mac') !== false)
			{
				$this->is_macIE = 1;
				$this->agent_name = 'msie';
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

			if( strpos($user_agent, 'Win') !== false)
			{
				$this->agent_platform = 'win';
			}
			elseif( strpos($user_agent, 'Mac') !== false)
			{
				$this->agent_platform = 'mac';
			}
		}
		$this->is_IE = (($this->is_macIE) || ($this->is_winIE));

		$Debuglog->add( 'Agent name: '.$this->agent_name, 'hit' );
		$Debuglog->add( 'Agent platform: '.$this->agent_platform, 'hit' );


		/*
		 * Detect requests for XML feeds by $skin / $tempskin param.
		 * fp> TODO: this is WEAK! Do we really need to know before going into the skin?
		 * dh> not necessary, but only where ->agent_type gets used (logging).
		 * Use $skin, if not empty (may be set in /xmlsrv/atom.php for example), otherwise $tempskin.
		 */
		$used_skin = empty( $skin ) ? param( 'tempskin', 'string', '', true ) : $skin;
		if( in_array( $used_skin, array( '_atom', '_rdf', '_rss', '_rss2' ) ) )
		{
			$Debuglog->add( 'detect_useragent(): RSS', 'hit' );
			$this->agent_type = 'rss';
		}
		else
		{ // Lookup robots
			foreach( $user_agents as $lUserAgent )
			{
				if( ($lUserAgent[0] == 'robot') && (strstr($this->user_agent, $lUserAgent[1])) )
				{
					$Debuglog->add( 'detect_useragent(): robot', 'hit' );
					$this->agent_type = 'robot';
				}
			}
		}
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
			$Debuglog->add( 'Hit NOT logged, (Admin page logging is disabled)', 'hit' );
			return false;
		}

		if( ! $is_admin_page && ! $Settings->get('log_public_hits') )
		{	// We don't want to log public hits:
			$Debuglog->add( 'Hit NOT logged, (Public page logging is disabled)', 'hit' );
			return false;
		}

		if( $this->referer_type == 'spam' && ! $Settings->get('log_spam_hits') )
		{	// We don't want to log referer spam hits:
			$Debuglog->add( 'Hit NOT logged, (Referer spam)', 'hit' );
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

		$Debuglog->add( 'Recording the hit.', 'hit' );

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
				hit_referer, hit_referer_dom_ID, hit_keyphrase_keyp_ID, hit_serprank, hit_blog_ID, hit_remote_addr, hit_agnt_ID )
			VALUES( '".$Session->ID."', FROM_UNIXTIME(".$localtimenow."), '".$DB->escape($hit_uri)."', '".$this->referer_type
				."', '".$DB->escape($hit_referer)."', ".$DB->null($this->get_referer_domain_ID()).', '.$DB->null($keyp_ID)
				.', '.$DB->null($serprank).', '.$DB->null($blog_ID).", '".$DB->escape( $this->IP )."', ".$this->get_agent_ID().'
			)';

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
				$Debuglog->add( 'detect_useragent(): '.T_('bad char in User Agent'), 'hit');
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
	 * Get DB ID for current user agent. This is used when logging the hit.
	 *
	 * @return integer
	 */
	function get_agent_ID()
	{
		if( ! isset($this->agent_ID) )
		{
			global $DB;
			if( $agnt_ID = $DB->get_var( "
				SELECT agnt_ID FROM T_useragents
				 WHERE agnt_signature = ".$DB->quote( $this->get_user_agent() )."
					 AND agnt_type = ".$DB->quote($this->get_agent_type()) ) )
			{ // this agent (with that type) hit us once before, re-use ID
				$this->agent_ID = $agnt_ID;
			}
			else
			{ // create new user agent entry
				$DB->query( "
					INSERT INTO T_useragents ( agnt_signature, agnt_type )
					VALUES ( ".$DB->quote( $this->get_user_agent() ).", ".$DB->quote($this->get_agent_type())." )" );

				$this->agent_ID = $DB->insert_id;
			}
		}
		return $this->agent_ID;
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
					SELECT hit_ID FROM T_hitlog INNER JOIN T_sessions ON hit_sess_ID = sess_ID
					 WHERE sess_user_ID = ".$current_User->ID."
						 AND hit_uri = '".$DB->escape( substr($ReqURI, 0, 250) )."'
					 LIMIT 1";
			}
			else
			{ // select by remote_addr/agnt_signature:
				$sql = "
					SELECT hit_ID
					  FROM T_hitlog INNER JOIN T_useragents
					    ON hit_agnt_ID = agnt_ID
					 WHERE hit_datetime > '".date( 'Y-m-d H:i:s', $localtimenow - $Settings->get('reloadpage_timeout') )."'
					   AND hit_remote_addr = ".$DB->quote( $this->IP )."
					   AND hit_uri = '".$DB->escape( substr($ReqURI, 0, 250) )."'
					   AND agnt_signature = ".$DB->quote($this->get_user_agent())."
					 LIMIT 1";
			}
			if( $DB->get_var( $sql, 0, 0, 'Hit: Check for reload' ) )
			{
				$Debuglog->add( 'No new view!', 'hit' );
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
		global $evo_charset;

		$kwout = '';
		if( ($pos_question = strpos( $ref, '?' )) == false )
		{
			return NULL;
		}

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
				);

		$ref_params = explode( '&', substr( $ref, $pos_question+1 ) );
		foreach( $ref_params as $ref_param )
		{
			$param_parts = explode( '=', $ref_param );
			if( !empty($param_parts[1]) && in_array( $param_parts[0], $known_search_params )	)
			{ // found "q" query parameter
				$q = trim(urldecode($param_parts[1]));
				$q = convert_charset($q, $evo_charset);
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
		if( preg_match( '/[?&](start|cd)=([0-9]+)/i', $ref, $matches ) )
		{
			return $matches[2];
		}

		return NULL;
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
 * Revision 1.25  2009/05/10 00:28:51  fplanque
 * serp rank logging
 *
 * Revision 1.24  2009/03/21 23:00:21  fplanque
 * minor
 *
 * Revision 1.23  2009/03/21 01:00:41  waltercruz
 * Fixing http://forums.b2evolution.net//viewtopic.php?p=89122
 *
 * Revision 1.22  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.21  2009/03/06 14:11:09  tblue246
 * doc
 *
 * Revision 1.20  2009/03/04 00:10:42  blueyed
 * Make Hit constructor more lazy.
 *  - Move referer_dom_ID generation/fetching to own method
 *  - wrap Debuglog additons with "debug"
 *  - Conditionally call detect_useragent, if required. Move
 *    vars to methods for this
 *  - get_user_agent alone does not require detect_useragent
 * Feel free to revert it (since it changed all the is_foo vars
 * to methods - PHP5 would allow to use __get to handle legacy
 * access to those vars however), but please consider also
 * removing this stuff from HTML classnames, since that is kind
 * of disturbing/unreliable by itself).
 *
 * Revision 1.19  2009/03/03 20:32:11  blueyed
 * TODO about making Hit more lazy.
 *
 * Revision 1.18  2009/03/03 20:23:46  blueyed
 * Move extract_keyphrase_from_referer to Hit class. Otherwise it should get moved to hit.funcs.
 *
 * Revision 1.17  2009/02/23 20:34:31  blueyed
 * Cleanup whitespace/indent in comments for known_search_params
 *
 * Revision 1.16  2009/02/23 07:45:37  sam2kb
 * Added 'text' to known_search_params
 *
 * Revision 1.15  2009/02/13 19:57:50  blueyed
 * doc, indent
 *
 * Revision 1.14  2008/12/28 19:02:19  fplanque
 * minor
 *
 * Revision 1.13  2008/06/30 21:24:20  blueyed
 * - convert_charset(): auto-detect source encoding, if not given (UTF-8, ISO-8859-1, ISO-8859-15)
 * - extract_keyphrase_from_referer: use convert_charset() to convert query string to $evo_charset
 *
 * Revision 1.12  2008/05/10 22:59:10  fplanque
 * keyphrase logging
 *
 * Revision 1.10  2008/05/07 18:07:12  fplanque
 * trying to fix.
 *
 * Revision 1.9  2008/05/01 18:53:42  blueyed
 * Fix SQL injection through $blog
 *
 * Revision 1.8  2008/04/26 22:23:59  fplanque
 * reverted dirty hack (you must treat search engines through conf file)
 *
 * Revision 1.6  2008/02/19 11:11:18  fplanque
 * no message
 *
 * Revision 1.5  2008/01/21 09:35:33  fplanque
 * (c) 2008
 *
 * Revision 1.4  2008/01/19 15:45:28  fplanque
 * refactoring
 *
 * Revision 1.3  2007/09/18 00:00:59  fplanque
 * firefox mac specific forms
 *
 * Revision 1.2  2007/09/17 02:36:25  fplanque
 * CSS improvements
 *
 * Revision 1.1  2007/06/25 11:00:57  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.49  2007/04/27 09:11:37  fplanque
 * saving "spam" referers again (instead of buggy empty referers)
 *
 * Revision 1.48  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.47  2007/03/25 15:07:38  fplanque
 * multiblog fixes
 *
 * Revision 1.46  2007/02/19 13:15:34  waltercruz
 * Changing double quotes to single quotes in queries
 *
 * Revision 1.45  2007/02/06 13:44:05  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.44  2007/02/06 00:03:38  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.43  2006/12/07 23:13:11  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.42  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.41  2006/10/07 20:45:11  blueyed
 * Handle logging with referers longer than 250 chars
 *
 * Revision 1.40  2006/10/06 21:54:16  blueyed
 * Fixed hit_uri handling, especially in strict mode
 */
?>
