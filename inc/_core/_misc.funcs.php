<?php
/**
 * This file implements general purpose functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Dependencies
 */
load_funcs('antispam/model/_antispam.funcs.php');
load_funcs('tools/model/_email.funcs.php');

// @todo sam2kb> Move core functions get_admin_skins, get_filenames, cleardir_r, rmdir_r and some other
// to a separate file, and split files_Module from _core_Module
load_funcs('files/model/_file.funcs.php');

// Load utf8 support functions
load_funcs( '_ext/_portable_utf8.php' );


/**
 * Call a method for all modules in a row
 *
 * @param string the name of the method which should be called
 * @param array params
 * @return array[module_name][return value], or NULL if the method doesn't have any return value
 */
function modules_call_method( $method_name, $params = NULL )
{
	global $modules;

	$result = NULL;

	foreach( $modules as $module )
	{
		$Module = & $GLOBALS[$module.'_Module'];
		if( $params == NULL )
		{
			$ret = $Module->{$method_name}();
		}
		else
		{
			$ret = $Module->{$method_name}( $params );
		}
		if( isset( $ret ) )
		{
			$result[$module] = $ret;
		}
	}

	return $result;
}


/**
 * Loads the b2evo database scheme.
 *
 * This gets updated through {@link db_delta()} which generates the queries needed to get
 * to this scheme.
 *
 * @param boolean set true to load installed plugins table as well, leave it on false otherwise
 *        - currently used only on table normalization
 *
 * Please see {@link db_delta()} for things to take care of.
 */
function load_db_schema( $inlcude_plugins = false )
{
	global $schema_queries;
	global $modules, $inc_path;
	global $db_storage_charset, $DB;

	if( empty( $db_storage_charset ) )
	{ // If no specific charset has been requested for datstorage, use the one of the current connection (optimize for speed - no conversions)
		$db_storage_charset = $DB->connection_charset;
	}

	// Load modules:
	foreach( $modules as $module )
	{
		echo get_install_format_text( 'Loading module: <code>'.$module.'/model/_'.$module.'.install.php</code><br />', 'br' );
		require_once $inc_path.$module.'/model/_'.$module.'.install.php';
	}

	if( $inlcude_plugins )
	{ // Load all plugins table into the schema queries
		global $Plugins;

		if( empty( $Plugins ) )
		{
			load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
			$Plugins = new Plugins();
		}

		$admin_Plugins = & get_Plugins_admin();
		$admin_Plugins->restart();
		while( $loop_Plugin = & $admin_Plugins->get_next() )
		{ // loop through all installed plugins
			$create_table_queries = $loop_Plugin->GetDbLayout();
			foreach( $create_table_queries as $create_table_query )
			{
				if( ! preg_match( '|^\s*CREATE TABLE\s+(IF NOT EXISTS\s+)?([^\s(]+).*$|is', $create_table_query, $match) )
				{ // Could not parse the CREATE TABLE command
					continue;
				}
				$schema_queries[$match[2]] = array( 'Creating table for plugin', $create_table_query );
				$DB->dbaliases[] = '#\b'.$match[2].'\b#';
				$DB->dbreplaces[] = $match[2];
			}
		}
	}
}


/**
 * @deprecated kept only for plugin backward compatibility (core is being modified to call getters directly)
 * To be removed, maybe in b2evo v5.
 *
 * @return DataObjectCache
 */
function & get_Cache( $objectName )
{
	global $Plugins;
	global $$objectName;

	if( isset( $$objectName ) )
	{	// Cache already exists:
		return $$objectName;
	}

	$func_name = 'get_'.$objectName;

	if( function_exists($func_name) )
	{
		return $func_name();
	}
	else
	{
		debug_die( 'getCache(): Unknown Cache type get function:'.$func_name.'()' );
	}
}


/**
 * Load functions file
 */
function load_funcs( $funcs_path )
{
	global $inc_path;
	require_once $inc_path.$funcs_path;
}


/**
 * Shutdown function: save HIT and update session!
 *
 * This is registered in _main.inc.php with register_shutdown_function()
 * This is called by PHP at the end of the script.
 *
 * NOTE: before PHP 4.1 nothing can be echoed here any more, but the minimum PHP requirement for b2evo is PHP 4.3
 */
function shutdown()
{
	/**
	 * @var Hit
	 */
	global $Hit;

	/**
	 * @var Session
	 */
	global $Session;

	global $Settings;
	global $Debuglog;

	global $Timer;

	// Try forking a background process and let the parent return as fast as possbile.
	if( is_callable('pcntl_fork') && function_exists('posix_kill') && defined('STDIN') )
	{
		if( $pid = pcntl_fork() )
			return; // Parent

		function shutdown_kill()
		{
			posix_kill(posix_getpid(), SIGHUP);
		}

		if ( ob_get_level() )
		{	// Discard the output buffer and close
			ob_end_clean();
		}

		fclose(STDIN);  // Close all of the standard
		fclose(STDOUT); // file descriptors as we
		fclose(STDERR); // are running as a daemon.

		register_shutdown_function('shutdown_kill');

		if( posix_setsid() < 0 )
			return;

		if( $pid = pcntl_fork() )
			return;     // Parent

		// Now running as a daemon. This process will even survive
		// an apachectl stop.
	}

	$Timer->resume('shutdown');

	// echo '*** SHUTDOWN FUNC KICKING IN ***';

	// fp> do we need special processing if we are in CLI mode?  probably earlier actually
	// if( ! $is_cli )

	// Note: it might be useful at some point to do special processing if the script has been aborted or has timed out
	// connection_aborted()
	// connection_status()

	// Save the current HIT, but set delayed since the hit ID will not be required here:
	$Hit->log( true );

	// Update the SESSION:
	$Session->dbsave();

	// Get updates here instead of slowing down normal display of the dashboard
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );
	b2evonet_get_updates();

	// Auto pruning of old HITS, old SESSIONS and potentially MORE analytics data:
	if( $Settings->get( 'auto_prune_stats_mode' ) == 'page' )
	{ // Autopruning is requested
		load_class( 'sessions/model/_hitlist.class.php', 'Hitlist' );
		Hitlist::dbprune(); // will prune once per day, according to Settings
	}

	// Calling debug_info() here will produce complete data but it will be after </html> hence invalid.
	// Then again, it's for debug only, so it shouldn't matter that much.
	debug_info();

	// Update the SESSION again, at the very end:
	// (e.g. "Debuglogs" may have been removed in debug_info())
	$Session->dbsave();

	$Timer->pause('shutdown');
}


/***** Formatting functions *****/

/**
 * Format a string/content for being output
 *
 * @author fplanque
 * @todo htmlspecialchars() takes a charset argument, which we could provide ($evo_charset?)
 * @param string raw text
 * @param string format, can be one of the following
 * - raw: do nothing
 * - htmlbody: display in HTML page body: allow full HTML
 * - entityencoded: Special mode for RSS 0.92: allow full HTML but escape it
 * - htmlhead: strips out HTML (mainly for use in Title)
 * - htmlattr: use as an attribute: escapes quotes, strip tags
 * - formvalue: use as a form value: escapes quotes and < > but leaves code alone
 * - text: use as plain-text, e.g. for ascii-mails
 * - xml: use in an XML file: strip HTML tags
 * - xmlattr: use as an attribute: strips tags and escapes quotes
 * @return string formatted text
 */
function format_to_output( $content, $format = 'htmlbody' )
{
	global $Plugins, $evo_charset;

	switch( $format )
	{
		case 'raw':
			// do nothing!
			break;

		case 'htmlbody':
			// display in HTML page body: allow full HTML
			$content = convert_chars($content, 'html');
			break;

		case 'urlencoded':
			// Encode string to be passed as part of an URL
			$content = rawurlencode( $content );
			break;

		case 'entityencoded':
			// Special mode for RSS 0.92: apply renders and allow full HTML but escape it
			$content = convert_chars($content, 'html');
			$content = htmlspecialchars( $content, ENT_QUOTES, $evo_charset );
			break;

		case 'htmlfeed':
			// For use in RSS <content:encoded><![CDATA[ ... ]]></content:encoded>
			// allow full HTML + absolute URLs...
			$content = make_rel_links_abs($content);
			$content = convert_chars($content, 'html');
			$content = str_replace(']]>', ']]&gt;', $content); // encode CDATA closing tag to prevent injection/breaking of the <![CDATA[ ... ]]>
			break;

		case 'htmlhead':
			// Strips out HTML (mainly for use in Title)
			$content = strip_tags($content);
			$content = convert_chars($content, 'html');
			break;

		case 'htmlattr':
			// use as an attribute: strips tags and escapes quotes
			// TODO: dh> why not just htmlspecialchars?fp> because an attribute can never contain a tag? dh> well, "onclick='return 1<2;'" would get stripped, too. I'm just saying: why mess with it, when we can just use htmlspecialchars.. fp>ok
			$content = strip_tags($content);
			$content = convert_chars($content, 'html');
			$content = str_replace( array('"', "'"), array('&quot;', '&#039;'), $content );
			break;

		case 'htmlspecialchars':
		case 'formvalue':
			// Replace special chars to &amp;, &quot;, &#039;|&apos;, &lt; and &gt; :
			if( version_compare( phpversion(), '5.4', '>=' ) )
			{	// Handles & " ' < > to &amp; &quot; &apos; &lt; &gt;
				$content = htmlspecialchars( $content, ENT_QUOTES | ENT_HTML5, $evo_charset );
			}
			else
			{	// Handles & " ' < > to &amp; &quot; &#039; &lt; &gt;
				$content = htmlspecialchars( $content, ENT_QUOTES, $evo_charset );
			}
			break;

		case 'xml':
			// use in an XML file: strip HTML tags
			$content = strip_tags($content);
			$content = convert_chars($content, 'xml');
			break;

		case 'xmlattr':
			// use as an attribute: strips tags and escapes quotes
			$content = strip_tags($content);
			$content = convert_chars($content, 'xml');
			$content = str_replace( array('"', "'"), array('&quot;', '&#039;'), $content );
			break;

		case 'text':
			// use as plain-text, e.g. for ascii-mails
			$content = strip_tags( $content );
			$trans_tbl = get_html_translation_table( HTML_ENTITIES );
			$trans_tbl = array_flip( $trans_tbl );
			$content = strtr( $content, $trans_tbl );
			$content = preg_replace( '/[ \t]+/', ' ', $content);
			$content = trim($content);
			break;

		case 'syslog':
			// Replace special chars to &amp;, &quot;, &#039;|&apos;, &lt; and &gt; :
			if( version_compare( phpversion(), '5.4', '>=' ) )
			{	// Handles & " ' < > to &amp; &quot; &apos; &lt; &gt;
				$content = htmlspecialchars( $content, ENT_QUOTES | ENT_HTML5, $evo_charset );
			}
			else
			{	// Handles & " ' < > to &amp; &quot; &#039; &lt; &gt;
				$content = htmlspecialchars( $content, ENT_QUOTES, $evo_charset );
			}
			$content = preg_replace( "/\[\[(.+?)]]/is", "<code>$1</code>", $content ); // Replaces [[...]] into <code>...</code>
			break;

		default:
			debug_die( 'Output format ['.$format.'] not supported.' );
	}

	return $content;
}


/*
 * autobrize(-)
 */
function autobrize($content) {
	$content = callback_on_non_matching_blocks( $content, '~<code>.+?</code>~is', 'autobrize_callback' );
	return $content;
}

/**
 * Adds <br>'s to non code blocks
 *
 * @param string $content
 * @return string content with <br>'s added
 */
function autobrize_callback( $content )
{
	$content = preg_replace("/<br>\n/", "\n", $content);
	$content = preg_replace("/<br \/>\n/", "\n", $content);
	$content = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />\n", $content);
	return($content);
}

/*
 * unautobrize(-)
 */
function unautobrize($content)
{
	$content = callback_on_non_matching_blocks( $content, '~<code>.+?</code>~is', 'unautobrize_callback' );
	return $content;
}

/**
 * Removes <br>'s from non code blocks
 *
 * @param string $content
 * @return string content with <br>'s removed
 */
function unautobrize_callback( $content )
{
	$content = preg_replace("/<br>\n/", "\n", $content);   //for PHP versions before 4.0.5
	$content = preg_replace("/<br \/>\n/", "\n", $content);
	return($content);
}

/**
 * Add leading zeroes to a number when necessary.
 *
 * @param string The original number.
 * @param integer How many digits shall the number have?
 * @return string The padded number.
 */
function zeroise( $number, $threshold )
{
	return str_pad( $number, $threshold, '0', STR_PAD_LEFT );
}


/**
 * Get a limited text-only excerpt
 *
 * @param string
 * @param int Maximum length
 * @return string
 */
function excerpt( $str, $maxlen = 254, $tail = '&hellip;' )
{
	// Add spaces
	$str = str_replace( array( '<p>', '<br' ), array( ' <p>', ' <br' ), $str );

	// Remove <code>
	$str = preg_replace( '#<code>(.+)</code>#is', '', $str );

	// Strip tags:
	$str = strip_tags( $str );

	// Remove spaces:
	$str = preg_replace( '/[ \t]+/', ' ', $str);
	$str = trim( $str );

	// Ger rid of all new lines and Display the html tags as source text:
	$str = trim( preg_replace( '#[\r\n\t\s]+#', ' ', $str ) );

	$str = strmaxlen( $str, $maxlen, $tail, 'raw', true );

	return $str;
}


/**
 * Get a limited text-only excerpt based on number of words
 *
 * @param string
 * @param int Maximum length
 * @return string
 */
function excerpt_words( $str, $maxwords = 50 )
{
	// Add spaces
	$str = str_replace( array( '<p>', '<br' ), array( ' <p>', ' <br' ), $str );

	// Remove <code>
	$str = preg_replace( '#<code>(.+)</code>#is', '', $str );

	// Strip tags:
	$str = strip_tags( $str );

	// Remove spaces:
	$str = preg_replace( '/[ \t]+/', ' ', $str);
	$str = trim( $str );

	// Ger rid of all new lines and Display the html tags as source text:
	$str = trim( preg_replace( '#[\r\n\t\s]+#', ' ', $str ) );

	$str = strmaxwords( $str, $maxwords );

	return $str;
}


/**
 * Crop string to maxlen with &hellip; (default tail) at the end if needed.
 *
 * If $format is not "raw", we make sure to not cut in the middle of an
 * HTML entity, so that strmaxlen('1&amp;2', 3, NULL, 'formvalue') will not
 * become/stay '1&amp;&hellip;'.
 *
 * @param string
 * @param int Maximum length
 * @param string Tail to use, when string gets cropped. Its length gets
 *               substracted from the total length (with HTML entities
 *               being decoded). Default is "&hellip;" (HTML entity)
 * @param string Format, see {@link format_to_output()}
 * @param boolean Crop at whitespace, if possible?
 *        (any word split at the end will get its head removed)
 * @return string
 */
function strmaxlen( $str, $maxlen = 50, $tail = NULL, $format = 'raw', $cut_at_whitespace = false  )
{
	if( is_null($tail) )
	{
		$tail = '&hellip;';
	}

	$str = utf8_rtrim($str);

	if( utf8_strlen( $str ) > $maxlen )
	{
		// Replace all HTML entities by a single char. html_entity_decode for example
		// would not handle &hellip;.
		$tail_for_length = preg_replace('~&\w+?;~', '.', $tail);
		$tail_length = utf8_strlen( html_entity_decode($tail_for_length) );
		$len = $maxlen-$tail_length;
		if( $len < 1 )
		{ // special case; $tail length is >= $maxlen
			$len = 0;
		}
		$str_cropped = utf8_substr( $str, 0, $len );
		if( $format != 'raw' )
		{ // if the format isn't raw we make sure that we do not cut in the middle of an HTML entity
			$maxlen_entity = 7; # "&amp;" is 5, min 3!
			$str_inspect = utf8_substr($str_cropped, 1-$maxlen_entity);
			$pos_amp = utf8_strpos($str_inspect, '&');
			if( $pos_amp !== false )
			{ // there's an ampersand at the end of the cropped string
				$look_until = $pos_amp;
				$str_cropped_len = utf8_strlen($str_cropped);
				if( $str_cropped_len < $maxlen_entity )
				{ // we have to look at least for the length of an entity
					$look_until += $maxlen_entity-$str_cropped_len;
				}
				if( strpos(utf8_substr($str, $len, $look_until), ';') !== false )
				{
					$str_cropped = utf8_substr( $str, 0, $len-utf8_strlen($str_inspect)+$pos_amp);
				}
			}
		}

		if( $cut_at_whitespace )
		{
			// Get the first character being cut off. Note: we can't use $str[index] in case of utf8 strings!
			$first_cut_off_char = utf8_substr( $str, utf8_strlen( $str_cropped ), 1 );
			if( ! ctype_space( $first_cut_off_char ) )
			{ // first character being cut off is not whitespace
				// Get the chars as an array from the cropped string to be able to get chars by position
				$str_cropped_chars = preg_split('//u',$str_cropped, -1, PREG_SPLIT_NO_EMPTY);
				$i = utf8_strlen($str_cropped);
				while( $i && isset( $str_cropped_chars[ --$i ] ) && ! ctype_space( $str_cropped_chars[ $i ] ) )
				{}
				if( $i )
				{
					$str_cropped = utf8_substr($str_cropped, 0, $i);
				}
			}
		}

		$str = format_to_output(utf8_rtrim($str_cropped), $format);
		$str .= $tail;

		return $str;
	}
	else
	{
		return format_to_output($str, $format);
	}
}


/**
 * Crop string to maxwords preserving tags.
 *
 * @param string
 * @param int Maximum number words
 * @param mixed array Optional parameters
 * @return string
 */
function strmaxwords( $str, $maxwords = 50, $params = array() )
{
	$params = array_merge( array(
			'cutting_mark'    => '&hellip;',
			'continued_link'  => '',
			'continued_text'  => '&hellip;',
			'continued_class' => '',
			'always_continue' => false,
		), $params );

	// STATE MACHINE:
	$in_tag = false;
	$have_seen_non_whitespace = false;
	$end = strlen( $str );  // If we use utf8_strlen here(), we also need to access UTF chars below
	for( $i = 0; $i < $end; $i++ )
	{
		switch( $char = $str[$i] )		// This is NOT UTF-8
		{
			case '<' :	// start of a tag
				$in_tag = true;
				break;

			case '>' : // end of a tag
				$in_tag = false;
				break;

			case ctype_space($char): // This is a whitespace char...
				if( ! $in_tag )
				{	// it's a word gap:
					// Eat any additional whitespace:
					while( isset($str[$i+1]) && ctype_space($str[$i+1]) )
					{
						$i++;
					}
					if( isset($str[$i+1]) && $have_seen_non_whitespace )
					{ // only decrement words, if there's been at least one non-space char before.
						--$maxwords;
					}
				}
				// ignore white space in a tag...
				break;

			default:
				$have_seen_non_whitespace = true;
				break;
		}

		if( $maxwords < 1 )
		{	// We have reached the cutting point:
			break;
		} 
	}

	if( $maxwords < 1 )
	{	// Cutting is necessary:
		// restrict content to required number of words:
		$str = utf8_substr( $str, 0, $i );

		// Add a cutting mark:
		$str .= $params['cutting_mark'];

		// balance the tags out:
		$str = balance_tags( $str );
		// remove empty tags:
		$str = preg_replace( '~<([\s]+?)[^>]*?></\1>~is', '', $str );
	}

	if( $params['always_continue'] || $maxwords < 1 )
	{ // we want a continued text
		$str .= ' <a href="'.$params['continued_link'].'" class="'.$params['continued_class'].'">'.$params['continued_text'].'</a>';
	}

	return $str;
}


/**
 * Convert all non ASCII chars (except if UTF-8, GB2312 or CP1251) to &#nnnn; unicode references.
 * Also convert entities to &#nnnn; unicode references if output is not HTML (eg XML)
 *
 * Preserves < > and quotes.
 *
 * fplanque: simplified
 * sakichan: pregs instead of loop
 */
function convert_chars( $content, $flag = 'html' )
{
	global $b2_htmltrans, $evo_charset;

	/**
	 * Translation of invalid Unicode references range to valid range.
	 * These are Windows CP1252 specific characters.
	 * They would look weird on non-Windows browsers.
	 * If you've ever pasted text from MSWord, you'll understand.
	 *
	 * You should not have to change this.
	 */
	static $b2_htmltranswinuni = array(
		'&#128;' => '&#8364;', // the Euro sign
		'&#130;' => '&#8218;',
		'&#131;' => '&#402;',
		'&#132;' => '&#8222;',
		'&#133;' => '&#8230;',
		'&#134;' => '&#8224;',
		'&#135;' => '&#8225;',
		'&#136;' => '&#710;',
		'&#137;' => '&#8240;',
		'&#138;' => '&#352;',
		'&#139;' => '&#8249;',
		'&#140;' => '&#338;',
		'&#142;' => '&#382;',
		'&#145;' => '&#8216;',
		'&#146;' => '&#8217;',
		'&#147;' => '&#8220;',
		'&#148;' => '&#8221;',
		'&#149;' => '&#8226;',
		'&#150;' => '&#8211;',
		'&#151;' => '&#8212;',
		'&#152;' => '&#732;',
		'&#153;' => '&#8482;',
		'&#154;' => '&#353;',
		'&#155;' => '&#8250;',
		'&#156;' => '&#339;',
		'&#158;' => '&#382;',
		'&#159;' => '&#376;'
	);

	// Convert highbyte non ASCII/UTF-8 chars to urefs:
	if( ! in_array(strtolower($evo_charset), array( 'utf8', 'utf-8', 'gb2312', 'windows-1251') ) )
	{ // This is a single byte charset
		// fp> why do we actually bother doing this:?
		$content = preg_replace_callback(
			'/[\x80-\xff]/',
			create_function( '$j', 'return "&#".ord($j[0]).";";' ),
			$content);
	}

	// Convert Windows CP1252 => Unicode (valid HTML)
	// TODO: should this go to input conversions instead (?)
	$content = strtr( $content, $b2_htmltranswinuni );

	if( $flag == 'html' )
	{ // we can use entities
		// Convert & chars that are not used in an entity
		$content = preg_replace('/&(?![#A-Za-z0-9]{2,20};)/', '&amp;', $content);
	}
	else
	{ // unicode, xml...
		// Convert & chars that are not used in an entity
		$content = preg_replace('/&(?![#A-Za-z0-9]{2,20};)/', '&#38;', $content);

		// Convert HTML entities to urefs:
		$content = strtr($content, $b2_htmltrans);
	}

	return( $content );
}


/**
 * Get number of bytes in $string. This works around mbstring.func_overload, if
 * activated for strlen/mb_strlen.
 * @param string
 * @return int
 */
function evo_bytes( $string )
{
	$fo = ini_get('mbstring.func_overload');
	if( $fo && $fo & 2 && function_exists('mb_strlen') )
	{ // overloading of strlen is enabled
		return mb_strlen( $string, 'ASCII' );
	}
	return strlen($string);
}


/**
 * mbstring wrapper for strtolower function
 *
 * @deprecated by {@link utf8_strtolower()}
 *
 * fp> TODO: instead of those "when used" ifs, it would make more sense to redefine
 * mb_strtolower beforehand if it doesn"t exist (it would then just be a fallback
 * to the strtolower + a Debuglog->add() )
 *
 * @param string
 * @return string
 */
function evo_strtolower( $string )
{
	global $current_charset;

	if( $current_charset != 'iso-8859-1' && $current_charset != '' && function_exists('mb_strtolower') )
	{
		return mb_strtolower( $string, $current_charset );
	}

	return strtolower($string);
}


/**
 * mbstring wrapper for strlen function
 *
 * @deprecated by {@link utf8_strlen()}
 *
 * @param string
 * @return string
 */
function evo_strlen( $string )
{
	global $current_charset;

	if( $current_charset != 'iso-8859-1' && $current_charset != '' && function_exists('mb_strlen') )
	{
		return mb_strlen( $string, $current_charset );
	}

	return strlen($string);
}

/**
 * mbstring wrapper for strpos function
 *
 * @deprecated by {@link utf8_strpos()}
 *
 * @param string
 * @param string
 * @return int
 */
function evo_strpos( $string , $needle , $offset = null )
{
	global $current_charset;

	if( $current_charset != 'iso-8859-1' && $current_charset != '' && function_exists('mb_strpos') )
	{
		return mb_strpos( $string, $needle, $offset ,$current_charset );
	}

	return strpos( $string , $needle , $offset );
}


/**
 * mbstring wrapper for substr function
 *
 * @deprecated by {@link utf8_substr()}
 *
 * @param string
 * @param int start position
 * @param int string length
 * @return string
 */
function evo_substr( $string, $start = 0, $length = '#' )
{
	global $current_charset;

	if( ! $length )
	{ // make mb_substr and substr behave consistently (mb_substr returns string for length=0)
		return '';
	}
	if( $length == '#' )
	{
		$length = utf8_strlen($string);
	}

	if( $current_charset != 'iso-8859-1' && $current_charset != '' && function_exists('mb_substr') )
	{
		return mb_substr( $string, $start, $length, $current_charset );
	}

	return substr( $string, $start, $length );
}


/**
 * Split $text into blocks by using $pattern and call $callback on the non-matching blocks.
 *
 * The non-matching block's text is the first param to $callback and additionally $params gets passed.
 *
 * This gets used to make links clickable or replace smilies.
 *
 * E.g., to replace only in non-HTML tags, call it like:
 * <code>callback_on_non_matching_blocks( $text, '~<[^>]*>~s', 'your_callback' );</code>
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @param string Text to handle
 * @param string Regular expression pattern that defines blocks to exclude.
 * @param callback Function name or object/method array to use as callback.
 *               Each non-matching block gets passed as first param, additional params may be
 *               passed with $params.
 * @param array Of additional ("static") params to $callback.
 * @return string
 */
function callback_on_non_matching_blocks( $text, $pattern, $callback, $params = array() )
{
	if( preg_match_all( $pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER ) )
	{	// $pattern matches, call the callback method on full text except of matching blocks

		// Create an unique string in order to replace all matching blocks temporarily
		$unique_replacement = md5( time() + rand() );

		$matches_search = array();
		$matches_replace = array();
		foreach( $matches[0] as $l => $l_matching )
		{	// Build arrays with a source code of the matching blocks and with temporary replacement
			$matches_source[] = $l_matching[0];
			$matches_temp[] = '?'.$l.$unique_replacement.$l.'?';
		}

		// Replace all matching blocks with temporary text like '?X219a33da9c1b8f4e335bffc015df8c96X?'
		// where X is index of match block in array $matches[0]
		// It is used to avoid any changes in the matching blocks
		$text = str_ireplace( $matches_source, $matches_temp, $text );

		// Callback:
		$callback_params = $params;
		array_unshift( $callback_params, $text );
		$text = call_user_func_array( $callback, $callback_params );

		// Revert a source code of the matching blocks in content
		$text = str_ireplace( $matches_temp, $matches_source, $text );

		return $text;
	}

	$callback_params = $params;
	array_unshift( $callback_params, $text );
	return call_user_func_array( $callback, $callback_params );
}


/**
 * Perform a global regular expression match outside blocks <code></code>, <pre></pre> and markdown codeblocks
 *
 * @param string Pattern to search for
 * @param string Content
 * @param array Array of all matches in multi-dimensional array
 * @return integer|boolean Number of full pattern matches (which might be zero), or FALSE if an error occurred.
 */
function preg_match_outcode( $search, $content, & $matches )
{
	if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false || strstr( $content, '`' ) !== false )
	{	// Call preg_match_all() on everything outside code/pre and markdown codeblocks:
		return callback_on_non_matching_blocks( $content,
			'~(`|<(code|pre)[^>]*>).*?(\1|</\2>)~is',
			'preg_match_outcode_callback', array( $search, & $matches ) );
	}
	else
	{	// No code/pre blocks, search in the whole thing:
		return preg_match_all( $search, $content, $matches );
	}
}


/**
 * Used for function callback_on_non_matching_blocks(), because there is different order of params
 *
 * @param string Pattern to search for
 * @param string Content
 * @param array Array of all matches in multi-dimensional array
 * @return integer|boolean Number of full pattern matches (which might be zero), or FALSE if an error occurred.
 */
function preg_match_outcode_callback( $content, $search, & $matches )
{
	return preg_match_all( $search, $content, $matches );
}


/**
 * Replace content outside blocks <code></code>, <pre></pre> and markdown codeblocks
 *
 * @param array|string Search list
 * @param array|string Replace list or Callback function
 * @param string Source content
 * @param string Callback function name
 * @param string Type of callback function: 'preg' -> preg_replace(), 'str' -> str_replace() (@see replace_content())
 * @return string Replaced content
 */
function replace_content_outcode( $search, $replace, $content, $replace_function_callback = 'replace_content', $replace_function_type = 'preg' )
{
	if( !empty( $search ) )
	{
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false || strstr( $content, '`' ) !== false )
		{ // Call replace_content() on everything outside code/pre and markdown codeblocks:
			$content = callback_on_non_matching_blocks( $content,
				'~(`|<(code|pre)[^>]*>).*?(\1|</\2>)~is',
				$replace_function_callback, array( $search, $replace, $replace_function_type ) );
		}
		else
		{ // No code/pre blocks, replace on the whole thing
			$content = call_user_func( $replace_function_callback, $content, $search, $replace, $replace_function_type );
		}
	}

	return $content;
}


/**
 * Replace content, Used for function callback_on_non_matching_blocks(), because there is different order of params
 *
 * @param string Source content
 * @param array|string Search list
 * @param array|string Replace list
 * @param string Type of function: 'preg' -> preg_replace(), 'str' -> str_replace()
 * @param string The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
 * @return string Replaced content
 */
function replace_content( $content, $search, $replace, $type = 'preg', $limit = -1 )
{
	if( $limit == 0 )
	{	// Strange request to nothing replace, Return original content:
		return $content;
	}

	switch( $type )
	{
		case 'str':
			if( $limit == -1 )
			{	// Unlimited replace:
				return str_replace( $search, $replace, $content );
			}
			else
			{	// Limited replace:
				$pos = strpos( $content, $search );
				if( $pos !== false )
				{	// Do the limited replacements:
					for( $p = 0; $p < $limit; $p++ )
					{
						if( $pos === false )
						{	// Stop searching:
							break;
						}
						$content = substr_replace( $content, $replace, $pos, strlen( $search ) );
						// Go to next searched substring:
						$pos = strpos( $content, $search, $pos + strlen( $replace ) );
					}
				}
				return $content;
			}

		default: // 'preg'
			return preg_replace( $search, $replace, $content, $limit );
	}
}


/**
 * Replace content by callback, Used for function callback_on_non_matching_blocks(), because there is different order of params
 *
 * @param string Source content
 * @param array|string Search list
 * @param array|string Replace callback
 * @return string Replaced content
 */
function replace_content_callback( $content, $search, $replace_callback )
{
	return preg_replace_callback( $search, $replace_callback, $content );
}


/**
 * Split a content by separators outside <code> and <pre> blocks
 *
 * @param string|array Separators
 * @param string Content
 * @param boolean TRUE - parenthesized expression of separator will be captured and returned as well
 * @return array The result of explode() function
 */
function split_outcode( $separators, $content, $capture_separator = false )
{
	// Check if the separators exists in content
	if( ! is_array( $separators ) )
	{ // Convert string to array with one element
		$separators = array( $separators );
	}
	$separators_exists = false;
	if( is_array( $separators ) )
	{ // Find in array
		foreach( $separators as $separator )
		{
			if( strpos( $content, $separator ) !== false )
			{ // Separator is found
				$separators_exists = true;
				break;
			}
		}
	}

	if( $separators_exists )
	{ // There are separators in content, Split the content:

		// Initialize temp values for replace the separators
		if( $capture_separator )
		{
			$rplc_separators = array();
			foreach( $separators as $s => $separator )
			{
				$rplc_separators[] = '#separator'.$s.'='.md5( rand() ).'#';
			}
		}
		else
		{
			$rplc_separators = '#separator='.md5( rand() ).'#';
		}
		// Replace the content separators with temp value
		if( strpos( $content, '<code' ) !== false || strpos( $content, '<pre' ) !== false )
		{ // Call replace_separators_callback() on everything outside code/pre:
			$content = callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]*>.*?</\1>~is',
				'replace_content', array( $separators, $rplc_separators, 'str' ) );
		}
		else
		{ // No code/pre blocks, replace on the whole thing
			$content = str_replace( $separators, $rplc_separators, $content );
		}

		if( $capture_separator )
		{ // Save the separators
			$split_regexp = '~('.implode( '|', $rplc_separators ).')~s';
			$content_parts = preg_split( $split_regexp, $content, -1, PREG_SPLIT_DELIM_CAPTURE );
			foreach( $content_parts as $c => $content_part )
			{
				if( ( $s = array_search( $content_part, $rplc_separators ) ) !== false )
				{ // Replace original separator back
					$content_parts[ $c ] = $separators[ $s ];
				}
			}
			return $content_parts;
		}
		else
		{ // Return only splitted content(without separators)
			return explode( $rplc_separators, $content );
		}
	}
	else
	{ // No separators in content, Return whole content as one element of array
		return array( $content );
	}
}


/**
 * Remove [image:] and [video:] short tags that are inside <p> blocks and move them before the paragraph
 *
 * @param string Source content
 * @param string Search pattern
 * @param function Optional callback function that accepts search pattern and current paragraph as arguments and returns the new_paragraph
 * @return string Content
 */
function move_short_tags( $content, $pattern = NULL, $callback = NULL )
{	// Move [image:], [video:] and [audio:] tags out of <p> blocks

	// Get individual paragraphs
	preg_match_all( '/<p[\s*|>].*?<\/p>/i', $content, $paragraphs );

	if( is_null( $pattern ) )
	{
		$pattern = '/\[(image|video|audio):\d+:?[^\[\]]*\]/i';
	}

	foreach( $paragraphs[0] as $i => $current_paragraph )
	{
		if( $callback )
		{
			$new_paragraph = call_user_func( $callback, $pattern, $current_paragraph );
		}
		else
		{
			// get short tags in each paragraph
			preg_match_all( $pattern, $current_paragraph, $matches );
			$new_paragraph = $current_paragraph;

			if( $matches[0] )
			{
				$new_paragraph = str_replace( $matches[0], '', $current_paragraph );

				// convert &nbsp; to space
				$x = str_replace( "\xC2\xA0", ' ', $new_paragraph );

				if( preg_match( '/<p[\s*|>]\s*<\/p>/i', $x ) === 1 )
				{ // remove paragraph the if moving out the short tag will result to an empty paragraph
					$new_paragraph = '';
				}

				$new_paragraph = implode( '', $matches[0] ).$new_paragraph;
			}
		}
		$content = str_replace( $current_paragraph, $new_paragraph, $content );
	}

	return $content;
}


/**
 * Make links clickable in a given text.
 *
 * It replaces only text which is not between <a> tags already.
 *
 * @todo dh> this should not replace links in tags! currently fails for something
 *           like '<img src=" http://example.com/" />' (not usual though!)
 * fp> I am trying to address this by not replacing anything inside tags
 * fp> This should be replaced by a clean state machine (one single variable for current state)
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @param string Text
 * @param string Url delimeter
 * @param string Callback function name
 * @param string Additional attributes for tag <a>
 * @param boolean TRUE to exclude links from header tags like h1, h2, etc.
 * @return string
 */
function make_clickable( $text, $moredelim = '&amp;', $callback = 'make_clickable_callback', $additional_attrs = '', $exclude_headers = false )
{
	$r = '';
	$inside_tag = false;
	$in_a_tag = false;
	$in_code_tag = false;
	$in_tag_quote = false;
	$in_header_tag = false;
	$from_pos = 0;
	$i = 0;
	$n = strlen($text);

	// Not using callback_on_non_matching_blocks(), because it requires
	// wellformed HTML and the implementation below should be
	// faster and less memory intensive (tested for some example content)
	while( $i < $n )
	{	// Go through each char in string... (we will fast forward from tag to tag)
		if( $inside_tag )
		{	// State: We're currently inside some tag:
			switch( $text[$i] )
			{
				case '>':
					if( $in_tag_quote )
					{ // This is in a quoted string so it doesn't really matter...
						break;
					}
					// end of tag:
					$inside_tag = false;
					$r .= substr($text, $from_pos, $i-$from_pos+1);
					$from_pos = $i+1;
					// $r .= '}';
					break;

				case '"':
				case '\'':
					// This is the beginning or the end of a quoted string:
					if( ! $in_tag_quote )
					{
						$in_tag_quote = $text[$i];
					}
					elseif( $in_tag_quote == $text[$i] )
					{
						$in_tag_quote = false;
					}
					break;
			}
		}
		elseif( $in_a_tag )
		{	// In a link but no longer inside <a>...</a> tag or any other embedded tag like <strong> or whatever
			switch( $text[$i] )
			{
				case '<':
					if( strtolower(substr($text, $i+1, 3)) == '/a>' )
					{	// Ok, this is the end tag of the link:
						// $r .= substr($text, $from_pos, $i-$from_pos+4);
						// $from_pos = $i+4;
						$i += 4;
						// pre_dump( 'END A TAG: '.substr($text, $from_pos, $i-$from_pos) );
						$r .= substr($text, $from_pos, $i-$from_pos);
						$from_pos = $i;
						$in_a_tag = false;
						$in_tag_quote = false;
						$in_header_tag = false;
					}
					break;
			}
		}
		elseif( $in_code_tag )
		{	// In a code but no longer inside <code>...</code> tag or any other embedded tag like <strong> or whatever
			switch( $text[$i] )
			{
				case '<':
					if( strtolower(substr($text, $i+1, 5)) == '/code' )
					{	// Ok, this is the end tag of the code:
						// $r .= substr($text, $from_pos, $i-$from_pos+4);
						// $from_pos = $i+4;
						$i += 7;
						// pre_dump( 'END A TAG: '.substr($text, $from_pos, $i-$from_pos) );
						$r .= substr($text, $from_pos, $i-$from_pos);
						$from_pos = $i;
						$in_code_tag = false;
						$in_tag_quote = false;
						$in_header_tag = false;
					}
					break;
			}
		}
		elseif( $in_header_tag )
		{	// In a code but no longer inside <h#>...</h#> tags or any other embedded tag like <strong> or whatever
			switch( $text[$i] )
			{
				case '<':
					if( strtolower( substr( $text, $i+1, 3 ) ) == '/'.$in_header_tag )
					{	// Ok, this is the end tag of the header:
						$i += 5;
						$r .= substr( $text, $from_pos, $i - $from_pos );
						$from_pos = $i;
						$in_code_tag = false;
						$in_tag_quote = false;
						$in_header_tag = false;
					}
					break;
			}
		}
		else
		{ // State: we're not currently in any tag:
			// Find next tag opening:
			$i = strpos($text, '<', $i);
			if( $i === false )
			{ // No more opening tags:
				break;
			}

			$inside_tag = true;
			$in_tag_quote = false;
			// s$r .= '{'.$text[$i+1];

			if( ($text[$i+1] == 'a' || $text[$i+1] == 'A') && ctype_space($text[$i+2]) )
			{ // opening "A" tag
				$in_a_tag = true;
			}

			if( ( substr( $text, $i+1, 4 ) == 'code') )
			{ // opening "code" tag
				$in_code_tag = true;
			}

			if( $exclude_headers && preg_match( '/^h[1-6]$/', substr( $text, $i+1, 2 ), $h_match ) )
			{	// opening "h1" - "h6" tags:
				$in_header_tag = $h_match[0];
			}

			// Make the text before the opening < clickable:
			$r .= call_user_func_array( $callback, array( substr( $text, $from_pos, $i-$from_pos ), $moredelim, $additional_attrs ) );
			$from_pos = $i;

			// $i += 2;
		}

		$i++;
	}

	// the remaining part:
	if( $in_a_tag )
	{ // may happen for invalid html:
		$r .= substr($text, $from_pos);
	}
	else
	{	// Make remplacements in the remaining part:
		$r .= call_user_func_array( $callback, array( substr( $text, $from_pos ), $moredelim, $additional_attrs ) );
	}

	return $r;
}


/**
 * Callback function for {@link make_clickable()}.
 *
 * original function: phpBB, extended here for AIM & ICQ
 * fplanque restricted :// to http:// and mailto://
 * Fixed to not include trailing dot and comma.
 *
 * fp> I'm thinking of moving this into the autolinks plugin (only place where it's used)
 *     and break it up into something more systematic.
 *
 * @param string Text
 * @param string Url delimeter
 * @param string Additional attributes for tag <a>
 * @return string The clickable text.
 */
function make_clickable_callback( $text, $moredelim = '&amp;', $additional_attrs = '' )
{
	if( !empty( $additional_attrs ) )
	{
		$additional_attrs = ' '.trim( $additional_attrs );
	}
	//return $text;
	/*preg_match( '/<code>([.\r\n]+?)<\/code>/i', $text, $matches );
	pre_dump( $text, $matches );*/

	$pattern_domain = '([\p{L}0-9\-]+\.[\p{L}0-9\-.\~]+)'; // a domain name (not very strict)
	$text = preg_replace(
		/* Tblue> I removed the double quotes from the first RegExp because
				  it made URLs in tag attributes clickable.
				  See http://forums.b2evolution.net/viewtopic.php?p=92073 */
		array( '#(^|[\s>\(]|\[url=)(https?|mailto)://([^"<>{}\s]+[^".,:;!\?<>{}\s\]\)])#i',
			'#(^|[\s>\(]|\[url=)aim:([^",<\s\]\)]+)#i',
			'#(^|[\s>\(]|\[url=)icq:(\d+)#i',
			'#(^|[\s>\(]|\[url=)www\.'.$pattern_domain.'([^"<>{}\s]*[^".,:;!\?\s\]\)])#i',
			'#(^|[\s>\(]|\[url=)([a-z0-9\-_.]+?)@'.$pattern_domain.'([^".,:;!\?<\s\]\)]+)#i', ),
		array( '$1<a href="$2://$3"'.$additional_attrs.'>$2://$3</a>',
			'$1<a href="aim:goim?screenname=$2$3'.$moredelim.'message='.rawurlencode(T_('Hello')).'"'.$additional_attrs.'>$2$3</a>',
			'$1<a href="http://wwp.icq.com/scripts/search.dll?to=$2"'.$additional_attrs.'>$2</a>',
			'$1<a href="http://www.$2$3$4"'.$additional_attrs.'>www.$2$3$4</a>',
			'$1<a href="mailto:$2@$3$4"'.$additional_attrs.'>$2@$3$4</a>', ),
		$text );

	return $text;
}


/***** // Formatting functions *****/

/**
 * Convert timestamp to MySQL/ISO format.
 *
 * @param integer UNIX timestamp
 * @return string Date formatted as "Y-m-d H:i:s"
 */
function date2mysql( $ts )
{
	return date( 'Y-m-d H:i:s', $ts );
}

/**
 * Convert a MYSQL date to a UNIX timestamp.
 *
 * @param string Date formatted as "Y-m-d H:i:s"
 * @param boolean true to use GM time
 * @return integer UNIX timestamp
 */
function mysql2timestamp( $m, $useGM = false )
{
	$func = $useGM ? 'gmmktime' : 'mktime';
	return $func(
		intval( substr( $m, 11, 2 ) ),  // hour
		intval( substr( $m, 14, 2 ) ),  // minute
		intval( substr( $m, 17, 2 ) ),  // second
		intval( substr( $m, 5, 2 ) ),   // month
		intval( substr( $m, 8, 2 ) ),   // day
		intval( substr( $m, 0, 4 ) ) ); // year
}

/**
 * Convert a MYSQL date -- WITHOUT the time -- to a UNIX timestamp
 *
 * @param string Date formatted as "Y-m-d"
 * @param boolean true to use GM time
 * @return integer UNIX timestamp
 */
function mysql2datestamp( $m, $useGM = false )
{
	$func = $useGM ? 'gmmktime' : 'mktime';
	return $func( 0, 0, 0, substr($m,5,2), substr($m,8,2), substr($m,0,4) );
}

/**
 * Format a MYSQL date to current locale date format.
 *
 * @param string MYSQL date YYYY-MM-DD HH:MM:SS
 */
function mysql2localedate( $mysqlstring )
{
	return mysql2date( locale_datefmt(), $mysqlstring );
}

function mysql2localetime( $mysqlstring )
{
	return mysql2date( locale_timefmt(), $mysqlstring );
}

function mysql2localedatetime( $mysqlstring )
{
	return mysql2date( locale_datefmt().' '.locale_timefmt(), $mysqlstring );
}

function mysql2localedatetime_spans( $mysqlstring, $datefmt = NULL, $timefmt = NULL )
{
	if( is_null( $datefmt ) )
	{
		$datefmt = locale_datefmt();
	}
	if( is_null( $timefmt ) )
	{
		$timefmt = locale_timefmt();
	}

	return '<span class="date">'
					.mysql2date( $datefmt, $mysqlstring )
					.'</span> <span class="time">'
					.mysql2date( $timefmt, $mysqlstring )
					.'</span>';
}


/**
 * Format a MYSQL date.
 *
 * @param string enhanced format string
 * @param string MYSQL date YYYY-MM-DD HH:MM:SS
 * @param boolean true to use GM time
 */
function mysql2date( $dateformatstring, $mysqlstring, $useGM = false )
{
	$m = $mysqlstring;
	if( empty($m) || ($m == '0000-00-00 00:00:00' ) )
		return false;

	// Get a timestamp:
	$unixtimestamp = mysql2timestamp( $m );

	return date_i18n( $dateformatstring, $unixtimestamp, $useGM );
}


/**
 * Date internationalization: same as date() formatting but with i18n support.
 *
 * @todo dh> support for MySQL date format instead of $unixtimestamp? This would simplify callees, where currently mktime() is used.
 * @param string enhanced format string
 * @param integer UNIX timestamp
 * @param boolean true to use GM time
 */
function date_i18n( $dateformatstring, $unixtimestamp, $useGM = false )
{
	global $month, $month_abbrev, $weekday, $weekday_abbrev, $weekday_letter;
	global $localtimenow, $time_difference;

	if( $dateformatstring == 'isoZ' )
	{ // full ISO 8601 format
		$dateformatstring = 'Y-m-d\TH:i:s\Z';
	}

	if( $useGM )
	{ // We want a Greenwich Meridian time:
		// TODO: dh> what's the point of the substraction? UNIX timestamp should contain no time_difference in the first place?! Otherwise it should be substracted for !$useGM, too.
		// TODO: dh> Why does $useGM do not get the special symbols handling?
		$r = gmdate($dateformatstring, ($unixtimestamp - $time_difference));
	}
	else
	{ // We want default timezone time:

		/*
		Special symbols:
			'b': wether it's today (1) or not (0)
			'l': weekday
			'D': weekday abbrev
			'e': weekday letter
			'F': month
			'M': month abbrev
		*/

		#echo $dateformatstring, '<br />';

		// protect special symbols, that date() would need proper locale set for
		$protected_dateformatstring = preg_replace( '/(?<!\\\)([blDeFM])/', '@@@\\\$1@@@', $dateformatstring );

		#echo $protected_dateformatstring, '<br />';

		$r = date( $protected_dateformatstring, $unixtimestamp );

		if( $protected_dateformatstring != $dateformatstring )
		{ // we had special symbols, replace them

			$istoday = ( date('Ymd',$unixtimestamp) == date('Ymd',$localtimenow) ) ? '1' : '0';
			$datemonth = date('m', $unixtimestamp);
			$dateweekday = date('w', $unixtimestamp);

			// replace special symbols
			$r = str_replace( array(
						'@@@b@@@',
						'@@@l@@@',
						'@@@D@@@',
						'@@@e@@@',
						'@@@F@@@',
						'@@@M@@@',
						),
					array( $istoday,
						trim(T_($weekday[$dateweekday])),
						trim(T_($weekday_abbrev[$dateweekday])),
						trim(T_($weekday_letter[$dateweekday])),
						trim(T_($month[$datemonth])),
						trim(T_($month_abbrev[$datemonth])) ),
					$r );
		}
	}

	return $r;
}


/**
 * Add given # of days to a timestamp
 *
 * @param integer timestamp
 * @param integer days
 * @return integer timestamp
 */
function date_add_days( $timestamp, $days )
{
	return mktime( date('H',$timestamp), date('m',$timestamp), date('s',$timestamp),
								date('m',$timestamp), date('d',$timestamp)+$days, date('Y',$timestamp)  );
}

/**
 * Format dates into a string in a way similar to sprintf()
 */
function date_sprintf( $string, $timestamp )
{
	global $date_sprintf_timestamp;
	$date_sprintf_timestamp = $timestamp;

	return preg_replace_callback( '/%\{(.*?)\}/', 'date_sprintf_callback', $string );
}

function date_sprintf_callback( $matches )
{
	global $date_sprintf_timestamp;

	return date_i18n( $matches[1], $date_sprintf_timestamp );
}


/**
 * Get date name when date was happened
 *
 * @param integer Timestamp
 * @return string Name of date (Today, Yesterday, x days ago, x months ago, x years ago)
 */
function date_ago( $timestamp )
{
	global $servertimenow;

	$days = floor( ( $servertimenow - $timestamp ) / 86400 );
	$months = ceil( $days / 31 );

	if( $days < 1 )
	{	// Today
		return T_('Today');
	}
	elseif( $days == 1 )
	{	// Yesterday
		return T_('Yesterday');
	}
	elseif( $days > 1 && $days <= 31 )
	{	// Days
		return sprintf( T_('%s days ago'), $days );
	}
	elseif( $days > 31 && $months <= 12 )
	{	// Months
		return sprintf( $months == 1 ? T_('%s month ago') : T_('%s months ago'), $months );
	}
	else
	{	// Years
		$years = floor( $months / 12 );
		return sprintf( $years == 1 ? T_('%s year ago') : T_('%s years ago'), $years );
	}
}


/**
 * Convert seconds to readable period
 *
 * @param integer Seconds
 * @return string Readable time period
 */
function seconds_to_period( $seconds )
{
	$periods = array(
		array( 31536000, T_('1 year'),   T_('%s years') ), // 365 days
		array( 2592000,  T_('1 month'),  T_('%s months') ), // 30 days
		array( 86400,    T_('1 day'),    T_('%s days') ),
		array( 3600,     T_('1 hour'),   T_('%s hours') ),
		array( 60,       T_('1 minute'), T_('%s minutes') ),
		array( 1,        T_('1 second'), T_('%s seconds') ),
	);

	foreach( $periods as $p_info )
	{
		$period_value = intval( $seconds / $p_info[0] * 10 ) /10;
		if( $period_value >= 1 )
		{ // Stop on this period
			if( $period_value == 1 )
			{ // One unit of period
				$period_text = $p_info[1];
			}
			else
			{ // Two and more units of period
				$period_text = sprintf( $p_info[2], $period_value );
			}
			break;
		}
	}

	if( !isset( $period_text ) )
	{ // 0 seconds
		$period_text = sprintf( T_('%s seconds'), 0 );
	}

	return $period_text;
}


/**
 * Converts an ISO 8601 date to MySQL DateTime format.
 *
 * @param string date and time in ISO 8601 format {@link http://en.wikipedia.org/wiki/ISO_8601}.
 * @return string date and time in MySQL DateTime format Y-m-d H:i:s.
 */
function iso8601_to_datetime( $iso_date )
{
	return preg_replace('#([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(Z|[\+|\-][0-9]{2,4}){0,1}#', '$1-$2-$3 $4:$5:$6', $iso_date);
}


/**
 * Converts a MySQL DateTime to ISO 8601 date format.
 *
 * @param string date and time in MySQL DateTime format Y-m-d H:i:s
 * @return string date and time in ISO 8601 format {@link http://en.wikipedia.org/wiki/ISO_8601}.
 */
function datetime_to_iso8601( $datetime, $useGM = false )
{
	$iso_date = mysql2date('U', $datetime);

	if( $useGM )
	{
		$iso_date = gmdate('Ymd', $iso_date).'T'.gmdate('H:i:s', $iso_date);
	}
	else
	{
		$iso_date = date('Ymd', $iso_date).'T'.date('H:i:s', $iso_date);
	}

	return $iso_date;
}


/**
 *
 * @param integer year
 * @param integer month (0-53)
 * @param integer 0 for sunday, 1 for monday
 */
function get_start_date_for_week( $year, $week, $startofweek )
{
	$new_years_date = mktime( 0, 0, 0, 1, 1, $year );
	$weekday = date('w', $new_years_date);
	// echo '<br> 1st day is a: '.$weekday;

	// How many days until start of week:
	$days_to_new_week = (7 - $weekday + $startofweek) % 7;
	// echo '<br> days to new week: '.$days_to_new_week;

	// We now add the required number of days to find the 1st sunday/monday in the year:
	//$first_week_start_date = $new_years_date + $days_to_new_week * 86400;
	//echo '<br> 1st week starts on '.date( 'Y-m-d H:i:s', $first_week_start_date );

	// We add the number of requested weeks:
	// This will fail when passing to Daylight Savings Time: $date = $first_week_start_date + (($week-1) * 604800);
	$date = mktime( 0, 0, 0, 1, $days_to_new_week + 1 + ($week-1) * 7, $year );
	// echo '<br> week '.$week.' starts on '.date( 'Y-m-d H:i:s', $date );

	return $date;
}



/**
 * Get start and end day of a week, based on day f the week and start-of-week
 *
 * Used by Calendar
 *
 * @param date
 * @param integer 0 for Sunday, 1 for Monday
 */
function get_weekstartend( $date, $startOfWeek )
{
	while( date('w', $date) <> $startOfWeek )
	{
		// echo '<br />'.date('Y-m-d H:i:s w', $date).' - '.$startOfWeek;
		// mktime is needed so calculations work for DST enabling. Example: March 30th 2008, start of week 0 sunday
		$date = mktime( 0, 0, 0, date('m',$date), date('d',$date)-1, date('Y',$date) );
	}
	// echo '<br />'.date('Y-m-d H:i:s w', $date).' = '.$startOfWeek;
	$week['start'] = $date;
	$week['end']   = $date + 604800; // 7 days

	// pre_dump( 'weekstartend: ', date( 'Y-m-d', $week['start'] ), date( 'Y-m-d', $week['end'] ) );

	return( $week );
}


/**
 * Get datetime rounded to lower minute. This is meant to remove seconds and
 * leverage MySQL's query cache by having SELECT queries remain identical for 60 seconds instead of just 1.
 *
 * @param integer UNIX timestamp
 * @param string Format (defaults to "Y-m-d H:i"). Use "U" for UNIX timestamp.
 */
function remove_seconds($timestamp, $format = 'Y-m-d H:i')
{
	return date($format, floor($timestamp/60)*60);
}


/**
 * Convert from seconds to months, days, hours, minutes and seconds
 *
 * @param integer duration in seconds
 * @return array of [ years, months, days, hours, minutes, seconds ]
 */
function get_duration_fields( $duration )
{
	$result = array();

	$year_seconds = 31536000; // 1 year
	$years = floor( $duration / $year_seconds );
	$duration = $duration - $years * $year_seconds;
	$result[ 'years' ] = $years;

	$month_seconds = 2592000; // 1 month
	$months = floor( $duration / $month_seconds );
	$duration = $duration - $months * $month_seconds;
	$result[ 'months' ] = $months;

	$day_seconds = 86400; // 1 day
	$days = floor( $duration / $day_seconds );
	$duration = $duration - $days * $day_seconds;
	$result[ 'days' ] = $days;

	$hour_seconds = 3600; // 1 hour
	$hours = floor( $duration / $hour_seconds );
	$duration = $duration - $hours * $hour_seconds;
	$result[ 'hours' ] = $hours;

	$minute_seconds = 60; // 1 minute
	$minutes = floor( $duration / $minute_seconds );
	$duration = $duration - $minutes * $minute_seconds;
	$result[ 'minutes' ] = $minutes;

	$result[ 'seconds' ] = $duration;
	return $result;
}


/**
 * Get a title of duration
 *
 * @param integer Duration in seconds
 * @param array Titles
 * @return string Duration title
 */
function get_duration_title( $duration, $titles = array() )
{
	$titles = array_merge( array(
		'year'   => T_('Last %d years'),
		'month'  => T_('Last %d months'),
		'day'    => T_('Last %d days'),
		'hour'   => T_('Last %d hours'),
		'minute' => T_('Last %d minutes'),
		'second' => T_('Last %d seconds'),
		), $titles );

	$delay_fields = get_duration_fields( $duration );

	if( ! empty( $delay_fields[ 'years' ] ) )
	{ // Years
		return sprintf( $titles['year'], $delay_fields[ 'years' ] );
	}
	elseif( ! empty( $delay_fields[ 'months' ] ) )
	{ // Months
		return sprintf( $titles['month'], $delay_fields[ 'months' ] );
	}
	elseif( ! empty( $delay_fields[ 'days' ] ) )
	{ // Days
		return sprintf( $titles['day'], $delay_fields[ 'days' ] );
	}
	elseif( ! empty( $delay_fields[ 'hours' ] ) )
	{ // Hours
		return sprintf( $titles['hour'], $delay_fields[ 'hours' ] );
	}
	elseif( ! empty( $delay_fields[ 'minutes' ] ) )
	{ // Minutes
		return sprintf( $titles['minute'], $delay_fields[ 'minutes' ] );
	}
	else
	{ // Seconds
		return sprintf( $titles['second'], $delay_fields[ 'seconds' ] );
	}
}

function get_lastseen_date( $date, $view = 'exact_date', $cheat = 0 )
{
	$result = mysql2localedate( $date );

	if( $view == 'blurred_date' )
	{
		$result = (int)( ( ( time() - strtotime( $date ) ) / 86400 ) - $cheat);

		if( $result < 0 )
		{
			$result = 0;
		}

		if( $result < 3 )
		{
			$result = T_('less than 3 days ago');
		}
		elseif( $result < 7 )
		{
			$result = T_('less than a week ago');
		}
		elseif( $result < 30 )
		{
			$result = T_('less than a month ago');
		}
		elseif( $result < 90 )
		{
			$result = T_('less than 3 months ago');
		}
		else
		{
			$result = T_('more than 3 months ago');
		}
	}

	return $result;
}


/**
 * Validate variable
 *
 * @param string param name
 * @param string validator function name
 * @param boolean true if variable value can't be empty
 * @param custom error message
 * @return boolean true if OK
 */
function param_validate( $variable, $validator, $required = false, $custom_msg = NULL )
{
	/* Tblue> Note: is_callable() does not check whether a function is
	 *        disabled (http://www.php.net/manual/en/function.is-callable.php#79151).
	 */
	if( ! is_callable( $validator ) )
	{
		debug_die( 'Validator function '.$validator.'() is not callable!' );
	}

	if( ! isset( $GLOBALS[$variable] ) )
	{	// Variable not set, we cannot handle this using the validator function...
		if( $required )
		{	// Add error:
			param_check_not_empty( $variable, $custom_msg );
			return false;
		}

		return true;
	}

	if( $GLOBALS[$variable] === '' && ! $required )
	{	// Variable is empty or not set. That's fine since it isn't required:
		return true;
	}

	$msg = $validator( $GLOBALS[$variable] );

	if( !empty( $msg ) )
	{
		if( !empty( $custom_msg ) )
		{
			$msg = $custom_msg;
		}

		param_error( $variable, $msg );
		return false;
	}

	return true;
}


/**
 * Checks if the param is a decimal number
 *
 * @param string decimal to check
 * @return boolean true if OK
 */
function is_decimal( $decimal )
{
	return preg_match( '#^[0-9]*(\.[0-9]+)?$#', $decimal );
}


/**
 * Checks if the param is an integer (no float, e.g. 3.14).
 *
 * @param string number to check
 * @return boolean true if OK
 */
function is_number( $number )
{
	return preg_match( '#^[0-9]+$#', $number );
}


/**
 * Check that email address looks valid.
 *
 * @param string email address to check
 * @param string Format to use ('simple', 'rfc')
 *    'simple':
 *      Single email address.
 *    'rfc':
 *      Full email address, may include name (RFC2822)
 *      - example@example.org
 *      - Me <example@example.org>
 *      - "Me" <example@example.org>
 * @param boolean Return the match or boolean
 *
 * @return bool|array Either true/false or the match (see {@link $return_match})
 */
function is_email( $email, $format = 'simple', $return_match = false )
{
	#$chars = "/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i";

	switch( $format )
	{
		case 'rfc':
		case 'rfc2822':
			/**
			 * Regexp pattern converted from: http://www.regexlib.com/REDetails.aspx?regexp_id=711
			 * Extended to allow escaped quotes.
			 */
			$pattern_email = '/^
				(
					(?>[a-zA-Z\d!\#$%&\'*+\-\/=?^_`{|}~]+\x20*
						|"( \\\" | (?=[\x01-\x7f])[^"\\\] | \\[\x01-\x7f] )*"\x20*)* # Name
					(<)
				)?
				(
					(?!\.)(?>\.?[a-zA-Z\d!\#$%&\'*+\-\/=?^_`{|}~]+)+
					|"( \\\" | (?=[\x01-\x7f])[^"\\\] | \\[\x01-\x7f] )* " # quoted mailbox name
				)
				@
				(
					((?!-)[a-zA-Z\d\-]+(?<!-)\.)+[a-zA-Z]{2,}
					|
					\[(
						( (?(?<!\[)\.)(25[0-5] | 2[0-4]\d | [01]?\d?\d) ){4}
						|
						[a-zA-Z\d\-]*[a-zA-Z\d]:( (?=[\x01-\x7f])[^\\\[\]] | \\[\x01-\x7f] )+
					)\]
				)
				(?(3)>) # match ">" if it was there
				$/x';
			break;

		case 'simple':
		default:
			// '/^\S+@[^\.\s]\S*\.[a-z]{2,}$/i'
			$pattern_email = '~^(([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,}))$~i';
			break;
	}

	if( strpos( $email, '@' ) !== false && strpos( $email, '.' ) !== false )
	{
		if( $return_match )
		{
			preg_match( $pattern_email, $email, $match );
			return $match;
		}
		else
		{
			return (bool)preg_match( $pattern_email, $email );
		}
	}
	else
	{
		return $return_match ? array() : false;
	}
}


/**
 * Checks if the phone number is valid
 *
 * @param string phone number to check
 * @return boolean true if OK
 */
function is_phone( $phone )
{
	return preg_match( '|^\+?[\-*#/(). 0-9]+$|', $phone );
}


/**
 * Checks if the url is valid
 *
 * @param string url to check
 * @return boolean true if OK
 */
function is_url( $url )
{
	if( validate_url( $url, 'posting', false ) )
	{
		return false;
	}

	return true;
}


/**
 * Checks if the word is valid
 *
 * @param string word to check
 * @return boolean true if OK
 */
function is_word( $word )
{
	return preg_match( '#^[A-Za-z]+$#', $word );
}


/**
 * Check if the login is valid (in terms of allowed chars)
 *
 * @param string login
 * @return boolean true if OK
 */
function is_valid_login( $login, $force_strict_logins = false )
{
	global $Settings;

	$strict_logins = isset( $Settings ) ? $Settings->get('strict_logins') : 1;

	// NOTE: in some places usernames are typed in by other users (messaging) or admins.
	// Having cryptic logins with hard to type letters is a PITA.

	// Step 1
	// Forbid the following characters in logins
	if( preg_match( '~[\'"><@&\s]~', $login ) )
	{	// WARNING: allowing ' or " or > or < will open security issues!
		// NOTE: allowing @ will make some "average" users use their email address (not good for their spam health)
		// NOTE: we do not allow whitespace in logins
		return false;
	}

	// Step 2
	if( ($strict_logins || $force_strict_logins) && ! preg_match( '~^[A-Za-z0-9_.]+$~', $login ) )
	{	// WARNING: allowing special chars like latin 1 accented chars ( \xDF-\xF6\xF8-\xFF ) will create issues with
		// user media directory names (tested on Max OS X) -- Do no allow any of this until we have a clean & safe media dir name generator.

		// fp> TODO: check why a dash '-' prevents renaming the fileroot
		return false;
	}
	elseif( ! $strict_logins )
	{	// We allow any character that is not explicitly forbidden in Step 1
		// Enforce additional limitations
		$login = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $login ); // Kill octets
		$login = preg_replace( '/&.+?;/', '', $login ); // Kill entities
	}

	// Step 3
	// Special case, the login is valid however we forbid it's usage.
	// param_check_valid_login() will display a special error message in this case.
	if( preg_match( '~^usr_~', $login ) )
	{	// Logins cannot start with 'usr_', this prefix is reserved for system use
		// We create user media directories for users with non-ASCII logins in format /media/users/usr_55/, where 55 is user ID
		return 'usr';
	}

	return true;
}


/**
 * Checks if the color is valid
 *
 * @param string color to check
 * @return boolean true if OK
 */
function is_color( $color )
{
	return preg_match( '~^(#([a-f0-9]{3}){1,2})?$~i', $color );
}


/**
 * Check if the login is valid (user exists)
 *
 * @param string login
 * @return boolean true if OK
 */
function user_exists( $login )
{
	global $DB;

	$SQL = new SQL();
	$SQL->SELECT( 'COUNT(*)' );
	$SQL->FROM( 'T_users' );
	$SQL->WHERE( 'user_login = "'.$DB->escape($login).'"' );

	$var = $DB->get_var( $SQL->get() );
	return $var > 0 ? true : false; // PHP4 compatibility
}


/**
 * Are we running on a Windows server?
 */
function is_windows()
{
	return ( strtoupper(substr(PHP_OS,0,3)) == 'WIN' );
}


/**
 * Get all "a" tags from the given content
 *
 * @param string content
 * @return array all <a../a> part from the given content
 */
function get_atags( $content )
{
	$tags = array();

	if( preg_match_all( '#(<a[^>]+>(.*?)</a>|&lt;a.+&gt;(.*?)&lt;/a&gt;)#i', $content, $result ) )
	{
		$tags = $result[0];
	}

	return $tags;
}


/**
 * Add class to an html tag
 *
 * @param string HTML content
 * @param string Class to add to the tag in the HTML
 * @param integer Number of tags to add the class to
 * @return string HTML content with the added class
 */
function add_tag_class( $content, $class, $limit = 1 )
{
	global $add_class;
	$add_class = $class;

	$content = preg_replace_callback( '/<[^\/].*?>/i', 'callback_add_tag_class',
		$content, $limit );

	unset( $add_class );

	return $content;
}
function callback_add_tag_class( $matches )
{
	global $add_class;
	// Check if class attribute is already defined
	if( preg_match( '/\sclass\s*=/i', $matches[0] ) )
	{ // Insert class
		//$content = preg_replace( '/(<.*)(class\s*=\s*")(.*)"/i', '$1$2$3 '.$class.'"', $content, $limit );
		return preg_replace( '/(<[a-zA-z_:]\s*?.*?\s*?class=")(.*?)(".*?>)/i', '$1$2 '.$add_class.'$3', $matches[0] );
	}
	else
	{
		return preg_replace( '/>/i', ' class="'.$add_class.'"$1>', $matches[0] );
	}
}


/**
 * Get all "img" tags from the given content
 *
 * @param string content
 * @return array all <img../img> part from the given content
 */
function get_imgtags( $content )
{
	$tag = 'img';
	$regexp = '{<'.$tag.'[^>]*[ (</'.$tag.'>) | (/>) ]}';

	preg_match_all( $regexp, $content, $result );
	return $result[0];
}


/**
 * Get all urls from the given content
 *
 * @param string content
 * @return array all url from content
 */
function get_urls( $content )
{
	$regexp = '^(?#Protocol)(?:(?:ht|f)tp(?:s?)\:\/\/|~\/|\/)?(?#Username:Password)(?:\w+:\w+@)?(?#Subdomains)(?:(?:[-\w]+\.)+(?#TopLevel Domains)(?:com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum|travel|[a-z]{2,4}))(?#Port)(?::[\d]{1,5})?(?#Directories)(?:(?:(?:\/(?:[-\w~!$+|.,;=]|%[a-f\d]{2})+)+|\/)+|\?|#)?(?#Query)(?:(?:\?(?:[-\w~!$+|.,;*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,;*:=]|%[a-f\d]{2})*)(?:&(?:[-\w~!$+|.,;*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,;*:=]|%[a-f\d]{2})*)*)*(?#Anchor)(?:#(?:[-\w~!$+|.,;*:=]|%[a-f\d]{2})*)?^';

	preg_match_all( $regexp, $content, $result );
	return $result[0];
}


function xmlrpc_getposttitle($content)
{
	global $post_default_title;
	if (preg_match('/<title>(.+?)<\/title>/is', $content, $matchtitle))
	{
		$post_title = $matchtitle[1];
	}
	else
	{
		$post_title = $post_default_title;
	}
	return($post_title);
}


/**
 * Also used by post by mail
 *
 * @deprecated by xmlrpc_getpostcategories()
 */
function xmlrpc_getpostcategory($content)
{
	if (preg_match('/<category>([0-9]+?)<\/category>/is', $content, $matchcat))
	{
		return $matchcat[1];
	}

	return false;
}


/**
 * Extract categories out of "<category>" tag from $content.
 *
 * NOTE: w.bloggar sends something like "<category>00000013,00000001,00000004,</category>" to
 *       blogger.newPost.
 *
 * @return false|array
 */
function xmlrpc_getpostcategories($content)
{
	$cats = array();

	if( preg_match('~<category>(\d+\s*(,\s*\d*)*)</category>~i', $content, $match) )
	{
		$cats = preg_split('~\s*,\s*~', $match[1], -1, PREG_SPLIT_NO_EMPTY);
		foreach( $cats as $k => $v )
		{
			$cats[$k] = (int)$v;
		}
	}

	return $cats;
}


/*
 * xmlrpc_removepostdata(-)
 */
function xmlrpc_removepostdata($content)
{
	$content = preg_replace('/<title>(.*?)<\/title>/si', '', $content);
	$content = preg_replace('/<category>(.*?)<\/category>/si', '', $content);
	$content = trim($content);
	return($content);
}


/**
 * Echo the XML-RPC call Result and optionally log into file
 *
 * @param object XMLRPC response object
 * @param boolean true to echo
 * @param mixed File resource or == '' for no file logging.
 */
function xmlrpc_displayresult( $result, $display = true, $log = '' )
{
	if( ! $result )
	{ // We got no response:
		if( $display ) echo T_('No response!')."<br />\n";
		return false;
	}

	if( $result->faultCode() )
	{ // We got a remote error:
		if( $display ) echo T_('Remote error'), ': ', $result->faultString(), ' (', $result->faultCode(), ")<br />\n";
		debug_fwrite($log, $result->faultCode().' -- '.$result->faultString());
		return false;
	}

	// We'll display the response:
	$val = $result->value();
	$value = xmlrpc_decode_recurse($result->value());

	if( is_array($value) )
	{
		$out = '';
		foreach($value as $l_value)
		{
			if( is_array( $l_value ) )
			{
				$out .= ' [';
				foreach( $l_value as $lv_key => $lv_val )
				{
					$out .= $lv_key.' => '.( is_array( $lv_val ) ? '{'.implode( '; ', $lv_val ).'}' : $lv_val ).'; ';
				}
				$out .= '] ';
			}
			else
			{
				$out .= ' ['.$l_value.'] ';
			}
		}
	}
	else
	{
		$out = $value;
	}

	debug_fwrite($log, $out);

	if( $display ) echo T_('Response').': '.$out."<br />\n";

	return $value;
}


/**
 * Log the XML-RPC call Result into LOG object
 *
 * @param object XMLRPC response object
 * @param Log object to add messages to
 * @return boolean true = success, false = error
 */
function xmlrpc_logresult( $result, & $message_Log, $log_payload = true )
{
	if( ! $result )
	{ // We got no response:
		$message_Log->add( T_('No response!'), 'error' );
		return false;
	}

	if( $result->faultCode() )
	{ // We got a remote error:
		$message_Log->add( T_('Remote error').': '.$result->faultString().' ('.$result->faultCode().')', 'error' );
		return false;
	}

	if( $log_payload )
	{
		// We got a response:
		$value = xmlrpc_decode_recurse($result->value());

		if( is_array($value) )
		{
			$out = '';
			foreach($value as $l_value)
			{
				$out .= ' ['.var_export($l_value, true).'] ';
			}
		}
		else
		{
			$out = $value;
		}

		$message_Log->add( T_('Response').': '.$out, 'success' );
	}

	return true;
}



function debug_fopen($filename, $mode) {
	global $debug;
	if ($debug == 1 && ( !empty($filename) ) )
	{
		$fp = fopen($filename, $mode);
		return $fp;
	} else {
		return false;
	}
}

function debug_fwrite($fp, $string)
{
	global $debug;
	if( $debug && $fp )
	{
		fwrite($fp, $string);
	}
}

function debug_fclose($fp)
{
	global $debug;
	if( $debug && $fp )
	{
		fclose($fp);
	}
}



/**
 * Wrap pre tag around {@link var_dump()} for better debugging.
 *
 * @param $var__var__var__var__,... mixed variable(s) to dump
 * @return true
 */
function pre_dump( $var__var__var__var__ )
{
	global $is_cli;

	#echo 'pre_dump(): '.debug_get_backtrace(); // see where a pre_dump() comes from

	$func_num_args = func_num_args();
	$count = 0;

	if( ! empty($is_cli) )
	{ // CLI, no encoding of special chars:
		$count = 0;
		foreach( func_get_args() as $lvar )
		{
			var_dump($lvar);

			$count++;
			if( $count < $func_num_args )
			{ // Put newline between arguments
				echo "\n";
			}
		}
	}
	elseif( function_exists('xdebug_var_dump') )
	{ // xdebug already does fancy displaying:

		// no limits:
		$old_var_display_max_children = @ini_set('xdebug.var_display_max_children', -1); // default: 128
		$old_var_display_max_data = @ini_set('xdebug.var_display_max_data', -1); // max string length; default: 512
		$old_var_display_max_depth = @ini_set('xdebug.var_display_max_depth', -1); // default: 3

		echo "\n<div style=\"padding:1ex;border:1px solid #00f;\">\n";
		foreach( func_get_args() as $lvar )
		{
			xdebug_var_dump($lvar);

			$count++;
			if( $count < $func_num_args )
			{ // Put HR between arguments
				echo "<hr />\n";
			}
		}
		echo '</div>';

		// restore xdebug settings:
		@ini_set('xdebug.var_display_max_children', $old_var_display_max_children);
		@ini_set('xdebug.var_display_max_data', $old_var_display_max_data);
		@ini_set('xdebug.var_display_max_depth', $old_var_display_max_depth);
	}
	else
	{
		$orig_html_errors = @ini_set('html_errors', 0); // e.g. xdebug would use fancy html, if this is on; we catch (and use) xdebug explicitly above, but just in case

		echo "\n<pre style=\"padding:1ex;border:1px solid #00f;overflow:auto\">\n";
		foreach( func_get_args() as $lvar )
		{
			ob_start();
			var_dump($lvar); // includes "\n"; do not use var_export() because it does not detect recursion by design
			$buffer = ob_get_contents();
			ob_end_clean();
			echo htmlspecialchars($buffer);

			$count++;
			if( $count < $func_num_args )
			{ // Put HR between arguments
				echo "<hr />\n";
			}
		}
		echo "</pre>\n";
		@ini_set('html_errors', $orig_html_errors);
	}
	evo_flush();
	return true;
}


/**
 * Get a function trace from {@link debug_backtrace()} as html table.
 *
 * Adopted from {@link http://us2.php.net/manual/de/function.debug-backtrace.php#47644}.
 *
 * @todo dh> Add support for $is_cli = true (e.g. in case of MySQL error)
 *
 * @param integer|NULL Get the last x entries from the stack (after $ignore_from is applied). Anything non-numeric means "all".
 * @param array After a key/value pair matches a stack entry, this and the rest is ignored.
 *              For example, array('class' => 'DB') would exclude everything after the stack
 *              "enters" class DB and everything that got called afterwards.
 *              You can also give an array of arrays which means that every condition in one of the given array must match.
 * @param integer Number of stack entries to include, after $ignore_from matches.
 * @return string HTML table
 */
function debug_get_backtrace( $limit_to_last = NULL, $ignore_from = array( 'function' => 'debug_get_backtrace' ), $offset_ignore_from = 0 )
{
	if( ! function_exists( 'debug_backtrace' ) ) // PHP 4.3.0
	{
		return 'Function debug_backtrace() is not available!';
	}

	$r = '';

	$backtrace = debug_backtrace();
	$count_ignored = 0; // remember how many have been ignored
	$limited = false;   // remember if we have limited to $limit_to_last

	if( $ignore_from )
	{	// we want to ignore from a certain point
		$trace_length = 0;
		$break_because_of_offset = false;

		for( $i = count($backtrace); $i > 0; $i-- )
		{	// Search the backtrace from behind (first call).
			$l_stack = & $backtrace[$i-1];

			if( $break_because_of_offset && $offset_ignore_from < 1 )
			{ // we've respected the offset, but need to break now
				break; // ignore from here
			}

			foreach( $ignore_from as $l_ignore_key => $l_ignore_value )
			{	// Check if we want to ignore from here
				if( is_array($l_ignore_value) )
				{	// It's an array - all must match
					foreach( $l_ignore_value as $l_ignore_mult_key => $l_ignore_mult_val )
					{
						if( !isset($l_stack[$l_ignore_mult_key]) /* not set with this stack entry */
							|| strcasecmp($l_stack[$l_ignore_mult_key], $l_ignore_mult_val) /* not this value (case-insensitive) */ )
						{
							continue 2; // next ignore setting, because not all match.
						}
					}
					if( $offset_ignore_from-- > 0 )
					{
						$break_because_of_offset = true;
						break;
					}
					break 2; // ignore from here
				}
				elseif( isset($l_stack[$l_ignore_key])
					&& !strcasecmp($l_stack[$l_ignore_key], $l_ignore_value) /* is equal case-insensitive */ )
				{
					if( $offset_ignore_from-- > 0 )
					{
						$break_because_of_offset = true;
						break;
					}
					break 2; // ignore from here
				}
			}
			$trace_length++;
		}

		$count_ignored = count($backtrace) - $trace_length;

		$backtrace = array_slice( $backtrace, 0-$trace_length ); // cut off ignored ones
	}

	$count_backtrace = count($backtrace);
	if( is_numeric($limit_to_last) && $limit_to_last < $count_backtrace )
	{	// we want to limit to a maximum number
		$limited = true;
		$backtrace = array_slice( $backtrace, 0, $limit_to_last );
		$count_backtrace = $limit_to_last;
	}

	$r .= '<div style="padding:1ex; margin-bottom:1ex; text-align:left; color:#000; background-color:#ddf;">
					<h3>Backtrace:</h3>'."\n";
	if( $count_backtrace )
	{
		$r .= '<ol style="font-family:monospace;">';

		$i = 0;
		foreach( $backtrace as $l_trace )
		{
			if( ++$i == $count_backtrace )
			{
				$r .= '<li style="padding:0.5ex 0;">';
			}
			else
			{
				$r .= '<li style="padding:0.5ex 0; border-bottom:1px solid #77d;">';
			}
			$args = array();
			if( isset($l_trace['args']) && is_array( $l_trace['args'] ) )
			{	// Prepare args:
				foreach( $l_trace['args'] as $l_arg )
				{
					$l_arg_type = gettype($l_arg);
					switch( $l_arg_type )
					{
						case 'integer':
						case 'double':
							$args[] = $l_arg;
							break;
						case 'string':
							$args[] = '"'.strmaxlen(str_replace("\n", '\n', $l_arg), 255, NULL, 'htmlspecialchars').'"';
							break;
						case 'array':
							$args[] = 'Array('.count($l_arg).')';
							break;
						case 'object':
							$args[] = 'Object('.get_class($l_arg).')';
							break;
						case 'resource':
							$args[] = htmlspecialchars((string)$l_arg);
							break;
						case 'boolean':
							$args[] = $l_arg ? 'true' : 'false';
							break;
						default:
							$args[] = $l_arg_type;
					}
				}
			}

			$call = "<strong>\n";
			if( isset($l_trace['class']) )
			{
				$call .= htmlspecialchars($l_trace['class']);
			}
			if( isset($l_trace['type']) )
			{
				$call .= htmlspecialchars($l_trace['type']);
			}
			$call .= htmlspecialchars($l_trace['function'])."( </strong>\n";
			if( $args )
			{
				$call .= ' '.implode( ', ', $args ).' ';
			}
			$call .='<strong>)</strong>';

			$r .= $call."<br />\n";

			$r .= '<strong>';
			if( isset($l_trace['file']) )
			{
				$r .= "File: </strong> ".$l_trace['file'];
			}
			else
			{
				$r .= '[runtime created function]</strong>';
			}
			if( isset($l_trace['line']) )
			{
				$r .= ' on line '.$l_trace['line'];
			}

			$r .= "</li>\n";
		}
		$r .= '</ol>';
	}
	else
	{
		$r .= '<p>No backtrace available.</p>';
	}

	// Extra notes, might be to much, but explains why we stopped at some point. Feel free to comment it out or remove it.
	$notes = array();
	if( $count_ignored )
	{
		$notes[] = 'Ignored last: '.$count_ignored;
	}
	if( $limited )
	{
		$notes[] = 'Limited to'.( $count_ignored ? ' remaining' : '' ).': '.$limit_to_last;
	}
	if( $notes )
	{
		$r .= '<p class="small">'.implode( ' - ', $notes ).'</p>';
	}

	$r .= "</div>\n";

	return $r;
}


/**
 * Outputs Unexpected Error message. When in debug mode it also prints a backtrace.
 *
 * This should be used instead of die() everywhere.
 * This should NOT be used instead of exit() anywhere.
 * Dying means the application has encontered an unexpected situation,
 * i-e: something that should never occur during normal operation.
 * Examples: database broken, user changed URL by hand...
 *
 * @param string Message to output
 * @param array Additional params
 *        - "status" (Default: '500 Internal Server Error')
 *        - "debug_info" - Use this info instead of $additional_info when debug is ON
 */
function debug_die( $additional_info = '', $params = array() )
{
	global $debug, $baseurl;
	global $log_app_errors, $app_name, $is_cli, $display_errors_on_production, $is_api_request;

	$params = array_merge( array(
		'status'     => '500 Internal Server Error',
		'debug_info' => '',
		), $params );

	if( $debug && ! empty( $params['debug_info'] ) )
	{ // Display 'debug_info' when debug is ON
		$additional_info = $params['debug_info'];
	}

	// Send the predefined cookies:
	evo_sendcookies();

	if( $is_api_request )
	{	// REST API or XMLRPC request:

		// Set JSON content type:
		headers_content_mightcache( 'application/json', 0, '#', false ); // Do NOT cache error messages! (Users would not see they fixed them)
		header_http_response( $params['status'] );

		echo json_encode( array(
				'error_status' => $params['status'],
				'error_info'   => $additional_info,
			) );

		die(1); // Error code 1. Note: This will still call the shutdown function.
	}
	elseif( $is_cli )
	{ // Command line interface, e.g. in cron_exec.php:
		echo '== '.T_('An unexpected error has occurred!')." ==\n";
		echo T_('If this error persists, please report it to the administrator.')."\n";
		if( $debug || $display_errors_on_production )
		{ // Display additional info only in debug mode or when it was explicitly set by display_errors_on_production setting because it can reveal system info to hackers and greatly facilitate exploits
			echo T_('Additional information about this error:')."\n";
			echo strip_tags( $additional_info )."\n\n";
		}
	}
	else
	{
		// Attempt to output an error header (will not work if the output buffer has already flushed once):
		// This should help preventing indexing robots from indexing the error :P
		if( ! headers_sent() )
		{
			load_funcs('_core/_template.funcs.php');
			headers_content_mightcache( 'text/html', 0, '#', false );  // Do NOT cache error messages! (Users would not see they fixed them)
			$status_header = $_SERVER['SERVER_PROTOCOL'].' '.$params['status'];
			header($status_header);
		}

		echo '<div style="background-color: #fdd; padding: 1ex; margin-bottom: 1ex;">';
		echo '<h3 style="color:#f00;">'.T_('An unexpected error has occurred!').'</h3>';
		echo '<p>'.T_('If this error persists, please report it to the administrator.').'</p>';
		echo '<p><a href="'.$baseurl.'">'.T_('Go back to home page').'</a></p>';
		echo '</div>';

		if( ! empty( $additional_info ) )
		{
			echo '<div style="background-color: #ddd; padding: 1ex; margin-bottom: 1ex;">';
			if( $debug || $display_errors_on_production )
			{ // Display additional info only in debug mode or when it was explicitly set by display_errors_on_production setting because it can reveal system info to hackers and greatly facilitate exploits
				echo '<h3>'.T_('Additional information about this error:').'</h3>';
				echo $additional_info;
			}
			else
			{
				echo '<p><i>Enable debugging to get additional information about this error.</i></p>' . get_manual_link('debugging','How to enable debug mode?');
			}
			echo '</div>';

			// Append the error text to AJAX log if it is AJAX request
			global $Ajaxlog;
			if( ! empty( $Ajaxlog ) )
			{
				$Ajaxlog->add( $additional_info, 'error' );
				$Ajaxlog->display( NULL, NULL, true, 'all',
								array(
										'error' => array( 'class' => 'jslog_error', 'divClass' => false ),
										'note'  => array( 'class' => 'jslog_note',  'divClass' => false ),
									), 'ul', 'jslog' );
			}
		}
	}

	if( $log_app_errors > 1 || $debug )
	{ // Prepare backtrace
		$backtrace = debug_get_backtrace();

		if( $log_app_errors > 1 || $is_cli )
		{
			$backtrace_cli = trim(strip_tags($backtrace));
		}
	}

	if( $log_app_errors )
	{ // Log error through PHP's logging facilities:
		$log_message = $app_name.' error: ';
		if( ! empty($additional_info) )
		{
			$log_message .= trim( strip_tags($additional_info) );
		}
		else
		{
			$log_message .= 'No info specified in debug_die()';
		}

		// Get file and line info:
		$file = 'Unknown';
		$line = 'Unknown';
		if( function_exists('debug_backtrace') /* PHP 4.3 */ )
		{ // get the file and line
			foreach( debug_backtrace() as $v )
			{
				if( isset($v['function']) && $v['function'] == 'debug_die' )
				{
					$file = isset($v['file']) ? $v['file'] : 'Unknown';
					$line = isset($v['line']) ? $v['line'] : 'Unknown';
					break;
				}
			}
		}
		$log_message .= ' in '.$file.' at line '.$line;

		if( $log_app_errors > 1 )
		{ // Append backtrace:
			// indent after newlines:
			$backtrace_cli = preg_replace( '~(\S)(\n)(\S)~', '$1  $2$3', $backtrace_cli );
			$log_message .= "\nBacktrace:\n".$backtrace_cli;
		}
		$log_message .= "\nREQUEST_URI:  ".( isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-' );
		$log_message .= "\nHTTP_REFERER: ".( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-' );

		error_log( str_replace("\n", ' / ', $log_message), 0 /* PHP's system logger */ );
	}


	// DEBUG OUTPUT:
	if( $debug )
	{
		if( $is_cli )
			echo $backtrace_cli;
		else
			echo $backtrace;
	}

	// EXIT:
	if( ! $is_cli )
	{ // Attempt to keep the html valid (but it doesn't really matter anyway)
		echo '</body></html>';
	}

	die(1);	// Error code 1. Note: This will still call the shutdown function.
}


/**
 * Outputs Bad request Error message. When in debug mode it also prints a backtrace.
 *
 * This should be used when a bad user input is detected.
 *
 * @param string Message to output (HTML)
 */
function bad_request_die( $additional_info = '' )
{
	global $debug, $baseurl, $is_api_request;

	// Send the predefined cookies:
	evo_sendcookies();

	if( $is_api_request )
	{	// REST API or XMLRPC request:

		// Set JSON content type:
		headers_content_mightcache( 'application/json', 0, '#', false ); // Do NOT cache error messages! (Users would not see they fixed them)
		header_http_response( '400 Bad Request' );

		echo json_encode( array(
				'error_status' => '400 Bad Request',
				'error_info'   => $additional_info,
			) );

		die(2); // Error code 2. Note: this will still call the shutdown function.
	}

	// Attempt to output an error header (will not work if the output buffer has already flushed once):
	// This should help preventing indexing robots from indexing the error :P
	if( ! headers_sent() )
	{
		load_funcs('_core/_template.funcs.php');
		headers_content_mightcache( 'text/html', 0, '#', false );		// Do NOT cache error messages! (Users would not see they fixed them)
		header_http_response('400 Bad Request');
	}

	if( ! function_exists( 'T_' ) )
	{	// Load locale funcs to initialize function "T_" because it is used below:
		load_funcs( 'locales/_locale.funcs.php' );
	}

	echo '<div style="background-color: #fdd; padding: 1ex; margin-bottom: 1ex;">';
	echo '<h3 style="color:#f00;">'.T_('Bad Request!').'</h3>';
	echo '<p>'.T_('The parameters of your request are invalid.').'</p>';
	echo '<p>'.T_('If you have obtained this error by clicking on a link INSIDE of this site, please report the bad link to the administrator.').'</p>';
	echo '<p><a href="'.$baseurl.'">'.T_('Go back to home page').'</a></p>';
	echo '</div>';

	if( !empty( $additional_info ) )
	{
		echo '<div style="background-color: #ddd; padding: 1ex; margin-bottom: 1ex;">';
		if( $debug )
		{	// Display additional info only in debug mode because it can reveal system info to hackers and greatly facilitate exploits
			echo '<h3>'.T_('Additional information about this error:').'</h3>';
			echo $additional_info;
		}
		else
		{
			echo '<p><i>Enable debugging to get additional information about this error.</i></p>' . get_manual_link('debugging','How to enable debug mode?');
		}
		echo '</div>';

		// Append the error text to AJAX log if it is AJAX request
		global $Ajaxlog;
		if( ! empty( $Ajaxlog ) )
		{
			$Ajaxlog->add( $additional_info, 'error' );
			$Ajaxlog->display( NULL, NULL, true, 'all',
							array(
									'error' => array( 'class' => 'jslog_error', 'divClass' => false ),
									'note'  => array( 'class' => 'jslog_note',  'divClass' => false ),
								), 'ul', 'jslog' );
		}
	}

	if( $debug )
	{
		echo debug_get_backtrace();
	}

	// Attempt to keep the html valid (but it doesn't really matter anyway)
	echo '</body></html>';

	die(2); // Error code 2. Note: this will still call the shutdown function.
}


/**
 * Outputs debug info, according to {@link $debug} or $force param. This gets called typically at the end of the page.
 *
 * @param boolean true to force output regardless of {@link $debug}
 * @param boolean true to force clean output (without HTML) regardless of {@link $is_cli}
 */
function debug_info( $force = false, $force_clean = false )
{
	global $debug, $debug_done, $debug_jslog, $debug_jslog_done, $Debuglog, $DB, $obhandler_debug, $Timer, $ReqHost, $ReqPath, $is_cli;
	global $cache_imgsize, $cache_File;
	global $Session;
	global $db_config, $tableprefix, $http_response_code, $disp, $disp_detail, $robots_index, $robots_follow, $content_type_header;
	/**
	 * @var Hit
	 */
	global $Hit;

	// Detect content-type
	$content_type = NULL;
	foreach(headers_list() as $header)
	{
		if( stripos($header, 'content-type:') !== false )
		{ // content type sent
			# "Content-Type:text/html;charset=utf-8" => "text/html"
			$content_type = explode( ':', $header, 2 );
			$content_type = explode( ';', array_pop( $content_type ) );
			$content_type = trim( array_shift( $content_type ) );
			break;
		}
	}

	// ---- Print AJAX Log
	if( empty( $debug_jslog_done ) && ( $debug || $debug_jslog ) && $content_type == 'text/html' )
	{	// Display debug jslog once
		global $rsc_url, $app_version_long;

		$relative_to = ( is_admin_page() ? 'rsc_url' : 'blog' );

		require_js( '#jqueryUI#', $relative_to, false, true );
		require_css( '#jqueryUI_css#', $relative_to, NULL, NULL, '#', true );
		require_js( 'debug_jslog.js', $relative_to, false, true );
		require_js( 'jquery/jquery.cookie.min.js', $relative_to, false, true );

		$jslog_style_cookies = param_cookie( 'jslog_style', 'string' );
		$jslog_styles = array();
		if( !empty( $jslog_style_cookies ) )
		{	// Get styles only from cookies
			$jslog_style_cookies = explode( ';', $jslog_style_cookies );
			foreach( $jslog_style_cookies as $jsc => $style )
			{
				if( strpos( $style, 'height' ) !== false /*|| ( strpos( $style, 'display' ) !== false && !$debug_jslog )*/ )
				{	// Unset the height param from defined styles ( and the display param if jslog is disabled )
					unset( $jslog_style_cookies[$jsc] );
				}
			}
			$jslog_styles[] = implode( ';', $jslog_style_cookies );
		}
		else
		{
			if( !is_logged_in() )
			{	// Align top when evobar is hidden
				$jslog_styles[] = 'top:0';
			}
			if( $debug_jslog )
			{	// Display the jslog
				$jslog_styles[] = 'display:block';
			}
		}
		$jslog_styles = count( $jslog_styles ) > 0 ? ' style="'.implode( ';', $jslog_styles ).'"' : '';

		$close_url = url_add_param( $_SERVER['REQUEST_URI'], 'jslog' );
		echo '<div id="debug_ajax_info" class="debug"'.$jslog_styles.'>';
		echo '<div class="jslog_titlebar">'.
				'AJAX Debug log'.get_manual_link('ajax_debug_log').
				action_icon( T_('Close'), 'close', $close_url, NULL, NULL, NULL, array( 'class' => 'jslog_switcher' ) ).
			'</div>';
		echo '<div id="jslog_container"></div>';
		echo '<div class="jslog_statusbar">'.
				'<a href="'.$_SERVER['REQUEST_URI'].'#" class="jslog_clear">'.T_('Clear').'</a>'.
			'</div>';
		echo '</div>';

		// Make sure debug jslog output only happens once:
		$debug_jslog_done = true;
	}
	// ----

	// clean output:
	$clean = $is_cli || $force_clean;

	if( ! $force )
	{
		if( ! empty( $debug_done ) )
		{ // Already displayed!
			return;
		}

		if( empty( $debug ) || // No debug output desired:
		    ( $debug < 2 && $content_type != 'text/html' ) ) // Do not display, if no content-type header has been sent or it's != "text/html" (debug > 1 skips this)
		{
			global $evo_last_handled_error;
			if( ! empty( $evo_last_handled_error ) )
			{ // If script has been stoppped by some error
				// Display a message when debug is OFF and error has occured
				$debug_off_title = 'An unexpected error has occured!';
				$debug_off_msg1 = 'We apologize for the inconvenience.';
				$debug_off_msg2 = 'This error has been automatically reported and we will work to resolve it as fast as possible.';
				if( $clean )
				{ // CLI mode
					echo '*** '.$debug_off_title.' ***'."\n\n"
						.$debug_off_msg1."\n"
						.$debug_off_msg2;
				}
				else
				{ // View from browser
					echo '<div style="margin:1em auto;padding:10px;background:#FEFFFF;border:2px solid #F00;border-radius:6px;text-align:center;">'
							.'<h2 style="margin:0;color:#F00;">'.$debug_off_title.'</h2>'
							.'<p>'.$debug_off_msg1.'</p>'
							.'<p style="margin-bottom:0">'.$debug_off_msg2.'</p>'
						.'</div>';
				}
			}
			return;
		}
	}
	//Make sure debug output only happens once:
	$debug_done = true;

	$printf_format = '| %-45s | %-5s | %-7s | %-5s |';
	$table_headerlen = 73;
	/* This calculates the number of dashes to print e. g. on the top and
	 * bottom of the table and after the header, making the table look
	 * better (looks like the tables of the mysql command line client).
	 * Normally, the value won't change, so it's hardcoded above. If you
	 * change the printf() format above, this might be useful.
	preg_match_all( '#\d+#', $printf_format, $table_headerlen );
	$table_headerlen = array_sum( $table_headerlen[0] ) +
									strlen( preg_replace( '#[^ \|]+#', '',
												$printf_format ) ) - 2;
	*/

	$ReqHostPathQuery = $ReqHost.$ReqPath.( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?'.$_SERVER['QUERY_STRING'] );

	echo "\n\n\n";
	echo ( $clean ? '*** Debug info ***'."\n\n" : '<div class="debug" id="debug_info"><h2>Debug info</h2>' );

	if( !$obhandler_debug )
	{ // don't display changing items when we want to test obhandler

		// ---------------------------

		echo '<div class="log_container"><div>';

		echo 'HTTP Response code: '.$http_response_code;
		echo $clean ? "\n" : '<br />';

		echo '$content_type_header: '.$content_type_header;
		echo $clean ? "\n" : '<br />';

		echo '$disp: '.$disp.' -- detail: '.$disp_detail;
		echo $clean ? "\n" : '<br />';

		echo '$robots_index: '.$robots_index;
		echo $clean ? "\n" : '<br />';

		echo '$robots_follow: '.$robots_follow;
		echo $clean ? "\n" : '<br />';

		echo '</div></div>';

		// ================================== DB Summary ================================
		if( isset($DB) )
		{
			echo '<div class="log_container"><div>';
			echo $DB->num_queries.' SQL queries executed in '.$Timer->get_duration( 'SQL QUERIES' )." seconds\n";
			if( ! $clean )
			{
				echo ' &nbsp; <a href="'.$ReqHostPathQuery.'#evo_debug_queries">scroll down to details</a><p>';
			}
			echo '</div></div>';
		}

		// ========================== Timer table ================================
		$time_page = $Timer->get_duration( 'total' );
		$timer_rows = array();
		foreach( $Timer->get_categories() as $l_cat )
		{
			if( $l_cat == 'sql_query' )
			{
				continue;
			}
			$timer_rows[ $l_cat ] = $Timer->get_duration( $l_cat );
		}
		// Don't sort to see orginal order of creation
		// arsort( $timer_rows );
		// ksort( $timer_rows );

		// Remove "total", it will get output as the last one:
		$total_time = $timer_rows['total'];
		unset($timer_rows['total']);

		$percent_total = $time_page > 0 ? number_format( 100/$time_page * $total_time, 2 ) : '0';

		if( $clean )
		{
			echo '== Timers =='."\n\n";
			echo '+'.str_repeat( '-', $table_headerlen ).'+'."\n";
			printf( $printf_format."\n", 'Category', 'Time', '%', 'Count' );
			echo '+'.str_repeat( '-', $table_headerlen ).'+'."\n";
		}
		else
		{
			echo '<table class="debug_timer"><thead>'
				.'<tr><td colspan="4" class="center">Timers</td></tr>' // dh> TODO: should be TH. Workaround so that tablesorter does not pick it up. Feedback from author requested.
				.'<tr><th>Category</th><th>Time</th><th>%</th><th>Count</th></tr>'
				.'</thead>';

			// Output "total":
			echo "\n<tfoot><tr>"
				.'<td>total</td>'
				.'<td class="right red">'.$total_time.'</td>'
				.'<td class="right">'.$percent_total.'%</td>'
				.'<td class="right">'.$Timer->get_count('total').'</td></tr></tfoot>';

			echo '<tbody>';
		}

		$table_rows_collapse = array();
		foreach( $timer_rows as $l_cat => $l_time )
		{
			$percent_l_cat = $time_page > 0 ? number_format( 100/$time_page * $l_time, 2 ) : '0';

			if( $clean )
			{
				$row = sprintf( $printf_format, $l_cat, $l_time, $percent_l_cat.'%', $Timer->get_count( $l_cat ) );
			}
			else
			{
				$row = "\n<tr>"
					.'<td>'.$l_cat.'</td>'
					.'<td class="right">'.$l_time.'</td>'
					.'<td class="right">'.$percent_l_cat.'%</td>'
					.'<td class="right">'.$Timer->get_count( $l_cat ).'</td></tr>';
			}

			// Maybe ignore this row later, but not for clean display.
			if( ! $clean && ( $percent_l_cat < 1  ) )
			{	// Hide everything that tool less tahn 5% of the time
				$table_rows_collapse[] = $row;
			}
			else
			{
				echo $row."\n";
			}
		}
		$count_collapse = count($table_rows_collapse);
		// Collapse ignored rows, allowing to expand them with Javascript:
		if( $count_collapse > 5 )
		{
			echo '<tr><td colspan="4" class="center" id="evo-debuglog-timer-long-header">';
			echo '<a href="" onclick="var e = document.getElementById(\'evo-debuglog-timer-long\'); e.style.display = (e.style.display == \'none\' ? \'\' : \'none\'); return false;">+ '.$count_collapse.' queries &lt; 1%</a> </td></tr>';
			echo '</tbody>';
			echo '<tbody id="evo-debuglog-timer-long" style="display:none;">';
		}
		echo implode( "\n", $table_rows_collapse )."\n";

		if ( $clean )
		{ // "total" (done in tfoot for html above)
			echo sprintf( $printf_format, 'total', $total_time, $percent_total.'%', $Timer->get_count('total') );
			echo '+'.str_repeat( '-', $table_headerlen ).'+'."\n\n";
		}
		else
		{
			echo "\n</tbody></table>";

			// add jquery.tablesorter to the "Debug info" table.
			$relative_to = ( is_admin_page() ? 'rsc_url' : 'blog' );
			require_js( 'jquery/jquery.tablesorter.min.js', $relative_to, true, true );
			echo '
			<script type="text/javascript">
			(function($){
				var clicked_once;
				jQuery("table.debug_timer th").click( function(event) {
					if( clicked_once ) return; else clicked_once = true;
					jQuery("#evo-debuglog-timer-long tr").appendTo(jQuery("table.debug_timer tbody")[0]);
					jQuery("#evo-debuglog-timer-long-header").remove();
					// click for tablesorter:
					jQuery("table.debug_timer").tablesorter();
					jQuery(event.currentTarget).click();
				});
			})(jQuery);
			</script>';
		}


		// ================================ Opcode caching ================================
		echo '<div class="log_container"><div>';
		echo 'Opcode cache: '.get_active_opcode_cache();
		echo $clean ? "\n" : '<p>';

		// ================================ User caching ================================
		echo 'User cache: '.get_active_user_cache();
		echo $clean ? "\n" : '<p>';
		echo '</div></div>';


		// ================================ Memory Usage ================================
		echo '<div class="log_container"><div>';

		foreach( array( // note: 8MB is default for memory_limit and is reported as 8388608 bytes
			'memory_get_usage' => array( 'display' => 'Memory usage', 'high' => 8000000 ),
			'memory_get_peak_usage' /* PHP 5.2 */ => array( 'display' => 'Memory peak usage', 'high' => 8000000 ) ) as $l_func => $l_var )
		{
			if( function_exists( $l_func ) )
			{
				$_usage = $l_func();

				if( $_usage > $l_var['high'] )
				{
					echo $clean ? '[!!] ' : '<span style="color:red; font-weight:bold">';
				}

				echo $l_var['display'].': '.bytesreadable( $_usage, ! $clean );

				if( ! $clean && $_usage > $l_var['high'] )
				{
					echo '</span>';
				}
				echo $clean ? "\n" : '<br />';
			}
		}

		echo 'Len of serialized $cache_imgsize: '.strlen(serialize($cache_imgsize));
		echo $clean ? "\n" : '<br />';
		echo 'Len of serialized $cache_File: '.strlen(serialize($cache_File));
		echo $clean ? "\n" : '<br />';

		echo '</div></div>';
	}


	// DEBUGLOG(s) FROM PREVIOUS SESSIONS, after REDIRECT(s) (with list of categories at top):
	if( isset($Session) && ($sess_Debuglogs = $Session->get('Debuglogs')) && ! empty($sess_Debuglogs) )
	{
		$count_sess_Debuglogs = count($sess_Debuglogs);
		if( $count_sess_Debuglogs > 1 )
		{ // Links to those Debuglogs:
			if ( $clean )
			{	// kind of useless, but anyway...
				echo "\n".'There are '.$count_sess_Debuglogs.' Debuglogs from redirected pages.'."\n";
			}
			else
			{
				echo '<p>There are '.$count_sess_Debuglogs.' Debuglogs from redirected pages: ';
				for( $i = 1; $i <= $count_sess_Debuglogs; $i++ )
				{
					echo '<a href="'.$ReqHostPathQuery.'#debug_sess_debuglog_'.$i.'">#'.$i.'</a> ';
				}
				echo '</p>';
			}
		}

		foreach( $sess_Debuglogs as $k => $sess_Debuglog )
		{
			$log_categories = array( 'error', 'note', 'all' ); // Categories to output (in that order)

			if( $clean )
			{
				$log_container_head = "\n".'== Debug messages from redirected page (#'.($k+1).') =='."\n"
									 .'See below for the Debuglog from the current request.'."\n";
				echo format_to_output(
					$sess_Debuglog->display( array(
							'container' => array( 'string' => $log_container_head, 'template' => false ),
							'all' => array( 'string' => '= %s ='."\n\n", 'template' => false ) ),
						'', false, $log_categories, '', 'raw', false ),
					'raw' );
			}
			else
			{
				$log_container_head = '<h3 id="debug_sess_debuglog_'.($k+1).'" style="color:#f00;">Debug messages from redirected page (#'.($k+1).')</h3>'
					// link to real Debuglog:
					.'<p><a href="'.$ReqHostPathQuery.'#debug_debuglog">See below for the Debuglog from the current request.</a></p>';
				$log_cats = array_keys($sess_Debuglog->get_messages( $log_categories )); // the real list (with all replaced and only existing ones)
				$log_head_links = array();

				foreach( $log_cats as $l_cat )
				{
					$log_head_links[] .= '<a href="'.$ReqHostPathQuery.'#debug_redir_'.($k+1).'_info_cat_'.str_replace( ' ', '_', $l_cat ).'">'.$l_cat.'</a>';
				}
				$log_container_head .= implode( ' | ', $log_head_links );

				echo '<div style="border:1px solid #F00;background:#aaa">'.
					format_to_output(
						$sess_Debuglog->display( array(
								'container' => array( 'string' => $log_container_head, 'template' => false ),
								'all' => array( 'string' => '<h4 id="debug_redir_'.($k+1).'_info_cat_%s">%s:</h4>', 'template' => false ) ),
							'', false, $log_categories ),
						'htmlbody' ).
					'</div>';
			}
		}

		// Delete logs since they have been displayed...
		// EXCEPT if we are redirecting, because in this case we won't see these logs in a browser (only in request debug tools)
		// So in that case we want them to move over to the next page...
		if( $http_response_code < 300 || $http_response_code >= 400 )
		{	// This is NOT a 3xx redirect, assume debuglogs have been seen & delete them:
			$Session->delete( 'Debuglogs' );
		}
	}


	// CURRENT DEBUGLOG (with list of categories at top):
	$log_categories = array( 'error', 'note', 'all' ); // Categories to output (in that order)
	$log_container_head = $clean ? ( "\n".'== Debug messages =='."\n" ) : '<h3 id="debug_debuglog">Debug messages</h3>';
	if( ! empty($sess_Debuglogs) )
	{ // link to first sess_Debuglog:
		if ( $clean )
		{
			$log_container_head .= 'See above for the Debuglog(s) from before the redirect.'."\n";
		}
		else
		{
			$log_container_head .= '<p><a href="'.$ReqHostPathQuery.'#debug_sess_debuglog_1">See above for the Debuglog(s) from before the redirect.</a></p>';
		}
	}

	if ( ! $clean )
	{
		$log_cats = array_keys($Debuglog->get_messages( $log_categories )); // the real list (with all replaced and only existing ones)
		$log_head_links = array();
		foreach( $log_cats as $l_cat )
		{
			$log_head_links[] .= '<a href="'.$ReqHostPathQuery.'#debug_info_cat_'.str_replace( ' ', '_', $l_cat ).'">'.$l_cat.'</a>';
		}
		$log_container_head .= implode( ' | ', $log_head_links );

		echo format_to_output(
			$Debuglog->display( array(
					'container' => array( 'string' => $log_container_head, 'template' => false ),
					'all' => array( 'string' => '<h4 id="debug_info_cat_%s">%s:</h4>', 'template' => false ) ),
				'', false, $log_categories ),
			'htmlbody' );

		echo '<h3 id="evo_debug_queries">DB</h3>';
	}
	else
	{
		echo format_to_output(
			$Debuglog->display( array(
					'container' => array( 'string' => $log_container_head, 'template' => false ),
					'all' => array( 'string' => '= %s ='."\n\n", 'template' => false ) ),
				'', false, $log_categories, '', 'raw', false ),
			'raw' );

		echo "\n".'== DB =='."\n\n";
	}

	if($db_config)
	{
		if ( ! $clean )
		{
			echo '<pre>';
		}

		echo 'Config DB Username: '.$db_config['user']."\n".
			'Config DB Database: '.$db_config['name']."\n".
			 'Config DB Host: '.(isset($db_config['host']) ? $db_config['host'] : 'unset (localhost)')."\n".
			 'Config DB tables prefix: '.$tableprefix."\n".
			 'Config DB connection charset: '.$db_config['connection_charset']."\n";

		echo $clean ? "\n" : '</pre>';
	}

	if( !isset($DB) )
	{
		echo 'No DB object.'.( $clean ? "\n" : '' );
	}
	else
	{
		echo '<pre>Current DB charset: '.$DB->connection_charset."</pre>\n";

		$DB->dump_queries( ! $clean );
	}

	if ( ! $clean )
	{
		echo '</div>';
	}
}


/**
 * Exit when request is blocked
 *
 * @param string Block type: 'IP', 'Domain', 'Country'
 * @param string Log message
 * @param string Syslog origin type: 'core', 'plugin'
 * @param integer Syslog origin ID
 */
function exit_blocked_request( $block_type, $log_message, $syslog_origin_type = 'core', $syslog_origin_ID = NULL )
{
	global $debug;

	// Write system log for the request:
	syslog_insert( $log_message, 'warning', NULL, NULL, $syslog_origin_type, $syslog_origin_ID );

	// Print out this text to inform an user:
	echo 'This request has been blocked.';

	if( $debug )
	{ // Display additional info on debug mode:
		echo ' ('.$block_type.')';
	}

	// EXIT:
	exit( 0 );
}


/**
 * Check if the current request exceed the post max size limit.
 * If too much data was sent add an error message and call header redirect.
 */
function check_post_max_size_exceeded()
{
	global $Messages;

	if( ( $_SERVER['REQUEST_METHOD'] == 'POST' ) && empty( $_POST ) && empty( $_FILES ) && ( $_SERVER['CONTENT_LENGTH'] > 0 ) )
	{
		// Check post max size ini setting
		$post_max_size = ini_get( 'post_max_size' );

		// Convert post_max_size value to bytes
		switch ( substr( $post_max_size, -1 ) )
		{
			case 'G':
				$post_max_size = $post_max_size * 1024;
			case 'M':
				$post_max_size = $post_max_size * 1024;
			case 'K':
				$post_max_size = $post_max_size * 1024;
		}

		// Add error message and redirect back to the referer url
		$Messages->add( sprintf( T_('You have sent too much data (too many large files?) for the server to process (%s sent / %s maximum). Please try again by sending less data/files at a time.'), bytesreadable( $_SERVER['CONTENT_LENGTH'] ), bytesreadable( $post_max_size ) ) );
		header_redirect( $_SERVER['HTTP_REFERER'] );
		exit(0); // Already exited here
	}
}


/**
 * Prevent email header injection.
 */
function mail_sanitize_header_string( $header_str, $close_brace = false )
{
	// Prevent injection! (remove everything after (and including) \n or \r)
	$header_str = preg_replace( '~(\r|\n).*$~s', '', trim($header_str) );

	if( $close_brace && strpos( $header_str, '<' ) !== false && strpos( $header_str, '>' ) === false )
	{ // We have probably stripped the '>' at the end!
		$header_str .= '>';
	}

	return $header_str;
}

/**
 * Encode to RFC 1342 "Representation of Non-ASCII Text in Internet Message Headers"
 *
 * @param string
 * @param string 'Q' for Quoted printable, 'B' for base64
 */
function mail_encode_header_string( $header_str, $mode = 'Q' )
{
	global $evo_charset;

	/* mbstring way  (did not work for Alex RU)
	if( function_exists('mb_encode_mimeheader') )
	{ // encode subject
		$orig = mb_internal_encoding();
		mb_internal_encoding('utf-8');
		$r = mb_encode_mimeheader( $header_str, 'utf-8', $mode );
		mb_internal_encoding($orig);
		return $r;
	}
	*/

	if( preg_match( '~[^a-z0-9!*+\-/ ]~i', $header_str ) )
	{ // If the string actually needs some encoding
		if( $mode == 'Q' )
		{ // Quoted printable is best for reading with old/text terminal mail reading/debugging stuff:
			$header_str = preg_replace_callback( '#[^a-z0-9!*+\-/ ]#i', 'mail_encode_header_string_callback', $header_str );
			$header_str = str_replace( ' ', '_', $header_str );
			$header_str = '=?'.$evo_charset.'?Q?'.$header_str.'?=';
		}
		else
		{ // Base 64 -- Alex RU way:
			$header_str = '=?'.$evo_charset.'?B?'.base64_encode( $header_str ).'?=';
		}
	}

	return $header_str;
}


/**
 * Callback function for mail header encoding
 *
 * @param array Matches
 * @return string
 */
function mail_encode_header_string_callback( $matches )
{
	return sprintf( '=%02x', ord( stripslashes( $matches[0] ) ) );
}


/**
 * Get setting's value from General or User's settings
 *
 * @param integer User ID
 * @param string Setting ( email | name )
 * @return string Setting's value
 */
function user_get_notification_sender( $user_ID, $setting )
{
	global $Settings;

	$setting_name = 'notification_sender_'.$setting;

	if( empty( $user_ID ) )
	{	// Get value from general settings
		return $Settings->get( $setting_name );
	}

	$UserCache = & get_UserCache();
	if( $User = & $UserCache->get_by_ID( $user_ID ) )
	{
		if( $User->check_status( 'is_validated' ) )
		{	// User is Activated or Autoactivated
			global $UserSettings;
			if( $UserSettings->get( $setting_name, $user_ID ) == '' )
			{	// The user's setting is not defined yet
				// Update the user's setting from general setting
				$UserSettings->set( $setting_name, $Settings->get( $setting_name ), $user_ID );
				$UserSettings->dbupdate();
			}
			else
			{	// User has a defined setting; Use this
				return $UserSettings->get( $setting_name, $user_ID );
			}
		}
	}

	return $Settings->get( $setting_name );
}


/**
 * Sends an email, wrapping PHP's mail() function.
 * ALL emails sent by b2evolution must be sent through this function (for consistency and for logging)
 *
 * {@link $current_locale} will be used to set the charset.
 *
 * Note: we use a single \n as line ending, though it does not comply to {@link http://www.faqs.org/rfcs/rfc2822 RFC2822}, but seems to be safer,
 * because some mail transfer agents replace \n by \r\n automatically.
 *
 * @todo Unit testing with "nice addresses" This gets broken over and over again.
 *
 * @param string Recipient email address.
 * @param string Recipient name.
 * @param string Subject of the mail
 * @param string|array The message text OR Array: 'charset', 'full', 'html', 'text'
 * @param string From address, being added to headers (we'll prevent injections); see {@link http://securephp.damonkohler.com/index.php/Email_Injection}.
 *               Defaults to {@link GeneralSettings::get('notification_sender_email') } if NULL.
 * @param string From name.
 * @param array Additional headers ( headername => value ). Take care of injection!
 * @param integer User ID
 * @return boolean True if mail could be sent (not necessarily delivered!), false if not - (return value of {@link mail()})
 */
function send_mail( $to, $to_name, $subject, $message, $from = NULL, $from_name = NULL, $headers = array(), $user_ID = NULL )
{
	global $servertimenow, $email_send_simulate_only;

	// Stop a request from the blocked IP addresses or Domains
	antispam_block_request();

	global $debug, $app_name, $app_version, $current_locale, $current_charset, $evo_charset, $locales, $Debuglog, $Settings, $demo_mode;

	$message_data = $message;
	if( is_array( $message_data ) && isset( $message_data['full'] ) )
	{ // If content is multipart
		$message = $message_data['full'];
	}
	elseif( is_string( $message_data ) )
	{ // Convert $message_data to array
		$message_data = array( 'full' => $message );
	}

	// Replace secret content in the mail logs message body
	$message = preg_replace( '~\$secret_content_start\$.*\$secret_content_end\$~', '***secret-content-removed***', $message );
	// Remove secret content marks from the message
	$message_data = str_replace( array( '$secret_content_start$', '$secret_content_end$' ), '', $message_data );

	// Memorize email address
	$to_email_address = $to;

	$NL = "\r\n";

	if( $demo_mode )
	{ // Debug mode restriction: Sending email in demo mode is not allowed
		return false;
	}

	if( !is_array( $headers ) )
	{ // Make sure $headers is an array
		$headers = array( $headers );
	}

	if( empty( $from ) )
	{
		$from = user_get_notification_sender( $user_ID, 'email' );
	}

	if( empty( $from_name ) )
	{
		$from_name = user_get_notification_sender( $user_ID, 'name' );
	}

	// Pass these data for SMTP mailer
	$message_data['to_email'] = $to;
	$message_data['to_name'] = empty( $to_name ) ? NULL : $to_name;
	$message_data['from_email'] = $from;
	$message_data['from_name'] = empty( $from_name ) ? NULL : $from_name;

	$return_path = $Settings->get( 'notification_return_path' );

	// Add real name into $from...
	if( ! is_windows() )
	{	// fplanque: Windows XP, Apache 1.3, PHP 4.4, MS SMTP : will not accept "nice" addresses.
		if( !empty( $to_name ) )
		{
			$to = '"'.mail_encode_header_string($to_name).'" <'.$to.'>';
		}
		if( !empty( $from_name ) )
		{
			$from = '"'.mail_encode_header_string($from_name).'" <'.$from.'>';
		}
	}

	$from = mail_sanitize_header_string( $from, true );
	// From has to go into headers
	$headers['From'] = $from;
	if( !empty( $return_path ) )
	{	// Set a return path
		$headers['Return-Path'] = $return_path;
	}

	// echo 'sending email to: ['.htmlspecialchars($to).'] from ['.htmlspecialchars($from).']';

	$clear_subject = $subject;
	$subject = mail_encode_header_string($subject);

	$message = str_replace( array( "\r\n", "\r" ), $NL, $message );

	// Convert encoding of message (from internal encoding to the one of the message):
	// fp> why do we actually convert to $current_charset?
	// dh> I do not remember. Appears to make sense sending it unconverted in $evo_charset.
	// asimo> converting the message creates wrong output, no need for conversion, however this needs further investigation
	// $message = convert_charset( $message, $current_charset, $evo_charset );

	if( !isset( $headers['Content-Type'] ) )
	{	// Specify charset and content-type of email
		$headers['Content-Type'] = 'text/plain; charset='.$current_charset;
	}
	$headers['MIME-Version'] = '1.0';

	$headers['Date'] = gmdate( 'r', $servertimenow );

	// ADDITIONAL HEADERS:
	$headers['X-Mailer'] = $app_name.' '.$app_version.' - PHP/'.phpversion();
	$ip_list = implode( ',', get_ip_list() );
	if( !empty( $ip_list ) )
	{ // Add X-Remote_Addr param only if its value is not empty
		$headers['X-Remote-Addr'] = $ip_list;
	}

	// COMPACT HEADERS:
	$headerstring = get_mail_headers( $headers, $NL );

	// Set an additional parameter for the return path:
	switch( $Settings->get( 'sendmail_params' ) )
	{
		case 'return':
			$sendmail_params = '-r $return-address$';
			break;
		case 'from':
			$sendmail_params = '-f $return-address$';
			break;
		case 'custom':
			$sendmail_params = $Settings->get( 'sendmail_params_custom' );
			break;
	}
	if( ! empty( $sendmail_params ) )
	{
		$additional_parameters = str_replace(
			array( '$from-address$', '$return-address$' ),
			array( $message_data['from_email'], ( empty( $return_path ) ? $message_data['from_email'] : $return_path ) ),
			$sendmail_params );
	}
	else
	{
		$additional_parameters = '';
	}

	if( mail_is_blocked( $to_email_address ) )
	{ // Check if the email address is blocked
		$Debuglog->add( 'Sending mail to &laquo;'.htmlspecialchars( $to_email_address ).'&raquo; FAILED, because this email marked with spam or permanent errors.', 'error' );

		mail_log( $user_ID, $to_email_address, $clear_subject, $message, $headerstring, 'blocked' );

		return false;
	}

	if( $email_send_simulate_only )
	{	// The email sending is turned on simulation mode, Don't send a real message:
		$send_mail_result = true;
	}
	else
	{	// Send email message on real mode:
		$send_mail_result = evo_mail( $to, $subject, $message_data, $headers, $additional_parameters );
	}

	if( ! $send_mail_result )
	{	// The message has not been sent successfully
		if( $debug > 1 )
		{ // We agree to die for debugging...
			mail_log( $user_ID, $to_email_address, $clear_subject, $message, $headerstring, 'error' );

			debug_die( 'Sending mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo; FAILED.' );
		}
		else
		{ // Soft debugging only....
			$Debuglog->add( 'Sending mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo; FAILED.', 'error' );

			mail_log( $user_ID, $to_email_address, $clear_subject, $message, $headerstring, 'error' );

			return false;
		}
	}

	$Debuglog->add( 'Sent mail from &laquo;'.htmlspecialchars($from).'&raquo; to &laquo;'.htmlspecialchars($to).'&raquo;, Subject &laquo;'.htmlspecialchars($subject).'&raquo;.' );

	mail_log( $user_ID, $to_email_address, $clear_subject, $message, $headerstring, ( $email_send_simulate_only ? 'simulated' : 'ok' ) );

	return true;
}


/**
 * Sends an email to User
 *
 * @param integer Recipient ID.
 * @param string Subject of the mail
 * @param string Email template name
 * @param array Email template params
 * @param boolean Force to send this email even if the user is not activated. By default not activated user won't get emails.
 *                Pasword reset, and account activation emails must be always forced.
 * @param array Additional headers ( headername => value ). Take care of injection!
 * @param string Use this param if you want use different email address instead of $User->email
 * @return boolean True if mail could be sent (not necessarily delivered!), false if not - (return value of {@link mail()})
 */
function send_mail_to_User( $user_ID, $subject, $template_name, $template_params = array(), $force_on_non_activated = false, $headers = array(), $force_email_address = '' )
{
	global $UserSettings, $Settings, $current_charset;

	$UserCache = & get_UserCache();
	if( $User = $UserCache->get_by_ID( $user_ID ) )
	{
		if( !$User->check_status( 'can_receive_any_message' ) )
		{ // user status doesn't allow to receive nor emails nor private messages
			return false;
		}

		if( !( $User->check_status( 'is_validated' ) || $force_on_non_activated ) )
		{ // user is not activated and non activated users should not receive emails, unless force_on_non_activated is turned on
			return false;
		}

		// Check if a new email to User with the corrensponding email type is allowed
		switch( $template_name )
		{
			case 'account_activate':
				if( $Settings->get( 'validation_process' ) == 'easy' && !$template_params['is_reminder'] )
				{ // this is not a notification email
					break;
				}
			case 'private_message_new':
			case 'private_messages_unread_reminder':
			case 'post_new':
			case 'comment_new':
			case 'comment_spam':
			case 'account_activated':
			case 'account_closed':
			case 'account_reported':
			case 'account_changed':
				// this is a notificaiton email
				$email_limit_setting = 'notification_email_limit';
				$email_counter_setting = 'last_notification_email';
				if( !check_allow_new_email( $email_limit_setting, $email_counter_setting, $User->ID ) )
				{ // more notification email is not allowed today
					return false;
				}
				break;
			case 'newsletter':
				// this is a newsletter email
				$email_limit_setting = 'newsletter_limit';
				$email_counter_setting = 'last_newsletter';
				if( !check_allow_new_email( $email_limit_setting, $email_counter_setting, $User->ID ) )
				{ // more newsletter email is not allowed today
					return false;
				}
				break;
			case 'newsletter_test':
				// this is a newsletter email, used to send test email by current admin
				$template_name = 'newsletter';
				break;
		}

		// Update notification sender's info from General settings
		$User->update_sender();

		switch( $UserSettings->get( 'email_format', $User->ID ) )
		{	// Set Content-Type from user's setting "Email format"
			case 'auto':
				$template_params['boundary'] = 'b2evo-'.md5( rand() );
				$headers['Content-Type'] = 'multipart/mixed; boundary="'.$template_params['boundary'].'"';
				break;
			case 'html':
				$headers['Content-Type'] = 'text/html; charset='.$current_charset;
				break;
			case 'text':
				$headers['Content-Type'] = 'text/plain; charset='.$current_charset;
				break;
		}

		if( ! isset( $template_params['recipient_User'] ) )
		{ // Set recipient User, it should be defined for each template because of email footer
			$template_params['recipient_User'] = $User;
		}

		// Get a message text from template file
		$message = mail_template( $template_name, $UserSettings->get( 'email_format', $User->ID ), $template_params, $User );

		// Autoinsert user's data
		$subject = mail_autoinsert_user_data( $subject, $User );
		$message = mail_autoinsert_user_data( $message, $User );

		$to_email = !empty( $force_email_address ) ? $force_email_address : $User->email;

		if( send_mail( $to_email, NULL, $subject, $message, NULL, NULL, $headers, $user_ID ) )
		{ // email was sent, update last email settings;
			if( isset( $email_limit_setting, $email_counter_setting ) )
			{ // User Settings(email counters) need to be updated
				update_user_email_counter( $email_limit_setting, $email_counter_setting, $user_ID );
			}
			return true;
		}
	}

	// No user or email could not be sent
	return false;
}


/**
 * Autoinsert user's data into subject or message of the email
 *
 * @param string Text
 * @param object User
 * @return string Text
*/
function mail_autoinsert_user_data( $text, $User = NULL )
{
	if( !$User )
	{	// No user
		return $text;
	}

	$rpls_from = array( '$login$' , '$email$', '$user_ID$', '$unsubscribe_key$' );
	$rpls_to = array( $User->login, $User->email, $User->ID, '$secret_content_start$'.md5( $User->ID.$User->unsubscribe_key ).'$secret_content_end$' );

	return str_replace( $rpls_from, $rpls_to, $text );
}


/**
 * Get a mail message text by template name
 *
 * @param string Template name
 * @param string Email format ( auto | html | text )
 * @param array Params
 * @param object User
 * @return string|array Mail message OR Array of the email contents when message is multipart content
 */
function mail_template( $template_name, $format = 'auto', $params = array(), $User = NULL )
{
	global $current_charset;

	if( !empty( $params['locale'] ) )
	{ // Switch to locale for current email template
		locale_temp_switch( $params['locale'] );
	}

	// Set extension of template
	$template_exts = array();
	switch( $format )
	{
		case 'auto':
			// $template_exts['non-mime'] = '.txt.php'; // The area that is ignored by MIME-compliant clients
			$template_exts['text'] = '.txt.php';
			$template_exts['html'] = '.html.php';
			$boundary = $params['boundary'];
			$boundary_alt = 'b2evo-alt-'.md5( rand() );
			$template_headers = array(
					'text' => 'Content-Type: text/plain; charset='.$current_charset,
					'html' => 'Content-Type: text/html; charset='.$current_charset,
				);
			// Store all contents in this array for multipart message
			$template_contents = array(
					'charset' => $current_charset, // Charset for email message
					'full' => '', // Full content with html and plain
					'html' => '', // HTML
					'text' => '', // Plain text
				);
			break;

		case 'html':
			$template_exts['html'] = '.html.php';
			break;

		case 'text':
			$template_exts['text'] = '.txt.php';
			break;
	}

	$template_message = '';

	if( isset( $boundary, $boundary_alt ) )
	{ // Start new boundary content
		$template_message .= "\n".'--'.$boundary."\n";
		$template_message .= 'Content-Type: multipart/alternative; boundary="'.$boundary_alt.'"'."\n\n";
	}

	foreach( $template_exts as $format => $ext )
	{
		$formated_message = '';

		if( isset( $boundary, $boundary_alt ) && $format != 'non-mime' )
		{ // Start new boundary alt content
			$template_message .= "\n".'--'.$boundary_alt."\n";
		}

		if( isset( $template_headers[ $format ] ) )
		{ // Header data for each content
			$template_message .= $template_headers[ $format ]."\n\n";
		}

		// Get mail template
		ob_start();
		emailskin_include( $template_name.$ext, $params );
		$formated_message .= ob_get_clean();

		if( ! empty( $User ) )
		{ // Replace $login$ with gender colored link + icon in HTML format,
		  //   and with simple login text in PLAIN TEXT format
			if( $format == 'html' )
			{
				$username = $User->get_colored_login( array(
						'mask'      => '$avatar$ $login$',
						'login_text'=> 'name',
						'use_style' => true,
						'protocol'  => 'http:',
					) );

				$user_login = $User->get_colored_login( array(
						'mask'      => '$avatar$ $login$',
						'use_style' => true,
						'protocol'  => 'http:',
					) );
			}
			else
			{
				$username = $User->get_username();
				$user_login = $User->login;
			}
			$formated_message = str_replace( array( '$login$', '$username$' ), array( $user_login, $username ) , $formated_message );
		}

		$template_message .= $formated_message;
		if( isset( $template_contents ) )
		{ // Multipart content
			$template_contents[ $format ] = $formated_message;
		}
	}

	if( isset( $boundary, $boundary_alt ) )
	{ // End all boundary contents
		$template_message .= "\n".'--'.$boundary_alt.'--'."\n";
		$template_message .= "\n".'--'.$boundary.'--'."\n";
	}

	if( !empty( $params['locale'] ) )
	{ // Restore previous locale
		locale_restore_previous();
	}

	if( isset( $template_contents ) )
	{ // Return array for multipart content
		$template_contents['full'] = $template_message;
		return $template_contents;
	}
	else
	{ // Return string if email message contains one content (html or text)
		return $template_message;
	}
}


/**
 * Include email template from folder /skins_email/custom/ or /skins_email/
 *
 * @param string Template name
 * @param array Params
 */
function emailskin_include( $template_name, $params = array() )
{
	global $emailskins_path, $rsc_url;

	/**
	* @var Log
	*/
	global $Debuglog;
	global $Timer;

	$timer_name = 'emailskin_include('.$template_name.')';
	$Timer->resume( $timer_name );

	$is_customized = false;

	// Try to include custom template firstly
	$template_path = $emailskins_path.'custom/'.$template_name;
	if( file_exists( $template_path ) )
	{ // Include custom template file if it exists
		$Debuglog->add( 'emailskin_include: '.rel_path_to_base( $template_path ), 'skins' );
		require $template_path;
		// This template is customized, Don't include standard template
		$is_customized = true;
	}

	if( !$is_customized )
	{ // Try to include standard template only if custom template doesn't exist
		$template_path = $emailskins_path.$template_name;
		if( file_exists( $template_path ) )
		{ // Include standard template file if it exists
			$Debuglog->add( 'emailskin_include: '.rel_path_to_base( $template_path ), 'skins' );
			require $template_path;
		}
	}

	$Timer->pause( $timer_name );
}


/**
 * Get attribute "style" by class name for element in email templates
 *
 * @param string Class name
 * @param boolean TRUE to return string as ' style="css_properties"' otherwise only 'css_properties'
 * @return string
 */
function emailskin_style( $class, $set_attr_name = true )
{
	global $emailskins_styles;

	if( ! is_array( $emailskins_styles ) )
	{ // Load email styles only first time
		global $emailskins_path;
		require_once $emailskins_path.'_email_style.php';

		foreach( $emailskins_styles as $classes => $styles )
		{
			if( strpos( $classes, ',' ) !== false )
			{ // This style is used for several classes
				unset( $emailskins_styles[ $classes ] );
				$classes = explode( ',', $classes );
				foreach( $classes as $class_name )
				{
					$class_name = trim( $class_name );
					if( isset( $emailskins_styles[ $class_name ] ) )
					{
						$emailskins_styles[ $class_name ] .= $styles;
					}
					else
					{
						$emailskins_styles[ $class_name ] = $styles;
					}
				}
			}
		}
	}

	if( strpos( $class, '+' ) !== false )
	{ // Several classes should be applied this
		$classes = explode( '+', $class );
		$style = '';
		foreach( $classes as $c => $class )
		{
			$style .= emailskin_style( $class, false );
		}

		return empty( $style ) ? '' : ( $set_attr_name ? ' style="'.$style.'"' : $style );
	}
	elseif( isset( $emailskins_styles[ $class ] ) )
	{ // One class
		$style = trim( str_replace( array( "\r", "\n", "\t" ), '', $emailskins_styles[ $class ] ) );
		$style = str_replace( ': ', ':', $style );

		return $set_attr_name ? ' style="'.$style.'"' : $style;
	}

	return '';
}


/**
 * If first parameter evaluates to true printf() gets called using the first parameter
 * as args and the second parameter as print-pattern
 *
 * @param mixed variable to test and output if it's true or $disp_none is given
 * @param string printf-pattern to use (%s gets replaced by $var)
 * @param string printf-pattern to use, if $var is numeric and > 1 (%s gets replaced by $var)
 * @param string printf-pattern to use if $var evaluates to false (%s gets replaced by $var)
 */
function disp_cond( $var, $disp_one, $disp_more = NULL, $disp_none = NULL )
{
	if( is_numeric($var) && $var > 1 )
	{
		printf( ( $disp_more === NULL ? $disp_one : $disp_more ), $var );
		return true;
	}
	elseif( $var )
	{
		printf( $disp_one, $var );
		return true;
	}
	else
	{
		if( $disp_none !== NULL )
		{
			printf( $disp_none, $var );
			return false;
		}
	}
}


/**
 * Create IMG tag for an action icon.
 *
 * @param string TITLE text (IMG and A link)
 * @param string icon code for {@link get_icon()}
 * @param string URL where the icon gets linked to (empty to not wrap the icon in a link)
 * @param string word to be displayed after icon (if no icon gets displayed, $title will be used instead!)
 * @param integer 1-5: weight of the icon. The icon will be displayed only if its weight is >= than the user setting threshold.
 *                     Use 5, if it's a required icon - all others could get disabled by the user. (Default: 4)
 * @param integer 1-5: weight of the word. The word will be displayed only if its weight is >= than the user setting threshold.
 *                     (Default: 1)
 * @param array Additional attributes to the A tag. The values must be properly encoded for html output (e.g. quotes).
 *        It may also contain these params:
 *         - 'use_js_popup': if true, the link gets opened as JS popup. You must also pass an "id" attribute for this!
 *         - 'use_js_size': use this to override the default popup size ("500, 400")
 *         - 'class': defaults to 'action_icon', if not set; use "" to not use it
 * @param array Attributes for the icon
 * @return string The generated action icon link.
 */
function action_icon( $title, $icon, $url, $word = NULL, $icon_weight = NULL, $word_weight = NULL, $link_attribs = array(), $icon_attribs = array() )
{
	global $UserSettings;

	$link_attribs['href'] = $url;
	$link_attribs['title'] = $title;

	if( is_null($icon_weight) )
	{
		$icon_weight = 4;
	}
	if( is_null($word_weight) )
	{
		$word_weight = 1;
	}

	if( ! isset($link_attribs['class']) )
	{
		$link_attribs['class'] = 'action_icon';
	}

	if( get_icon( $icon, 'rollover' ) )
	{
		if( empty($link_attribs['class']) )
		{
			$link_attribs['class'] = 'rollover';
		}
		else
		{
			$link_attribs['class'] .= ' rollover';
		}

		if( get_icon( $icon, 'sprite' ) )
		{ // Set class "rollover_sprite" If image uses sprite
			$link_attribs['class'] .= '_sprite';
		}
	}
	//$link_attribs['class'] .= $icon != '' ? ' '.$icon : ' noicon';

	// "use_js_popup": open link in a JS popup
	// TODO: this needs to be rewritten with jQuery instead
	if( false && ! empty($link_attribs['use_js_popup']) )
	{
		$popup_js = 'var win = new PopupWindow(); win.setUrl( \''.$link_attribs['href'].'\' ); win.setSize(  ); ';

		if( isset($link_attribs['use_js_size']) )
		{
			if( ! empty($link_attribs['use_js_size']) )
			{
				$popup_size = $link_attribs['use_js_size'];
			}
		}
		else
		{
			$popup_size = '500, 400';
		}
		if( isset($popup_size) )
		{
			$popup_js .= 'win.setSize( '.$popup_size.' ); ';
		}
		$popup_js .= 'win.showPopup(\''.$link_attribs['id'].'\'); return false;';

		if( empty( $link_attribs['onclick'] ) )
		{
			$link_attribs['onclick'] = $popup_js;
		}
		else
		{
			$link_attribs['onclick'] .= $popup_js;
		}
		unset($link_attribs['use_js_popup']);
		unset($link_attribs['use_js_size']);
	}

	$display_icon = empty( $UserSettings ) ? false : ($icon_weight >= $UserSettings->get('action_icon_threshold'));
	$display_word = empty( $UserSettings ) ? false : ($word_weight >= $UserSettings->get('action_word_threshold'));

	$a_body = '';

	if( $display_icon || ! $display_word )
	{	// We MUST display an action icon in order to make the user happy:
		// OR we default to icon because the user doesn't want the word either!!

		$icon_attribs = array_merge( array(
				'title' => $title
			), $icon_attribs );

		if( $icon_s = get_icon( $icon, 'imgtag', $icon_attribs, true ) )
		{
			$a_body .= $icon_s;
		}
		else
		{ // fallback to word
			$display_word = true;
		}
	}

	if( $display_word )
	{	// We MUST display an action word in order to make the user happy:

		if( $display_icon )
		{ // We already have an icon, display a SHORT word:
			if( !empty($word) )
			{	// We have provided a short word:
				$a_body .= $word;
			}
			else
			{	// We fall back to alt:
				$a_body .= get_icon( $icon, 'legend' );
			}
		}
		else
		{	// No icon display, let's display a LONG word/text:
			$a_body .= trim( $title, ' .!' );
		}

		// Add class "hoverlink" for icon with text
		$link_attribs['class'] .= ' hoverlink';
	}


	// NOTE: We do not use format_to_output with get_field_attribs_as_string() here, because it interferes with the Results class (eval() fails on entitied quotes..) (blueyed)
	return '<a'.get_field_attribs_as_string( $link_attribs, false ).'>'.$a_body.'</a>';
}


/**
 * Get properties of an icon.
 *
 * Note: to get a file type icon, use {@link File::get_icon()} instead.
 *
 * @uses get_icon_info()
 * @param string icon for what? (key)
 * @param string what to return for that icon ('imgtag', 'alt', 'legend', 'file', 'url', 'size' {@link imgsize()})
 * @param array additional params
 *   - 'class' => class name when getting 'imgtag',
 *   - 'size' => param for 'size',
 *   - 'title' => title attribute for 'imgtag'
 * @param boolean true to include this icon into the legend at the bottom of the page (works for 'imgtag' only)
 * @return mixed False on failure, string on success.
 */
function get_icon( $iconKey, $what = 'imgtag', $params = NULL, $include_in_legend = false )
{
	global $admin_subdir, $Debuglog, $use_strict;
	global $conf_path;
	global $rsc_path, $rsc_uri;

	if( ! function_exists('get_icon_info') )
	{
		require_once $conf_path.'_icons.php';
	}

	$icon = get_icon_info($iconKey);
	if( ! $icon )
	{
		$Debuglog->add('No image defined for '.var_export( $iconKey, true ).'!', 'icons');
		return false;
	}

	if( !isset( $icon['file'] ) && $what != 'imgtag' )
	{
		$icon['file'] = 'icons/icons_sprite.png';
	}

	switch( $what )
	{
		case 'rollover':
			if( isset( $icon['rollover'] ) )
			{ // Image has rollover available
				global $b2evo_icons_type;

				if( isset( $b2evo_icons_type ) && ( ! empty( $icon['glyph'] ) || ! empty( $icon['fa'] ) ) )
				{ // Glyph and font-awesome icons don't have rollover effect
					return false;
				}
				return $icon['rollover'];
			}
			return false;
			/* BREAK */


		case 'file':
			return $rsc_path.$icon['file'];
			/* BREAK */


		case 'alt':
			if( isset( $icon['alt'] ) )
			{ // alt tag from $map_iconfiles
				return $icon['alt'];
			}
			else
			{ // fallback to $iconKey as alt-tag
				return $iconKey;
			}
			/* BREAK */


		case 'legend':
			if( isset( $icon['legend'] ) )
			{ // legend tag from $map_iconfiles
				return $icon['legend'];
			}
			else
			if( isset( $icon['alt'] ) )
			{ // alt tag from $map_iconfiles
				return $icon['alt'];
			}
			else
			{ // fallback to $iconKey as alt-tag
				return $iconKey;
			}
			/* BREAK */


		case 'class':
			if( isset($icon['class']) )
			{
				return $icon['class'];
			}
			else
			{
				return '';
			}
			/* BREAK */

		case 'url':
			return $rsc_uri.$icon['file'];
			/* BREAK */

		case 'size':
			if( !isset( $icon['size'] ) )
			{
				$Debuglog->add( 'No iconsize for ['.$iconKey.']', 'icons' );

				$icon['size'] = imgsize( $rsc_path.$icon['file'] );
			}

			switch( $params['size'] )
			{
				case 'width':
					return $icon['size'][0];

				case 'height':
					return $icon['size'][1];

				case 'widthxheight':
					return $icon['size'][0].'x'.$icon['size'][1];

				case 'width':
					return $icon['size'][0];

				case 'string':
					return 'width="'.$icon['size'][0].'" height="'.$icon['size'][1].'"';

				default:
					return $icon['size'];
			}
			/* BREAK */


		case 'xy':
			if( isset( $icon['xy'] ) )
			{ // Return data for style property "background-position"
				return "-".$icon['xy'][0]."px -".$icon['xy'][1]."px";
			}
			return false;


		case 'sprite':
			if( isset( $icon['xy'] ) )
			{	// Image uses spite file
				return true;
			}
			return false;
			/* BREAK */


		case 'imgtag':
			global $b2evo_icons_type;

			if( isset( $b2evo_icons_type ) )
			{ // Specific icons type is defined
				$current_icons_type = $b2evo_icons_type;
				if( $current_icons_type == 'fontawesome-glyphicons' )
				{ // Use fontawesome icons as a priority over the glyphicons
					$current_icons_type = isset( $icon['fa'] ) ? 'fontawesome' : 'glyphicons';
				}
				switch( $current_icons_type )
				{
					case 'glyphicons':
						// Use glyph icons of bootstrap
						$icon_class_prefix = 'glyphicon glyphicon-';
						$icon_param_name = 'glyph';
						$icon_content = '&nbsp;';
						break;

					case 'fontawesome':
						// Use the icons from http://fortawesome.github.io/Font-Awesome/icons/
						$icon_class_prefix = 'fa fa-';
						$icon_param_name = 'fa';
						$icon_content = '';
						break;
				}
			}

			if( isset( $icon_class_prefix ) && ! empty( $icon[ $icon_param_name ] ) )
			{ // Use glyph or fa icon if it is defined in icons config
				if( isset( $params['class'] ) )
				{ // Get class from params
					$params['class'] = $icon_class_prefix.$icon[ $icon_param_name ].' '.$params['class'];
				}
				else
				{ // Set default class
					$params['class'] = $icon_class_prefix.$icon[ $icon_param_name ];
				}

				$styles = array();
				if( isset( $icon['color-'.$icon_param_name] ) )
				{ // Set a color for icon only for current type
					if( $icon['color-'.$icon_param_name] != 'default' )
					{
						$styles[] = 'color:'.$icon['color-'.$icon_param_name];
					}
				}
				elseif( isset( $icon['color'] ) )
				{ // Set a color for icon for all types
					if( $icon['color'] != 'default' )
					{
						$styles[] = 'color:'.$icon['color'];
					}
				}
				if( isset( $icon['color-over'] ) )
				{ // Set a color for mouse over event
					$params['data-color'] = $icon['color-over'];
				}
				if( isset( $icon['toggle-'.$icon_param_name] ) )
				{ // Set a color for mouse over event
					$params['data-toggle'] = $icon['toggle-'.$icon_param_name];
				}

				if( ! isset( $params['title'] ) )
				{ // Use 'alt' for 'title'
					if( isset( $params['alt'] ) )
					{
						$params['title'] = $params['alt'];
						unset( $params['alt'] );
					}
					else if( ! isset( $params['alt'] ) && isset( $icon['alt'] ) )
					{
						$params['title'] = $icon['alt'];
					}
				}

				if( isset( $icon['size-'.$icon_param_name] ) )
				{ // Set a size for icon only for current type
					if( isset( $icon['size-'.$icon_param_name][0] ) )
					{ // Width
						$styles['width'] = 'width:'.$icon['size-'.$icon_param_name][0].'px';
					}
					if( isset( $icon['size-'.$icon_param_name][1] ) )
					{ // Height
						$styles['height'] = 'height:'.$icon['size-'.$icon_param_name][1].'px';
					}
				}

				if( isset( $params['style'] ) )
				{ // Keep styles from params
					$styles[] = $params['style'];
				}
				if( ! empty( $styles ) )
				{ // Init attribute 'style'
					$params['style'] = implode( ';', $styles );
				}

				// Add all the attributes:
				$params = get_field_attribs_as_string( $params, false );

				$r = '<span'.$params.'>'.$icon_content.'</span>';
			}
			elseif( ! isset( $icon['file'] ) )
			{ // Use span tag with sprite instead of img
				$styles = array();

				if( isset( $params['xy'] ) )
				{ // Get background position from params
					$styles[] = "background-position: ".$params['xy'][0]."px ".$params['xy'][1]."px";
					unset( $params['xy'] );
				}
				else if( isset( $icon['xy'] ) )
				{ // Set background position in the icons_sprite.png
					$styles[] = "background-position: -".$icon['xy'][0]."px -".$icon['xy'][1]."px";
				}

				if( isset( $params['size'] ) )
				{ // Get sizes from params
					$icon['size'] = $params['size'];
					unset( $params['size'] );
				}
				if( isset( $icon['size'] ) )
				{ // Set width & height
					if( $icon['size'][0] != 16 )
					{
						$styles[] = "width: ".$icon['size'][0]."px";
					}
					if( $icon['size'][1] != 16 )
					{
						$styles[] = "height: ".$icon['size'][1]."px; line-height: ".$icon['size'][1]."px";
					}
				}

				if( isset( $params['style'] ) )
				{ // Get styles from params
					$styles[] = $params['style'];
				}
				if( count( $styles ) > 0 )
				{
					$params['style'] = implode( '; ', $styles);
				}

				if( ! isset( $params['title'] ) )
				{	// Use 'alt' for 'title'
					if( isset( $params['alt'] ) )
					{
						$params['title'] = $params['alt'];
						unset( $params['alt'] );
					}
					else if( ! isset( $params['alt'] ) && isset( $icon['alt'] ) )
					{
						$params['title'] = $icon['alt'];
					}
				}

				if( isset( $params['class'] ) )
				{	// Get class from params
					$params['class'] = 'icon '.$params['class'];
				}
				else
				{	// Set default class
					$params['class'] = 'icon';
				}

				// Add all the attributes:
				$params = get_field_attribs_as_string( $params, false );

				$r = '<span'.$params.'>&nbsp;</span>';
			}
			else
			{ // Use img tag
				$r = '<img src="'.$rsc_uri.$icon['file'].'" ';

				if( !$use_strict )
				{	// Include non CSS fallbacks - transitional only:
					$r .= 'border="0" align="top" ';
				}

				// Include class (will default to "icon"):
				if( ! isset( $params['class'] ) )
				{
					if( isset($icon['class']) )
					{	// This icon has a class
						$params['class'] = $icon['class'];
					}
					else
					{
						$params['class'] = '';
					}
				}

				// Include size (optional):
				if( isset( $icon['size'] ) )
				{
					$r .= 'width="'.$icon['size'][0].'" height="'.$icon['size'][1].'" ';
				}

				// Include alt (XHTML mandatory):
				if( ! isset( $params['alt'] ) )
				{
					if( isset( $icon['alt'] ) )
					{ // alt-tag from $map_iconfiles
						$params['alt'] = $icon['alt'];
					}
					else
					{ // $iconKey as alt-tag
						$params['alt'] = $iconKey;
					}
				}

				// Add all the attributes:
				$r .= get_field_attribs_as_string( $params, false );

				// Close tag:
				$r .= '/>';


				if( $include_in_legend && ( $IconLegend = & get_IconLegend() ) )
				{ // This icon should be included into the legend:
					$IconLegend->add_icon( $iconKey );
				}
			}
			return $r;
			/* BREAK */

		case 'noimg':
			global $b2evo_icons_type;

			if( isset( $b2evo_icons_type ) )
			{ // Specific icons type is defined
				$current_icons_type = $b2evo_icons_type;
				if( $current_icons_type == 'fontawesome-glyphicons' )
				{ // Use fontawesome icons as a priority over the glyphicons
					$current_icons_type = isset( $icon['fa'] ) ? 'fontawesome' : 'glyphicons';
				}
				switch( $current_icons_type )
				{
					case 'glyphicons':
						// Use glyph icons of bootstrap
						$icon_param_name = 'glyph';
						break;

					case 'fontawesome':
						// Use the icons from http://fortawesome.github.io/Font-Awesome/icons/
						$icon_param_name = 'fa';
						break;
				}
			}

			$styles = array();
			if( isset( $icon_param_name ) && ! empty( $icon[ $icon_param_name ] ) )
			{ // Use glyph or fa icon if it is defined in icons config
				if( isset( $icon['size-'.$icon_param_name] ) )
				{ // Set a size for icon only for current type
					if( isset( $icon['size-'.$icon_param_name][0] ) )
					{ // Width
						$styles['width'] = 'width:'.$icon['size-'.$icon_param_name][0].'px';
					}
					if( isset( $icon['size-'.$icon_param_name][1] ) )
					{ // Height
						$styles['width'] = 'height:'.$icon['size-'.$icon_param_name][1].'px';
					}
					if( isset( $icon['size'] ) )
					{ // Unset size for sprite icon
						unset( $icon['size'] );
					}
				}
			}

			// Include size (optional):
			if( isset( $icon['size'] ) )
			{
				$params['size'] = $icon['size'];
			}
			$styles[] = 'margin:0 2px';

			if( isset( $params['style'] ) )
			{ // Keep styles from params
				$styles[] = $params['style'];
			}
			if( ! empty( $styles ) )
			{ // Init attribute 'style'
				$params['style'] = implode( ';', $styles );
			}

			return get_icon( 'pixel', 'imgtag', $params );
			/* BREAK */
			/*
			$blank_icon = get_icon_info('pixel');

			$r = '<img src="'.$rsc_uri.$blank_icon['file'].'" ';

			// TODO: dh> add this only for !$use_strict, like above?
			// Include non CSS fallbacks (needed by bozos... and basic skin):
			$r .= 'border="0" align="top" ';

			// Include class (will default to "noicon"):
			if( ! isset( $params['class'] ) )
			{
				if( isset($icon['class']) )
				{	// This icon has a class
					$params['class'] = $icon['class'];
				}
				else
				{
					$params['class'] = 'no_icon';
				}
			}

			// Include size (optional):
			if( isset( $icon['size'] ) )
			{
				$r .= 'width="'.$icon['size'][0].'" height="'.$icon['size'][1].'" ';
			}

			// Include alt (XHTML mandatory):
			if( ! isset( $params['alt'] ) )
			{
				$params['alt'] = '';
			}

			// Add all the attributes:
			$r .= get_field_attribs_as_string( $params, false );

			// Close tag:
			$r .= '/>';

			return $r;*/
			/* BREAK */
	}
}


/**
 * @param string date (YYYY-MM-DD)
 * @param string time
 */
function form_date( $date, $time = '' )
{
	return substr( $date.'          ', 0, 10 ).' '.$time;
}


/**
 * Get list of client IP addresses from REMOTE_ADDR and HTTP_X_FORWARDED_FOR,
 * in this order. '' is used when no IP could be found.
 *
 * @param boolean True, to get only the first IP (probably REMOTE_ADDR)
 * @param boolean True, to convert IPv6 to IPv4 format
 * @return array|string Depends on first param.
 */
function get_ip_list( $firstOnly = false, $convert_to_ipv4 = false )
{
	$r = array();

	if( ! empty( $_SERVER['REMOTE_ADDR'] ) )
	{
		foreach( explode( ',', $_SERVER['REMOTE_ADDR'] ) as $l_ip )
		{
			$l_ip = trim( $l_ip );
			if( ! empty( $l_ip ) )
			{
				if( $convert_to_ipv4 )
				{ // Convert IP address to IPv4 format(if it is in IPv6 format)
					$l_ip = int2ip( ip2int( $l_ip ) );
				}
				$r[] = $l_ip;
			}
		}
	}

	if( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
	{ // IP(s) behind Proxy - this can be easily forged!
		foreach( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $l_ip )
		{
			$l_ip = trim( $l_ip );
			if( ! empty( $l_ip ) && $l_ip != 'unknown' )
			{
				if( $convert_to_ipv4 )
				{ // Convert IP address to IPv4 format(if it is in IPv6 format)
					$l_ip = int2ip( ip2int( $l_ip ) );
				}
				$r[] = $l_ip;
			}
		}
	}

	if( ! isset( $r[0] ) )
	{ // No IP found.
		$r[] = '';
	}

	// Remove the duplicates
	$r = array_unique( $r );

	return $firstOnly ? $r[0] : $r;
}


/**
 * Get list of IP addresses with link to back-office page if User has an access
 *
 * @param object|NULL User
 * @param array|NULL List of IP addresses
 * @param string Text of link, Use '#' to display IP address
 * @return array List of IP addresses
 */
function get_linked_ip_list( $ip_list = NULL, $User = NULL, $link_text = '#' )
{
	if( $User === NULL )
	{ // Get current User by default
		global $current_User;
		$User = & $current_User;
	}

	if( $ip_list === NULL )
	{ // Get IP addresses by function get_ip_list()
		$ip_list = get_ip_list( false, true );
	}

	if( ! empty( $User ) &&
	    $User->check_perm( 'admin', 'restricted' ) &&
	    $User->check_perm( 'spamblacklist', 'view' ) )
	{ // User has an access to backoffice, Display a link for each IP address
		global $admin_url;
		foreach( $ip_list as $i => $ip_address )
		{
			if( $link_text == '#' )
			{ // Use IP address aslink text
				$link_text = $ip_address;
			}
			$ip_list[ $i ] = '<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;ip_address='.$ip_address.'">'.$link_text.'</a>';
		}
	}

	return $ip_list;
}


/**
 * Get the base domain (without protocol and any subdomain) of an URL.
 *
 * Gets a max of 3 domain parts (x.y.tld)
 *
 * @param string URL
 * @return string the base domain (may become empty, if found invalid)
 */
function get_base_domain( $url )
{
	global $evo_charset;

	// Chop away the protocol part(http,htpps,ftp) and the path:
	$domain = preg_replace( '~^([a-z]+://)?([^:/#]+)(.*)$~i', '\\2', $url );

	if( empty( $domain ) || preg_match( '~^(\d+\.)+\d+$~', $domain ) )
	{	// Empty or All numeric = IP address, don't try to cut it any further:
		return $domain;
	}

	// Get the base domain up to 2 or 3 levels (x.y.tld):
	// NOTE: "_" is not really valid, but for Windows it is..
	// NOTE: \w includes "_"

	// Convert URL to IDN:
	$domain = idna_encode( $domain );

	if( preg_match( '~\.(com|net|org|int|edu|gov|mil)$~i', $domain ) )
	{	// Use max 2 level domain for very well known TLDs:
		// (for example: "sub3.sub2.sub1.domain.com" will be "domain.com")
		$max_level = 2;
	}
	else
	{	// Use max 3 level domain for all others:
		// (for example: "sub3.sub2.sub1.domain.fr" will be "sub1.domain.fr")
		$max_level = 3;
	}

	// Limit domain by 2 or 3 level depending on TLD:
	if( ! preg_match( '~ ( \w (\w|-|_)* \. ){0,'.( $max_level - 1 ).'}   \w (\w|-|_)* $~ix', $domain, $match ) )
	{	// Return an empty if domain doesn't match to proper format:
		return '';
	}

	// Convert all symbols of domain name to UTF-8:
	$domain = convert_charset( idna_decode( $match[0] ), $evo_charset, 'UTF-8' );

	// Remove any prefix like "www.", "www2.", "www9999." and etc.:
	$domain = preg_replace( '~^www[0-9]*\.~i', '', $domain );

	return $domain;
}


/**
 * Generate a valid key of size $length.
 *
 * @param integer length of key
 * @param string chars to use in generated key
 * @return string key
 */
function generate_random_key( $length = 32, $keychars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' )
{
	$key = '';
	$rnd_max = strlen($keychars) - 1;

	for( $i = 0; $i < $length; $i++ )
	{
		$key .= $keychars{mt_rand(0, $rnd_max)}; // get a random character out of $keychars
	}

	return $key;
}


/**
 * Generate a random password with no ambiguous chars
 *
 * @param integer length of password
 * @return string password
 */
function generate_random_passwd( $length = NULL )
{
	// fp> NOTE: do not include any characters that would make autogenerated passwords ambiguous
	// 1 (one) vs l (L) vs I (i)
	// O (letter) vs 0 (digit)

	if( empty($length) )
	{
		$length = rand( 8, 14 );
	}

	return generate_random_key( $length, 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789' );
}


function is_create_action( $action )
{
	$action_parts = explode( '_', $action );

	switch( $action_parts[0] )
	{
		case 'new':
		case 'new_switchtab':
		case 'copy':
		case 'create':	// we return in this state after a validation error
		case 'preview':
			return true;

		case 'edit':
		case 'edit_switchtab':
		case 'update':	// we return in this state after a validation error
		case 'delete':
		// The following one's a bit far fetched, but can happen if we have no sheet display:
		case 'unlink':
		case 'view':
		case 'extract':
			return false;

		default:
			debug_die( 'Unhandled action in form: '.strip_tags($action_parts[0]) );
	}
}


/**
 * Compact a date in a number keeping only integer value of the string
 *
 * @param string date
 */
function compact_date( $date )
{
	return preg_replace( '#[^0-9]#', '', $date );
}


/**
 * Decompact a date in a date format ( Y-m-d h:m:s )
 *
 * @param string date
 */
function decompact_date( $date )
{
	$date0 = $date;

	return  substr($date0,0,4).'-'.substr($date0,4,2).'-'.substr($date0,6,2).' '
								.substr($date0,8,2).':'.substr($date0,10,2).':'.substr($date0,12,2);
}

/**
 * Check the format of the phone number param and
 * format it in a french number if it is.
 *
 * @param string phone number
 */
function format_phone( $phone, $hide_country_dialing_code_if_same_as_locale = true )
{
	global $CountryCache;

	$dialing_code = NULL;

	if( substr( $phone, 0, 1 ) == '+' )
	{	// We have a dialing code in the phone, so we extract it:
		$dialing_code = $CountryCache->extract_country_dialing_code( substr( $phone, 1 ) );
	}

	if( !is_null( $dialing_code ) && ( locale_dialing_code() == $dialing_code )
			&& $hide_country_dialing_code_if_same_as_locale )
	{	// The phone dialing code is same as locale and we want to hide it in this case
		if( ( strlen( $phone ) - strlen( $dialing_code ) ) == 10 )
		{	// We can format it like a french phone number ( 0x.xx.xx.xx.xx )
			$phone_formated = format_french_phone( '0'.substr( $phone, strlen( $dialing_code )+1 ) );
		}
		else
		{ // ( 0xxxxxxxxxxxxxx )
			$phone_formated = '0'.substr( $phone, strlen( $dialing_code )+1 );
		}

	}
	elseif( !is_null( $dialing_code ) )
	{	// Phone has a dialing code
		if( ( strlen( $phone ) - strlen( $dialing_code ) ) == 10 )
		{ // We can format it like a french phone number with the dialing code ( +dialing x.xx.xx.xx.xx )
			$phone_formated = '+'.$dialing_code.format_french_phone( ' '.substr( $phone, strlen( $dialing_code )+1 ) );
		}
		else
		{ // ( +dialing  xxxxxxxxxxx )
			$phone_formated = '+'.$dialing_code.' '.substr( $phone, strlen( $dialing_code )+1 );
		}
	}
	else
	{
		if( strlen( $phone ) == 10 )
		{ //  We can format it like a french phone number ( xx.xx.xx.xx.xx )
			$phone_formated = format_french_phone( $phone );
		}
		else
		{	// We don't format phone: TODO generic format phone ( xxxxxxxxxxxxxxxx )
			$phone_formated = $phone;
		}
	}

	return $phone_formated;
}


/**
 * Format a string in a french phone number
 *
 * @param string phone number
 */
function format_french_phone( $phone )
{
	return substr($phone, 0 , 2).'.'.substr($phone, 2, 2).'.'.substr($phone, 4, 2)
					.'.'.substr($phone, 6, 2).'.'.substr($phone, 8, 2);
}


/**
 * Get the manual url for the given topic
 *
 * @param string topic
 * @return string url to the manual
 */
function get_manual_url( $topic )
{
	// fp> TODO: this below is a temmporary hack while we work on the new manual:
	return 'http://b2evolution.net/man/'.str_replace( '_', '-', strtolower( $topic ) );
}


/**
 * Generate a link to a online help resource.
 * testing the concept of online help (aka webhelp).
 * this function should be relocated somewhere better if it is taken onboard by the project
 *
 * @todo replace [?] with icon,
 * @todo write url suffix dynamically based on topic and language
 *
 * QUESTION: launch new window with javascript maybe?
 * @param string Topic
 *        The topic should be in a format like [\w]+(/[\w]+)*, e.g features/online_help.
 * @param string link text, leave it NULL to get link with manual icon
 * @param string a word to be displayed after the manual icon (if no icon gets displayed, $title will be used instead!)
 * @param integer 1-5: weight of the word. The word will be displayed only if its weight is >= than the user setting threshold. (Default: 1)
 * @return string
 */
function get_manual_link( $topic, $link_text = NULL, $action_word = NULL, $word_weight = 1 )
{
	global $online_help_links;

	if( $online_help_links )
	{
		$manual_url = get_manual_url( $topic );

		if( $link_text == NULL )
		{
			if( $action_word == NULL )
			{
				$action_word = T_('Manual');
			}
			$webhelp_link = action_icon( T_('Open relevant page in online manual'), 'manual', $manual_url, $action_word, 5, $word_weight, array( 'target' => '_blank' ) );
		}
		else
		{
			$webhelp_link = '<a href="'.$manual_url.'" target = "_blank">'.$link_text.'</a>';
		}

		return ' '.$webhelp_link;
	}
	else
	{
		return '';
	}
}


/**
 * Build a string out of $field_attribs, with each attribute
 * prefixed by a space character.
 *
 * @param array Array of field attributes.
 * @param boolean Use format_to_output() for the attributes?
 * @return string
 */
function get_field_attribs_as_string( $field_attribs, $format_to_output = true )
{
	$r = '';
	foreach( $field_attribs as $l_attr => $l_value )
	{
		if( $l_value === NULL )
		{ // don't generate empty attributes (it may be NULL if we pass 'value' => NULL as field_param for example, because isset() does not match it!)
			// sam2kb> what about alt="" how do we handle this?
			// I've removed the "=== ''" check now. Should not do any harm. IIRC NULL is what we want to avoid here.
			continue;
		}

		if( $format_to_output )
		{
			$r .= ' '.$l_attr.'="'.format_to_output( $l_value, 'formvalue' ).'"';
		}
		else
		{
			$r .= ' '.$l_attr.'="'.$l_value.'"';
		}
	}

	return $r;
}


/**
 * Update values of HTML tag attributes
 *
 * @param string HTML tag
 * @param array Attributes
 * @param array Actions for each attribute:
 *              'append'  - Append to existing attribute value (Default for all)
 *              'skip'    - Skip if attribute already exists
 *              'replace' - Replace attribute to new value completely
 * @return string Updated HTML tag
 */
function update_html_tag_attribs( $html_tag, $new_attribs, $attrib_actions = array() )
{
	if( ! preg_match( '#^<([\S]+)[^>]*>$#i', $html_tag, $tag_match ) )
	{	// Wrong HTML tag format, Return original string:
		return $html_tag;
	}

	$html_tag_name = $tag_match[1];

	$old_attribs = array();
	$updated_attribs = array();
	if( preg_match_all( '@(\S+)=("|\'|)(.*)("|\'|>)@isU', $html_tag, $attr_matches ) )
	{	// Get all existing attributes:
		foreach( $attr_matches[1] as $o => $old_attr_name )
		{
			$old_attribs[ $old_attr_name ] = $attr_matches[3][ $o ];
			if( ! isset( $new_attribs[ $old_attr_name ] ) )
			{	// This attribute is not updated, keep current value:
				$updated_attribs[] = $old_attr_name.'="'.format_to_output( $attr_matches[3][ $o ], 'formvalue' ).'"';
			}
		}
	}

	foreach( $new_attribs as $new_attrib_name => $new_attrib_value )
	{
		if( isset( $old_attribs[ $new_attrib_name ] ) )
		{	// If attribute exists in original HTML tag then Update it depending on selected action:
			$attrib_action = isset( $attrib_actions[ $new_attrib_name ] ) ? $attrib_actions[ $new_attrib_name ] : 'append';
			switch( $attrib_action )
			{
				case 'skip':
					// Don't update old value:
					$new_attrib_value = $old_attribs[ $new_attrib_name ];
					break;

				case 'append':
				default:
					// Append new value to old:
					$new_attrib_value = $old_attribs[ $new_attrib_name ].' '.$new_attrib_value;
					break;
			}
		}
		// ELSE If attribute doesn't exist in original HTML tag then create new one.

		$updated_attribs[] = $new_attrib_name.'="'.format_to_output( $new_attrib_value, 'formvalue' ).'"';
	}

	return '<'.$html_tag_name.' '.implode( ' ', $updated_attribs ).'>';
}


/**
 * Is the current page an install page?
 *
 * @return boolean
 */
function is_install_page()
{
	global $is_install_page;

	return isset( $is_install_page ) && $is_install_page === true; // check for type also, because of register_globals!
}


/**
 * Is the current page an admin/backoffice page?
 *
 * @return boolean
 */
function is_admin_page()
{
	global $is_admin_page;

	return isset( $is_admin_page ) && $is_admin_page === true; // check for type also, because of register_globals!
}


/**
 * Is the current page a default 'Front' page of a blog?
 *
 * @return boolean
 */
function is_front_page()
{
	global $is_front;

	return isset( $is_front ) && $is_front === true;
}


/**
 * Does the given url require logged in user
 *
 * @param string url
 * @param boolean set true to also check if url is login screen or not
 * @return boolean
 */
function require_login( $url, $check_login_screen )
{
	global $Settings;
	if( preg_match( '#/admin.php([&?].*)?$#', $url ) )
	{ // admin always require logged in user
		return true;
	}

	if( $check_login_screen &&  preg_match( '#/login.php([&?].*)?$#', $url ) )
	{
		return true;
	}

	$disp_names = 'threads|messages|contacts';
	if( !$Settings->get( 'allow_anonymous_user_list' ) )
	{
		$disp_names .= '|users';
	}
	if( !$Settings->get( 'allow_anonymous_user_profiles' ) )
	{
		$disp_names .= '|user';
	}
	if( $check_login_screen )
	{
		$disp_names .= '|login';
	}
	if( preg_match( '#disp=('.$disp_names.')#', $url ) )
	{ // $url require logged in user
		return true;
	}

	return false;
}


/**
 * Implode array( 'x', 'y', 'z' ) to something like 'x, y and z'. Useful for displaying list to the end user.
 *
 * If there's one element in the table, it is returned.
 * If there are at least two elements, the last one is concatenated using $implode_last, while the ones before are imploded using $implode_by.
 *
 * @todo dh> I don't think using entities/HTML as default for $implode_last is sane!
 *           Use "&" instead and make sure that the output for HTML is HTML compliant..
 * @todo Support for locales that have a different kind of enumeration?!
 * @return string
 */
function implode_with_and( $arr, $implode_by = ', ', $implode_last = ' &amp; ' )
{
	switch( count($arr) )
	{
		case 0:
			return '';

		case 1:
			$r = array_shift($arr);
			return $r;

		default:
			$r = implode( $implode_by, array_slice( $arr, 0, -1 ) )
			    .$implode_last.array_pop( $arr );
			return $r;
	}
}


/**
 * Display an array as a list:
 *
 * @param array
 * @param string
 * @param string
 * @param string
 * @param string
 * @param string
 */
function display_list( $items, $list_start = '<ul>', $list_end = '</ul>', $item_separator = '',
												$item_start = '<li>', $item_end = '</li>', $force_hash = NULL, $max_items = NULL, $link_params = array() )
{
	if( !is_null($max_items) && $max_items < 1 )
	{
		return;
	}

	if( !empty( $items ) )
	{
		echo $list_start;
		$count = 0;
		$first = true;

		foreach( $items as $item )
		{	// For each list item:

			$link = resolve_link_params( $item, $force_hash, $link_params );
			if( empty( $link ) )
			{
				continue;
			}

			$count++;
			if( $count>1 )
			{
				echo $item_separator;
			}
			echo $item_start.$link.$item_end;

			if( !is_null($max_items) && $count >= $max_items )
			{
				break;
			}
		}
		echo $list_end;
	}
}


/**
 * Credits stuff.
 */
function display_param_link( $params )
{
	echo resolve_link_params( $params );
}


/**
 * Resolve a link based on params (credits stuff)
 *
 * @param array
 * @param integer
 * @param array
 * @return string
 */
function resolve_link_params( $item, $force_hash = NULL, $params = array() )
{
	global $current_locale;

	// echo 'resolve link ';

	if( is_array( $item ) )
	{
		if( isset( $item[0] ) )
		{	// Older format, which displays the same thing for all locales:
			return generate_link_from_params( $item, $params );
		}
		else
		{	// First get the right locale:
			// echo $current_locale;
			foreach( $item as $l_locale => $loc_item )
			{
				if( $l_locale == substr( $current_locale, 0, strlen($l_locale) ) )
				{	// We found a matching locale:
					//echo "[$l_locale/$current_locale]";
					if( is_array( $loc_item[0] ) )
					{	// Randomize:
						$loc_item = hash_link_params( $loc_item, $force_hash );
					}

					return generate_link_from_params( $loc_item, $params );
				}
			}
			// No match found!
			return '';
		}
	}

	// Super old format:
	return $item;
}


/**
 * Get a link line, based url hash combined with probability percentage in first column
 *
 * @param array of arrays
 * @param display for a specific hash key
 */
function hash_link_params( $link_array, $force_hash = NULL )
{
	global $ReqHost, $ReqPath, $ReqURI;

	static $hash;

	if( !is_null($force_hash) )
	{
		$hash = $force_hash;
	}
	elseif( !isset($hash) )
	{
		$key = $ReqHost.$ReqPath;

		global $Collection, $Blog;
		if( !empty($Blog) && strpos( $Blog->get_setting('single_links'), 'param_' ) === 0 )
		{	// We are on a blog that doesn't even have clean URLs for posts
			$key .= $ReqURI;
		}

		$hash = 0;
		for( $i=0; $i<strlen($key); $i++ )
		{
			$hash += ord($key[$i]);
		}
		$hash = $hash % 100 + 1;

		// $hash = rand( 1, 100 );
		global $debug, $Debuglog;
		if( $debug )
		{
			$Debuglog->add( 'Hash key: '.$hash, 'request' );
		}
	}
	//	echo "[$hash] ";

	foreach( $link_array as $link_params )
	{
		// echo '<br>'.$hash.'-'.$link_params[ 0 ];
		if( $hash <= $link_params[ 0 ] )
		{	// select this link!
			// pre_dump( $link_params );
			array_shift( $link_params );
			return $link_params;
		}
	}
	// somehow no match, return 1st element:
	$link_params = $link_array[0];
	array_shift( $link_params );
	return $link_params;
}


/**
 * Generate a link from params (credits stuff)
 *
 * @param array
 * @param array
 */
function generate_link_from_params( $link_params, $params = array() )
{
	$url = $link_params[0];
	if( empty( $url ) )
	{
		return '';
	}

	// Make sure we are not missing any param:
	$params = array_merge( array(
			'type'        => 'link',
			'img_url'     => '',
			'img_width'   => '',
			'img_height'  => '',
			'title'       => '',
			'target'      => '_blank',
		), $params );

	$text = $link_params[1];
	if( is_array($text) )
	{
		$text = hash_link_params( $text );
		$text = $text[0];
	}
	if( empty( $text ) )
	{
		return '';
	}

	$r = '<a href="'.$url.'"';

	if( !empty($params['target'] ) )
	{
		$r .= ' target="'.$params['target'].'"';
	}

	if( $params['type'] == 'img' )
	{
		return $r.' title="'.$params['title'].'"><img src="'.$params['img_url'].'" alt="'
						.$text.'" title="'.$params['title'].'" width="'.$params['img_width'].'" height="'.$params['img_height']
						.'" border="0" /></a>';
	}

	return $r.'>'.$text.'</a>';
}


/**
 * Send a result as javascript
 * automatically includes any Messages ( @see Log::display() )
 * no return from function as it terminates processing
 *
 * @author Yabba
 *
 * @todo dh> Move this out into some more specific (not always included) file.
 *
 * @param array $methods javascript funtions to call with array of parameters
 *		format : 'function_name' => array( param1, parm2, param3 )
 * @param boolean $send_as_html Wrap the script into an html page with script tag; default is to send as js file
 * @param string $target prepended to function calls : blank or window.parent
 */
function send_javascript_message( $methods = array(), $send_as_html = false, $target = '' )
{
	// lets spit out any messages
	global $Messages, $param_input_err_messages;
	ob_start();
	$Messages->display();
	$output = ob_get_clean();

	// Initialize JavaScript params to send what field should be marked are error
	$js_error_params = array();
	if( ! empty( $param_input_err_messages ) && is_array( $param_input_err_messages ) )
	{
		foreach( $param_input_err_messages as $param_name => $param_error )
		{
			$js_error_params[] = $param_name.': \''.format_to_js( $param_error ).'\'';
		}
	}
	$js_error_params = '{'.implode( ', ', $js_error_params ).'}';

	// set target
	$target = ( $target ? $target : param( 'js_target', 'string' ) );
	if( $target )
	{	// add trailing [dot]
		$target = trim( $target, '.' ).'.';
	}

	// target should be empty or window.parent.
	if( $target && $target != 'window.parent.' )
	{
		debug_die( 'Unexpected javascript target' );
	}

	if( $output )
	{	// we have some messages
		$output = $target.'DisplayServerMessages( \''.format_to_js( $output ).'\', '.$js_error_params.' );'."\n";
	}

	if( !empty( $methods ) )
	{	// we have a methods to call
		foreach( $methods as $method => $param_list )
		{	// loop through each requested method
			$params = array();
			$internal_scripts = array();
			if( !is_array( $param_list ) )
			{	// lets make it an array
				$param_list = array( $param_list );
			}
			foreach( $param_list as $param )
			{	// add each parameter to the output
				if( is_array( $param ) )
				{	// This is an array:
					$param = json_encode( $param );
				}
				elseif( !is_numeric( $param ) )
				{	// This is a string
					if( preg_match_all( '#<script(.*?)?>(.*?)</script>#is', $param, $match_scripts ) )
					{	// Extract internal scripts from content:
						$param = str_replace( $match_scripts[0], '', $param );
						foreach( $match_scripts[2] as $i => $internal_script_code )
						{
							if( $internal_script_code === '' && strpos( $match_scripts[1][ $i ], 'src=' ) !== false )
							{	// Append external JS file to <head> to execute code:
								$internal_script_code = 'jQuery( \'head\' ).append( \''.format_to_js( $match_scripts[0][ $i ] ).'\' );';
							}
							$internal_scripts[] = $internal_script_code;
						}
					}
					// Quote the string to javascript format:
					$param = '\''.format_to_js( $param ).'\'';
				}
				$params[] = $param;// add param to the list
			}
			// Add method and parameters:
			$output .= $target.$method.'('.implode( ',', $params ).');'."\n";
			// Append all internal scripts from content on order to execute this properly:
			foreach( $internal_scripts as $internal_script )
			{
				$output .= "\n// Internal script from {$target}{$method}():\n".$internal_script;
			}
		}
	}

	// Send the predefined cookies:
	evo_sendcookies();

	if( $send_as_html )
	{	// we want to send as a html document
		if( ! headers_sent() )
		{	// Send headers only when they are not send yet to avoid an error:
			headers_content_mightcache( 'text/html', 0 );		// Do NOT cache interactive communications.
		}
		echo '<html><head></head><body><script type="text/javascript">'."\n";
		echo $output;
		echo '</script></body></html>';
	}
	else
	{	// we want to send as js
		if( ! headers_sent() )
		{	// Send headers only when they are not send yet to avoid an error:
			headers_content_mightcache( 'text/javascript', 0 );		// Do NOT cache interactive communications.
		}
		echo $output;
	}

	exit(0);
}


/**
 * Basic tidy up of strings
 *
 * @author Yabba
 * @author Tblue
 *
 * @param string $unformatted raw data
 * @return string formatted data
 */
function format_to_js( $unformatted )
{
	return str_replace( array(
							'\'',
							'\n',
							'\r',
							'\t',
							"\n",
							"\r",
						),
						array(
							'\\\'',
							'\\\\n',
							'\\\\r',
							'\\\\t',
							'\n',
							'\r',
						), $unformatted );
}


/**
 * Get available sort options for items
 *
 * @return array key=>name
 */
function get_available_sort_options()
{
	return array(
		'datestart'       => T_('Date issued (Default)'),
		'order'           => T_('Order (as explicitly specified)'),
		//'datedeadline' => T_('Deadline'),
		'title'           => T_('Title'),
		'datecreated'     => T_('Date created'),
		'datemodified'    => T_('Date last modified'),
		'last_touched_ts' => T_('Date last touched'),
		'urltitle'        => T_('URL "filename"'),
		'priority'        => T_('Priority'),
		'numviews'        => T_('Number of members who have viewed the post (If tracking enabled)'),
		'RAND'            => T_('Random order!'),
	);
}


/**
 * Get available sort options for blogs
 *
 * @return array key=>name
 */
function get_coll_sort_options()
{
	return array(
		'order'        => T_('Order (Default)'),
		'ID'           => T_('Blog ID'),
		'name'         => T_('Name'),
		'shortname'    => T_('Short name'),
		'tagline'      => T_('Tagline'),
		'shortdesc'    => T_('Short Description'),
		'urlname'      => T_('URL "filename"'),
		'RAND'         => T_('Random order!'),
	);
}


/**
 * Converts array to form option list
 *
 * @param array of option values and descriptions
 * @param integer|array selected keys
 * @param array provide a choice for "none_value" with value ''
 * @return string
 */
function array_to_option_list( $array, $default = '', $allow_none = array() )
{
	if( !is_array( $default ) )
	{
		$default = array( $default );
	}

	$r = '';

	if( !empty($allow_none) )
	{
		$r .= '<option value="'.$allow_none['none_value'].'"';
		if( empty($default) ) $r .= ' selected="selected"';
		$r .= '>'.format_to_output($allow_none['none_text']).'</option>'."\n";
	}

	foreach( $array as $k=>$v )
	{
		$r .=  '<option value="'.format_to_output($k,'formvalue').'"';
		if( in_array( $k, $default ) ) $r .= ' selected="selected"';
		$r .= '>';
		$r .= format_to_output( $v, 'htmlbody' );
		$r .=  '</option>'."\n";
	}

	return $r;
}


/**
 * Get a value from a volatile/lossy cache.
 *
 * @param string key
 * @param boolean success (by reference)
 * @return mixed True in case of success, false in case of failure. NULL, if no backend is available.
 */
function get_from_mem_cache( $key, & $success )
{
	global $Timer;

	$Timer->resume( 'get_from_mem_cache', false );

	if( function_exists( 'apcu_fetch' ) )
	{	// APCu
		$r = apcu_fetch( $key, $success );
	}
	elseif( function_exists( 'apc_fetch' ) )
	{	// APC
		$r = apc_fetch( $key, $success );
	}
	elseif( function_exists( 'xcache_get' ) && ini_get( 'xcache.var_size' ) > 0 )
	{	// XCache
		$r = xcache_get( $key );
	}

	if( ! isset($success) )
	{ // set $success for implementation that do not set it itself (only APC does so)
		$success = isset($r);
	}
	if( ! $success )
	{
		$r = NULL;

		global $Debuglog;
		$Debuglog->add( 'No caching backend available for reading "'.$key.'".', 'cache' );
	}

	$Timer->pause( 'get_from_mem_cache', false );

	return $r;
}


/**
 * Set a value to a volatile/lossy cache.
 *
 * There's no guarantee that the data is still available, since e.g. old
 * values might get purged.
 *
 * @param string key
 * @param mixed Data. Objects would have to be serialized.
 * @param int Time to live (seconds). Default is 0 and means "forever".
 * @return mixed
 */
function set_to_mem_cache( $key, $payload, $ttl = 0 )
{
	global $Timer;

	$Timer->resume( 'set_to_mem_cache', false );

	if( function_exists( 'apcu_store' ) )
	{	// APCu
		$r = apcu_store( $key, $payload, $ttl );
	}
	elseif( function_exists( 'apc_store' ) )
	{	// APC
		$r = apc_store( $key, $payload, $ttl );
	}
	elseif( function_exists( 'xcache_set' ) && ini_get( 'xcache.var_size' ) > 0 )
	{	// XCache
		$r = xcache_set( $key, $payload, $ttl );
	}
	else
	{	// No available cache module:
		global $Debuglog;
		$Debuglog->add( 'No caching backend available for writing "'.$key.'".', 'cache' );
		$r = NULL;
	}

	$Timer->pause( 'set_to_mem_cache', false );

	return $r;
}


/**
 * Remove a given key from the volatile/lossy cache.
 *
 * @param string key
 * @return boolean True on success, false on failure. NULL if no backend available.
 */
function unset_from_mem_cache( $key )
{
	if( function_exists( 'apc_delete' ) )
	{	// APC
		return apc_delete( $key );
	}

	if( function_exists( 'xcache_unset' ) )
	{	// XCache
		return xcache_unset( gen_key_for_cache( $key ) );
	}

	if( function_exists( 'apcu_delete' ) )
	{	// APCu
		return apcu_delete( $key );
	}
}


/**
 * Generate order by clause
 *
 * @param string The order values are separated by space or comma
 * @param string An order direction: ASC, DESC
 * @param string DB prefix
 * @param string ID field name with prefix
 * @param array Names of DB fields(without prefix) that are available
 * @return string The order fields are separated by comma
 */
function gen_order_clause( $order_by, $order_dir, $dbprefix, $dbIDname_disambiguation, $available_fields = NULL )
{
	$order_by = str_replace( ' ', ',', $order_by );
	$orderby_array = explode( ',', $order_by );

	$order_dir = explode( ',', str_replace( ' ', ',', $order_dir ) );

	if( is_array( $available_fields ) )
	{ // Exclude the incorrect fields from order clause
		foreach( $orderby_array as $i => $orderby_field )
		{
			if( ! in_array( $orderby_field, $available_fields ) )
			{
				unset( $orderby_array[ $i ] );
			}
		}
	}

	// Format each order param with default column names:
	foreach( $orderby_array as $i => $orderby_value )
	{ // If the order_by field contains a '.' character which is a table separator we must not use the prefix ( E.g. temp_table.value )
		$use_dbprefix = ( strpos( $orderby_value, '.' ) !== false ) ? '' : $dbprefix;
		$orderby_array[ $i ] = $use_dbprefix.$orderby_value.' '.( isset( $order_dir[ $i ] ) ? $order_dir[ $i ] : $order_dir[0] );
	}

	// Add an ID parameter to make sure there is no ambiguity in ordering on similar items:
	$orderby_array[] = $dbIDname_disambiguation.' '.$order_dir[0];

	$order_by = implode( ', ', $orderby_array );

	// Special case for RAND:
	$order_by = str_replace( $dbprefix.'RAND ', 'RAND() ', $order_by );

	return $order_by;
}


/**
 * Get the IconLegend instance.
 *
 * @return IconLegend or false, if the user has not set "display_icon_legend"
 */
function & get_IconLegend()
{
	static $IconLegend;

	if( ! isset($IconLegend) )
	{
		global $UserSettings;
		if( $UserSettings->get('display_icon_legend') )
		{
			/**
			 * Icon Legend
			 */
			load_class( '_core/ui/_iconlegend.class.php', 'IconLegend' );
			$IconLegend = new IconLegend();
		}
		else
		{
			$IconLegend = false;
		}
	}
	return $IconLegend;
}


/**
 * Get name of active opcode cache, or "none".
 * {@internal Anyone using something else, please extend.}}
 * @return string
 */
function get_active_opcode_cache()
{
	if( function_exists('opcache_invalidate') )
	{
		return 'OPCache';
	}

	if( function_exists('apc_delete_file') )
	{
		return 'APC';
	}

	// xcache: xcache.var_size must be > 0. xcache_set is not necessary (might have been disabled).
	if( ini_get('xcache.size') > 0 )
	{
		return 'xcache';
	}

	if( ini_get('eaccelerator.enable') )
	{
		$eac_info = eaccelerator_info();
		if( $eac_info['cache'] )
		{
			return 'eAccelerator';
		}
	}

	return 'none';
}


/**
 * Get name of active user cache, or "none".
 * {@internal Anyone using something else, please extend.}}
 * @return string
 */
function get_active_user_cache()
{
	if( function_exists( 'apcu_cache_info' ) /* && ini_get( 'apc.enabled' ) */ )
	{
		return 'APCu';
	}

	if( function_exists( 'apc_cache_info' ) /* && ini_get( 'apc.enabled' ) */ )
	{
		return 'APC';
	}

	// xcache: xcache.var_size must be > 0. xcache_set is not necessary (might have been disabled).
	if( ini_get('xcache.size') > 0 )
	{
		return 'xcache';
	}

	return 'none';
}


/**
 * Invalidate all page caches.
 * This function should be processed every time, when some users or global settings was modified,
 * and this modification has an imortant influence for the front office display.
 * Modifications that requires to invalidate all page caches:
 *   - installing/removing/reloading/enabling/disabling plugins
 *   - editing user settings like allow profile pics, new users can register, user settings>display
 */
function invalidate_pagecaches()
{
	global $DB, $Settings, $servertimenow;

	// get current server time
	$timestamp = ( empty( $servertimenow ) ? time() : $servertimenow );

	// get all blog ids
	if( $blog_ids = $DB->get_col( 'SELECT blog_ID FROM T_blogs' ) )
	{	// build invalidate query
		$query = 'REPLACE INTO T_coll_settings ( cset_coll_ID, cset_name, cset_value ) VALUES';
		foreach( $blog_ids as $blog_id )
		{
			$query .= ' ('.$blog_id.', "last_invalidation_timestamp", '.$timestamp.' ),';
		}
		$query = substr( $query, 0, strlen( $query ) - 1 );
		$DB->query( $query, 'Invalidate blogs\'s page caches' );
	}

	// Invalidate general cache content also
	$Settings->set( 'last_invalidation_timestamp', $timestamp );
	$Settings->dbupdate();
}


/**
* Get $ReqPath, $ReqURI
*
* @return array ($ReqPath,$ReqURI);
*/
function get_ReqURI()
{
	global $Debuglog;

	// Investigation for following code by Isaac - http://isaacschlueter.com/
	if( isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) )
	{ // Warning: on some IIS installs it it set but empty!
		$Debuglog->add( 'vars: vars: Getting ReqURI from REQUEST_URI', 'request' );
		$ReqURI = $_SERVER['REQUEST_URI'];

		// Build requested Path without query string:
		$pos = strpos( $ReqURI, '?' );
		if( false !== $pos )
		{
			$ReqPath = substr( $ReqURI, 0, $pos  );
		}
		else
		{
			$ReqPath = $ReqURI;
		}
	}
	elseif( isset($_SERVER['URL']) )
	{ // ISAPI
		$Debuglog->add( 'vars: Getting ReqPath from URL', 'request' );
		$ReqPath = $_SERVER['URL'];
		$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
	}
	elseif( isset($_SERVER['PATH_INFO']) )
	{ // CGI/FastCGI
		if( isset($_SERVER['SCRIPT_NAME']) )
		{
			$Debuglog->add( 'vars: Getting ReqPath from PATH_INFO and SCRIPT_NAME', 'request' );

			if ($_SERVER['SCRIPT_NAME'] == $_SERVER['PATH_INFO'] )
			{	/* both the same so just use one of them
				 * this happens on a windoze 2003 box
				 * gotta love microdoft
				 */
				$Debuglog->add( 'vars: PATH_INFO and SCRIPT_NAME are the same', 'request' );
				$Debuglog->add( 'vars: Getting ReqPath from PATH_INFO only instead', 'request' );
				$ReqPath = $_SERVER['PATH_INFO'];
			}
			else
			{
				$ReqPath = $_SERVER['SCRIPT_NAME'].$_SERVER['PATH_INFO'];
			}
		}
		else
		{ // does this happen??
			$Debuglog->add( 'vars: Getting ReqPath from PATH_INFO only!', 'request' );

			$ReqPath = $_SERVER['PATH_INFO'];
		}
		$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
	}
	elseif( isset($_SERVER['ORIG_PATH_INFO']) )
	{ // Tomcat 5.5.x with Herbelin PHP servlet and PHP 5.1
		$Debuglog->add( 'vars: Getting ReqPath from ORIG_PATH_INFO', 'request' );
		$ReqPath = $_SERVER['ORIG_PATH_INFO'];
		$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
	}
	elseif( isset($_SERVER['SCRIPT_NAME']) )
	{ // Some Odd Win2k Stuff
		$Debuglog->add( 'vars: Getting ReqPath from SCRIPT_NAME', 'request' );
		$ReqPath = $_SERVER['SCRIPT_NAME'];
		$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
	}
	elseif( isset($_SERVER['PHP_SELF']) )
	{ // The Old Stand-By
		$Debuglog->add( 'vars: Getting ReqPath from PHP_SELF', 'request' );
		$ReqPath = $_SERVER['PHP_SELF'];
		$ReqURI = isset($_SERVER['QUERY_STRING']) && !empty( $_SERVER['QUERY_STRING'] ) ? ($ReqPath.'?'.$_SERVER['QUERY_STRING']) : $ReqPath;
	}
	else
	{
		$ReqPath = false;
		$ReqURI = false;
		?>
		<p class="error">
		Warning: $ReqPath could not be set. Probably an odd IIS problem.
		</p>
		<p>
		Go to your <a href="<?php echo $baseurl.$install_subdir ?>phpinfo.php">phpinfo page</a>,
		look for occurences of <code><?php
		// take the baseurlroot out..
		echo preg_replace('#^'.preg_quote( $baseurlroot, '#' ).'#', '', $baseurl.$install_subdir )
		?>phpinfo.php</code> and copy all lines
		containing this to the <a href="http://forums.b2evolution.net">forum</a>. Also specify what webserver
		you're running on.
		<br />
		(If you have deleted your install folder &ndash; what is recommended after successful setup &ndash;
		you have to upload it again before doing this).
		</p>
		<?php
	}

	return array($ReqPath,$ReqURI);
}


/**
 * Get URL to REST API script depending on current collection base url from front-office or site base url from back-office
 *
 * Note: For back-office or no collection page _init_hit.inc.php should be called before this call, because ReqHost and ReqPath must be initialized
 *
 * @return string URL to htsrv folder
 */
function get_restapi_url()
{
	global $restapi_script;

	return get_htsrv_url().$restapi_script;
}


/**
 * Get URL to htsrv folder depending on current collection base url from front-office or site base url from back-office
 *
 * Note: For back-office or no collection page _init_hit.inc.php should be called before this call, because ReqHost and ReqPath must be initialized
 *
 * @param boolean TRUE to use https URL
 * @return string URL to htsrv folder
 */
function get_htsrv_url( $force_https = false )
{
	global $Collection, $Blog;

	if( is_admin_page() || empty( $Blog ) )
	{	// For back-office or when no collection page:
		return get_samedomain_htsrv_url( $force_https );
	}
	else
	{	// For current collection:
		return $Blog->get_local_htsrv_url( NULL, $force_https );
	}
}


/**
 * Get htsrv url on the same domain as the http request came from
 *
 * Note: _init_hit.inc.php should be called before this call, because ReqHost and ReqPath must be initialized
 *
 * @param boolean TRUE to use https URL
 * @return string URL to htsrv folder
 */
function get_samedomain_htsrv_url( $secure = false )
{
	global $ReqHost, $ReqPath, $htsrv_url, $htsrv_url_sensitive, $htsrv_subdir, $Collection, $Blog;

	if( $secure )
	{
		$req_htsrv_url = $htsrv_url_sensitive;
	}
	else
	{
		$req_htsrv_url = $htsrv_url;
	}

	// Cut htsrv folder from end of the URL:
	$req_htsrv_url = substr( $req_htsrv_url, 0, strlen( $req_htsrv_url ) - strlen( $htsrv_subdir ) );

	if( strpos( $ReqHost.$ReqPath, $req_htsrv_url ) !== false )
	{	// If current request path contains the required htsrv URL:
		return $req_htsrv_url.$htsrv_subdir;
	}

	$req_url_parts = @parse_url( $ReqHost );
	$hsrv_url_parts = @parse_url( $req_htsrv_url );
	if( ( !isset( $req_url_parts['host'] ) ) || ( !isset( $hsrv_url_parts['host'] ) ) )
	{
		debug_die( 'Invalid hosts!' );
	}

	$req_domain = $req_url_parts['host'];
	$htsrv_domain = $hsrv_url_parts['host'];

	// Replace domain + path of htsrv URL with current request:
	$samedomain_htsrv_url = substr_replace( $req_htsrv_url, $req_domain, strpos( $req_htsrv_url, $htsrv_domain ), strlen( $htsrv_domain ) );

	// Revert htsrv folder to end of the URL which has been cut above:
	$samedomain_htsrv_url .= $htsrv_subdir;

	// fp> The following check would apply well if we always had 301 redirects.
	// But it's possible to turn them off in SEO settings for some page and not others (we don't know which here)
  // And some kinds of pages do not have 301 redirections implemented yet, e-g: disp=users
  /*
	if( ( !is_admin_page() ) && ( !empty( $Blog ) ) && ( $samedomain_htsrv_url != $Blog->get_htsrv_url( $secure ) ) )
	{
		debug_die( 'The blog is configured to have /htsrv/ at:<br> '.$Blog->get_htsrv_url( $secure ).'<br>but in order to stay on the current domain, we would need to use:<br>'.$samedomain_htsrv_url.'<br>Maybe we have a missing redirection to the proper blog url?' );
	}
	*/

	return $samedomain_htsrv_url;
}


/**
 * Get secure htsrv url on the same domain as the http request came from
 * It is important on login and register calls
 * _init_hit.inc.php should be called before this call, because ReqHost and ReqPath must be initialized
 */
function get_secure_htsrv_url()
{
	return get_samedomain_htsrv_url( true );
}


/**
 * Set max execution time
 *
 * @param integer seconds
 * @return string the old value on success, false on failure.
 */
function set_max_execution_time( $seconds )
{
	if( function_exists( 'set_time_limit' ) )
	{
		set_time_limit( $seconds );
	}
	return @ini_set( 'max_execution_time', $seconds );
}


/**
 * Sanitize a comma-separated list of numbers (IDs)
 *
 * @param string
 * @param bool Return array if true, string otherwise
 * @param bool Quote each element (for use in SQL queries)
 * @return string
 */
function sanitize_id_list( $str, $return_array = false, $quote = false )
{
	if( is_null($str) )
	{	// Allow NULL values
		$str = '';
	}

	// Explode and trim
	$array = array_map( 'trim', explode(',', $str) );

	// Convert to integer and remove all empty values
	$array = array_filter( array_map('intval', $array) );

	if( !$return_array && $quote )
	{	// Quote each element and return a string
		global $DB;
		return $DB->quote($array);
	}
	return ( $return_array ? $array : implode(',', $array) );
}


/**
 * Create json_encode function if it does not exist ( PHP < 5.2.0 )
 *
 * @return string
 */
if ( !function_exists( 'json_encode' ) )
{
	function json_encode( $a = false )
	{
		if( is_null( $a ) )
		{
			return 'null';
		}
		if( $a === false )
		{
			return 'false';
		}
		if( $a === true )
		{
			return 'true';
		}
		if( is_scalar( $a ) )
		{
			if( is_float( $a ) )
			{ // Always use "." for floats.
				return floatval( str_replace( ",", ".", strval( $a ) ) );
			}

			if( is_string( $a ) )
			{
				$jsonReplaces = array( array( "\\", "/", "\n", "\t", "\r", "\b", "\f", '"' ), array( '\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"' ) );
				return '"'.str_replace( $jsonReplaces[0], $jsonReplaces[1], $a ).'"';
			}

			return $a;
		}
		$isList = true;
		for( $i = 0, reset($a); $i < count($a); $i++, next($a) )
		{
			if( key($a) !== $i )
			{
				$isList = false;
				break;
			}
		}
		$result = array();
		if( $isList )
		{
			foreach( $a as $v )
			{
				$result[] = json_encode($v);
			}
			return '['.join( ',', $result ).']';
		}
		else
		{
			foreach( $a as $k => $v )
			{
				$result[] = json_encode($k).':'.json_encode($v);
			}
			return '{'.join( ',', $result ).'}';
		}
	}
}


/**
 * A wrapper for json_encode function
 * We need to pass valid UTF-8 string to json_encode, otherwise it may return NULL
 *
 * @param mixed
 * @return string
 */
function evo_json_encode( $a = false )
{
	if( is_string( $a ) )
	{ // Convert to UTF-8
		$a = current_charset_to_utf8( $a );
	}
	elseif( is_array( $a ) )
	{ // Recursively convert to UTF-8
		array_walk_recursive( $a, 'current_charset_to_utf8' );
	}

	$result = json_encode( $a );
	if( $result === false )
	{ // If json_encode returns FALSE because of some error we should set correct json empty value as '[]' instead of false
		$result = '[]';
	}

	return $result;
}


/**
 * A helper function to conditionally convert a string from current charset to UTF-8
 *
 * @param string
 * @return string
 */
function current_charset_to_utf8( & $a )
{
	global $current_charset;

	if( is_string( $a ) && $current_charset != '' && $current_charset != 'utf-8' )
	{ // Convert string to utf-8 if it has another charset
		$a = convert_charset( $a, 'utf-8', $current_charset );
	}

	return $a;
}


if( !function_exists( 'property_exists' ) )
{
	/**
	 * Create property_exists function if it does not exist ( PHP < 5.1 )
	 * @param object
	 * @param string
	 *
	 * @return bool
	 */
	function property_exists( $class, $property )
	{
		if( is_object( $class ) )
		{
			$vars = get_object_vars( $class );
		}
		else
		{
			$vars = get_class_vars( $class );
		}
		return array_key_exists( $property, $vars );
	}
}


// fp>vitaliy: move to a file that is not included everywhere!
/**
 * Update global $http_response_code and call function header()
 *
 * NOTICE: When you start to use new code please add it to the hits filter "HTTP resp"
 *         in the file "/inc/sessions/views/_stats_view.funcs.php",
 *         function filter_hits(), array $resp_codes
 *
 * @param string Header
 * @param integer Header response code
 */
function header_http_response( $string, $code = NULL )
{
	global $http_response_code;

	$string = 'HTTP/1.1 '. $string;

	if( is_null( $code ) )
	{
		if( preg_match( '/(\d{3})/', $string, $matches ) )
		{
			$http_response_code = (int)$matches[0];
		}
	}
	else
	{
		$http_response_code = $code;
	}

	header( $string );
}


/**
 * Add a trailing slash, if none present
 *
 * @param string the path/url
 * @return string the path/url with trailing slash
 */
function trailing_slash( $path )
{
	if( empty($path) || utf8_substr( $path, -1 ) == '/' )
	{
		return $path;
	}
	else
	{
		return $path.'/';
	}
}


/**
 * Remove trailing slash, if present
 *
 * @param string the path/url
 * @return string the path/url without trailing slash
 */
function no_trailing_slash( $path )
{
	if( utf8_substr( $path, -1 ) == '/' )
	{
		return utf8_substr( $path, 0, utf8_strlen( $path )-1 );
	}
	else
	{
		return $path;
	}
}


/**
 * Provide sys_get_temp_dir for older versions of PHP (< 5.2.1)
 *
 * @return string path to system temporary directory
 */
if( !function_exists( 'sys_get_temp_dir' ) )
{
	function sys_get_temp_dir()
	{
		// Try to get from environment variable
		if( !empty($_ENV['TMP']) )
		{
			return realpath( $_ENV['TMP'] );
		}
		elseif( !empty($_ENV['TMPDIR']) )
		{
			return realpath( $_ENV['TMPDIR'] );
		}
		elseif( !empty($_ENV['TEMP']) )
		{
			return realpath( $_ENV['TEMP'] );
		}
		else
		{	// Detect by creating a temporary file

			// Try to use system's temporary directory as random name shouldn't exist
			$temp_file = tempnam( sha1(uniqid(rand()), true), '' );
			if( $temp_file )
			{
				$temp_dir = realpath( dirname($temp_file) );
				unlink($temp_file);
				return $temp_dir;
			}
			else
			{
				return false;
			}
		}
	}
}


/**
 * Provide inet_pton for older versions of PHP (< 5.1.0 linux & < 5.3.0 windows)
 *
 * Converts a human readable IP address to its packed in_addr representation
 * @param string A human readable IPv4 or IPv6 address
 * @return string The in_addr representation of the given address, or FALSE if a syntactically invalid address is given (for example, an IPv4 address without dots or an IPv6 address without colons
 */
if( !function_exists( 'inet_pton' ) )
{
	function inet_pton( $ip )
	{
		if( strpos( $ip, '.' ) !== FALSE )
		{	// IPv4
			$ip = pack( 'N', ip2long( $ip ) );
		}
		elseif( strpos( $ip, ':' ) !== FALSE )
		{	// IPv6
			$ip = explode( ':', $ip );
			$res = str_pad( '', ( 4 * ( 8 - count( $ip ) ) ), '0000', STR_PAD_LEFT );
			foreach( $ip as $seg )
			{
				$res .= str_pad( $seg, 4, '0', STR_PAD_LEFT );
			}
			$ip = pack( 'H'.strlen( $res ), $res );
		}
		else
		{	// Invalid IP address
			$ip = FALSE;
		}

		return $ip;
	}
}


/**
 * Convert integer to IP address
 *
 * @param integer Number
 * @return string IP address
 */
function int2ip( $int )
{
	$ip = array();
	$ip[0] = (int) ( $int / 256 / 256 / 256 );
	$ip[1] = (int) ( ( $int - ( $ip[0] * 256 * 256 * 256 ) ) / 256 / 256 );
	$ip[2] = (int) ( ( $int - ( $ip[0] * 256 * 256 * 256 ) - ( $ip[1] * 256 * 256 ) ) / 256 );
	$ip[3] = $int - ( $ip[0] * 256 * 256 * 256 ) - ( $ip[1] * 256 * 256 ) - ( $ip[2] * 256 );

	return $ip[0].'.'.$ip[1].'.'.$ip[2].'.'.$ip[3];
}


/**
 * Check if the given string is a valid IPv4 or IPv6 address value
 *
 * @param string IP
 * @return boolean true if valid, false otherwise
 */
function is_valid_ip_format( $ip )
{
	return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
}




/**
 * Convert IP address to integer (get only 32bits of IPv6 address)
 *
 * @param string IP address
 * @return integer Number
 */
function ip2int( $ip )
{
	if( ! is_valid_ip_format( $ip ) )
	{ // IP format is incorrect
		return 0;
	}

	if( $ip == '::1' )
	{	// Reserved IP for localhost
		$ip = '127.0.0.1';
	}

	$parts = unpack( 'N*', inet_pton( $ip ) );
	// In case of IPv6 return only a parts of it
	$result = ( strpos( $ip, '.' ) !== false ) ? $parts[1] /* IPv4*/ : $parts[4] /* IPv6*/;

	if( $result < 0 )
	{ // convert unsigned int to signed from unpack.
		// this should be OK as it will be a PHP float not an int
		$result += 4294967296;
	}

	return $result;
}


/**
 * Check if URL has a domain in IP format
 *
 * @param string URL
 * @return boolean
 */
function is_ip_url_domain( $url )
{
	$url_data = parse_url( $url );

	if( $url_data === false || ! isset( $url_data['host'] ) )
	{	// Wrong url:
		return false;
	}

	// Check if host is IP address:
	return is_valid_ip_format( $url_data['host'] );
}


/**
 * Provide array_combine for older versions of PHP (< 5.0.0)
 *
 * Creates an array by using one array for keys and another for its values
 * @param array Keys
 * @param array Values
 * @return array Combined array, FALSE if the number of elements for each array isn't equal.
 */
if( !function_exists( 'array_combine' ) )
{
	function array_combine( $arr1, $arr2 )
	{
		if( count( $arr1 ) != count( $arr2 ) )
		{
			return false;
		}

		$out = array();
		foreach( $arr1 as $key1 => $value1 )
		{
			$out[$value1] = $arr2[$key1];
		}
		return $out;
	}
}


/**
 * Provide array_combine for older versions of PHP (< 5.0.0)
 *
 * List of already/potentially sent HTTP responsee headers(),
 * CANNOT be implemented
 */
if( !function_exists( 'headers_list' ) )
{
	function headers_list()
	{
		return array();
	}
}


/**
 * Provide array_fill_keys for older versions of PHP (< 5.2.0)
 *
 * Fills an array with the value of the value parameter, using the values of the keys array as keys.
 * @param array Keys
 * @param mixed Value
 * @return array Filled array
 */
if( !function_exists( 'array_fill_keys' ) )
{
	function array_fill_keys( $array, $value )
	{
		$filled_array = array();
		foreach( $array as $key )
		{
			$filled_array[$key] = $value;
		}

		return $filled_array;
	}
}


/**
 * Provide htmlspecialchars_decode for older versions of PHP (< 5.1.0)
 *
 * Convert special HTML entities back to characters
 * @param string Text to decode
 * @return string The decoded text
 */
if( !function_exists( 'htmlspecialchars_decode' ) )
{
	function htmlspecialchars_decode( $text )
	{
		return strtr( $text, array_flip( get_html_translation_table( HTML_SPECIALCHARS ) ) );
	}
}


/**
 * Provide array_walk_recursive for older versions of PHP (< 5.1.0)
 *
 * Apply a user function recursively to every member of an array
 * @param array The input array
 * @param string Funcname
 * @param string If the optional userdata parameter is supplied, it will be passed as the third parameter to the callback funcname.
 * @return TRUE on success or FALSE on failure
 */
if( !function_exists( 'array_walk_recursive' ) )
{
	function array_walk_recursive( &$input, $funcname, $userdata = '' )
	{
		if( !is_callable( $funcname ) )
		{
			return false;
		}

		if( !is_array( $input ) )
		{
			return false;
		}

		foreach( $input AS $key => $value )
		{
			if( is_array( $input[$key] ) )
			{
				array_walk_recursive( $input[$key], $funcname, $userdata );
			}
			else
			{
				$saved_value = $value;
				if( !empty( $userdata ) )
				{
					$funcname( $value, $key, $userdata );
				}
				else
				{
					$funcname( $value, $key );
				}

				if( $value != $saved_value )
				{
					$input[$key] = $value;
				}
			}
		}

		return true;
	}
}


/**
 * Provide hex2bin for older versions of PHP (< 5.4)
 *
 * Decodes a hexadecimally encoded binary string
 * @param string Hexadecimal representation of data
 * @return string The binary representation of the given data or FALSE on failure
 */
if( !function_exists( 'hex2bin' ) )
{
	function hex2bin( $hex )
	{
		return pack( 'H*', $hex );
	}
}


/**
 * Save text data to file, create target file if it doesn't exist
 *
 * @param string data to be written
 * @param string filename (full path to a file)
 * @param string fopen mode
 */
function save_to_file( $data, $filename, $mode = 'a' )
{
	global $Settings;

	if( ! file_exists($filename) )
	{	// Create target file
		@touch( $filename );

		// Doesn't work during installation
		if( !empty($Settings) )
		{
			$chmod = $Settings->get('fm_default_chmod_dir');
			@chmod( $filename, octdec($chmod) );
		}
	}

	if( ! is_writable($filename) )
	{
		return false;
	}

	$f = @fopen( $filename, $mode );
	$ok = @fwrite( $f, $data );
	@fclose( $f );

	if( $ok && file_exists($filename) )
	{
		return $filename;
	}
	return false;
}


/**
 * Check if current request is AJAX
 * Used in order to get only content of the requested page
 *
 * @param string Template name
 * @return boolean TRUE/FALSE
 */
function is_ajax_content( $template_name = '' )
{
	global $ajax_content_mode;

	// Template names of content: @see skin_include()
	$content_templates = array( '$disp$', '_item_block.inc.php', '_item_content.inc.php' );

	return !empty( $ajax_content_mode ) &&
		$ajax_content_mode === true &&
		!in_array( $template_name, $content_templates );
}


/**
 * Insert system log into DB
 *
 * @param string Message text
 * @param string Log type: 'info', 'warning', 'error', 'critical_error'
 * @param string Object type: 'comment', 'item', 'user', 'file' or leave default NULL if none of them
 * @param integer Object ID
 * @param string Origin type: 'core', 'plugin'
 * @param integer Origin ID
 * @param integer User ID
 */
function syslog_insert( $message, $log_type, $object_type = NULL, $object_ID = NULL, $origin_type = 'core', $origin_ID = NULL, $user_ID = NULL )
{
	global $servertimenow;

	$Syslog = new Syslog();
	$Syslog->set_user( $user_ID );
	$Syslog->set( 'type', $log_type );
	$Syslog->set_origin( $origin_type, $origin_ID );
	$Syslog->set_object( $object_type, $object_ID );
	$Syslog->set_message( $message );
	$Syslog->set( 'timestamp', date2mysql( $servertimenow ) );
	$Syslog->dbinsert();
}


/**
 * Get a param to know where script is calling now, Used for JS functions
 *
 * @return string
 */
function request_from()
{
	global $request_from;

	if( !empty( $request_from ) )
	{ // AJAX request
		return $request_from;
	}

	if( is_admin_page() )
	{ // Backoffice
		global $ctrl;
		return !empty( $ctrl ) ? $ctrl : 'admin';
	}
	else
	{ // Frontoffice
		return 'front';
	}
}


/**
 * Get an error message text about file permissions
 */
function get_file_permissions_message()
{
	return sprintf( T_( '(Please check UNIX file permissions on the parent folder. %s)' ), get_manual_link( 'file-permissions' ) );
}


/**
 * Flush the output buffer
 */
function evo_flush()
{
	global $Timer, $disable_evo_flush;

	if( isset( $disable_evo_flush ) && $disable_evo_flush )
	{	// This function is disabled (for example, used for ajax/rest api requests)
		return;
	}

	// To fill the output buffer in order to flush data:
	// NOTE: Uncomment the below line if flush doesn't work as expected
	// echo str_repeat( ' ', 4096 );

	// New line char is required as flag of that output portion is printed out:
	echo "\n";

	$zlib_output_compression = ini_get( 'zlib.output_compression' );
	if( empty( $zlib_output_compression ) || $zlib_output_compression == 'Off' )
	{ // This function helps to turn off output buffering
		// But do NOT use it when zlib.output_compression is ON, because it creates the die errors

		// fp/yura TODO: we need to optimize this: We want to flush to screen and continue caching.
		//               This needs investigation and checking other similar places.
		global $PageCache;
		if( ! ( isset( $PageCache ) && ! empty( $PageCache->is_collecting ) ) )
		{ // Only when page cache is not running now because a notice error can appears in function PageCache->end_collect()
			@ob_end_flush();
		}
	}
	flush();

	if( isset( $Timer ) && $Timer->get_state( 'first_flush' ) == 'running' )
	{ // The first fulsh() was called, stop the timer
		$Timer->pause( 'first_flush' );
	}
}

// ---------- APM : Application Performance Monitoring -----------

/**
 * Name the transaction for the APM.
 * This avoids that every request be called 'index.php' or 'admin.php' or 'cron_exec.php'
 *
 * @param mixed $request_transaction_name
 */
function apm_name_transaction( $request_transaction_name )
{
	if(extension_loaded('newrelic'))
	{	// New Relic is installed on the server for monitoring.
		newrelic_name_transaction( $request_transaction_name );
	}
}

/**
 * Log a custom metric
 *
 * @param mixed $name name of the custom metric
 * @param mixed $value assumed to be in milliseconds (ms)
 */
function apm_log_custom_metric( $name, $value )
{
	if(extension_loaded('newrelic'))
	{	// New Relic is installed on the server for monitoring.
		newrelic_custom_metric( 'Custom/'.$name, $value );
	}
}

/**
 * Log a custom param
 *
 * @param mixed $name name of the custom param
 * @param mixed $value of the custom param
 */
function apm_log_custom_param( $name, $value )
{
	if(extension_loaded('newrelic'))
	{	// New Relic is installed on the server for monitoring.
		newrelic_add_custom_parameter( $name, $value );
	}
}


/**
 * Get cookie domain depending on current page:
 *     - For back-office the config var $cookie_domain is used
 *     - For front-office it is dynamically generated from collection url
 *
 * @return string Cookie domain
 */
function get_cookie_domain()
{
	global $Collection, $Blog;

	if( is_admin_page() || empty( $Blog ) )
	{	// Use cookie domain of base url from config:
		global $cookie_domain;
		return $cookie_domain;
	}
	else
	{	// Use cookie domain of current collection url:
		return $Blog->get_cookie_domain();
	}
}


/**
 * Get cookie path depending on current page:
 *     - For back-office the config var $cookie_path is used
 *     - For front-office it is dynamically generated from collection url
 *
 * @return string Cookie path
 */
function get_cookie_path()
{
	global $Collection, $Blog;

	if( is_admin_page() || empty( $Blog ) )
	{	// Use cookie path of base url from config:
		global $cookie_path;
		return $cookie_path;
	}
	else
	{	// Use base path of current collection url:
		return $Blog->get_cookie_path();
	}
}


/**
 * Set a cookie to send it by evo_sendcookies()
 *
 * @param string The name of the cookie
 * @param string The value of the cookie
 * @param integer The time the cookie expires
 * @param string DEPRECATED: The path on the server in which the cookie will be available on
 * @param string DEPRECATED: The domain that the cookie is available
 * @param boolean Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
 * @param boolean (Added in PHP 5.2.0) When TRUE the cookie will be made accessible only through the HTTP protocol
 */
function evo_setcookie( $name, $value = '', $expire = 0, $dummy = '', $dummy2 = '', $secure = false, $httponly = false )
{
	global $evo_cookies;

	if( ! is_array( $evo_cookies ) )
	{	// Initialize array for cookies only first time:
		$evo_cookies = array();
	}

	// Store cookie in global var:
	$evo_cookies[ $name ] = array(
			'value'    => $value,
			'expire'   => $expire,
			'secure'   => $secure,
			'httponly' => $httponly,
		);
}


/**
 * Send the predefined cookies (@see setcookie() for more details)
 */
function evo_sendcookies()
{
	global $evo_cookies;

	if( headers_sent() )
	{	// Exit to avoid errors because headers already were sent:
		return;
	}

	if( empty( $evo_cookies ) )
	{	// No cookies:
		return;
	}

	$php_version_52 = version_compare( phpversion(), '5.2', '>=' );

	$current_cookie_domain = get_cookie_domain();
	$current_cookie_path = get_cookie_path();

	foreach( $evo_cookies as $evo_cookie_name => $evo_cookie )
	{
		if( $php_version_52 )
		{	// Use HTTP-only setting since PHP 5.2.0:
			setcookie( $evo_cookie_name, $evo_cookie['value'], $evo_cookie['expire'], $current_cookie_path, $current_cookie_domain, $evo_cookie['secure'], $evo_cookie['httponly'] );
		}
		else
		{	// PHP < 5.2 doesn't support HTTP-only:
			setcookie( $evo_cookie_name, $evo_cookie['value'], $evo_cookie['expire'], $current_cookie_path, $current_cookie_domain, $evo_cookie['secure'] );
		}

		// Unset to don't send cookie twice:
		unset( $evo_cookies[ $evo_cookie_name ] );
	}
}


/**
 * Echo JavaScript to edit values of column in the table list
 *
 * @param array Params
 */
function echo_editable_column_js( $params = array() )
{
	$params = array_merge( array(
			'column_selector' => '', // jQuery selector of cell
			'ajax_url'        => '', // AJAX url to update a column value
			'options'         => array(), // Key = Value of option, Value = Title of option
			'new_field_name'  => '', // Name of _POST variable that will be send to ajax request with new value
			'ID_value'        => '', // jQuery to get value of ID
			'ID_name'         => '', // ID of field in DB
			'tooltip'         => TS_('Click to edit'),
			'colored_cells'   => false, // Use TRUE when colors are used for background of cell
			'print_init_tags' => true, // Use FALSE to don't print <script> tags if it is already used inside js
			'field_type'      => 'select', // Type of the editable field: 'select', 'text'
			'field_class'     => '', // Class of the editable field
			'null_text'       => '', // Null text of an input field, Use TS_() to translate it
			'callback_code'   => '', // Additional JS code after main callback code
		), $params );

	// Set onblur action to 'submit' when type is 'text' in order to don't miss the selected user login from autocomplete list
	$onblur_action = $params['field_type'] == 'text' ? 'submit' : 'cancel';

	if( $params['field_type'] == 'select' )
	{
		$options = '';
		if( is_array( $params['options'] ) )
		{
			foreach( $params['options'] as $option_value => $option_title )
			{
				$options .= '\''.$option_value.'\':\''.$option_title.'\','."\n";
			}
		}
	}

	if( $params['print_init_tags'] )
	{
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
<?php
	}
?>
	jQuery( '<?php echo $params['column_selector']; ?>' ).editable( '<?php echo $params['ajax_url']; ?>',
	{
		data: function( value, settings )
		{
			value = ajax_debug_clear( value );
			<?php if( $params['field_type'] == 'select' ) { ?>
			var result = value.match( /rel="([^"]*)"/ );
			<?php
			if( is_array( $params['options'] ) )
			{
				echo "return { ".$options."'selected' : result[1] }";
			}
			else
			{
				echo "return ".$params['options'];
			}
			?>
			<?php } else { ?>
			var result = value.match( />\s*([^<]+)\s*</ );
			return result[1] == '<?php echo $params['null_text'] ?>' ? '' : result[1];
			<?php } ?>
		},
		type       : '<?php echo $params['field_type']; ?>',
		class_name : '<?php echo $params['field_class']; ?>',
		name       : '<?php echo $params['new_field_name']; ?>',
		tooltip    : '<?php echo $params['tooltip']; ?>',
		event      : 'click',
		onblur     : '<?php echo $onblur_action; ?>',
		callback   : function ( settings, original )
		{
			<?php
			if( $params['colored_cells'] )
			{ // Use different color for each value
			?>
			jQuery( this ).html( ajax_debug_clear( settings ) );
			var link = jQuery( this ).find( 'a' );
			jQuery( this ).css( 'background-color', link.attr( 'color' ) == 'none' ? 'transparent' : link.attr( 'color' ) );
			link.removeAttr( 'color' );
			<?php
			}
			else
			{ // Use simple fade effect
			?>
			if( typeof( evoFadeSuccess ) == 'function' )
			{
				evoFadeSuccess( this );
			}
			<?php
			}
			// Execute additional code:
			echo $params['callback_code'];
			?>
		},
		onsubmit: function( settings, original ) {},
		submitdata : function( value, settings )
		{
			return { <?php echo $params['ID_name']; ?>: <?php echo $params['ID_value']; ?> }
		},
		onerror : function( settings, original, xhr )
		{
			if( typeof( evoFadeFailure ) == 'function' )
			{
				evoFadeFailure( original );
			}
			var input = jQuery( original ).find( 'input' );
			if( input.length > 0 )
			{
				jQuery( original ).find( 'span.field_error' ).remove();
				input.addClass( 'field_error' );
				if( typeof( xhr.responseText ) != 'undefined' )
				{
					input.after( '<span class="note field_error">' + xhr.responseText + '</span>' );
				}
			}
		}
	} );
<?php
	if( $params['print_init_tags'] )
	{
?>
} );
</script>
<?php
	}
}


/**
 * Get a button class name depending on template
 *
 * @param string Type: 'button', 'button_text', 'button_group'
 * @param string TRUE - to get class value for jQuery selector
 * @return string Class name
 */
function button_class( $type = 'button', $jQuery_selector = false )
{
	// Default class names
	$classes = array(
			'button'       => 'roundbutton', // Simple button with icon
			'button_red'   => 'roundbutton_red', // Button with red background
			'button_green' => 'roundbutton_green', // Button with green background
			'text'         => 'roundbutton_text', // Button with text
			'text_primary' => 'roundbutton_text', // Button with text with special style color
			'text_success' => 'roundbutton_text', // Button with text with special style color
			'text_danger'  => 'roundbutton_text', // Button with text with special style color
			'group'        => 'roundbutton_group', // Group of the buttons
		);

	if( is_admin_page() )
	{ // Some admin skins may have special class names
		global $AdminUI;
		if( ! empty( $AdminUI ) )
		{
			$template_classes = $AdminUI->get_template( 'button_classes' );
		}
	}
	else
	{ // Some front end skins may have special class names
		global $Skin;
		if( ! empty( $Skin ) )
		{
			$template_classes = $Skin->get_template( 'button_classes' );
		}
	}
	if( !empty( $template_classes ) )
	{ // Get class names from admin template
		$classes = array_merge( $classes, $template_classes );
	}

	$class_name = isset( $classes[ $type ] ) ? $classes[ $type ] : '';

	if( $jQuery_selector && ! empty( $class_name ) )
	{ // Convert class name to jQuery selector
		$class_name = '.'.str_replace( ' ', '.', $class_name );
	}

	return $class_name;
}


/**
 * Initialize JavaScript to build and open window
 */
function echo_modalwindow_js()
{
	global $AdminUI, $Collection, $Blog, $modal_window_js_initialized;

	if( ! empty( $modal_window_js_initialized ) )
	{ // Don't print out these functions twice
		return;
	}

	// TODO: asimo> Should not use AdminUI templates for the openModalWindow function. The style part should be handled by css.
	if( is_admin_page() && isset( $AdminUI ) && $AdminUI->get_template( 'modal_window_js_func' ) !== false )
	{ // Use the modal functions from back-office skin
		$skin_modal_window_js_func = $AdminUI->get_template( 'modal_window_js_func' );
	}
	elseif( ! is_admin_page() && ! empty( $Blog ) )
	{ // Use the modal functions from front-office skin
		$blog_skin_ID = $Blog->get_skin_ID();
		$SkinCache = & get_SkinCache();
		$Skin = & $SkinCache->get_by_ID( $blog_skin_ID, false, false );
		if( $Skin && $Skin->get_template( 'modal_window_js_func' ) !== false )
		{
			$skin_modal_window_js_func = $Skin->get_template( 'modal_window_js_func' );
		}
	}

	if( ! empty( $skin_modal_window_js_func ) && is_string( $skin_modal_window_js_func ) && function_exists( $skin_modal_window_js_func ) )
	{ // Call skin function only if it exists
		call_user_func( $skin_modal_window_js_func );
		$modal_window_js_initialized = true;
		return;
	}

	$modal_window_js_initialized = true;
}

/**
 * Initialize JavaScript to build and open window for bootstrap skins
 */
function echo_modalwindow_js_bootstrap()
{
	// Initialize variables for the file "bootstrap-evo_modal_window.js":
	echo '<script type="text/javascript">
		var evo_js_lang_close = \''.TS_('Close').'\'
		var evo_js_lang_loading = \''.TS_('Loading...').'\';
	</script>';
}


/**
 * Handle fatal error in order to display info message when debug is OFF
 */
function evo_error_handler()
{
	global $evo_last_handled_error;

	// Get last error
	$error = error_get_last();

	if( ! empty( $error ) && $error['type'] === E_ERROR )
	{ // Save only last fatal error
		$evo_last_handled_error = $error;
	}

	// fp> WTF?!? and what about warnings?
	// fp> And where do we die()? why is there not a debug_die() here?
	// There should be ONE MILLION COMMENTS in this function to explain what we do!

}


/**
 * Get icon to collapse/expand fieldset
 *
 * @param string ID of fieldset
 * @param array Params
 * @return string Icon with hidden input field
 */
function get_fieldset_folding_icon( $id, $params = array() )
{
	if( ! is_logged_in() )
	{ // Only loggedin users can fold fieldset
		return;
	}

	$params = array_merge( array(
			'before'    => '',
			'after'     => ' ',
			'deny_fold' => false, // TRUE to don't allow fold the block and keep it opened always on page loading
		), $params );

	if( $params['deny_fold'] )
	{ // Deny folding for this case
		$value = 0;
	}
	else
	{ // Get the fold value from user settings
		global $UserSettings, $Collection, $Blog;
		if( empty( $Blog ) )
		{ // Get user setting value
			$value = intval( $UserSettings->get( 'fold_'.$id ) );
		}
		else
		{ // Get user-collection setting
			$value = intval( $UserSettings->get_collection_setting( 'fold_'.$id, $Blog->ID ) );
		}
	}

	// Icon
	if( $value )
	{
		$icon_current = 'filters_show';
		$icon_reverse = 'filters_hide';
		$title_reverse = T_('Collapse');
	}
	else
	{
		$icon_current = 'filters_hide';
		$icon_reverse = 'filters_show';
		$title_reverse = T_('Expand');
	}
	$icon = get_icon( $icon_current, 'imgtag', array(
			'id'         => 'icon_folding_'.$id,
			'data-xy'    => get_icon( $icon_reverse, 'xy' ),
			'data-title' => format_to_output( $title_reverse, 'htmlattr' ),
		) );

	// Hidden input to store current value of the folding status
	$hidden_input = '<input type="hidden" name="folding_values['.$id.']" id="folding_value_'.$id.'" value="'.$value.'" />';

	return $hidden_input.$params['before'].$icon.$params['after'];
}


/**
 * Output JavaScript code to collapse/expand fieldset
 */
function echo_fieldset_folding_js()
{
	if( ! is_logged_in() )
	{ // Only loggedin users can fold fieldset
		return;
	}

?>
<script type="text/javascript">
jQuery( 'span[id^=icon_folding_], span[id^=title_folding_]' ).click( function()
{
	var is_icon = jQuery( this ).attr( 'id' ).match( /^icon_folding_/ );
	var wrapper_obj = jQuery( this ).closest( '.fieldset_wrapper' );
	var value_obj = is_icon ? jQuery( this ).prev() : jQuery( this ).prev().prev();

	if( wrapper_obj.length == 0 || value_obj.length == 0 )
	{ // Invalid layout
		return false;
	}

	if( value_obj.val() == '1' )
	{ // Collapse
		wrapper_obj.removeClass( 'folded' );
		value_obj.val( '0' );
	}
	else
	{ // Expand
		wrapper_obj.addClass( 'folded' );
		value_obj.val( '1' );
	}

	// Change icon image
	var clickimg = is_icon ? jQuery( this ) : jQuery( this ).prev();
	if( clickimg.hasClass( 'fa' ) || clickimg.hasClass( 'glyphicon' ) )
	{ // Fontawesome icon | Glyph bootstrap icon
		if( clickimg.data( 'toggle' ) != '' )
		{ // This icon has a class name to toggle
			var icon_prefix = ( clickimg.hasClass( 'fa' ) ? 'fa' : 'glyphicon' );
			if( clickimg.data( 'toggle-orig-class' ) == undefined )
			{ // Store original class name in data
				clickimg.data( 'toggle-orig-class', clickimg.attr( 'class' ).replace( new RegExp( '^'+icon_prefix+' (.+)$', 'g' ), '$1' ) );
			}
			if( clickimg.hasClass( clickimg.data( 'toggle-orig-class' ) ) )
			{ // Replace original class name with exnpanded
				clickimg.removeClass( clickimg.data( 'toggle-orig-class' ) )
					.addClass( icon_prefix + '-' + clickimg.data( 'toggle' ) );
			}
			else
			{ // Revert back original class
				clickimg.removeClass( icon_prefix + '-' + clickimg.data( 'toggle' ) )
					.addClass( clickimg.data( 'toggle-orig-class' ) );
			}
		}
	}
	else
	{ // Sprite icon
		var icon_bg_pos = clickimg.css( 'background-position' );
		clickimg.css( 'background-position', clickimg.data( 'xy' ) );
		clickimg.data( 'xy', icon_bg_pos );
	}

	// Toggle title
	var title = clickimg.attr( 'title' );
	clickimg.attr( 'title', clickimg.data( 'title' ) );
	clickimg.data( 'title', title );
} );

jQuery( 'input[type=hidden][id^=folding_value_]' ).each( function()
{ // Check each feildset is folded correctly after refresh a page
	var wrapper_obj = jQuery( this ).closest( '.fieldset_wrapper' );
	if( jQuery( this ).val() == '1' )
	{ // Collapse
		wrapper_obj.addClass( 'folded' );
	}
	else
	{ // Expand
		wrapper_obj.removeClass( 'folded' );
	}
} );

// Expand all fieldsets that have the fields with error
jQuery( '.field_error' ).closest( '.fieldset_wrapper.folded' ).find( 'span[id^=icon_folding_]' ).click();
</script>
<?php
}


/**
 * Save the values of fieldset folding into DB
 *
 * @param integer Blog ID is used to save setting per blog, NULL- to don't save per blog
 */
function save_fieldset_folding_values( $blog_ID = NULL )
{
	if( ! is_logged_in() )
	{ // Only loggedin users can fold fieldset
		return;
	}

	$folding_values = param( 'folding_values', 'array:integer' );

	if( empty( $folding_values ) )
	{ // No folding values go from request, Exit here
		return;
	}

	global $UserSettings;

	foreach( $folding_values as $key => $value )
	{
		$setting_name = 'fold_'.$key;
		if( $blog_ID !== NULL )
		{ // Save setting per blog
			$setting_name .= '_'.$blog_ID;
		}
		$UserSettings->set( $setting_name, $value );
	}

	// Update the folding setting for current user
	$UserSettings->dbupdate();
}


/**
 * Get html code of bootstrap dropdown element
 *
 * @param array Params
 */
function get_status_dropdown_button( $params = array() )
{
	$params = array_merge( array(
			'name'             => '',
			'value'            => '',
			'title_format'     => '',
			'options'          => NULL,
			'exclude_statuses' => array( 'trash' ),
		), $params );

	if( $params['options'] === NULL )
	{	// Get status options by title format:
		$status_options = get_visibility_statuses( $params['title_format'], $params['exclude_statuses'] );
	}
	else
	{	// Use status options from params:
		$status_options = $params['options'];
	}
	$status_icon_options = get_visibility_statuses( 'icons', $params['exclude_statuses'] );

	$r = '<div class="btn-group dropdown autoselected" data-toggle="tooltip" data-placement="top" data-container="body" title="'.get_status_tooltip_title( $params['value'] ).'">';
	$r .= '<button type="button" class="btn btn-status-'.$params['value'].' dropdown-toggle" data-toggle="dropdown" aria-expanded="false">'
					.'<span>'.$status_options[ $params['value'] ].'</span>'
				.' <span class="caret"></span></button>';
	$r .= '<ul class="dropdown-menu" role="menu" aria-labelledby="'.$params['name'].'">';
	foreach( $status_options as $status_key => $status_title )
	{
		$r .= '<li rel="'.$status_key.'" role="presentation"><a href="#" role="menuitem" tabindex="-1">'.$status_icon_options[ $status_key ].' <span>'.$status_title.'</span></a></li>';
	}
	$r .= '</ul>';
	$r .= '</div>';

	return $r;
}

/**
 * Output JavaScript code to work with dropdown bootstrap element
 */
function echo_form_dropdown_js()
{
	// Build a string to initialize javascript array with button titles
	$tooltip_titles = get_visibility_statuses( 'tooltip-titles' );
	$tooltip_titles_js_array = array();
	foreach( $tooltip_titles as $status => $tooltip_title )
	{
		$tooltip_titles_js_array[] = $status.': \''.TS_( $tooltip_title ).'\'';
	}
	$tooltip_titles_js_array = implode( ', ', $tooltip_titles_js_array );
?>
<script type="text/javascript">
jQuery( '.btn-group.dropdown.autoselected li a' ).click( function()
{
	var item_status_tooltips = {<?php echo $tooltip_titles_js_array ?>};
	var item = jQuery( this ).parent();
	var status = item.attr( 'rel' );
	var btn_group = item.parent().parent();
	var button = jQuery( item.parent().parent().find( 'button:first' ) );
	var field_name = jQuery( this ).parent().parent().attr( 'aria-labelledby' );

	// Change status class name to new changed for all buttons:
	button.attr( 'class', button.attr( 'class' ).replace( /btn-status-[^\s]+/, 'btn-status-' + status ) );
	// Update selector button to status title:
	button.find( 'span:first' ).html( item.find( 'span:last' ).html() ); // update selector button to status title
	// Update hidden field to new status value:
	jQuery( 'input[type=hidden][name=' + field_name + ']' ).val( status );
	// Hide dropdown menu:
	item.parent().parent().removeClass( 'open' );

	// Update tooltip
	btn_group.tooltip( 'hide' ).attr( 'data-original-title', item_status_tooltips[status] ).tooltip( 'show' );

	return false;
} );
</script>
<?php
}


/**
 * Get baseurl depending on current called script
 *
 * @return string URL
 */
function get_script_baseurl()
{
	if( isset( $_SERVER['SERVER_NAME'] ) )
	{ // Set baseurl from current server name

		$temp_baseurl = 'http://'.$_SERVER['SERVER_NAME'];

		if( isset( $_SERVER['SERVER_PORT'] ) )
		{
			if( $_SERVER['SERVER_PORT'] == '443' )
			{	// Rewrite that as hhtps:
				$temp_baseurl = 'https://'.$_SERVER['SERVER_NAME'];
			}	// Add port name
			elseif( $_SERVER['SERVER_PORT'] != '80' )
			{ // Get also a port number
				$temp_baseurl .= ':'.$_SERVER['SERVER_PORT'];
			}
		}

		if( isset( $_SERVER['SCRIPT_NAME'] ) )
		{ // Get also the subfolders, when script is called e.g. from http://localhost/blogs/b2evolution/
			$temp_baseurl .= preg_replace( '~(.*/)[^/]*$~', '$1', $_SERVER['SCRIPT_NAME'] );
		}
	}
	else
	{ // Use baseurl from config
		global $baseurl;
		$temp_baseurl = $baseurl;
	}

	return $temp_baseurl;
}


/**
 * Get badge to inform the settings are edited only by collection/user admins
 *
 * @param string Type: 'coll', 'user'
 * @param string Manual URL, '#' - default, false - don't set URL
 * @param string Text
 * @param string Title
 * @param string Value
 * @return string
 */
function get_admin_badge( $type = 'coll', $manual_url = '#', $text = '#', $title = '#', $value = NULL )
{
	switch( $type )
	{
		case 'coll':
			if( $text == '#' )
			{	// Use default text:
				$text = T_('Coll. Admin');
			}
			if( $title == '#' )
			{	// Use default title:
				$title = T_('This can only be edited by users with the Collection Admin permission.');
			}
			if( $manual_url == '#' )
			{	// Use default manual url:
				$manual_url = 'collection-admin';
			}
			break;

		case 'user':
			if( $text == '#' )
			{	// Use default text:
				$text = T_('User Admin');
			}
			if( $title == '#' )
			{	// Use default title:
				$title = T_('This can only be edited by users with the User Admin permission.');
			}
			if( $manual_url == '#' )
			{	// Use default manual url:
				$manual_url = 'user-admin';
			}
			break;

		default:
			// Unknown badge type:
			return '';
	}

	if( empty( $manual_url ) )
	{	// Don't use a link:
		$r = ' <b';
	}
	else
	{	// Use link:
		$r = ' <a href="'.get_manual_url( $manual_url ).'" target="_blank"';
	}
	$r .= ' class="badge badge-warning"';
	if( ! empty( $title ) && $title != '#' )
	{	// Use title for tooltip:
		$r .= ' data-toggle="tooltip" data-placement="top" title="'.format_to_output( $title, 'htmlattr' ).'"';
	}
	$r .= '>';
	$r .= $text;
	if( empty( $manual_url ) )
	{	// End of text formatted badge:
		$r .= '</b>';
	}
	else
	{	// End of the link:
		$r .= '</a>';
	}

	return $r;
}


/**
 * Compares two "PHP-standardized" version number strings
 *
 * @param string First version number, Use 'current' for global $app_version
 * @param string Second version number
 * @param string If the third optional operator argument is specified, test for a particular relationship.
 *               The possible operators are: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively.
 *               This parameter is case-sensitive, values should be lowercase.
 * @return integer|boolean -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower.
 *                         When using the optional operator argument, the function will return TRUE if the relationship is the one specified by the operator, FALSE otherwise.
 */
function evo_version_compare( $version1, $version2, $operator = NULL )
{
	if( $version1 === 'current' )
	{	// Use current version of application:
		global $app_version;
		$version1 = $app_version;
	}

	// Remove "stable" suffix to compare such versions as upper than "alpha", "beta" and etc.:
	$version1 = str_replace( '-stable', '', $version1 );
	$version2 = str_replace( '-stable', '', $version2 );

	if( is_null( $operator ) )
	{	// To return integer:
		return version_compare( $version1, $version2 );
	}
	else
	{	// To return boolean:
		return version_compare( $version1, $version2, $operator );
	}
}


/**
 * Get text for install page depending on param $display == 'cli'
 *
 * @param string Original text
 * @param string Format (Used for CLI mode)
 * @return string Prepared text
 */
function get_install_format_text( $text, $format = 'string' )
{
	global $display;

	if( empty( $display ) || $display != 'cli' )
	{	// Don't touch text for non CLI modes:
		return $text;
	}

	// Don't remove these HTML tags on CLI mode:
	$allowable_html_tags = '<evo:error><evo:warning><evo:success><evo:note><evo:login><evo:password>';

	// Remove all new lines because we build them from requested format:
	$text = str_replace( array( "\n", "\r" ), '', $text );

	// Keep all URLs and display them
	$text = preg_replace( '/<a[^>]+href="([^"]+)"[^>]*>(.+)<\/a>/i', '$2(URL: $1)', $text );

	// Remove HTML tags from text:
	$text = strip_tags( $text, $allowable_html_tags );

	switch( $format )
	{
		case 'h2':
			// Header 2
			$text = "\n\n----- ".$text." -----\n\n";
			break;

		case 'br':
			// Paragraph:
			$text = $text."\n";
			break;

		case 'p':
			// Paragraph:
			$text = "\n".$text."\n\n";
			break;

		case 'p-start':
			// Start paragraph:
			$text = "\n".$text;
			break;

		case 'p-start-br':
			// Start paragraph:
			$text = "\n".$text."\n";
			break;

		case 'p-end':
			// End paragraph:
			$text = $text."\n\n";
			break;

		case 'li':
			// List item:
			$text = "\n- ".$text."\n";
			break;

		case 'code':
			// Code:
			$text = "\n================\n".$text."\n================\n";
			break;
	}

	// Replace all html entities like "&nbsp;", "&raquo;", "&laquo;" to readable chars:
	$text = html_entity_decode( $text );

	return $text;
}


/**
 * Check if password should be transmitted in hashed format during Login
 *
 * @return boolean TRUE - hashed password will be transmitted, FALSE - raw password will be transmitted
 */
function can_use_hashed_password()
{
	global $transmit_hashed_password;

	if( isset( $transmit_hashed_password ) )
	{	// Get value from already defined var:
		return $transmit_hashed_password;
	}

	global $Settings, $Plugins;

	// Allow to transmit hashed password only when:
	// - it is enabled by general setting "Password hashing during Login"
	// - no plugins that automatically disable this option during Login
	$transmit_hashed_password = (bool)$Settings->get( 'js_passwd_hashing' ) && !(bool)$Plugins->trigger_event_first_true( 'LoginAttemptNeedsRawPassword' );

	return $transmit_hashed_password;
}


/**
 * Convert inline file tags like [image|file:123:link title:.css_class_name] or [inline:123:.css_class_name] into HTML tags
 *
 * @param string Source content
 * @param object Source object
 * @param array Params
 * @return string Content
 */
function render_inline_files( $content, $Object, $params = array() )
{
	if( isset( $params['check_code_block'] ) && $params['check_code_block'] && ( ( stristr( $content, '<code' ) !== false ) || ( stristr( $content, '<pre' ) !== false ) ) )
	{	// Call render_inline_files() on everything outside code/pre:
		$params['check_code_block'] = false;
		$content = callback_on_non_matching_blocks( $content,
			'~<(code|pre)[^>]*>.*?</\1>~is',
			'render_inline_files', array( $Object, $params ) );
		return $content;
	}

	// No code/pre blocks, replace on the whole thing

	// Remove block level short tags inside <p> blocks and move them before the paragraph
	$content = move_short_tags( $content );

	// Find all matches with inline tags
	preg_match_all( '/\[(image|file|inline|video|audio|thumbnail):(\d+)(:?)([^\]]*)\]/i', $content, $inlines );

	if( !empty( $inlines[0] ) )
	{	// There are inline tags in the content...

		$rendered_tags = render_inline_tags( $Object, $inlines[0], $params );
		if( $rendered_tags )
		{	// Do replacing if the object really contains inline attached tags:
			foreach( $rendered_tags as $current_link_tag => $rendered_link_tag )
			{
				$content = str_replace( $current_link_tag, $rendered_link_tag, $content );
			}
		}	
	}

	return $content;
}


/**
 * Convert inline tags like [image:|file:|inline:|video:|audio:|thumbnail:] into HTML tags
 *
 * @param object Source object: Item, EmailCampaign
 * @param array Inline tags
 * @param array Params
 * @return array Associative array of rendered HTML tags with inline tags as key
 */
function render_inline_tags( $Object, $tags, $params = array() )
{
	global $Plugins;
	$inlines = array();

	$params = array_merge( array(
				'before'                   => '<div>',
				'before_image'             => '<div class="image_block">',
				'before_image_legend'      => '<div class="image_legend">',
				'after_image_legend'       => '</div>',
				'after_image'              => '</div>',
				'after'                    => '</div>',
				'image_size'               => 'fit-400x320',
				'image_link_to'            => 'original', // Can be 'orginal' (image) or 'single' (this post)
				'limit'                    => 1000, // Max # of images displayed
				'get_rendered_attachments' => true,
			), $params );

	$object_class = get_class( $Object );

	if( !isset( $LinkList ) )
	{	// Get list of attached Links only first time:
		if( $Object->ID == 0 )
		{	// Get temporary object ID on preview new creating object:
			$temp_link_owner_ID = param( 'temp_link_owner_ID', 'integer', NULL );
		}
		else
		{	// Don't use temporary object for existing object:
			$temp_link_owner_ID = NULL;
		}
		switch( $object_class )
		{
			case 'Item':
				$LinkOwner = new LinkItem( $Object, $temp_link_owner_ID );
				$prepare_plugin_event_name = 'PrepareForRenderItemAttachment';
				$render_plugin_event_name = 'RenderItemAttachment';
				break;

			case 'EmailCampaign':
				$LinkOwner = new LinkEmailCampaign( $Object );
				$prepare_plugin_event_name = 'PrepareForRenderEmailAttachment';
				$render_plugin_event_name = 'RenderEmailAttachment';
				break;

			case 'Message':
				$LinkOwner = new LinkMessage( $Object, $temp_link_owner_ID );
				$prepare_plugin_event_name = 'PrepareForRenderMessageAttachment';
				$render_plugin_event_name = 'RenderMessageAttachment';
				break;

			default:
				// Wrong source object type:
				return false;
		}
		$LinkList = $LinkOwner->get_attachment_LinkList( $params['limit'], 'inline' );
	}

	if( empty( $LinkList ) )
	{ // This Item has no attached files for 'inline' position, Exit here
		return false;
	}

	foreach( $tags as $current_inline )
	{
		preg_match("/\[(image|file|inline|video|audio|thumbnail):(\d+)(:?)([^\]]*)\]/i", $current_inline, $inline);

		if( empty( $inline ) )
		{
			$inlines[$current_inline] = $current_inline;
			continue;
		}

		$inline_type = $inline[1]; // image|file|inline|video|audio|thumbnail
		$current_link_ID = (int) $inline[2];

		if( empty( $current_link_ID ) )
		{ // Invalid link ID, Go to next match
			$inlines[$current_inline] = $current_inline;
			continue;
		}

		if( ! ( $Link = & $LinkList->get_by_field( 'link_ID', $current_link_ID ) ) )
		{ // Link ID is not part of the linked files for position "inline"
			$inlines[$current_inline] = $current_inline;
			continue;
		}

		if( ! ( $File = & $Link->get_File() ) )
		{ // No File object:
			global $Debuglog;
			$Debuglog->add( sprintf( 'Link ID#%d of '.$object_class.' #%d does not have a file object!', $Link->ID, $Object->ID ), array( 'error', 'files' ) );
			$inlines[$current_inline] = $current_inline;
			continue;
		}

		if( ! $File->exists() )
		{ // File doesn't exist:
			global $Debuglog;
			$Debuglog->add( sprintf( 'File linked to '.$object_class.' #%d does not exist (%s)!', $Object->ID, $File->get_full_path() ), array( 'error', 'files' ) );
			$inlines[$current_inline] = $current_inline;
			continue;
		}

		$params['File'] = $File;
		$params['Link'] = $Link;
		$params[ $object_class ] = $Object;

		$current_file_params = array();

		switch( $inline_type )
		{
			case 'image':
			case 'inline': // valid file type: image
				if( $File->is_image() )
				{
					$current_image_params = $params;

					if( ! empty( $inline[3] ) ) // check if second colon is present
					{
						// Get the inline params: caption and class
						$inline_params = explode( ':.', $inline[4] );

						if( ! empty( $inline_params[0] ) )
						{ // Caption is set, so overwrite the image link title
							if( $inline_params[0] == '-' )
							{ // Caption display is disabled
								$current_image_params['image_link_title'] = '';
								$current_image_params['hide_image_link_title'] = true;
							}
							else
							{ // New image caption was set
								$current_image_params['image_link_title'] = strip_tags( $inline_params[0] );
							}

							$current_image_params['image_desc'] = $current_image_params['image_link_title'];
							$current_file_params['title'] = $inline_params[0];
						}

						$class_index = ( $inline_type == 'inline' ) ? 0 : 1; // [inline] tag doesn't have a caption, so 0 index is for class param
						if( ! empty( $inline_params[ $class_index ] ) )
						{ // A class name is set for the inline tags
							$image_extraclass = strip_tags( trim( str_replace( '.', ' ', $inline_params[ $class_index ] ) ) );
							if( preg_match('#^[A-Za-z0-9\s\-_]+$#', $image_extraclass ) )
							{	// Overwrite 'before_image' setting to add an extra class name:
								$current_image_params['before_image'] = update_html_tag_attribs( $current_image_params['before_image'], array( 'class' => $image_extraclass ) );

								// Append extra class to file inline img tags:
								$current_file_params['class'] = $image_extraclass;
							}
						}
					}

					if( ! $current_image_params['get_rendered_attachments'] )
					{ // Save $r to temp var in order to don't get the rendered data from plugins
						$temp_r = $r;
					}

					$temp_params = $current_image_params;
					foreach( $current_image_params as $param_key => $param_value )
					{ 	// Pass all params by reference, in order to give possibility to modify them by plugin
						// So plugins can add some data before/after image tags (E.g. used by infodots plugin)
						$current_image_params[ $param_key ] = & $current_image_params[ $param_key ];
					}

					// Prepare params before rendering attachment:
					$Plugins->trigger_event_first_true_with_params( $prepare_plugin_event_name, $current_image_params );

					// Render attachments by plugin, Append the html content to $current_image_params['data'] and to $r:
					if( count( $Plugins->trigger_event_first_true( $render_plugin_event_name, $current_image_params ) ) != 0 )
					{	// This attachment has been rendered by a plugin (to $current_image_params['data']):
						if( ! $current_image_params['get_rendered_attachments'] )
						{	// Restore $r value and mark this item has the rendered attachments:
							$r = $temp_r;
							$plugin_render_attachments = true;
						}
					}

					if( $inline_type == 'image' )
					{	// Generate the IMG tag with all the alt, title and desc if available:
						switch( $object_class )
						{
							case 'Item':
								// Get the IMG tag with link to original image or to Item page:
								$inlines[ $current_inline ] = $Object->get_attached_image_tag( $Link, $current_image_params );
								break;

							case 'EmailCampaign':
								// Get the IMG tag without link for email content:
								$inlines[ $current_inline ] = $Link->get_tag( array_merge( $current_image_params, array(
										'image_link_to' => false
									) ) );
								break;

							default:
								// Get the IMG tag with link to original big image:
								$inlines[ $current_inline ] = $Link->get_tag( array_merge( $params, $current_image_params ) );
								break;
						}
					}
					elseif( $inline_type == 'inline' )
					{	// Generate simple IMG tag with resized image size:
						$inlines[ $current_inline ] = $File->get_tag( '', '', '', '', $current_image_params['image_size'], '', '', '',
							( empty( $current_file_params['class'] ) ? '' : $current_file_params['class'] ), '', '', '' );
					}
				}
				else
				{ // not an image file, do not process
					$inlines[$current_inline] = $current_inline;
				}
				break;

			case 'thumbnail':
				if( $File->is_image() )
				{
					global $thumbnail_sizes;

					$thumbnail_size = 'medium';
					$thumbnail_position = 'left';

					$thumbnail_classes = array();

					if( ! empty( $inline[3] ) ) // check if second colon is present
					{
						// Get the inline params: caption and class
						$inline_params = explode( ':', $inline[4] );

						$valid_thumbnail_sizes = array( 'small', 'medium', 'large' );
						if( ! empty( $inline_params[0] ) && in_array( $inline_params[0], $valid_thumbnail_sizes ) )
						{
							$thumbnail_size = $inline_params[0];
						}

						$valid_thumbnail_positions = array( 'left', 'right' );
						if( ! empty( $inline_params[1] ) && in_array( $inline_params[1], $valid_thumbnail_positions ) )
						{
							$thumbnail_position = $inline_params[1];
						}

						if( ! empty( $inline_params[2] ) )
						{
							$extra_classes = explode( '.', ltrim( $inline_params[2], '.' ) );
						}
					}

					switch( $thumbnail_size )
					{
						case 'small':
							$thumbnail_size = 'fit-128x128';
							break;

						case 'large':
							$thumbnail_size = 'fit-320x320';
							break;

						case 'medium':
						default:
							$thumbnail_size = 'fit-192x192';
							break;
					}

					$thumbnail_classes[] = 'evo_thumbnail';
					$thumbnail_classes[] = 'evo_thumbnail__'.$thumbnail_position;
					if( isset( $extra_classes ) )
					{
						$thumbnail_classes = array_merge( $thumbnail_classes, $extra_classes );
					}

					$current_image_params = array(
						'before_image'        => '',
						'before_image_legend' => '', // can be NULL
						'after_image_legend'  => '',
						'after_image'         => '',
						'image_size'          => $thumbnail_size,
						'image_link_to'       => 'original',
						'image_link_title'    => '',	// can be text or #title# or #desc#
						'image_class'         => implode( ' ', $thumbnail_classes ),
					);

					switch( $object_class )
					{
						case 'Item':
							// Get the IMG tag with link to original image or to Item page:
							$inlines[ $current_inline ] = $Object->get_attached_image_tag( $Link, $current_image_params );
							break;

						case 'EmailCampaign':
							// Get the IMG tag without link for email content:
							$inlines[ $current_inline ] = $Link->get_tag( array_merge( $current_image_params, array(
									'image_link_to' => false
								) ) );
							break;

						default:
							// Get the IMG tag with link to original big image:
							$inlines[ $current_inline ] = $Link->get_tag( array_merge( $params, $current_image_params ) );
							break;
					}
				}
				else
				{
					continue;
				}
				break;

			case 'file': // valid file types: image, video, audio, other
				$valid_file_types = array( 'image', 'video', 'audio', 'other' );
				if( in_array( $File->get_file_type(), $valid_file_types ) )
				{
					if( ! empty( $inline[3] ) ) // check if second colon is present
					{
						// Get the file caption
						$caption = $inline[4];

						if( ! empty( $caption ) )
						{ // Caption is set
							$current_file_params['title'] = strip_tags( $caption );
						}
					}

					if( empty( $current_file_params['title'] ) )
					{ // Use real file name as title when it is not defined for inline tag
						$file_title = $File->get( 'title' );
						$current_file_params['title'] = ' '.( empty( $file_title ) ? $File->get_name() : $file_title );
					}
					elseif( $current_file_params['title'] == '-' )
					{ // Don't display a title in this case, Only file icon will be displayed
						$current_file_params['title'] = '';
					}
					else
					{ // Add a space between file icon and title
						$current_file_params['title'] = ' '.$current_file_params['title'];
					}

					$inlines[$current_inline] = '<a href="'.$File->get_url().'"'
						.( empty( $current_file_params['class'] ) ? '' : ' class="'.$current_file_params['class'].'"' )
						.'>'.$File->get_icon( $current_file_params ).$current_file_params['title'].'</a>';
				}
				else
				{ // not a valid file type, do not process
					$inlines[$current_inline] = $current_inline;
				}
				break;

			case 'video':	// valid file type: video
				if( $File->is_video() )
				{
					$current_video_params = $params;
					// Create an empty dummy element where the plugin is expected to append the rendered video
					$current_video_params['data'] = '';

					foreach( $current_video_params as $param_key => $param_value )
					{ // Pass all params by reference, in order to give possibility to modify them by plugin
						// So plugins can add some data before/after tags (E.g. used by infodots plugin)
						$current_video_params[ $param_key ] = & $current_video_params[ $param_key ];
					}

					// Prepare params before rendering attachment:
					$Plugins->trigger_event_first_true_with_params( $prepare_plugin_event_name, $current_video_params );

					// Render attachments by plugin:
					if( count( $Plugins->trigger_event_first_true( $render_plugin_event_name, $current_video_params ) ) != 0 )
					{	// This attachment has been rendered by a plugin (to $current_video_params['data']):
						$inlines[$current_inline] = $current_video_params['data'];
					}
					else
					{ // no plugin available or was able to render the tag
						$inlines[$current_inline] = $current_inline;
					}
				}
				else
				{ // not a video file, do not process
					$inlines[$current_inline] = $current_inline;
				}
				break;

			case 'audio': // valid file type: audio
				if( $File->is_audio() )
				{
					$current_audio_params = $params;
					// Create an empty dummy element where the plugin is expected to append the rendered video
					$current_audio_params['data'] = '';

					foreach( $current_audio_params as $param_key => $param_value )
					{ // Pass all params by reference, in order to give possibility to modify them by plugin
						// So plugins can add some data before/after tags (E.g. used by infodots plugin)
						$current_audio_params[ $param_key ] = & $current_audio_params[ $param_key ];
					}

					// Prepare params before rendering attachment:
					$Plugins->trigger_event_first_true_with_params( $prepare_plugin_event_name, $current_audio_params );

					// Render attachments by plugin:
					if( count( $Plugins->trigger_event_first_true( $render_plugin_event_name, $current_audio_params ) ) != 0 )
					{	// This attachment has been rendered by a plugin (to $current_audio_params['data']):
						$inlines[$current_inline] =  $current_audio_params['data'];
					}
					else
					{ // no plugin available or was able to render the tag
						$inlines[$current_inline] = $current_inline;
					}
				}
				else
				{ // not a video file, do not process
					$inlines[$current_inline] = $current_inline;
				}
				break;

			default:
				$inlines[$current_inline] = $current_inline;
		}
	}

	return $inlines;
}

function php_to_jquery_date_format( $php_format )
{
	$tokens = array(
			// Day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => '' );

	$js_format = "";
	$escaping = false;
	for( $i = 0; $i < strlen( $php_format ); $i++ )
	{
		$char = $php_format[$i];
		if($char === '\\') // PHP date format escaping character
		{
			$i++;
			if( $escaping ) $js_format .= $php_format[$i];
			else $js_format .= '\'' . $php_format[$i];
			$escaping = true;
		}
		else
		{
			if( $escaping )
			{
				$jqueryui_format .= "'"; $escaping = false;
			}
			if( isset( $tokens[$char] ) )
			{
				$js_format .= $tokens[$char];
			}
			else
			{
				$js_format .= $char;
			}
		}
	}

	return $js_format;
}
?>