<?php
/**
 * This file implements functions for handling locales and i18n.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 * @todo Make it a class / global object!
 *        - Provide (static) functions to extract .po files / generate _global.php files (single quoted strings!)
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_locale'] = true;


// LOCALIZATION:
if( isset( $use_l10n ) && $use_l10n )
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
			$Debuglog->add( 'We need to load a new translation file to translate: "'. $string.'"', 'locale' );

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

		// sam2kb> b2evolution creates _global.php files with "\n" line breaks, and we must normalize newlines
		// in supplied string before trying to translate it. Otherwise strings won't match.
		// fp> TODO: this is not really satisfying in the long term. We need our own
		// parser that will extract T_() TS_() NT_() etc string and create a normalized potfile.
		// Actually it sgould create several potfiles. One for general use, one for admin, one for install, etc.
		// That way translators can concentrate on the most essential stuff first.
		$search_string = str_replace( array("\r\n", "\r"), "\n", $string );

		if( isset( $trans[ $messages ][ $search_string ] ) )
		{ // If the string has been translated:
			//$Debuglog->add( 'String ['.$string.'] found', 'locale' );
			$r = $trans[ $messages ][ $search_string ];
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

			// $messages_charset = 'iso-8859-1'; // our .php file encoding  
			// fp> I am changing the above for the User custom field group labels (in theroy the php files are plain ASCII anyways!!)
			$messages_charset = $evo_charset;
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

	return $locales[$current_locale]['shorttimefmt'];
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
 * @param string DEPRECATED PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 * @param string name of class for IMG tag   !! OLD PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 * @param string deprecated HTML align attribute   !! OLD PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 * @param boolean to echo or not
 * @param mixed use absolute url (===true) or path to flags directory (used in installer)   !! OLD PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 */
function locale_flag( $locale = '', $collection = 'deprecated_param', $class = 'flag', $align = '', $disp = true, $absoluteurl = true )
{
	global $locales, $current_locale, $country_flags_bg;

	if( empty( $locale ) )
	{
		$locale = $current_locale;
	}

	// extract flag name:
	$country_code = strtolower( substr( $locale, 3, 2 ) );

	$flag_attribs = array(
		'class' => 'flag',
		'title' => isset($locales[$locale]['name']) ? $locales[$locale]['name'] : $locale,
	);

	if( isset( $country_flags_bg[ $country_code ] ) )
	{	// Set background-position from config
		$flag_attribs['style'] = 'background-position:'.$country_flags_bg[ $country_code ];
	}

	$r = '<span'.get_field_attribs_as_string( $flag_attribs ).'></span>';

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
 * @author Rewritten by blueyed in Revision 1.42
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
	global $DB, $locales, $default_locale, $Settings, $Debuglog, $Messages;

	// Load all locales from disk to get 'charset' insrtad of DB
	locales_load_available_defs();

	$usedprios = array();  // remember which priorities are used already.
	$priocounter = 0;
	$query = 'SELECT
						loc_locale, loc_datefmt, loc_timefmt, loc_shorttimefmt, loc_startofweek,
						loc_name, loc_messages, loc_priority, loc_transliteration_map, loc_enabled
						FROM T_locales ORDER BY loc_priority';

	$ctrl = isset( $_GET['ctrl'] ) ? $_GET['ctrl'] : '';
	$priocounter_max = 0;
	$wrong_locales = array();
	$wrong_default_locale = '';
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
		$priocounter_max = $priocounter;

		//remember that we used this
		$usedprios[] = $priocounter;

		$transliteration_map = '';
		// Try to unserialize the value
		if( ( $r = @unserialize( @base64_decode( $row[ 'loc_transliteration_map' ] ) ) ) !== false )
		{
			$transliteration_map = $r;
		}

		if( isset( $locales[ $row['loc_locale'] ], $locales[ $row['loc_locale'] ]['charset'] ) )
		{ // Use charset from locale file if such file exists on disk
			$loc_charset = $locales[ $row['loc_locale'] ]['charset'];
		}
		else
		{ // Use charset from DB
			$loc_charset = 'utf-8';
		}

		if( ( $ctrl == 'locales' || $ctrl == 'regional' ) && $row['loc_enabled'] && $loc_charset != 'utf-8' )
		{ // Check wrong non utf-8 locales in order to deactivate them
			$Messages->add( sprintf( T_('The locale %s has been deactivated because it\'s not UTF-8'), '<b>'.$row['loc_locale'].'</b>' ) );
			if( $Settings->get( 'default_locale' ) == $row['loc_locale'] && $default_locale != $row['loc_locale'] )
			{ // Change main locale to default
				$wrong_default_locale = $row['loc_locale'];
				$Messages->add( sprintf( T_('The main locale was no longer valid and has been set to %s.'), '<b>'.$default_locale.'</b>' ) );
			}
			$wrong_locales[] = $row['loc_locale'];
			$row['loc_enabled'] = 0;
		}

		$locales[ $row['loc_locale'] ] = array(
				'charset'      => $loc_charset,
				'datefmt'      => $row['loc_datefmt'],
				'timefmt'      => $row['loc_timefmt'],
				'shorttimefmt' => $row['loc_shorttimefmt'],
				'startofweek'  => $row['loc_startofweek'],
				'name'         => $row['loc_name'],
				'messages'     => $row['loc_messages'],
				'transliteration_map' => $transliteration_map,
				'priority'     => $priocounter,
				'enabled'      => $row['loc_enabled'],
				'fromdb'       => 1
			);
	}

	if( count( $wrong_locales ) )
	{ // Deactivate locales that have wrong charset
		$DB->query( 'UPDATE T_locales
			  SET loc_enabled = 0
			WHERE loc_locale IN( '.$DB->quote( $wrong_locales ).' )' );
		if( ! empty( $wrong_default_locale ) )
		{ // If main locale is wring we sgould updated it to default and use this for all users and blogs that used this wrong locale
			$Settings->set( 'default_locale', $default_locale );
			$Settings->dbupdate();
			$DB->query( 'UPDATE T_users
				  SET user_locale = '.$DB->quote( $default_locale ).'
				WHERE user_locale = '.$DB->quote( $wrong_default_locale ) );
			$DB->query( 'UPDATE T_blogs
				  SET blog_locale = '.$DB->quote( $default_locale ).'
				WHERE blog_locale = '.$DB->quote( $wrong_default_locale ) );
		}
	}

	foreach( $locales as $l_ley => $locale )
	{ // Change priority of non-db locales to put them at the end of list
		if( empty( $locale['fromdb'] ) && $locale['priority'] < $priocounter_max )
		{
			$locales[ $l_ley ]['priority'] = $priocounter_max + $locale['priority'];
		}
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
	global $locales, $DB, $Settings, $Messages, $action;
	global $saved_params;

	$templocales = $locales;
	$disabled_locales = array();
	$saved_params = array();

	$current_default_locale = $Settings->get( 'default_locale' );

	$lnr = 0;
	// Loop through list of all HTTP POSTed params:
	foreach( $_POST as $pkey => $pval )
	{
		if( ! preg_match( '/loc_(\d+)_(.*)/', $pkey, $matches ) )
		{
			continue;
		}

		// This is a locale related parameter, get it now:
		$pval = param( $pkey, 'string', '' );
		$saved_params[ $pkey ] = $pval;

		$lfield = $matches[2];

		if( $matches[1] != $lnr )
		{ // we have a new locale
			$lnr = $matches[1];
			$plocale = $pval;

			if( $templocales[ $plocale ]['enabled'] || ( $plocale == $current_default_locale && ! isset( $_POST['loc_'.$lnr.'_enabled'] ) ) )
			{	// Collect previously enabled locales AND if current default locale is not enabled yet:
				$disabled_locales[ $plocale ] = $plocale;
			}
			// checkboxes default to 0
			$templocales[ $plocale ]['enabled'] = 0;
		}
		elseif( $lnr != 0 )  // be sure to have catched a locale before
		{
			if( $lfield == 'startofweek' && ( $lfield < 0 || $lfield > 6 ) )
			{ // startofweek must be between 0 and 6
				continue;
			}
			if( $lfield == 'enabled' )
			{ // This locale is enabled, remove from the list of the disabled locales
				unset( $disabled_locales[ $plocale ] );
			}
			elseif( $plocale == $current_default_locale && isset( $disabled_locales[ $plocale ] ) && ! isset( $_POST['loc_'.$lnr.'_enabled'] ) )
			{ // The default locale must be always enabled
				unset( $disabled_locales[ $plocale ] );
				// Force the default locale to be enabled
				$templocales[ $plocale ]['enabled'] = 1;
				// Inform user about this action
				$Messages->add( sprintf( T_('The locale %s was re-enabled automatically because it\'s currently the default locale. The default locale must be a valid and enabled locale.'), '<b>'.$current_default_locale.'</b>' ), 'warning' );
			}
			$templocales[ $plocale ][ $lfield ] = $pval;
		}
	}

	$locales = $templocales;

	// Check the usage of the disabled locales
	$disabled_locales = ( count( $disabled_locales ) > 0 ) ? $DB->quote( $disabled_locales ) : false;
	if( $disabled_locales && ( $action != 'confirm_update' ) )
	{ // Some locales were disabled, create warning message if required
		$user_disabled_locales = $DB->get_assoc( '
			SELECT user_locale, count( user_ID ) as number_of_users
			FROM T_users
			WHERE user_locale IN ( '.$disabled_locales.' )
			GROUP BY user_locale
			HAVING number_of_users > 0
			ORDER BY user_locale'
		);
		$coll_disabled_locales = $DB->get_assoc( '
			SELECT blog_locale, count( blog_ID ) as number_of_blogs
			FROM T_blogs
			WHERE blog_locale IN ( '.$disabled_locales.' )
			GROUP BY blog_locale
			HAVING number_of_blogs > 0
			ORDER BY blog_locale'
		);

		global $warning_message;
		$warning_message = array();
		$users_message = T_('%d users with invalid locale %s');
		$colls_message = T_('%d collections with invalid locale %s');
		foreach( $user_disabled_locales as $disabled_locale => $numbe_of_users )
		{ // Disabled locale is used by users, add warning
			$warning_message[] = sprintf( $users_message, $numbe_of_users, $disabled_locale );
			if( isset( $coll_disabled_locales[$disabled_locale] ) )
			{ // Disabled locale is used by blogs, add warning
				$warning_message[] = sprintf( $colls_message, $coll_disabled_locales[$disabled_locale], $disabled_locale );
				unset( $coll_disabled_locales[$disabled_locale] );
			}
		}
		foreach( $coll_disabled_locales as $disabled_locale => $numbe_of_colls )
		{ // Disabled locale is used by blogs, add warning
			$warning_message[] = sprintf( $colls_message, $numbe_of_colls, $disabled_locale );
		}

		if( !empty( $warning_message ) )
		{ // There are disabled locales which are used, create the final warning message
			$warning_message = T_('You have disabled some locales. This results in:')."\n"
				.'<ul><li>'.implode( '</li><li>', $warning_message ).'</li></ul>'
				.sprintf( T_('These will be assigned the default locale %s'), $current_default_locale );
			return false;
		}
	}

	$query = "REPLACE INTO T_locales ( loc_locale, loc_datefmt, loc_timefmt, loc_shorttimefmt, loc_startofweek, loc_name, loc_messages, loc_priority, loc_transliteration_map, loc_enabled ) VALUES ";
	foreach( $locales as $localekey => $lval )
	{
		if( empty($lval['messages']) )
		{ // if not explicit messages file is given we'll translate the locale
			$lval['messages'] = strtr($localekey, '-', '_');
		}

		$transliteration_map = '';
		if( !empty($lval['transliteration_map']) )
		{
			if( is_string($lval['transliteration_map']) )
			{	// The value is already serialized and encoded
				$transliteration_map = $lval['transliteration_map'];
			}
			else
			{	// Encode the value
				$transliteration_map = base64_encode( serialize($lval['transliteration_map']) );
			}
		}

		$query .= '(
			'.$DB->quote($localekey).',
			'.$DB->quote($lval['datefmt']).',
			'.$DB->quote($lval['timefmt']).',
			'.$DB->quote($lval['shorttimefmt']).',
			'.$DB->quote($lval['startofweek']).',
			'.$DB->quote($lval['name']).',
			'.$DB->quote($lval['messages']).',
			'.$DB->quote($lval['priority']).',
			'.$DB->quote($transliteration_map).',
			'.$DB->quote($lval['enabled']).'
		), ';
	}
	$query = substr($query, 0, -2);

	$DB->begin();

	$result = true;
	if( $action == 'confirm_update' )
	{ // Update users and blogs locale to the default if the prevously used locale was disabled
		$users_update = $DB->query( 'UPDATE T_users
			SET user_locale = '.$DB->quote( $current_default_locale ).'
			WHERE  user_locale IN ( '.$disabled_locales.' )'
		);
		$blogs_update = $DB->query( 'UPDATE T_blogs
			SET blog_locale = '.$DB->quote( $current_default_locale ).'
			WHERE  blog_locale IN ( '.$disabled_locales.' )'
		);
		$result = ( $users_update !== false ) && ( $blogs_update !== false );
	}

	if( $result && ( $DB->query($query) !== false ) )
	{ // Commit after successful update
		$DB->commit();
		return true;
	}

	// Some error occured, rollback the transaction and add error message
	$DB->rollback();
	$Messages->add( T_('Unexpected error occured, regional settings could not be updated.') );
	return false;
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
			|| preg_match( '~\b(\*|'.preg_quote( $force_io_charset_if_accepted, '~' ).')\b~', $_SERVER['HTTP_ACCEPT_CHARSET'] ) )
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
	global $locales_path, $locales, $locales_loaded_form_disk, $Messages;

	if( ! empty( $locales_loaded_form_disk ) )
	{ // All locales have been already loaded
		return;
	}

	// This is where language packs will store their locale defintions:
	$locale_defs = array();

	// Get all locale folder names:
	$filename_params = array(
			'inc_files' => false,
			'recurse'   => false,
			'basename'  => true,
		);
	$locale_folders = get_filenames( $locales_path, $filename_params );
	// Go through all locale folders:
	foreach( $locale_folders as $locale_folder )
	{
		//pre_dump( $locale_folder );
		$ad_locale_folder = $locales_path.'/'.$locale_folder;
		// Get files in folder:
		$filename_params = array(
				'inc_dirs'	=> false,
				'recurse'	=> false,
				'basename'	=> true,
			);
		$locale_def_files = get_filenames( $ad_locale_folder, $filename_params );
		if( $locale_def_files === false )
		{	// Impossible to read the locale folder:
			$Messages->add( sprintf( T_('The locale %s is not readable. Please check file permissions.'), $locale_folder ), 'warning' );
			continue;
		}
		// Go through files in locale folder:
		foreach( $locale_def_files as $locale_def_file )
		{	// Check if it's a definition file:
			// pre_dump( $locale_def_file );
			if( preg_match( '~[a-z0-9\-.]\.locale\.php$~i', $locale_def_file ) )
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

	foreach( $locales as $lkey => $locale )
	{
		if( !isset($locale['priority']) )
		{
			$locales[$lkey]['priority'] = ++$max_prio;
		}
	}

	// Set this var to TRUE to don't call this function twice
	$locales_loaded_form_disk = true;
}


/**
 * Get a number of the messages in the .PO & .POT file
 *
 * @param string File name (messages.po)
 * @param boolean TRUE - to calc a percent of translated messages
 * @return integer Number of the messages
 */
function locale_file_po_info( $po_file_name, $calc_percent_done = false )
{
	$all = 0;
	$fuzzy = 0;
	$this_fuzzy = false;
	$untranslated = 0;
	$translated = 0;
	$status = '-';
	$matches = array();

	if( file_exists( $po_file_name ) )
	{
		$lines = file( $po_file_name );
		$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file

		foreach( $lines as $line )
		{
			if( trim( $line ) == '' )
			{	// Blank line, go back to base status:
				if( $status == 't' )
				{	// ** End of a translation ** :
					if( $msgstr == '' )
					{
						$untranslated++;
						// echo 'untranslated: ', $msgid, '<br />';
					}
					else
					{
						$translated++;
					}
					if( $msgid == '' && $this_fuzzy )
					{	// It's OK if first line is fuzzy
						$fuzzy--;
					}
					$msgid = '';
					$msgstr = '';
					$this_fuzzy = false;
				}
				$status = '-';
			}
			elseif( ( $status == '-' ) && preg_match( '#^msgid "(.*)"#', $line, $matches ) )
			{	// Encountered an original text
				$status = 'o';
				$msgid = $matches[1];
				// echo 'original: "', $msgid, '"<br />';
				$all++;
			}
			elseif( ( $status == 'o' ) && preg_match( '#^msgstr "(.*)"#', $line, $matches ) )
			{	// Encountered a translated text
				$status = 't';
				$msgstr = $matches[1];
				// echo 'translated: "', $msgstr, '"<br />';
			}
			elseif( preg_match( '#^"(.*)"#', $line, $matches ) )
			{	// Encountered a followup line
				if( $status == 'o' )
					$msgid .= $matches[1];
				elseif( $status == 't' )
					$msgstr .= $matches[1];
			}
			elseif( strpos( $line,'#, fuzzy' ) === 0 )
			{
				$this_fuzzy = true;
				$fuzzy++;
			}
		}
	}

	$info = array(
			'all'          => $all,
			'fuzzy'        => $fuzzy,
			'translated'   => $translated,
			'untranslated' => $untranslated
		);

	if( $calc_percent_done )
	{
		$info['percent'] = locale_file_po_percent_done( $info );
	}

	return $info;
}


/**
 * Get a percent of translated messages in the .PO file
 *
 * @param array File info (see result of the function locale_file_po_info() )
 * @return integer Percent
 */
function locale_file_po_percent_done( $po_file_info )
{
	global $messages_pot_file_info;

	if( !isset( $messages_pot_file_info ) )
	{	// Initialize a file info for the main language file if it doesn't yet set
		global $locales_path;
		$messages_pot_file_info = locale_file_po_info( $locales_path.'messages.pot' );
	}

	$percent_done = ( $messages_pot_file_info['all'] > 0 ) ? round( ( $po_file_info['translated'] - $po_file_info['fuzzy'] / 2 ) / $messages_pot_file_info['all'] * 100 ) : 0;

	return $percent_done;
}


/**
 * Insert default locales into T_locales.
 */
function locale_insert_default()
{
	global $DB, $current_locale, $locales, $install_test_features;

	$activate_locales = array();

	if( ! empty( $install_test_features ) )
	{	// Activate also additional locales on install:
		$activate_locales[] = 'en-US';
		$activate_locales[] = 'de-DE';
		$activate_locales[] = 'fr-FR';
		$activate_locales[] = 'ru-RU';
	}

	if( ! empty( $current_locale ) )
	{ // Make sure the user sees his new system localized.
		$activate_locales[] = $current_locale;
	}

	$activate_locales = array_unique( $activate_locales );

	if( ! empty( $activate_locales ) )
	{ // Insert locales into DB
		$SQL = new SQL();
		$SQL->SELECT( 'loc_locale' );
		$SQL->FROM( 'T_locales' );
		$existing_locales = $DB->get_col( $SQL->get() );

		$insert_data = array();
		foreach( $activate_locales as $a_locale )
		{
			if( !isset( $locales[ $a_locale ] ) || in_array( $a_locale, $existing_locales ) )
			{ // Skip an incorrect locale or it already exists in DB
				continue;
			}

			// Make sure default transliteration_map is set
			$transliteration_map = '';
			if( isset( $locales[ $a_locale ]['transliteration_map'] ) && is_array( $locales[ $a_locale ]['transliteration_map'] ) )
			{
				$transliteration_map = base64_encode( serialize( $locales[ $a_locale ]['transliteration_map'] ) );
			}

			$insert_data[] = '( '.$DB->quote( $a_locale ).', '
				.$DB->quote( $locales[ $a_locale ]['datefmt'] ).', '
				.$DB->quote( $locales[ $a_locale ]['timefmt'] ).', '
				.$DB->quote( empty( $locales[ $a_locale ]['shorttimefmt'] ) ? str_replace( ':s', '', $locales[ $a_locale ]['timefmt'] ) : $locales[ $a_locale ]['shorttimefmt'] ).', '
				.$DB->quote( $locales[ $a_locale ]['startofweek'] ).', '
				.$DB->quote( $locales[ $a_locale ]['name'] ).', '
				.$DB->quote( $locales[ $a_locale ]['messages'] ).', '
				.$DB->quote( $locales[ $a_locale ]['priority'] ).', '
				.$DB->quote( $transliteration_map ).', '
				.'1 )';
		}

		if( count( $insert_data ) )
		{ // Do insert only if at least one locale requires this
			$DB->query( 'INSERT INTO T_locales '
					 .'( loc_locale, loc_datefmt, loc_timefmt, loc_shorttimefmt, '
					 .'loc_startofweek, loc_name, loc_messages, loc_priority, '
					 .'loc_transliteration_map, loc_enabled ) '
					 .'VALUES '.implode( ', ', $insert_data ) );
		}
	}
}


/**
 * Restore default values of locales
 *
 * @param array Locale keys/codes
 */
function locale_restore_defaults( $restored_locales )
{
	global $DB, $locales;

	if( empty( $restored_locales ) && ! is_array( $restored_locales ) )
	{	// No locales to restore, Exit here:
		return;
	}

	foreach( $restored_locales as $restored_locale_key )
	{
		if( ! isset( $locales[ $restored_locale_key ] ) )
		{	// Skip an incorrect locale:
			continue;
		}

		$restored_locale = $locales[ $restored_locale_key ];

		// Make sure default transliteration_map is set:
		$transliteration_map = '';
		if( isset( $restored_locale['transliteration_map'] ) && is_array( $restored_locale['transliteration_map'] ) )
		{
			$transliteration_map = base64_encode( serialize( $restored_locale['transliteration_map'] ) );
		}

		// Restore all locale fields to default values:
		$DB->query( 'UPDATE T_locales SET
			loc_datefmt             = '.$DB->quote( $restored_locale['datefmt'] ).',
			loc_timefmt             = '.$DB->quote( $restored_locale['timefmt'] ).',
			loc_shorttimefmt        = '.$DB->quote( empty( $restored_locale['shorttimefmt'] ) ? str_replace( ':s', '', $restored_locale['timefmt'] ) : $restored_locale['shorttimefmt'] ).',
			loc_startofweek         = '.$DB->quote( $restored_locale['startofweek'] ).',
			loc_name                = '.$DB->quote( $restored_locale['name'] ).',
			loc_messages            = '.$DB->quote( $restored_locale['messages'] ).',
			loc_priority            = '.$DB->quote( $restored_locale['priority'] ).',
			loc_transliteration_map = '.$DB->quote( $transliteration_map ).'
			WHERE loc_locale = '.$DB->quote( $restored_locale_key ) );
	}
}


/**
 * Check default locale and enable if it is not yet
 * Used after each upgrade process
 */
function locale_check_default()
{
	global $DB, $Settings, $default_locale, $locales;

	// Get default locale from DB:
	$current_default_locale = $Settings->get( 'default_locale' );

	if( isset( $locales[ $current_default_locale ] ) && $locales[ $current_default_locale ]['enabled'] )
	{	// Current default locale is correct and enabled
		// Nothing to fix, Exit here:
		return;
	}

	if( ! isset( $locales[ $current_default_locale ] ) )
	{	// If current default locale is wrong and doesn't exist anymore then use default locale from config:
		$current_default_locale = $default_locale;
		// Fix wrong default locale to correct value:
		$Settings->set( 'default_locale', $current_default_locale );
		$Settings->dbupdate();
	}

	// Get a row of current default locale in order to check if it is enabled:
	$SQL = new SQL();
	$SQL->SELECT( 'loc_enabled' );
	$SQL->FROM( 'T_locales' );
	$SQL->WHERE( 'loc_locale = '.$DB->quote( $current_default_locale ) );
	$current_default_locale_enabled = $DB->get_var( $SQL->get() );

	if( $current_default_locale_enabled !== NULL )
	{	// Current default locale exists in DB
		if( ! $current_default_locale_enabled )
		{	// Enable current default locale if it is not enabled yet:
			$DB->query( 'UPDATE T_locales
				  SET loc_enabled = 1
				WHERE loc_locale = '.$DB->quote( $current_default_locale ) );
		}
	}
	else
	{	// No record of default locale

		// Make sure default transliteration_map is set
		$transliteration_map = '';
		if( isset( $locales[ $current_default_locale ]['transliteration_map'] ) && is_array( $locales[ $current_default_locale ]['transliteration_map'] ) )
		{
			$transliteration_map = base64_encode( serialize( $locales[ $current_default_locale ]['transliteration_map'] ) );
		}

		// Insert default locale into DB in order to make it enabled:
		$DB->query( 'INSERT INTO T_locales '
			.'( loc_locale, loc_datefmt, loc_timefmt, loc_shorttimefmt, '
			.'loc_startofweek, loc_name, loc_messages, loc_priority, '
			.'loc_transliteration_map, loc_enabled ) '
			.'VALUES ( '
			.$DB->quote( $current_default_locale ).', '
			.$DB->quote( $locales[ $current_default_locale ]['datefmt'] ).', '
			.$DB->quote( $locales[ $current_default_locale ]['timefmt'] ).', '
			.$DB->quote( empty( $locales[ $current_default_locale ]['shorttimefmt'] ) ? str_replace( ':s', '', $locales[ $current_default_locale ]['timefmt'] ) : $locales[ $current_default_locale ]['shorttimefmt'] ).', '
			.$DB->quote( $locales[ $current_default_locale ]['startofweek'] ).', '
			.$DB->quote( $locales[ $current_default_locale ]['name'] ).', '
			.$DB->quote( $locales[ $current_default_locale ]['messages'] ).', '
			.$DB->quote( $locales[ $current_default_locale ]['priority'] ).', '
			.$DB->quote( $transliteration_map ).', '
			.'1 )' );
	}

}

?>