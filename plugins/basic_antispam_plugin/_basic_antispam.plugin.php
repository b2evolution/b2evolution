<?php
/**
 * This file implements the basic Antispam plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Basic Antispam Plugin
 *
 * This plugin doublechecks referers/referrers for Hit logging and trackbacks.
 *
 * @todo Ideas:
 *  - forbid cloned comments (same content) (on the same entry or all entries)
 *  - detect same/similar URLs in a short period (also look at author name: if it differs, it's more likely to be spam)
 */
class basic_antispam_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'Basic Antispam';
	var $code = 'b2evBAspm';
	var $priority = 60;
	var $version = '6.7.8';
	var $author = 'The b2evo Group';
	var $group = 'antispam';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Basic antispam methods');
		$this->long_desc = T_('This plugin provides basic methods to detect & block spam on referers, comments & trackbacks.');
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		return array(
				'check_dupes' => array(
					'type' => 'checkbox',
					'label' => T_('Detect feedback duplicates'),
					'note' => T_('Check this to check comments and trackback for duplicate content.'),
					'defaultvalue' => '1',
				),
				'max_number_of_links_feedback' => array(
					'type' => 'integer',
					'label' => T_('Feedback sensitivity to links'),
					'note' => T_('If a comment has more than this number of links in it, it will get 100 percent spam karma. -1 to disable it.'),
					'help' => '#set_max_number_of_links',
					'defaultvalue' => '4',
					'size' => 3,
				),
				'trim_whitespace' => array(
					'type' => 'checkbox',
					'label' => T_('Strip whitespace'),
					'note' => T_('Strip whitespace from the beginning and end of comment content.'),
					'defaultvalue' => 1,
				),
				'remove_repetitions' => array(
					'type' => 'checkbox',
					'label' => T_('Remove repetitive characters'),
					'note'=>T_('Remove repetitive characters in name and content. The string like "Thaaaaaaaaaanks!" becomes "Thaaanks!".'),
					'defaultvalue' => 0,
				),
				'block_common_spam' => array(
					'type' => 'checkbox',
					'label' => T_('Block common spam comments'),
					'note'=>T_('Block comments with both "[link=" and "[url=" tags.'),
					'defaultvalue' => 1,
				),
				'nofollow_for_hours' => array(
					'type' => 'integer',
					'label' => T_('Apply rel="nofollow"'),
					'note'=>T_('hours. For how long should rel="nofollow" be applied to comment links? (0 means never, -1 means always)'),
					'defaultvalue' => '-1', // use "nofollow" infinitely by default so lazy admins won't promote spam
					'size' => 5,
				),
				'check_url_referers' => array(
					'type' => 'checkbox',
					'label' => T_('Check referers for URL'),
					'note' => T_('Check refering pages, if they contain our URL. This may generate a lot of additional traffic!'),
					'defaultvalue' => '0',
				),

			);
	}


	/**
	 * Handle max_number_of_links_feedback setting.
	 *
	 * Try to detect as many links as possible
	 */
	function GetSpamKarmaForComment( & $params )
	{
		$max_comments = $this->Settings->get('max_number_of_links_feedback');
		if( $max_comments != -1 )
		{ // not deactivated:
			$count = preg_match_all( '~(https?|ftp)://~i', $params['Comment']->content, $matches );

			if( $count > $max_comments )
			{
				return 100;
			}

			if( $count == 0 )
			{
				return 0;
			}

			return (100/$max_comments) * $count;
		}
	}


	/**
	 * Disable/Enable events according to settings.
	 *
	 * "AppendHitLog" gets enabled according to check_url_referers setting.
	 * "BeforeTrackbackInsert" gets disabled, if we do not check for duplicate content.
	 */
	function BeforeEnable()
	{
		if( $this->Settings->get('check_url_referers') )
		{
			$this->enable_event( 'AppendHitLog' );
		}
		else
		{
			$this->disable_event( 'AppendHitLog' );
		}

		if( ! $this->Settings->get('check_dupes') )
		{
			$this->disable_event( 'BeforeTrackbackInsert' );
		}
		else
		{
			$this->enable_event( 'BeforeTrackbackInsert' );
		}

		return true;
	}


	/**
	 * - Check for duplicate trackbacks.
	 */
	function BeforeTrackbackInsert( & $params )
	{
		if( $this->is_duplicate_comment( $params['Comment'] ) )
		{
			$this->msg( T_('The trackback seems to be a duplicate.'), 'error' );
			if( $comment_Item = & $params['Comment']->get_Item() )
			{
				syslog_insert( 'The trackback seems to be a duplicate', 'info', 'item', $comment_Item->ID, 'plugin', $this->ID );
			}
		}
	}


	function CommentFormSent( & $params )
	{
		if( $this->Settings->get('trim_whitespace') )
		{	// Strip whitespace
			$params['comment'] = trim( $params['comment'] );
		}

		if( $this->Settings->get('remove_repetitions') )
		{	// Remove repetitions
			$params['anon_name'] = $this->remove_repetition( $params['anon_name'] );
			$params['comment'] = $this->remove_repetition( $params['comment'] );
		}
	}


	/**
	 * Check for duplicate comments.
	 */
	function BeforeCommentFormInsert( & $params )
	{
		$comment_Item = & $params['Comment']->get_Item();

		if( $this->is_duplicate_comment( $params['Comment'] ) )
		{
			$this->msg( T_('The comment seems to be a duplicate.'), 'error' );
			if( $comment_Item )
			{
				syslog_insert( 'The comment seems to be a duplicate', 'info', 'item', $comment_Item->ID, 'plugin', $this->ID );
			}
		}

		if( $this->Settings->get('block_common_spam') && preg_match_all( '~\[(link|url)=~', $params['Comment']->content, $m ) )
		{	// Block common bbcode spam comments with both [url= and [link= tags
			if( !empty($m[1]) && count($m[1]) > 1 )
			{
				$this->msg( T_('Your comment was rejected because it appeared to be spam.'), 'error' );
				if( $comment_Item )
				{
					syslog_insert( 'The comment was rejected because it appeared to be spam', 'warning', 'item', $comment_Item->ID, 'plugin', $this->ID );
				}
			}
		}
	}


	/**
	 * If we use "makelink", handle nofollow rel attrib.
	 *
	 * @uses basic_antispam_plugin::apply_nofollow()
	 */
	function FilterCommentAuthor( & $params )
	{
		if( ! $params['makelink'] )
		{
			return false;
		}

		$this->apply_nofollow( $params['data'], $params['Comment'] );
	}


	/**
	 * Handle nofollow in author URL (if it's made clickable)
	 *
	 * @uses basic_antispam_plugin::FilterCommentAuthor()
	 */
	function FilterCommentAuthorUrl( & $params )
	{
		$this->FilterCommentAuthor( $params );
	}


	/**
	 * Handle nofollow rel attrib in comment content.
	 *
	 * @uses basic_antispam_plugin::FilterCommentAuthor()
	 */
	function FilterCommentContent( & $params )
	{
		$this->apply_nofollow( $params['data'], $params['Comment'] );
	}


	/**
	 * Do we want to apply rel="nofollow" tag?
	 *
	 * @return boolean
	 */
	function apply_nofollow( & $data, $Comment )
	{
		global $localtimenow;

		$hours = $this->Settings->get('nofollow_for_hours'); // 0=never, -1 always, otherwise for x hours

		if( $hours == 0 )
		{ // "never"
			return;
		}

		if( $hours > 0 // -1 is "always"
			&& mysql2timestamp( $Comment->date ) <= ( $localtimenow - $hours*3600 ) )
		{
			return;
		}

		$data = preg_replace_callback( '~(<a\s)([^>]+)>~i', create_function( '$m', '
				if( preg_match( \'~\brel=([\\\'"])(.*?)\1~\', $m[2], $match ) )
				{ // there is already a rel attrib:
					$rel_values = explode( " ", $match[2] );

					if( ! in_array( \'nofollow\', $rel_values ) )
					{
						$rel_values[] = \'nofollow\';
					}

					return $m[1]
						.preg_replace(
							\'~\brel=([\\\'"]).*?\1~\',
							\'rel=$1\'.implode( " ", $rel_values ).\'$1\',
							$m[2] )
						.">";
				}
				else
				{
					return $m[1].$m[2].\' rel="nofollow">\';
				}' ), $data );
	}


	function remove_repetition( $str = '' )
	{
		if( ($newstring = @preg_replace( '~(.)\\1{3,}~u', '$1$1$1', $str )) === NULL )
		{	// Some error occured, just return the original string
			$newstring = $str;
		}
		return $newstring;
	}


	/**
	 * Check if the deprecated hit_doublecheck_referer setting is set and then
	 * do not disable the AppendHitLog event. Also removes the old setting.
	 */
	function AfterInstall()
	{
		global $Settings;

		if( $Settings->get('hit_doublecheck_referer') )
		{ // old general settings, "transform it"
			$this->Settings->set( 'check_url_referers', '1' );
			$this->Settings->dbupdate();
		}

		$Settings->delete('hit_doublecheck_referer');
		$Settings->dbupdate();
	}


	/**
	 * Check if our Host+URI is in the referred page, preferrably through
	 * {@link register_shutdown_function()}.
	 *
	 * @return boolean true, if we handle {@link Hit::record_the_hit() recording of the Hit} ourself
	 */
	function AppendHitLog( & $params )
	{
		$Hit = & $params['Hit'];

		if( $Hit->referer_type != 'referer' )
		{
			return false;
		}

		if( function_exists( 'register_shutdown_function' ) )
		{ // register it as a shutdown function, because it will be slow!
			$this->debug_log( 'AppendHitLog: loading referering page.. (through register_shutdown_function())' );

			register_shutdown_function( array( &$this, 'double_check_referer' ), $Hit->referer ); // this will also call Hit::record_the_hit()
		}
		else
		{
			// flush now, so that the meat of the page will get shown before it tries to check back against the refering URL.
			evo_flush();

			$this->debug_log( 'AppendHitLog: loading referering page..' );

			$this->double_check_referer($Hit->referer); // this will also call Hit::record_the_hit()
		}

		return true; // we handle recording
	}


	/**
	 * This function gets called (as a {@link register_shutdown_function() shutdown function}, if possible) and checks
	 * if the referering URL's content includes the current URL - if not it is probably spam!
	 *
	 * On success, this methods records the hit.
	 *
	 * @uses Hit::record_the_hit()
	 */
	function double_check_referer( $referer )
	{
		global $Hit, $ReqURI;

		if( $this->is_referer_linking_us( $referer, $ReqURI ) )
		{
			$Hit->record_the_hit();
		}

		return;
	}


	/**
	 * Check the content of a given URL (referer), if the requested URI (with different hostname variations)
	 * is present.
	 *
	 * @todo Use DB cache to avoid checking the same page again and again! (Plugin DB table)
	 *
	 * @param string
	 * @param string URI to append to matching pattern for hostnames
	 * @return boolean
	 */
	function is_referer_linking_us( $referer, $uri )
	{
		global $misc_inc_path, $lib_subdir, $ReqHost;

		if( empty($referer) )
		{
			return false;
		}

		// Load page content (max. 500kb), using fsockopen:
		$url_parsed = @parse_url($referer);
		if( ! $url_parsed )
		{
			return false;
		}
		if( empty($url_parsed['scheme']) ) {
			$url_parsed = parse_url('http://'.$referer);
		}

		$host = $url_parsed['host'];
		$port = ( empty($url_parsed['port']) ? 80 : $url_parsed['port'] );
		$path = empty($url_parsed['path']) ? '/' : $url_parsed['path'];
		if( ! empty($url_parsed['query']) )
		{
			$path .= '?'.$url_parsed['query'];
		}

		$fp = @fsockopen($host, $port, $errno, $errstr, 30);
		if( ! $fp )
		{ // could not access referring page
			$this->debug_log( 'is_referer_linking_us(): could not access &laquo;'.$referer.'&raquo; (host: '.$host.'): '.$errstr.' (#'.$errno.')' );
			return false;
		}

		// Set timeout for data:
		if( function_exists('stream_set_timeout') )
			stream_set_timeout( $fp, 20 ); // PHP 4.3.0
		else
			socket_set_timeout( $fp, 20 ); // PHP 4

		// Send request:
		$out = "GET $path HTTP/1.0\r\n";
		$out .= "Host: $host:$port\r\n";
		$out .= "Connection: Close\r\n\r\n";
		fwrite($fp, $out);

		// Skip headers:
		$i = 0;
		$source_charset = 'iso-8859-1'; // default
		while( ($s = fgets($fp, 4096)) !== false )
		{
			$i++;
			if( $s == "\r\n" || $i > 100 /* max 100 head lines */ )
			{
				break;
			}
			if( preg_match('~^Content-Type:.*?charset=([\w-]+)~i', $s, $match ) )
			{
				$source_charset = $match[1];
			}
		}

		// Get the refering page's content
		$content_ref_page = '';
		$bytes_read = 0;
		while( ($s = fgets($fp, 4096)) !== false )
		{
			$content_ref_page .= $s;
			$bytes_read += strlen($s);
			if( $bytes_read > 512000 )
			{ // do not pull more than 500kb of data!
				break;
			}
		}
		fclose($fp);

		if( ! strlen($content_ref_page) )
		{
			$this->debug_log( 'is_referer_linking_us(): empty $content_ref_page ('.bytesreadable($bytes_read).' read)' );
			return false;
		}


		$have_idn_name = false;

		// Build the search pattern:
		// We match for basically for 'href="[SERVER][URI]', where [SERVER] is a list of possible hosts (especially IDNA)
		$search_pattern = '~\shref=["\']?https?://(';
		$possible_hosts = array( $_SERVER['HTTP_HOST'] );
		if( $_SERVER['SERVER_NAME'] != $_SERVER['HTTP_HOST'] )
		{
			$possible_hosts[] = $_SERVER['SERVER_NAME'];
		}
		$search_pattern_hosts = array();
		foreach( $possible_hosts as $l_host )
		{
			if( preg_match( '~^([^.]+\.)(.*?)([^.]+\.[^.]+)$~', $l_host, $match ) )
			{ // we have subdomains in this hostname
				if( stristr( $match[1], 'www' ) )
				{ // search also for hostname without 'www.'
					$search_pattern_hosts[] = $match[2].$match[3];
				}
			}
			$search_pattern_hosts[] = $l_host;
		}
		$search_pattern_hosts = array_unique($search_pattern_hosts);
		foreach( $search_pattern_hosts as $l_host )
		{ // add IDN, because this could be linked:
			$l_idn_host = idna_decode( $l_host ); // the decoded puny-code ("xn--..") name (utf8)

			if( $l_idn_host != $l_host )
			{
				$have_idn_name = true;
				$search_pattern_hosts[] = $l_idn_host;
			}
		}

		// add hosts to pattern, preg_quoted
		for( $i = 0, $n = count($search_pattern_hosts); $i < $n; $i++ )
		{
			$search_pattern_hosts[$i] = preg_quote( $search_pattern_hosts[$i], '~' );
		}
		$search_pattern .= implode( '|', $search_pattern_hosts ).')';
		if( empty($uri) )
		{ // host(s) should end with "/", "'", '"', "?" or whitespace
			$search_pattern .= '[/"\'\s?]';
		}
		else
		{
			$search_pattern .= preg_quote($uri, '~');
			// URI should end with "'", '"' or whitespace
			$search_pattern .= '["\'\s]';
		}
		$search_pattern .= '~i';

		if( $have_idn_name )
		{ // Convert charset to UTF-8, because the decoded domain name is UTF-8, too:
			if( can_convert_charsets( 'utf-8', $source_charset ) )
			{
				$content_ref_page = convert_charset( $content_ref_page, 'utf-8', $source_charset );
			}
			else
			{
				$this->debug_log( 'is_referer_linking_us(): warning: cannot convert charset of referring page' );
			}
		}

		if( preg_match( $search_pattern, $content_ref_page ) )
		{
			$this->debug_log( 'is_referer_linking_us(): found current URL in page ('.bytesreadable($bytes_read).' read)' );

			return true;
		}
		else
		{
			if( strpos( $referer, $ReqHost ) === 0 && ! empty($uri) )
			{ // Referer is the same host.. just search for $uri
				if( strpos( $content_ref_page, $uri ) !== false )
				{
					$this->debug_log( 'is_referer_linking_us(): found current URI in page ('.bytesreadable($bytes_read).' read)' );

					return true;
				}
			}
			$this->debug_log( 'is_referer_linking_us(): '.sprintf('did not find &laquo;%s&raquo; in &laquo;%s&raquo; (%s bytes read).', $search_pattern, $referer, bytesreadable($bytes_read) ) );

			return false;
		}
	}


	/**
	 * Simple check for duplicate comment/content from same author
	 *
	 * @param Comment
	 */
	function is_duplicate_comment( $Comment )
	{
		global $DB;

		if( ! $this->Settings->get('check_dupes') )
		{
			return false;
		}

		if( $Comment->content == '' )
		{ // User may has many comments with empty content but with attachment pictures
			return false;
		}

		$sql = '
				SELECT comment_ID
				  FROM T_comments
				 WHERE comment_item_ID = '.$Comment->item_ID;

		if( isset($Comment->author_user_ID) )
		{ // registered user:
			$sql .= ' AND comment_author_user_ID = '.$Comment->author_user_ID;
		}
		else
		{ // visitor (also trackback):
			$sql_ors = array();
			if( ! empty($Comment->author) )
			{
				$sql_ors[] = 'comment_author = '.$DB->quote($Comment->author);
			}
			if( ! empty($Comment->author_email) )
			{
				$sql_ors[] = 'comment_author_email = '.$DB->quote( $Comment->author_email );
			}
			if( ! empty($Comment->author_url) )
			{
				$sql_ors[] = 'comment_author_url = '.$DB->quote($Comment->author_url);
			}

			if( ! empty($sql_ors) )
			{
				$sql .= ' AND ( '.implode( ' OR ', $sql_ors ).' )';
			}
		}

		$sql .= ' AND comment_content = '.$DB->quote($Comment->content).' LIMIT 1';

		return $DB->get_var( $sql, 0, 0, 'Checking for duplicate feedback content.' );
	}


	/**
	 * A little housekeeping.
	 * @return true
	 */
	function PluginVersionChanged( & $params )
	{
		$this->Settings->delete('check_url_trackbacks');
		$this->Settings->dbupdate();
		return true;
	}

}

?>