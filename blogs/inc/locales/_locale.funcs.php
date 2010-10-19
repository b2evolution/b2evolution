<?php
/**
 * This file implements functions for handling locales and i18n.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @todo Make it a class / global object!
 *        - Provide (static) functions to extract .po files / generate _global.php files (single quoted strings!)
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_locale'] = false;


// LOCALIZATION:
if( $use_l10n )
{ // We are going to use localization:

	/**
	 * TRANSLATE!
	 *
	 * Translate a text to the desired locale or to the current locale.
	 *
	 * @param string String to translate, '' to get language file info (as in gettext spec)
	 * @param string locale to translate to, '' to use current locale
	 * @param array Array containing the following keys (all optional):
	 *              - 'ext_transarray': A reference to an alternate array
	 *                                  to use for the caching of the
	 *                                  translated strings or NULL to use
	 *                                  the internal array.
	 *              - 'alt_basedir': Alternate base directory to search
	 *                               for translations, e. g. a plugin or
	 *                               skin directory.
	 *              - 'for_helper': (boolean) Is the translation for the b2evoHelper object?
	 * @return string The translated string or the original string on error.
	 *
	 * @internal The last parameter/its 'alt_basedir' key is used by
	 *           Plugin::T_() and Skin::T_().
	 */
	function T_( $string, $req_locale = '', $params = array() )
	{
		/**
		 * The translations keyed by locale.
		 *
		 * This array is only used if $params['ext_transarray'] === NULL.
		 *
		 * @var array
		 * @static
		 */
		static $_trans = array();

		global $current_locale, $locales, $locales_path, $plugins_path;
		global $evo_charset, $Debuglog;

		$params = array_merge( array(
								'ext_transarray' => NULL,
								'alt_basedir'    => '',
								'for_helper'     => false,
								), $params );

		if( empty( $req_locale ) )
		{ // By default we use the current locale
			if( empty( $current_locale ) )
			{ // don't translate if we have no locale
				return $string;
			}

			$req_locale = $current_locale;
		}

		if( ! isset( $locales[$req_locale]['messages'] ) )
		{
			$Debuglog->add( 'No messages file path for locale. $locales["'
					.$req_locale.'"] is '.var_export( @$locales[$req_locale], true ), 'locale' );

			if( ! empty( $evo_charset ) ) // this extra check is needed, because $evo_charset may not yet be determined.. :/
			{
				$string = convert_charset( $string, $evo_charset, 'iso-8859-1' );
			}
			return $string;
		}

		$messages = $locales[$req_locale]['messages'];

		if ( is_null( $params['ext_transarray'] ) )
		{	// use our array
			//$Debuglog->add( 'Using internal array', 'locale' );
			$trans = & $_trans;
		}
		else
		{	// use external array:
			//$Debuglog->add( 'Using external array', 'locale' );
			$trans = & $params['ext_transarray'];
		}

		if( ! isset( $trans[ $messages ] ) )
		{ // Translations for current locale have not yet been loaded:
			if ( $params['alt_basedir'] != '' )
			{	// Load the translation file from the alternative base dir:
				//$Debuglog->add( 'Using alternative basedir ['.$params['alt_basedir'].']', 'locale' );
				$path = $params['alt_basedir'].'/locales/'.$messages.'/_global.php';
			}
			else
			{	// Load our global translation file.
				$path = $locales_path.$messages.'/_global.php';
			}

			if( file_exists($path) && is_readable($path) )
			{
				$Debuglog->add( 'T_: Loading file: '.$path, 'locale' );
				include_once $path;
			}
			else
			{
				$Debuglog->add( 'T_: Messages file does not exist or is not readable: '.$path, 'locale' );
			}
			if( ! isset($trans[ $messages ] ) )
			{ // Still not loaded... file doesn't exist, memorize that no translations are available
				// echo 'file not found!';
				$trans[ $messages ] = array();
			}
			else
			{
				if( ! isset($trans[$messages]['__meta__']) )
				{ // Unknown/old messages format (< version 1):
					$Debuglog->add( 'Found deprecated messages format (no __meta__ info).', 'locale' );
					// Translate keys (e.g. 'foo\nbar') to real strings ("foo\nbar")
					// Doing this here for all strings, is actually faster than doing it on key lookup (like it has been done before always)
					foreach($trans[$messages] as $k => $v)
					{
						if( ($pos = strpos($k, '\\')) === false )
						{ // fast-path-skip
							continue;
						}
						// Replace string as done in the good old days:
						$new_k = str_replace( array('\n', '\r', '\t'), array("\n", "\r", "\t"), $k );
						if( $new_k != $k )
						{
							$trans[$messages][$new_k] = $v;
							unset($trans[$messages][$k]);
						}
					}
				}
			}
		}

		if( isset( $trans[ $messages ][ $string ] ) )
		{ // If the string has been translated:
			//$Debuglog->add( 'String ['.$string.'] found', 'locale' );
			$r = $trans[ $messages ][ $string ];
			if( isset($trans[$messages]['__meta__']['charset']) )
			{ // new format: charset in meta data:
				$messages_charset = $trans[$messages]['__meta__']['charset'];
			}
			else
			{ // old format.. extract charset from content type or fall back to setting from global locale definition:
				$meta = $trans[$messages][''];
				if( preg_match( '~^Content-Type: text/plain; charset=(.*);?$~m', $meta, $match ) )
				{
					$messages_charset = $match[1];
				}
				else
				{
					$messages_charset = $locales[$req_locale]['charset'];
				}
				// Set it accordingly to new format.
				$trans[$messages]['__meta__']['charset'] = $messages_charset;
			}
		}
		else
		{
			//$Debuglog->add( 'String ['.$string.'] not found', 'locale' );
			// Return the English string:
			$r = $string;
			$messages_charset = 'iso-8859-1'; // our .php file encoding
		}

		if( ! empty($evo_charset) ) // this extra check is needed, because $evo_charset may not yet be determined.. :/
		{
			$r = convert_charset( $r, $evo_charset, $messages_charset );
		}
		else
		{
			$Debuglog->add(sprintf('Warning: evo_charset not set to translate "%s"', htmlspecialchars($string)), 'locale');
		}

		if( $params['for_helper'] )
		{ // translation is for the b2evoHelper object
			add_js_translation( $string, $r );
		}

		//$Debuglog->add( 'Result: ['.$r.']', 'locale' );
		return $r;
	}

}
else
{ // We are not localizing at all:

	/**
	 * @ignore
	 */
	function T_( $string, $req_locale = '', $params = array() )
	{
		return $string;
	}

}

/**
 * Translate and escape single quotes.
 *
 * This is to be used mainly for Javascript strings.
 *
 * @uses T_()
 * @param string String to translate
 * @param string Locale to use
 * @param array  See {@link T_()}
 * @return string The translated and escaped string.
 */
function TS_( $string, $req_locale = '', $params = array() )
{
	return str_replace( "'", "\\'", T_( $string, $req_locale, $params ) );
}


/**
 * Temporarily switch to another locale
 *
 * Calls can be nested, see {@link locale_restore_previous()}.
 *
 * @param string locale to activate
 * @return boolean true on success, false on failure
 */
function locale_temp_switch( $locale )
{
	global $saved_locales, $current_locale, $Timer;

	// $Timer->resume( 'locale_temp_switch' );

	if( !isset( $saved_locales ) || ! is_array( $saved_locales ) )
	{
		$saved_locales = array();
	}

	$prev_locale = $current_locale;
	if( locale_activate( $locale ) )
	{
		array_push( $saved_locales, $prev_locale );
		return true;
	}

	// $Timer->stop( 'locale_temp_switch' );
	return false;
}


/**
 * Restore the locale in use before the switch
 *
 * @see locale_temp_switch()
 * @return boolean true on success, false on failure (no locale stored before)
 */
function locale_restore_previous()
{
	global $saved_locales;

	if( !empty( $saved_locales ) && is_array( $saved_locales ) )
	{
		locale_activate( array_pop( $saved_locales ) );
		return true;
	}
	return false;
}


/**
 * Activate a locale.
 *
 * @todo dh> this should make sure, that e.g. "charset" is set for the locale in {@link $locales}. See http://forums.b2evolution.net/viewtopic.php?p=43980#43980
 *
 * @param string locale to activate
 * @param boolean True on success/change, false on failure (if already set or not existant)
 */
function locale_activate( $locale )
{
	global $locales, $current_locale, $current_charset;

	if( $locale == $current_locale
			|| empty( $locale )
			|| ! isset( $locales[$locale] ) )
	{
		return false;
	}

	// Memorize new locale:
	$current_locale = $locale;
	// Memorize new charset:
	$current_charset = $locales[ $locale ][ 'charset' ];
	return true;
}


/**
 * locale_by_lang(-)
 *
 * Find first locale matching lang
 */
function locale_by_lang( $lang, $fallback_to_default = true )
{
	global $locales, $default_locale;

	foreach( $locales as $locale => $locale_params )
	{
		if( substr( $locale, 0 ,2 ) == $lang )
		{ // found first matching locale
			return $locale;
		}
	}

	// Not found...
	if( $fallback_to_default )
		return $default_locale;
	else
		return $lang;
}


/**
 * Displays/Returns the current locale. (for backward compatibility)
 *
 * This is for HTML lang attributes
 *
 * @param boolean true (default) if we want it to be outputted
 * @return string current locale, if $disp = false
 */
function locale_lang( $disp = true )
{
	global $current_locale;

	if( $disp )
		echo $current_locale;
	else
		return $current_locale;
}


/**
 * Returns the charset of the current locale
 */
function locale_charset( $disp = true )
{
	global $current_charset;

	if( $disp )
		echo $current_charset;
	else
		return $current_charset;
}


/**
 * Returns the current locale's default date format
 * @param string Locale, must be set in {@link $locales}
 * @return string Date format of the locale, e.g. 'd.m.Y'
 */
function locale_datefmt( $locale = NULL )
{
	global $locales;

	if( empty($locale) )
	{
		global $current_locale;
		$locale = $current_locale;
	}

	return $locales[$locale]['datefmt'];
}


/**
 * Returns the current locale's default time format
 */
function locale_timefmt()
{
	global $locales, $current_locale;

	return $locales[$current_locale]['timefmt'];
}

/**
 * Returns the current locale's default short time format
 */
function locale_shorttimefmt()
{
	global $locales, $current_locale;

	return str_replace( ':s', '', $locales[$current_locale]['timefmt'] );
}


function locale_datetimefmt( $separator = ' ' )
{
	global $locales, $current_locale;

	return $locales[$current_locale]['datefmt'].$separator.$locales[$current_locale]['timefmt'];
}

/**
 * Returns the current locale's start of week
 *
 * @return integer 0 for Sunday, 1 for Monday
 */
function locale_startofweek()
{
	global $locales, $current_locale;

	return (int)$locales[$current_locale]['startofweek'];
}


/**
 * Get the country locale
 *
 * @param string locale to use, '' for current
 *
 * @return string country locale
 */
function locale_country( $locale = '' )
{
	global $current_locale;

	if( empty($locale) ) $locale = $current_locale;

	return substr( $locale, 3, 2 );
}


/**
 *	Get the locale country dialing code
 */
function locale_dialing_code( $locale = '' )
{
		global $current_locale, $CountryCache;

		if( empty($locale) )
		{
			$locale = locale_country();
		}

		$edited_Country = $CountryCache->get_by_ID( $locale);

		return $edited_Country->dialing_code;
}


/**
 * Template function: Display locale flag
 *
 * @param string locale to use, '' for current
 * @param string collection name (subdir of img/flags)
 * @param string name of class for IMG tag
 * @param string deprecated HTML align attribute
 * @param boolean to echo or not
 * @param mixed use absolute url (===true) or path to flags directory (used in installer)
 */
function locale_flag( $locale = '', $collection = 'w16px', $class = 'flag', $align = '', $disp = true, $absoluteurl = true )
{
	global $locales, $current_locale, $rsc_path, $rsc_url;

	if( empty($locale) ) $locale = $current_locale;

	// extract flag name:
	$country = strtolower(substr( $locale, 3, 2 ));

	$img_attribs = array(
		'alt' => isset($locales[$locale]['name']) ? $locales[$locale]['name'] : $locale,
	);

	if( $absoluteurl !== true )
	{
		$absoluteurl = trailing_slash($absoluteurl);
		$img_attribs['src'] = $absoluteurl.$collection.'/'.$country.'.gif';
	}
	else
	{
		// subpath below $rsc_path
		$subpath = 'flags/'.$collection.'/'.$country.'.gif';

		if( ! is_file( $rsc_path.$subpath ) )
		{ // File does not exist
			$country = 'default';
			$subpath = 'flags/'.$collection.'/'.$country.'.gif';
		}
		$img_attribs['src'] = $rsc_url.$subpath;

		// Add size, if available.
		if( $img_wh = imgsize($rsc_path.$subpath, 'widthheight_assoc') )
		{
			$img_attribs += $img_wh;
		}
	}

	if( ! empty( $class ) ) $img_attribs['class'] = $class;
	if( ! empty( $align ) ) $img_attribs['align'] = $align;

	$r = '<img'.get_field_attribs_as_string($img_attribs).' />';

	if( $disp )
		echo $r;   // echo it
	else
		return $r; // return it
}


/**
 * [callback function] Outputs an <option> set with default locale selected.
 * Optionally returns an array with a locale key and name if there's only one enabled locale.
 *
 * @param string default value
 * @param boolean echo output?
 * @param boolean Return array (locale key + name) if there's only one enabled locale?
 * @return string|array The options string or an array (locale key + name) if there's only one enabled locale and $array_if_onelocale == true.
 */
function locale_options( $default = '', $disp = true, $array_if_onelocale = false )
{
	global $locales, $default_locale;

	if( empty( $default ) ) $default = $default_locale;

	$r = '';
	$enabled_count = 0;
	$enabled_lastkey = '';

	foreach( $locales as $this_localekey => $this_locale )
	{
		if( $this_locale['enabled'] || $this_localekey == $default )
		{
			$r .= '<option value="'. $this_localekey. '"';
			if( $this_localekey == $default )
				$r .= ' selected="selected"';
			$r .= '>'. T_($this_locale['name']). '</option>';

			++$enabled_count;
			$enabled_lastkey = $this_localekey;
		}
	}

	if( $disp )
	{	// the result must be displayed
		echo $r;
	}

	if ( $array_if_onelocale && $enabled_count == 1 )
	{	// We've only one enabled locale:
		return array( $enabled_lastkey, $locales[$enabled_lastkey]['name'] );
	}
	else
	{	// Return the string.
		return $r;
	}
}


/**
 * [callback function] Returns an <option> set with default locale selected
 *
 * @param string default value
 */
function locale_options_return( $default = '' )
{
	$r = locale_options( $default, false );
	return $r;
}


/**
 * Detect language from HTTP_ACCEPT_LANGUAGE
 *
 * HTTP_ACCEPT_LANGUAGE is sorted by prio and then the best match is used
 * (either full locale ("en-US") or best fitting locale for a short one ("en").
 *
 * This gets tested in {@link test_locale_from_httpaccept()}.
 *
 * @return string Locale made out of HTTP_ACCEPT_LANGUAGE or $default_locale, if no match
 */
function locale_from_httpaccept()
{
	global $locales, $default_locale;
	if( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) )
	{
		$accept = strtolower( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );

		// Create list of accepted locales.
		if( ! preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1|0(?:\.[0-9]+)?))?/i', $accept, $accept_list) )
		{
			return $default_locale;
		}
		// Create list of enabled locales.
		$enabled_locales = array();
		foreach($locales as $k => $v)
		{
			if( empty($v['enabled']) )
			{
				continue;
			}
			$enabled_locales[strtolower($k)] = $k;
		}
		if( empty($enabled_locales) )
		{
			return $default_locale;
		}
		// Build mapping of short code to long code(s)
		$short_locales = array();
		foreach($enabled_locales as $v)
		{
			if( $pos = strpos($v, '-') )
			{
				$short = substr($v, 0, $pos);
				$short_locales[$short][] = $v;
			}
		}
		// Create "locale" => "prio" list
		$accept_list = array_combine($accept_list[1], $accept_list[2]);
		$maxq = count($accept_list)+1;
		foreach( $accept_list as $k => $v )
		{
			if( $v === '' )
			{ // should be kept in order
				$accept_list[$k] = $maxq--;
			}
			elseif( $v == 0 )
			{ // not acceptable (RFC 2616)
				unset($accept_list[$k]);
			}
		}
		arsort($accept_list);
		$accept_list = array_values(array_keys($accept_list));

		// Go through the list of accepted locales and find best match.
		for( $i = 0, $n = count($accept_list); $i<$n; $i++ )
		{
			$test = $accept_list[$i];

			if( isset($enabled_locales[$test]) )
			{ // the accepted locale is enabled and a full match
				return $enabled_locales[$test];
			}
			if( isset($short_locales[$test]) )
			{ // this is a short locale: find the best/first match in accepted locales
				$first = NULL;
				foreach($short_locales[$test] as $v)
				{
					$pos = array_search(strtolower($v), $accept_list);
					if( $pos !== false && ( ! isset($first) || $pos < $first ) )
					{
						$first = $pos;
					}
				}
				if( isset($first) )
				{ // found exact match, via short locale.
					return $enabled_locales[$accept_list[$first]];
				}
				if( isset($enabled_locales[$test.'-'.$test]) )
				{ // test for e.g. "de-DE" when only "de" is accepted
					return $enabled_locales[$test.'-'.$test];
				}
				// Fallback: use first enabled locale matching the short accepted
				return $short_locales[$test][0];
			}
		}
	}
	return $default_locale;
}


/**
 * user sort function to sort locales by priority
 *
 * 1 is highest priority.
 *
 */
function locale_priosort( $a, $b )
{
	return $a['priority'] - $b['priority'];
}


/**
 * load locales from DB into $locales array. Also sets $default_locale.
 *
 * @return mixed new default locale on succes, false on failure
 */
function locale_overwritefromDB()
{
	global $DB, $locales, $default_locale, $Settings, $Debuglog;

	$usedprios = array();  // remember which priorities are used already.
	$priocounter = 0;
	$query = 'SELECT
						loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_startofweek,
						loc_name, loc_messages, loc_priority, loc_enabled
						FROM T_locales ORDER BY loc_priority';

	foreach( $DB->get_results( $query, ARRAY_A ) as $row )
	{ // Loop through loaded locales:

		if( $row['loc_priority'] == $priocounter )
		{ // priority conflict (the same)
			$priocounter++;
		}
		else
		{
			$priocounter = $row['loc_priority'];
		}

		//remember that we used this
		$usedprios[] = $priocounter;

		$locales[ $row['loc_locale'] ] = array(
				'charset'     => $row[ 'loc_charset' ],
				'datefmt'     => $row[ 'loc_datefmt' ],
				'timefmt'     => $row[ 'loc_timefmt' ],
				'startofweek' => $row[ 'loc_startofweek' ],
				'name'        => $row[ 'loc_name' ],
				'messages'    => $row[ 'loc_messages' ],
				'priority'    => $priocounter,
				'enabled'     => $row[ 'loc_enabled' ],
				'fromdb'      => 1
			);
	}

	// set default priorities, if nothing was set in DB.
	// Missing "priority gaps" will get filled here.
	if( $DB->num_rows != count($locales) )
	{ // we have locales from conf file that need a priority
		$priocounter = 1;
		foreach( $locales as $lkey => $lval )
		{ // Loop through memory locales:
			if( !isset($lval['priority']) )
			{ // Found one that has no assigned priority
				while( in_array( $priocounter, $usedprios ) )
				{
					$priocounter++;
				}
				// Priocounter has max value
				$locales[$lkey]['priority'] = $priocounter;
				$usedprios[] = $priocounter;
			}
		}
	}

	// sort by priority
	uasort( $locales, 'locale_priosort' );

	// overwrite default_locale from DB settings - if enabled.
	// Checks also if previous $default_locale is enabled. Defaults to en-EU, even if not enabled.
	$locale_fromdb = $Settings->get('default_locale');

	if( $locale_fromdb )
	{
		if( !isset( $locales[$locale_fromdb] ) )
		{
			$Debuglog->add( 'Default locale ['.$locale_fromdb.'] from general settings is not available.', 'locale' );
			return false;
		}
		else
		{
			$default_locale = $locale_fromdb;
			return $default_locale;
		}
	}
}


/**
 * Write $locales array to DB table
 */
function locale_updateDB()
{
	global $locales, $DB;

	$templocales = $locales;

	$lnr = 0;
	// Loop through list of all HTTP POSTed params:
	foreach( $_POST as $pkey => $pval )
	{
		if( ! preg_match('/loc_(\d+)_(.*)/', $pkey, $matches) )
		{
			continue;
		}

		// This is a locale related parameter, get it now:
		$pval = param( $pkey, 'string', '' );

		$lfield = $matches[2];

		if( $matches[1] != $lnr )
		{ // we have a new locale
			$lnr = $matches[1];
			$plocale = $pval;

			// checkboxes default to 0
			$templocales[ $plocale ]['enabled'] = 0;
		}
		elseif( $lnr != 0 )  // be sure to have catched a locale before
		{
			if( $lfield == 'startofweek' && ( $lfield < 0 || $lfield > 6 ) )
			{ // startofweek must be between 0 and 6
				continue;
			}
			$templocales[ $plocale ][$lfield] = $pval;
		}
	}

	$locales = $templocales;

	$query = "REPLACE INTO T_locales ( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_startofweek, loc_name, loc_messages, loc_priority, loc_enabled ) VALUES ";
	foreach( $locales as $localekey => $lval )
	{
		if( empty($lval['messages']) )
		{ // if not explicit messages file is given we'll translate the locale
			$lval['messages'] = strtr($localekey, '-', '_');
		}
		$query .= '(
			'.$DB->quote($localekey).',
			'.$DB->quote($lval['charset']).',
			'.$DB->quote($lval['datefmt']).',
			'.$DB->quote($lval['timefmt']).',
			'.$DB->quote($lval['startofweek']).',
			'.$DB->quote($lval['name']).',
			'.$DB->quote($lval['messages']).',
			'.$DB->quote($lval['priority']).',
			'.$DB->quote($lval['enabled']).'
		), ';
	}
	$query = substr($query, 0, -2);
	$q = $DB->query($query);

	return (bool)$q;
}


/**
 * Convert a string from one charset to another.
 *
 * @todo Implement iconv and PHP mapping tables
 *
 * @see can_convert_charsets()
 * @param string String to convert
 * @param string Target charset (TO)
 * @param string Source charset (FROM)
 * @return string Encoded string (if it cannot be converted it's the original one)
 */
function convert_charset( $string, $dest_charset, $src_charset )
{
	if( isset($GLOBALS['Timer']) )
	{
		$GLOBALS['Timer']->resume('convert_charset', false );
	}
	if( $dest_charset == $src_charset || $dest_charset == '' /* may happen if $evo_charset is not defined yet */ )
	{ // no conversation required
		if( isset($GLOBALS['Timer']) )
		{
			$GLOBALS['Timer']->pause('convert_charset', false );
		}
		return $string;
	}

	if( function_exists('mb_convert_variables') )
	{ // mb_string extension:
		mb_convert_variables( $dest_charset, $src_charset, $string );
	}
	// pre_dump( $dest_charset, $src_charset, $string );

	if( isset($GLOBALS['Timer']) )
	{
		$GLOBALS['Timer']->pause('convert_charset', false );
	}
	return $string;
}


/**
 * Can we convert from charset A to charset B?
 * @param string Target charset (TO)
 * @param string Source charset (FROM)
 * @return boolean
 */
function can_convert_charsets( $dest_charset, $src_charset )
{
	if( empty($dest_charset) || empty($src_charset) )
	{
		return false;
	}

	if( function_exists('mb_internal_encoding') )
	{ // mb_string extension:
		$orig = mb_internal_encoding();

		$r = false;
		if( @mb_internal_encoding($dest_charset) && @mb_internal_encoding($src_charset) )
		{ // we can set both encodings, so we should be able to convert:
			$r = true;
		}

		mb_internal_encoding($orig);
		return $r;
	}

	return false;
}


/**
 * Can we check for valid encodings of strings, using {@link check_encoding()}?
 *
 * @return boolean
 */
function can_check_encoding()
{
	return function_exists('mb_check_encoding');
}


/**
 * Check if the string is valid for the specified encoding.
 *
 * @param string String to check
 * @param string Encoding to check
 * @return
 */
function check_encoding($str, $encoding)
{
	if( function_exists('mb_check_encoding') )
	{
		return mb_check_encoding($str, $encoding);
	}
	
	return NULL;
}


/**
 * Init charset handling between Input/Output ($io_charset) and the internal
 * handling ($evo_charset).
 *
 * Check and possibly adjust {@link $evo_charset}.
 *
 * @staticvar boolean Used to only start mb_output_handler once
 * @param string I/O (input/output) charset to use
 * @return boolean true, if encoding has been changed
 */
function init_charsets( $req_io_charset )
{
	static $mb_output_handler_started;
	global $io_charset, $evo_charset, $Debuglog, $DB;
	global $force_io_charset_if_accepted;

	if( $req_io_charset == $io_charset )
	{ // no conversation/init needed
		return false;
	}

	// check, if we want to force a specific charset (e.g. 'utf-8'):
	if( ! empty($force_io_charset_if_accepted) )
	{ // we want to force a specific charset:
		if( ! isset($_SERVER['HTTP_ACCEPT_CHARSET']) // all allowed
			|| preg_match( '~\b(\*|'.$force_io_charset_if_accepted.')\b~', $_SERVER['HTTP_ACCEPT_CHARSET'] ) )
		{
			$req_io_charset = $force_io_charset_if_accepted; // pretend that the first one has been requested
		}
	}

	if( $req_io_charset == $io_charset )
	{ // no conversation/init needed
		return false;
	}

	$io_charset = $req_io_charset;

	if( empty($evo_charset) )
	{ // empty evo_charset follows I/O charset:
		// TODO: $evo_charset will not follow, if it has followed before.. (because not empty anymore)
		$Debuglog->add( '$evo_charset follows $io_charset ('.$io_charset.').', array('locale') );
		$evo_charset = $io_charset;
	}
	elseif( $evo_charset != $io_charset )
	{ // we have to convert for I/O
		// TODO: dh> $io_charset has to forcefully follow $evo_charset, if we cannot convert, e.g. utf-8/iso-8859-1
		if( ! function_exists('mb_convert_encoding') )
		{
			$Debuglog->add( '$evo_charset differs from $io_charset, but mbstrings is not available - cannot convert I/O to internal charset!', array('errors','locale') );
			$evo_charset = $io_charset; // we cannot convert I/O to internal charset
		}
		else
		{ // check if the encodings are supported:
			// NOTE: mb_internal_encoding() is the best way to find out if the encoding is supported
			$old_mb_internal_encoding = mb_internal_encoding();
			if( ! @mb_internal_encoding($io_charset) )
			{
				$Debuglog->add( 'Cannot I/O convert because I/O charset ['.$io_charset.'] is not supported by mbstring!', array('error','locale') );
				$evo_charset = $io_charset;
				mb_internal_encoding($old_mb_internal_encoding);
			}
			elseif( ! @mb_internal_encoding($evo_charset) )
			{
				$Debuglog->add( 'Cannot I/O convert because $evo_charset='.$evo_charset.' is not supported by mbstring!', array('error','locale') );
				$evo_charset = $io_charset;
				mb_internal_encoding($old_mb_internal_encoding);
			}
			else
			{ // we can convert between I/O
				mb_http_output( $io_charset );
				if( ! $mb_output_handler_started )
				{
					ob_start( 'mb_output_handler' ); // NOTE: this will send a Content-Type header by itself for "text/..."
					$mb_output_handler_started = true;
					$Debuglog->add( 'Started mb_output_handler.', 'locale' );
				}
			}
		}
	}

	// Make sure the DB send us text in the same charset as $evo_charset (override whatever may have been set before)
	if( isset($DB) ) // not available in /install/index.php
	{	// Set encoding for MySQL connection:
		$DB->set_connection_charset( $evo_charset );
	}

	$Debuglog->add( 'evo_charset: '.$evo_charset, 'locale' );
	$Debuglog->add( 'io_charset: '.$io_charset, 'locale' );

	return true;
}


/**
 * Load available locale definitions
 */
function locales_load_available_defs()
{
	global $locales_path;
	global $locales;

	// This is where language packs will store their locale defintions:
	$locale_defs = array();

	// Get all locale folder names:
	$locale_folders = get_filenames( $locales_path, false, true, true, false, true );
	// Go through all locale folders:
	foreach( $locale_folders as $locale_folder )
	{
		$ad_locale_folder = $locales_path.'/'.$locale_folder;
		// Get files in folder:
		$locale_def_files = get_filenames( $ad_locale_folder, true, false, true, false, true );
		// Go through files in locale folder:
		foreach( $locale_def_files as $locale_def_file )
		{	// Check if it's a definition file:
			if( preg_match( '¤[a-z0-9\-]\.locale\.php$¤i', $locale_def_file ) )
			{	// We found a definition file:
				// pre_dump( $locale_def_file );
				include $ad_locale_folder.'/'.$locale_def_file;
			}
		}
	}

	// Copy any new locale definitions over to $locales:
	foreach( $locale_defs as $locale_code => $locale_def )
	{
		if( !isset( $locales[$locale_code] ) )
		{	// New locale, add to main array:
			$locales[$locale_code] = $locale_def;
			// ... but mark as not enabled!
			$locales[$locale_code]['enabled'] = 0;
		}
	}


	// Assign priorities:

	// Find highest used priority:
	$max_prio = 0;
	foreach( $locales as $locale )
	{
		if( isset($locale['priority']) && $locale['priority'] > $max_prio )
		{
			$max_prio = $locale['priority'];
		}
	}

	foreach( $locales as $lkey=>$locale )
	{
		if( !isset($locale['priority']) )
		{
			$locales[$lkey]['priority'] = ++$max_prio;
		}
	}

}

/*
 * $Log$
 * Revision 1.44  2010/10/19 02:00:54  fplanque
 * MFB
 *
 * Revision 1.43  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.42  2010/06/17 17:48:46  blueyed
 * Fix locale_from_httpaccept, complete rewrite.
 *
 * Revision 1.41  2010/05/13 19:09:08  blueyed
 * debug_locale=true
 *
 * Revision 1.40  2010/02/08 17:53:23  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.39  2009/12/12 03:54:05  blueyed
 * convert_charset: do not depend on Timer global
 *
 * Revision 1.38  2009/12/11 23:55:48  fplanque
 * doc
 *
 * Revision 1.37  2009/12/09 21:59:40  blueyed
 * Add check_encoding and can_check_encoding functions.
 *
 * Revision 1.36  2009/11/30 00:22:05  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.35  2009/10/01 18:50:15  tblue246
 * convert_charset(): Trying to remove unreliable charset detection and modify all calls accordingly -- needs testing to ensure all charset conversions work as expected.
 *
 * Revision 1.34  2009/09/30 21:14:20  blueyed
 * omg, fix locale_flag
 *
 * Revision 1.33  2009/09/30 17:41:19  blueyed
 * locale_flag: add image width/height
 *
 * Revision 1.32  2009/09/23 21:37:03  blueyed
 * Unify Debuglogging of T_ methods.
 *
 * Revision 1.31  2009/08/31 17:21:32  fplanque
 * minor
 *
 * Revision 1.30  2009/08/26 22:36:25  tblue246
 * Todo about mb_detect_encoding()
 *
 * Revision 1.29  2009/08/25 19:56:02  blueyed
 * Add convert_charset timer.
 *
 * Revision 1.28  2009/07/09 22:57:32  fplanque
 * Fixed init of connection_charset, especially during install.
 *
 * Revision 1.27  2009/06/23 07:52:02  tblue246
 * Add missing $param to TS_()
 *
 * Revision 1.26  2009/06/22 19:31:06  tblue246
 * Skin-specific translations ("locales" folder in the skin's folder, directory structure is the same as for plugins).
 *
 * Revision 1.25  2009/05/28 23:01:03  blueyed
 * Add Debuglog when charsets are not setup yet when translating: encoding issues in e.g. login form.. :/
 *
 * Revision 1.24  2009/03/15 23:07:56  tblue246
 * minor
 *
 * Revision 1.23  2009/03/15 12:44:39  tblue246
 * doc
 *
 * Revision 1.22  2009/03/15 08:37:08  yabs
 * Adding translation strings for b2evoHelper object
 *
 * Revision 1.21  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.20  2009/03/06 00:11:27  blueyed
 * Abstract POFile handling to POFile class completely.
 *  - move gettext/pofile.class.php to blogs/inc/locales
 *  - use it in locales.ctrl
 * _global.php generation:
 *  - use double quotes only when necessary (msgid/msgstr containing e.g. \n),
 *    this speeds up reading the file a lot
 *  - add __meta__ key to trans array, used for versioning, so old files still
 *    get handled (and converted when being read)
 * Not tested for long in CVS HEAD, but works well in whissip for some time
 * already.
 *
 * Revision 1.19  2009/03/03 20:15:49  tblue246
 * T_(): Adding workaround for PHP 4 compatibility...
 *
 * Revision 1.18  2009/03/03 15:00:22  tblue246
 * PHP 4 compat BREAKS plugin translations!
 *
 * Revision 1.17  2009/03/03 00:52:18  fplanque
 * & $ext_transarray = NULL does not wrok on PHP4
 * PLEASE CONFIRM THAT CODE WORKS WITHOUT &
 *
 * Revision 1.16  2009/02/25 23:47:12  blueyed
 * T(): return string always, if messages are not set; not only if $evo_char is defined (and it gets converted); minor doc
 *
 * Revision 1.15  2009/02/25 20:15:21  tblue246
 * L10n:
 * - Remove Gettext functionality (that means we now use our PHP arrays from the _global.php files only).
 * - Try to merge most functionality of Plugin::T_() into the global T_() function.
 *
 * Revision 1.14  2009/01/29 18:16:35  tblue246
 * Hide language chooser on post form in expert mode if there's only one locale.
 *
 * Revision 1.13  2008/09/07 09:13:28  fplanque
 * Locale definitions are now included in language packs.
 * A bit experimental but it should work...
 *
 * Revision 1.12  2008/07/01 21:52:54  blueyed
 * convert_charset(): return string itself unconverted, if $dest_charset is empty, which may happen when $evo_charset is not defined yet(?)
 *
 * Revision 1.11  2008/07/01 08:34:10  fplanque
 * minor
 *
 * Revision 1.10  2008/06/30 21:24:20  blueyed
 * - convert_charset(): auto-detect source encoding, if not given (UTF-8, ISO-8859-1, ISO-8859-15)
 * - extract_keyphrase_from_referer: use convert_charset() to convert query string to $evo_charset
 *
 * Revision 1.9  2008/05/10 21:30:39  fplanque
 * better UTF-8 handling
 *
 * Revision 1.8  2008/03/07 02:04:45  blueyed
 * fix indent again
 *
 * Revision 1.7  2008/03/07 00:54:42  blueyed
 * indent
 *
 * Revision 1.6  2008/02/08 22:24:46  fplanque
 * bugfixes
 *
 * Revision 1.5  2008/01/21 09:35:32  fplanque
 * (c) 2008
 *
 * Revision 1.4  2007/11/03 21:04:27  fplanque
 * skin cleanup
 *
 * Revision 1.3  2007/07/09 21:24:12  fplanque
 * cleanup of admin page top
 *
 * Revision 1.2  2007/06/26 02:40:54  fplanque
 * security checks
 *
 * Revision 1.1  2007/06/25 11:00:37  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.38  2007/04/26 00:11:02  fplanque
 * (c) 2007
 *
 * Revision 1.37  2007/03/04 20:14:16  fplanque
 * GMT date now in system checks
 *
 * Revision 1.36  2006/12/04 21:20:28  blueyed
 * Abstracted convert_special_charsets() out of urltitle_validate()
 *
 * Revision 1.35  2006/11/27 01:36:04  blueyed
 * doc typo
 *
 * Revision 1.34  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.33  2006/11/16 22:33:52  blueyed
 * some more charset issue releated TODOs.. :/
 *
 * Revision 1.32  2006/11/14 21:12:38  blueyed
 * Fix for gettext-T_()
 *
 * Revision 1.31  2006/11/14 00:47:32  fplanque
 * doc
 *
 * Revision 1.30  2006/11/02 15:58:08  blueyed
 * todo/note
 *
 * Revision 1.29  2006/10/31 00:33:26  blueyed
 * Fixed item_issue_date for preview
 *
 * Revision 1.28  2006/10/29 21:20:53  blueyed
 * Replace special characters in generated URL titles
 *
 * Revision 1.27  2006/10/24 23:04:05  blueyed
 * Added can_convert_charsets() function
 *
 * Revision 1.26  2006/10/04 12:55:24  blueyed
 * - Reload $Blog, if charset has changed for Blog locale
 * - only update DB connection charset, if not forced with $db_config['connection_charset']
 */
?>
