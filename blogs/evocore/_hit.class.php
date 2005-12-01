<?php
/**
 * This file implements the Hit class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * A hit to a blog.
 *
 * NOTE: The internal function double_check_referers() uses the class Net_IDNA_php4 from /blogs/lib/_idna_convert.class.php.
 *       It's required() only, when needed.
 */
class Hit
{
	/**
	 * Is the hit already logged?
	 * @var boolean
	 */
	var $logged = false;

	/**
	 * The type of referer.
	 *
	 * @var string 'search'|'blacklist'|'referer'|'direct'|'spam'
	 */
	var $referer_type;

	/**
	 * The ID of the referer's base domain in T_basedomains
	 *
	 * @var integer
	 */
	var $referer_domain_ID = 0;

	/**
	 * Is this a reload?
	 * @var boolean
	 */
	var $reloaded = false;

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
	 * @var string
	 */
	var $user_agent;

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
	 *
	 * @var string 'rss'|'robot'|'browser'|'unknown'
	 */
	var $agent_type = 'unknown';

	/**
	 * The ID of the user agent entry in T_useragents.
	 * @var integer
	 */
	var $agent_ID;

	/**#@+
	 * @var integer|NULL Detected browser.
	 */
	var $is_lynx;
	var $is_gecko;
	var $is_winIE;
	var $is_macIE;
	var $is_opera;
	var $is_NS4;
	/**#@-*/


	/**
	 * Constructor
	 */
	function Hit()
	{
		global $Debuglog, $DB;
		global $comments_allowed_uri_scheme;

		// Get the first IP in the list of REMOTE_ADDR and HTTP_X_FORWARDED_FOR
		$this->IP = getIpList( true );

		// Check the referer:
		$this->detect_referer();
		$this->referer_basedomain = getBaseDomain($this->referer);

		if( $this->referer_basedomain )
		{
			$basedomain = $DB->get_row( '
				SELECT dom_ID, dom_status FROM T_basedomains
				 WHERE dom_name = "'.$DB->escape($this->referer_basedomain).'"' );
			if( $basedomain )
			{
				$this->referer_domain_ID = $basedomain->dom_ID;
				if( $basedomain->dom_status == 'blacklist' )
				{
					$this->referer_type = 'blacklist';
				}
			}
			else
			{
				$DB->query( '
					INSERT INTO T_basedomains (dom_name, dom_status)
						VALUES( "'.$DB->escape($this->referer_basedomain).'",
						"'.( $this->referer_type == 'blacklist' ? 'blacklist' : 'unknown' ).'" )' );
				$this->referer_domain_ID = $DB->insert_id;
			}
		}


		$this->detect_useragent();
		$this->detect_reload();


		$Debuglog->add( 'IP: '.$this->IP, 'hit' );
		$Debuglog->add( 'UserAgent: '.$this->user_agent, 'hit' );
		$Debuglog->add( 'Referer: '.var_export($this->referer, true).'; type='.$this->referer_type, 'hit' );
		$Debuglog->add( 'Remote Host: '.$this->get_remote_host(), 'hit' );
	}


	/**
	 * Detect a reload.
	 *
	 * What exactly do we need this for?
	 *
	 * @todo: if this is only useful to display who's online or view counts, provide option to disable all those resource consuming gadgets. (Those gadgets should be plugins actually, and they should enable this query only if needed)
	 */
	function detect_reload()
	{
		global $DB, $Debuglog, $Settings, $ReqURI, $localtimenow;

		/*
		 * Check for reloads (if the URI has been requested from same IP/useragent
		 * in past reloadpage_timeout seconds.)
		 */
		if( $DB->get_var( '
			SELECT hit_ID FROM T_hitlog INNER JOIN T_sessions ON hit_sess_ID = sess_ID
			       INNER JOIN T_useragents ON sess_agnt_ID = agnt_ID
			 WHERE hit_uri = "'.$DB->escape( $ReqURI ).'"
			   AND hit_datetime > "'.date( 'Y-m-d H:i:s', $localtimenow - $Settings->get('reloadpage_timeout') ).'"
			   AND hit_remote_addr = '.$DB->quote( $this->IP ).'
			   AND agnt_signature = '.$DB->quote($this->user_agent),
					0, 0, 'Hit: Check for reload' ) )
		{
			$Debuglog->add( 'Reload!', 'hit' );
			$this->reloaded = true;  // We don't want to log this hit again
		}
	}


	/**
	 * Detect Referer (sic!).
	 * Due to potential non-thread safety with getenv() (fallback), we'd better do this early.
	 *
	 * referer_type: enum('search', 'blacklist', 'referer', 'direct', 'spam')
	 */
	function detect_referer()
	{
		global $HTTP_REFERER; // might be set by PHP (give highest priority)
		global $Debuglog;
		global $comments_allowed_uri_scheme; // used to validate the Referer
		global $blackList, $search_engines;  // used to detect $referer_type

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


		// Check if the referer is valid and is not blacklisted:
		if( $error = validate_url( $this->referer, $comments_allowed_uri_scheme ) )
		{
			$Debuglog->add( 'detect_referer(): '.$error, 'hit');
			$this->referer_type = 'spam'; // Hazardous
			$this->referer = false;
			// QUESTION: add domain to T_basedomains, type 'blacklist' ?

			// This is most probably referer spam,
			// In order to preserve server resources, we're going to stop processing immediatly!!
			require dirname(__FILE__).'/_referer_spam.page.php';	// error & exit
			// THIS IS THE END!!
		}


		// Check blacklist, see {@link $blackList}
		// NOTE: This is NOT the antispam!!
		// fplanque: we log these again, because if we didn't we woudln't detect
		// reloads on these... and that would be a problem!
		foreach( $blackList as $lBlacklist )
		{
			if( strpos( $this->referer, $lBlacklist ) !== false )
			{
				$Debuglog->add( 'detect_referer(): blacklist ('.$lBlacklist.')', 'hit' );
				$this->referer_type = 'blacklist';
				return;
			}
		}


		// Is the referer a search engine?
		foreach( $search_engines as $lSearchEngine )
		{
			if( stristr($this->referer, $lSearchEngine) )
			{
				$Debuglog->add( 'detect_referer(): search engine ('.$lSearchEngine.')', 'hit' );
				$this->referer_type = 'search';
				return;
			}
		}

		if( !empty($this->referer) )
		{
			$this->referer_type = 'referer';
		}
		else
		{
			$this->referer_type = 'direct';
		}
	}


	/**
	 * Set {@link $user_agent} and detect the browser.
	 * This function also handles the relations with T_useragents and sets {@link $agent_type}.
	 */
	function detect_useragent()
	{
		global $HTTP_USER_AGENT; // might be set by PHP, give highest priority
		global $DB, $Debuglog;
		global $user_agents;
		global $ReqPath;         // used to detect RSS feeds

		if( isset($HTTP_USER_AGENT) )
		{
			$this->user_agent = $HTTP_USER_AGENT;
		}
		elseif( isset($_SERVER['HTTP_USER_AGENT']) )
		{
			$this->user_agent = $_SERVER['HTTP_USER_AGENT'];
		}

		if( !empty($this->user_agent) )
		{ // detect browser
			if(strpos($this->user_agent, 'Lynx') !== false)
			{
				$this->is_lynx = 1;
				$this->agent_type = 'browser';
			}
			elseif(strpos($this->user_agent, 'Gecko') !== false)
			{
				$this->is_gecko = 1;
				$this->agent_type = 'browser';
			}
			elseif(strpos($this->user_agent, 'MSIE') !== false && strpos($this->user_agent, 'Win') !== false)
			{
				$this->is_winIE = 1;
				$this->agent_type = 'browser';
			}
			elseif(strpos($this->user_agent, 'MSIE') !== false && strpos($this->user_agent, 'Mac') !== false)
			{
				$this->is_macIE = 1;
				$this->agent_type = 'browser';
			}
			elseif(strpos($this->user_agent, 'Opera') !== false)
			{
				$this->is_opera = 1;
				$this->agent_type = 'browser';
			}
			elseif(strpos($this->user_agent, 'Nav') !== false || preg_match('/Mozilla\/4\./', $this->user_agent))
			{
				$this->is_NS4 = 1;
				$this->agent_type = 'browser';
			}

			if( $this->user_agent != strip_tags($this->user_agent) )
			{ // then they have tried something funky, putting HTML or PHP into the user agent
				$Debuglog->add( 'detect_useragent(): '.T_('bad char in User Agent'), 'hit');
				$this->user_agent = '';
			}
		}
		$this->is_IE = (($this->is_macIE) || ($this->is_winIE));


		/*
		 * Detect requests for XML feeds
		 */
		if( stristr($ReqPath, 'rss') || stristr($ReqPath, 'rdf') || stristr($ReqPath, 'atom') )
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


		if( $agnt_data = $DB->get_row( '
			SELECT agnt_ID, agnt_type FROM T_useragents
			 WHERE agnt_signature = "'.$DB->escape( $this->user_agent ).'"
			   AND agnt_type = "'.$this->agent_type.'"' ) )
		{ // this agent (with that type) hit us once before
			$this->agent_type = $agnt_data->agnt_type;
			$this->agent_ID = $agnt_data->agnt_ID;
		}
		else
		{ // create new user agent entry
			$DB->query( '
				INSERT INTO T_useragents ( agnt_signature, agnt_type )
				VALUES ( "'.$DB->escape( $this->user_agent ).'", "'.$this->agent_type.'" )' );

			$this->agent_ID = $DB->insert_id;
		}
	}


	/**
	 * Log a hit on a blog page / rss feed.
	 *
	 * This function should be called at the end of the page, otherwise if the page
	 * is displaying previous hits, it may display the current one too.
	 *
	 * The hit will not be logged in special occasions, see {@link is_new_view()} and {@link is_good_hit()}.
	 *
	 * @return boolean true if the hit gets logged; false if not
	 */
	function log()
	{
		global $Debuglog, $DB, $blog, $debug_no_register_shutdown;
		global $Settings;

		if( $this->logged )
		{
			return false;
		}

		if( !$this->is_new_view() || !$this->is_good_hit() )
		{ // We don't want to log this hit!
			$hit_info = 'referer_type: '.var_export($this->referer_type, true)
				.', agent_type: '.var_export($this->agent_type, true)
				.', is'.( $this->is_new_view() ? '' : ' NOT' ).' a new view'
				.', is'.( $this->is_good_hit() ? '' : ' NOT' ).' a good hit';
			$Debuglog->add( 'log(): Hit NOT logged, ('.$hit_info.')', 'hit' );
			return false;
		}

		if( $this->referer_type == 'referer' && $Settings->get('hit_doublecheck_referer') )
		{
			if( !$debug_no_register_shutdown && function_exists( 'register_shutdown_function' ) )
			{ // register it as a shutdown function, because it will be slow!
				$Debuglog->add( 'log(): double-check: loading referering page.. (register_shutdown_function())', 'hit' );
				register_shutdown_function( array( &$this, 'double_check_referers' ) ); // this will also call _record_the_hit()
			}
			else
			{
				// flush now, so that the meat of the page will get shown before it tries to check
				// back against the refering URL.
				flush();

				$Debuglog->add( 'log(): double-check: loading referering page..', 'hit' );

				$this->double_check_referers(); // this will also call _record_the_hit()
			}
		}
		else
		{
			$this->_record_the_hit();
		}

		// Remember we have logged already:
		$this->logged = true;

		return true;
	}


	/**
	 * This records the hit. You should not call this directly, but {@link log()}!
	 *
	 * It gets called either by {@link log()} or by {@link double_check_referers()} when this is used.
	 *
	 * It will call Hitlist::dbprune() to do the automatic pruning of old hits.
	 *
	 * @access protected
	 */
	function _record_the_hit()
	{
		global $DB, $Session, $ReqURI, $Blog, $localtimenow, $Debuglog;

		$referer_basedomain = getBaseDomain( $this->referer );

		$Debuglog->add( 'log(): Recording the hit.', 'hit' );

		// insert hit into DB table:
		$sql = '
			INSERT INTO T_hitlog( hit_sess_ID, hit_datetime, hit_uri, hit_referer_type,
				hit_referer, hit_referer_dom_ID, hit_blog_ID, hit_remote_addr )
			VALUES( "'.$Session->ID.'", FROM_UNIXTIME('.$localtimenow.'), "'.$DB->escape($ReqURI).'",
				"'.$this->referer_type.'", "'.$DB->escape($this->referer).'",
				"'.$this->referer_domain_ID.'", "'.$Blog->ID.'", "'.$DB->escape( $this->IP ).'"
			)';

		$DB->query( $sql, 'Record the hit' );

		require_once( dirname(__FILE__).'/_hitlist.class.php' );
		Hitlist::dbprune(); // will prune once per day, according to Settings
	}


	/**
	 * This function gets called (as a {@link register_shutdown_function() shutdown function}, if possible) and checks
	 * if the referering URL's content includes the current URL - if not it is probably spam!
	 *
	 * On success, this methods records the hit.
	 *
	 * TODO: use DB cache to avoid checking the same page again and again!
	 * TODO: transform into plugin (blueyed)
	 *
	 * @uses _record_the_hit()
	 */
	function double_check_referers()
	{
		global $ReqURI, $Debuglog;
		global $core_dirout, $lib_subdir;

		if( !empty($this->referer) )
		{
			if( ($fp = @fopen( $this->referer, 'r' )) )
			{
				socket_set_timeout($fp, 5); // timeout after 5 seconds
				// Get the refering page's content
				$content_ref_page = '';
				$bytes_read = 0;
				while( ($l_byte = fgetc($fp)) !== false )
				{
					$content_ref_page .= $l_byte;
					if( ++$bytes_read > 512000 )
					{ // do not pull more than 500kb of data!
						break;
					}
				}

				$full_req_url = 'http://'.$_SERVER['HTTP_HOST'].$ReqURI;
				// $Debuglog->add( 'Hit Log: '. "full current url: ".$full_req_url, 'hit');

				/**
				 * IDNA converter class
				 */
				require_once dirname(__FILE__).'/'.$core_dirout.$lib_subdir.'_idna_convert.class.php';
				$IDNA = new Net_IDNA_php4();

				// TODO: match <a href="...">!?
				// TODO: http://demo.b2evolution.net links "HEAD/blogs/index.php?blog=2", but we search for "http://demo.b2evolution.net/HEAD/blogs/index.php?blog=2"!
				$idn_decoded_full_req_url = $IDNA->decode($full_req_url);

				if( strstr($content_ref_page, $full_req_url)
					  || ( ($idn_decoded_full_req_url != $full_req_url) && strstr($content_ref_page, $idn_decoded_full_req_url) ) )
				{
					$Debuglog->add( 'double_check_referers(): found current url in page ('.bytesreadable($bytes_read).' read)', 'hit' );
				}
				else
				{
					$Debuglog->add( 'double_check_referers(): '.sprintf('did not find &laquo;%s&raquo; in &laquo;%s&raquo; (%s bytes read). -> referer_type=spam!',
							$full_req_url.( $idn_decoded_full_req_url != $full_req_url ? ' / '.$idn_decoded_full_req_url : '' ),
							$this->referer, bytesreadable($bytes_read) ), 'hit' );
					$this->referer_type = 'spam';
				}
				unset( $content_ref_page );
			}
			else
			{ // This was probably spam!
				$Debuglog->add( 'double_check_referers(): could not access &laquo;'.$this->referer.'&raquo;', 'hit' );
				$this->referer_type = 'spam';
			}
		}

		$this->_record_the_hit();

		return true;
	}


	/**
	 * Get the User agent's signature.
	 *
	 * @return string
	 */
	function get_user_agent()
	{
		return $this->user_agent;
	}


	/**
	 * Get the remote hostname.
	 *
	 * @return string
	 */
	function get_remote_host()
	{
		if( is_null($this->_remoteHost) )
		{
			if( isset( $_SERVER['REMOTE_HOST'] ) )
			{
				$this->_remoteHost = $_SERVER['REMOTE_HOST'];
			}
			else
			{
				$this->_remoteHost = @gethostbyaddr($this->IP);
			}
		}

		return $this->_remoteHost;
	}


	/**
	 * Determine if a hit is a new view (not reloaded, ignored or a robot).
	 *
	 * @return boolean
	 */
	function is_new_view()
	{
		#pre_dump( 'is_new_view:', !$this->reloaded,  !$this->ignore,   $this->agent_type != 'robot' );
		return ( !$this->reloaded && !$this->ignore && $this->agent_type != 'robot' );
	}


	/**
	 * Is this a good hit? This means "no spam".
	 *
	 * @return boolean
	 */
	function is_good_hit()
	{
		return !in_array( $this->referer_type, array( 'spam' ) );
	}
}
?>