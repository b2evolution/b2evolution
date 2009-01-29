<?php
/**
 * This file implements functions for handling locales and i18n.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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


/**
 * TRANSLATE!
 *
 * Translate a text to the desired locale (b2evo localization only)
 * or to the current locale
 *
 * @param string String to translate, '' to get language file info (as in gettext spec)
 * @param string locale to translate to, '' to use current locale (basic gettext does only support '')
 */
if( ($use_l10n == 1) && function_exists('_') )
{ // We are going to use GETTEXT

	function T_( $string, $req_locale = '' )
	{
		global $current_locale, $locales, $evo_charset;

		if( empty( $req_locale ) )
		{
			if( empty( $current_locale ) )
			{ // don't translate if we have no locale
				return $string;
			}

			$req_locale = $current_locale;
		}

		if( $req_locale == $current_locale )
		{ // We have not asked for a different locale than the currently active one:
			$r = _($string);

			$messages_charset = $locales[$req_locale]['charset'];
		}
		else
		{ // We have asked for another locale...
			if( locale_temp_switch( $req_locale ) )
			{
				global $current_charset;
				$r = _($string);
				$messages_charset = $current_charset;
				locale_restore_previous();
			}
			else
			{ // Locale could not be activated:
				$r = $string;
				$messages_charset = 'iso-8859-1'; // charset of our .php files
			}
		}

		if( ! empty($evo_charset) ) // this extra check is needed, because $evo_charset may not yet be determined.. :/
		{
			$r = convert_charset( $r, $evo_charset, $messages_charset );
		}

		return $r;
	}

}
elseif( $use_l10n == 2 )
{ // We are going to use evoCore localization:

	/**
	 * @ignore
	 */
	function T_( $string, $req_locale = '' )
	{
		/**
		 * The translations keyed by locale. They get loaded through include() of _global.php
		 * @var array
		 * @static
		 */
		static $trans = array();

		global $current_locale, $locales, $Debuglog, $locales_path, $evo_charset;


		if( empty($req_locale) )
		{ // By default we use the current locale
			if( empty( $current_locale ) )
			{ // don't translate if we have no locale
				return $string;
			}

			$req_locale = $current_locale;
		}

		if( !isset( $locales[$req_locale]['messages'] ) )
		{
			$Debuglog->add( 'No messages file path for locale. $locales["'
					.$req_locale.'"] is '.var_export( @$locales[$req_locale], true ), 'locale' );
			$locales[$req_locale]['messages'] = false;
		}

		$messages = $locales[$req_locale]['messages'];

		// replace special characters to msgid-equivalents
		$search = str_replace( array("\n", "\r", "\t"), array('\n', '', '\t'), $string );

		// echo "Translating ", $search, " to $messages<br />";

		if( ! isset($trans[ $messages ] ) )
		{ // Translations for current locale have not yet been loaded:
			// echo 'LOADING', dirname(__FILE__).'/../locales/'. $messages. '/_global.php';
			if( file_exists($locales_path.$messages.'/_global.php') )
			{
				include_once $locales_path.$messages.'/_global.php';
			}
			if( ! isset($trans[ $messages ] ) )
			{ // Still not loaded... file doesn't exist, memorize that no translations are available
				// echo 'file not found!';
				$trans[ $messages ] = array();

				/*
				May be an english locale without translation.
				TODO: when refactoring locales, assign a key for 'original english'.
				$Debuglog->add( 'No messages found for locale ['.$req_locale.'],
												message file [/locales/'.$messages.'/_global.php]', 'locale' );*/

			}
		}

		if( isset( $trans[ $messages ][ $search ] ) )
		{ // If the string has been translated:
			$r = $trans[ $messages ][ $search ];
			$messages_charset = $locales[$req_locale]['charset'];
		}
		else
		{
			// echo "Not found!";
			// Return the English string:
			$r = $string;
			$messages_charset = 'iso-8859-1'; // our .php file encoding
		}

		if( ! empty($evo_charset) ) // this extra check is needed, because $evo_charset may not yet be determined.. :/
		{
			$r = convert_charset( $r, $evo_charset, $messages_charset );
		}

		return $r;
	}

}
else
{ // We are not localizing at all:

	/**
	 * @ignore
	 */
	function T_( $string, $req_locale = '' )
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
 * @return string
 */
function TS_( $string, $req_locale = '' )
{
	return str_replace( "'", "\'", T_( $string, $req_locale ) );
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
	global $use_l10n, $locales, $current_locale, $current_charset, $weekday;

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

	// Activate translations in gettext:
	if( ($use_l10n == 1) && function_exists( 'bindtextdomain' ) )
	{ // Only if we are using GETTEXT ( if not, look into T_(-) ...)
		global $locales_path;
		# Activate the locale->language in gettext:

		// Set locale: either to locale's definition
		if( isset( $locales[$locale]['set_locales'] ) )
		{
			$set_locale = explode( ' ', $locales[$locale]['set_locales'] );
		}
		else
		{
			$set_locale = $locales[ $locale ][ 'messages' ];
		}
		setlocale( LC_MESSAGES, $set_locale );

		// Specify location of translation tables and bind to domain
		bindtextdomain( 'messages', $locales_path );
		textdomain( 'messages' );

		# Activate the charset for conversions in gettext:
		/*
		TODO: this does not work, as $evo_charset gets set/adjusted, AFTER activating the locale.
		TODO: If this gets activated we won't need the mb_convert_variables() call in T_() for $use_l10n=1
		if( function_exists( 'bind_textdomain_codeset' ) )
		{ // Only if this gettext supports code conversions
			$r = bind_textdomain_codeset( 'messages', $evo_charset );
		}
		*/
	}

	# Set locale for default language:
	# This will influence the way numbers are displayed, etc.
	// We are not using this right now, the default 'C' locale seems just fine
	// setlocale( LC_ALL, $locale );

	# Use this to check locale: (not relevant)
	// echo setlocale( LC_MESSAGES, 0 );

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
 * @param mixed use absolute url (===true) or path to flags directory
 */
function locale_flag( $locale = '', $collection = 'w16px', $class = 'flag', $align = '', $disp = true, $absoluteurl = true )
{
	global $locales, $current_locale, $rsc_path, $rsc_url;

	if( empty($locale) ) $locale = $current_locale;

	// extract flag name:
	$country = strtolower(substr( $locale, 3, 2 ));

	if( ! is_file( $rsc_path.'flags/'.$collection.'/'.$country.'.gif') )
	{ // File does not exist
		$country = 'default';
	}

	if( $absoluteurl !== true )
	{
		$iurl = $absoluteurl;
	}
	else
	{
		$iurl = $rsc_url.'flags';
	}

	$r = '<img src="'.$iurl.'/'.$collection.'/'.$country.'.gif" alt="' .
				(isset($locales[$locale]['name']) ? $locales[$locale]['name'] : $locale) .
				'"';
	if( !empty( $class ) ) $r .= ' class="'.$class.'"';
	if( !empty( $align ) ) $r .= ' align="'.$align.'"';
	$r .= ' /> ';

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
 * First matched full locale code in HTTP_ACCEPT_LANGUAGE will win
 * Otherwise, first locale in table matching a lang code will win
 *
 * @return locale made out of HTTP_ACCEPT_LANGUAGE or $default_locale, if no match
 */
function locale_from_httpaccept()
{
	global $locales, $default_locale;
	if( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) )
	{
		// echo $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$accept = strtolower( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
		// echo 'http_accept_language:<br />'; pre_dump($accept);
		$selected_locale = '';
		$selected_match_pos = 10000;
		$selected_full_match = false;

		foreach( $locales as $localekey => $locale )
		{ // Check each locale
			if( empty($locale['enabled']) )
			{ // We only want to use activated locales
				continue;
			}
			// echo '<br />searching ', $localekey, ' in HTTP_ACCEPT_LANGUAGE ';
			if( ($pos = strpos( $accept, strtolower($localekey) )) !== false )
			{ // We found full locale
				if( !$selected_full_match || ($pos <= $selected_match_pos) )
				{ // This is a better choice than what we had before OR EQUIVALENT but with exact locale
					// echo $localekey.' @ '.$pos.' is better than '.
					//		$selected_locale.' @ '.$selected_match_pos.'<br />';
					$selected_locale = $localekey;
					$selected_match_pos = $pos;
					$selected_full_match = true;
				}
				// else echo $localekey.' @ '.$pos.' is not better than '.
				//					$selected_locale.' @ '.$selected_match_pos.'<br />';
			}

			if( !$selected_full_match && ($pos = strpos( $accept, substr($localekey, 0, 2) )) !== false )
			{ // We have no exact match yet but found lang code match
				if( $pos < $selected_match_pos )
				{ // This is a better choice than what we had before
					// echo $localekey.' @ '.$pos.' is better than '.
					//		$selected_locale.' @ '.$selected_match_pos.'<br />';
					$selected_locale = $localekey;
					$selected_match_pos = $pos;
				}
				// else echo $localekey.' @ '.$pos.' is not better than '.
				//					$selected_locale.' @ '.$selected_match_pos.'<br />';
			}
		}

		if( !empty($selected_locale) )
		{
			return $selected_locale;
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
 * @param string Source charset (FROM) - leave empty to detect it automatically (UTF8, latin1 or latin15)
 * @return string Encoded string (if it cannot be converted it's the original one)
 */
function convert_charset( $string, $dest_charset, $src_charset = NULL )
{
	if( $dest_charset == $src_charset || $dest_charset == '' )
	{ // no conversation required
		return $string;
	}

	if( empty($src_charset) )
	{
		if( function_exists('mb_detect_encoding') )
		{
			$detect_string = $string.'a'; // work around a bug in mb_detect_encoding, where an ISO string gets detected as UTF-8, if the last char is e.g. "ae" (german umlaut)
			$src_charset = mb_detect_encoding($detect_string, 'UTF-8, ISO-8859-1, ISO-8859-15', true);
		}
	}

	if( function_exists('mb_convert_variables') )
	{ // mb_string extension:
		mb_convert_variables( $dest_charset, $src_charset, $string );
	}
	// pre_dump( $dest_charset, $src_charset, $string );

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

	if( isset($DB) // not available in /install/index.php
			&& empty($db_config['connection_charset']) // do not override explicitly set one
		)
	{
		// Set encoding for MySQL connection:
		$DB->set_connection_charset( $evo_charset, true );
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
