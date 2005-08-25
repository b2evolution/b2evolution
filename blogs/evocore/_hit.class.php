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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * A hit to a blog.
 */
class Hit
{
	/**
	 * Is the hit already logged?
	 * @var boolean
	 */
	var $logged = false;

	/**
	 * The type of referrer.
	 *
	 * @var string 'search'|'blacklist'|'referer'|'direct'|'spam'
	 */
	var $refererType;

	/**
	 * The ID of the referer's base domain in T_basedomains
	 *
	 * @var integer
	 */
	var $refererDomainID = 0;

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
	var $userAgent;

	/**
	 * The user's remote host.
	 * Use {@link getRemoteHost()} to access it (lazy filled).
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
	var $agentType = 'unknown';

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
		global $localtimenow;

		$this->IP = getIpList( true );
		$this->localtimenow = $localtimenow;

		$this->detectReferrer();
		$this->refererBasedomain = getBaseDomain($this->referer);

		if( $this->refererBasedomain )
		{
			$basedomain = $DB->get_row( 'SELECT dom_ID, dom_status FROM T_basedomains
																		WHERE dom_name = "'.$DB->escape($this->refererBasedomain).'"' );
			if( $basedomain )
			{
				$this->refererDomainID = $basedomain->dom_ID;
				if( $basedomain->dom_status == 'blacklist' )
				{
					$this->refererType = 'blacklist';
				}
			}
			else
			{
				$DB->query( 'INSERT INTO T_basedomains (dom_name, dom_status)
											VALUES( "'.$DB->escape($this->refererBasedomain).'",
												"'.( $this->refererType == 'blacklist' ? 'blacklist' : 'unknown' ).'" )' );
				$this->refererDomainID = $DB->insert_id;
			}
		}


		$this->detectUseragent();
		$this->detectReload();


		$Debuglog->add( 'IP: '.$this->IP, 'hit' );
		$Debuglog->add( 'Useragent: '.$this->userAgent, 'hit' );
		$Debuglog->add( 'Referrer: '.$this->referer, 'hit' );
		$Debuglog->add( 'Remote Host: '.$this->getRemoteHost(), 'hit' );
	}


	/**
	 * Detect a reload.
	 */
	function detectReload()
	{
		global $DB, $Debuglog, $Settings, $ReqURI;

		/*
		 * Check for reloads (if the URI has been requested from same IP/useragent
		 * in past reloadpage_timeout seconds.)
		 */
		if( $DB->get_var(
					'SELECT hit_ID FROM T_hitlog, T_useragents
						WHERE hit_uri = "'.$DB->escape( $ReqURI ).'"
							AND hit_datetime > "'.date( 'Y-m-d H:i:s', $this->localtimenow - $Settings->get('reloadpage_timeout') ).'"
							AND hit_remote_addr = '.$DB->quote( getIpList( true ) ).'
							AND agnt_ID = hit_agnt_ID
							AND agnt_signature = '.$DB->quote($this->userAgent),
					0, 0, 'Hit: Check for reload' ) )
		{
			$Debuglog->add( 'Reload!', 'hit' );
			$this->reloaded = true;  // We don't want to log this hit again
		}
	}


	/**
	 * Detect Referrer (sic!).
	 * Due to potential non-thread safety with getenv() (fallback), we'd better do this early.
	 */
	function detectReferrer()
	{
		global $HTTP_REFERER; // might be set by PHP (give highest priority)
		global $Debuglog;
		global $comments_allowed_uri_scheme; // used to validate the Referer
		global $blackList, $search_engines;  // used to detect $refererType

		if( isset( $HTTP_REFERER ) )
		{
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


		/*
		 * Check if we have a valid referer:
		 * minimum length: http://az.fr/
		 */
		if( strlen($this->referer) < 13 )
		{ // this will be considered direct access
			$Debuglog->add( 'detectReferrer(): invalid referer / direct access?', 'hit' );
			$this->refererType = 'direct';
			return;
		}


		/*
		 * Check if the referer is clean:
		 */
		if( ( $this->referer != strip_tags($this->referer)
					&& $error = 'bad char in referer' )
				|| ( $error = validate_url( $this->referer, $comments_allowed_uri_scheme ) ) )
		{ // then they have tried something funny (putting HTML or PHP into the HTTP_REFERER)
			$Debuglog->add( 'detectReferrer(): '.$error, 'hit');

			$this->refererType = 'spam'; // Hazardous
			$this->referer = false;

			// QUESTION: add domain to T_basedomains, type 'blacklist' ?

			return;
		}


		/*
		 * Check blacklist, see {@link $blackList}
		 * fplanque: we log these again, because if we didn't we woudln't detect
		 * reloads on these... and that would be a problem!
		 */
		foreach( $blackList as $lBlacklist )
		{
			if( strpos( $this->referer, $lBlacklist ) !== false )
			{
				$Debuglog->add( 'detectReferrer(): blacklist ('.$lBlacklist.')', 'hit' );
				$this->refererType = 'blacklist';
				return;
			}
		}


		/*
		 * Is the referer a search engine?
		 */
		foreach( $search_engines as $lSearchEngine )
		{
			if( stristr($this->referer, $lSearchEngine) )
			{
				$Debuglog->add( 'detectReferrer(): search engine ('.$lSearchEngine.')', 'hit' );
				$this->refererType = 'search';
				break;
			}
		}

		$this->refererType = 'referer';
	}


	/**
	 * Set {@link $userAgent} and detect the browser.
	 * This function also handles the relations with T_useragents and sets {@link $agentType}.
	 */
	function detectUseragent()
	{
		global $HTTP_USER_AGENT; // might be set by PHP, give highest priority
		global $DB, $Debuglog;
		global $user_agents;
		global $ReqPath;         // used to detect RSS feeds

		if( isset($HTTP_USER_AGENT) )
		{
			$this->userAgent = $HTTP_USER_AGENT;
		}
		elseif( isset($_SERVER['HTTP_USER_AGENT']) )
		{
			$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
		}

		if( !empty($this->userAgent) )
		{ // detect browser
			if(strpos($this->userAgent, 'Lynx') !== false)
			{
				$this->is_lynx = 1;
				$this->agentType = 'browser';
			}
			elseif(strpos($this->userAgent, 'Gecko') !== false)
			{
				$this->is_gecko = 1;
				$this->agentType = 'browser';
			}
			elseif(strpos($this->userAgent, 'MSIE') !== false && strpos($this->userAgent, 'Win') !== false)
			{
				$this->is_winIE = 1;
				$this->agentType = 'browser';
			}
			elseif(strpos($this->userAgent, 'MSIE') !== false && strpos($this->userAgent, 'Mac') !== false)
			{
				$this->is_macIE = 1;
				$this->agentType = 'browser';
			}
			elseif(strpos($this->userAgent, 'Opera') !== false)
			{
				$this->is_opera = 1;
				$this->agentType = 'browser';
			}
			elseif(strpos($this->userAgent, 'Nav') !== false || preg_match('/Mozilla\/4\./', $this->userAgent))
			{
				$this->is_NS4 = 1;
				$this->agentType = 'browser';
			}

			if( $this->userAgent != strip_tags($this->userAgent) )
			{ // then they have tried something funky, putting HTML or PHP into the user agent
				$Debuglog->add( 'detectUseragent(): '.T_('bad char in User Agent'), 'hit');
				$this->userAgent = '';
			}
		}
		$this->is_IE = (($this->is_macIE) || ($this->is_winIE));


		/*
		 * Detect requests for XML feeds
		 */
		if( stristr($ReqPath, 'rss') || stristr($ReqPath, 'rdf') || stristr($ReqPath, 'atom') )
		{
			$Debuglog->add( 'detectUseragent(): RSS', 'hit' );
			$this->agentType = 'rss';
		}
		else
		{ // Lookup robots
			foreach( $user_agents as $lUserAgent )
			{
				if( ($lUserAgent[0] == 'robot') && (strstr($this->userAgent, $lUserAgent[1])) )
				{
					$Debuglog->add( 'detectUseragent(): robot', 'hit' );
					$this->agentType = 'robot';
				}
			}
		}


		if( $agnt_data = $DB->get_row( 'SELECT agnt_ID, agnt_type FROM T_useragents
																		WHERE agnt_signature = "'.$DB->escape( $this->userAgent ).'"' ) )
		{ // this agent hit us once before
			$this->agentType = $agnt_data->agnt_type;
			$this->agentID = $agnt_data->agnt_ID;
		}
		else
		{ // create new user agent entry
			$DB->query( 'INSERT INTO T_useragents ( agnt_signature, agnt_type )
										VALUES ( "'.$DB->escape( $this->userAgent ).'", "'.$this->agentType.'" )' );

			$this->agentID = $DB->insert_id;
		}
	}


	/**
	 * Log a hit on a blog page / rss feed
	 *
	 * This function should be called at the end of the page, otherwise if the page
	 * is displaying previous hits, it may display the current one too.
	 * The hit will not be logged in special occasions, see {@link isNewView()} and {@link isGoodHit()}.
	 */
	function log()
	{
		global $Debuglog, $DB, $blog;
		global $doubleCheckReferers, $page, $ReqURI;
		global $stats_autoprune;
		global $Settings;

		if( $this->logged )
		{
			return false;
		}

		if( !$this->isNewView() || !$this->isGoodHit() )
		{ // We don't want to log this hit!
			$Debuglog->add( 'log(): Hit NOT Logged ('.var_export($this->refererType, true)
																							.', '.var_export($this->agentType, true).')', 'hit' );
			return false;
		}

		if( $doubleCheckReferers )
		{
			$Debuglog->add( 'log(): double check: loading referering page', 'hit' );

			if( $Settings->get('use_register_shutdown_function')
					&& function_exists( 'register_shutdown_function' ) )
			{ // register it as a shutdown function, because it will be slow!
				register_shutdown_function( array( &$this, 'doubleCheckReferers' ) );
			}
			else
			{
				// flush now, so that the meat of the page will get shown before it tries to check
				// back against the refering URL.
				flush();

				$this->doubleCheckReferers();
			}
		}
		else
		{
			$this->recordTheHit();
		}

		// Remember we have logged already:
		$this->logged = true;

		return true;
	}


	/**
	 * This records the hit. You should not call this directly, but {@link log()}
	 */
	function recordTheHit()
	{
		global $DB, $Session, $ReqURI, $Blog;

		$refererBasedomain = getBaseDomain( $this->referer );

		// insert hit into DB table:
		$sql = 'INSERT INTO T_hitlog( hit_sess_ID, hit_datetime, hit_uri,
																	hit_agnt_ID, hit_referer_type, hit_referer,
																	hit_referer_dom_ID, hit_blog_ID, hit_remote_addr )
						VALUES( "'.$Session->ID.'", FROM_UNIXTIME('.$this->localtimenow.'), "'.$DB->escape($ReqURI).'",
										"'.$this->agentID.'", "'.$this->refererType.'", "'.$DB->escape($this->referer).'",
										"'.$this->refererDomainID.'", "'.$Blog->ID.'", "'.$DB->escape( $this->IP ).'"
									)';

						#VALUES( , '".$DB->escape($ReqURI)."', '$hit_type',
										#'".$DB->escape($this->referer)."', '".$DB->escape($baseDomain)."', $blog,
										#'".$DB->escape( getIpList( true ) )."', '".$DB->escape($HTTP_USER_AGENT)."')";

		$DB->query( $sql );
	}


	/**
	 * This function gets called (as a shutdown function, if possible) and checks
	 * if the referering URL includes the current URL - if not it is probably spam!
	 *
	 * On success, this methods records the hit.
	 *
	 * @uses recordTheHit()
	 */
	function doubleCheckReferers()
	{
		global $ReqURI, $Debuglog;

		if( !empty($this->referer) )
		{
			$fullCurrentURL = 'http://'.$_SERVER['HTTP_HOST'].$ReqURI;
			// $Debuglog->add( 'Hit Log: '. "full current url: ".$fullCurrentURL, 'hit');

			if( ($fp = @fopen( $this->referer, 'r' )) ) // QUESTION: use file_get_contents()? (PHP > 4.3.0)
			{
				// timeout after 5 seconds
				socket_set_timeout($fp, 5);
				while( !feof($fp) )
				{
					$page .= trim(fgets($fp));
				}

				if( strstr($page, $fullCurrentURL) )
				{
					$Debuglog->add( 'doubleCheckReferers(): found current url in page', 'hit' );
				}
				else
				{
					$Debuglog->add( 'doubleCheckReferers(): '.sprintf('did not find &laquo;%s&raquo; in &laquo;%s&raquo;', $fullCurrentURL, $this->referer ), 'hit' );
					$this->refererType = 'spam';
				}
			}
			else
			{ // This was probably spam!
				$Debuglog->add( 'doubleCheckReferers(): could not access &laquo;'.$this->referer.'&raquo;', 'hit' );
				$this->refererType = 'spam';
			}
		}

		$this->recordTheHit();

		return true;
	}


	/**
	 * Get the User agent's signature.
	 *
	 * @return string
	 */
	function getUserAgent()
	{
		return $this->userAgent;
	}


	/**
	 * Get the remote hostname.
	 *
	 * @return string
	 */
	function getRemoteHost()
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
	function isNewView()
	{
		#pre_dump( 'isNewView:', !$this->reloaded,  !$this->ignore,   $this->agentType != 'robot' );
		return ( !$this->reloaded && !$this->ignore && $this->agentType != 'robot' );
	}


	/**
	 * Is this a good hit? This means "no spam".
	 *
	 * @return boolean
	 */
	function isGoodHit()
	{
		return !in_array( $this->refererType, array( 'spam' ) );
	}
}
?>
