<?php
/**
 * This file implements functions for handling locales and i18n.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * TRANSLATE!
 *
 * Translate a text to the desired locale (b2evo localization only)
 * or to the current locale
 *
 * {@internal T_(-)}}
 *
 * @param string String to translate, '' to get language file info (as in gettext spec)
 * @param string locale to translate to, '' to use current locale (basic gettext does only support '')
 */
if( ($use_l10n == 1) && function_exists('_') )
{ // We are going to use GETTEXT

	function T_( $string, $req_locale = '' )
	{
		global $current_messages;

		if( empty( $req_locale ) || $req_locale == $current_messages )
		{ // We have not asked for a different locale than the currently active one:
			return _($string);
		}
		// We have asked for a funky locale... we'll get english instead:
		return $string;
	}

}
elseif( $use_l10n == 2 )
{ // We are going to use b2evo localization:

	/**
	 * @ignore
	 */
	function T_( $string, $req_locale = '' )
	{
		global $trans, $current_locale, $locales;

		// By default we use the current locale:
		if( empty($req_locale) ) $req_locale = $current_locale;

		if( empty($req_locale) )
			return $string;  // don't translate if we have no locale

		$messages = $locales[$req_locale]['messages'];

		// replace special characters that to msgid-equivalents
		$search = str_replace( array("\n", "\r", "\t"), array('\n', '', '\t'), $string );

		// echo "Translating ", $search, " to $messages<br />";

		if( !isset($trans[ $messages ] ) )
		{ // Translations for current locale have not yet been loaded:
			// echo 'LOADING', dirname(__FILE__). '/../locales/'. $messages. '/_global.php';
			@include_once dirname(__FILE__). '/../locales/'. $messages. '/_global.php';
			if( !isset($trans[ $messages ] ) )
			{ // Still not loaded... file doesn't exist, memorize that no translation are available
				// echo 'file not found!';
				$trans[ $messages ] = array();
			}
		}

		if( isset( $trans[ $messages ][ $search ] ) )
		{ // If the string has been translated:
			return $trans[ $messages ][ $search ];
		}

		// echo "Not found!";

		// Return the English string:
		return $string;
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
 * NT_(-)
 *
 * No Translation
 * Nevertheless, the string will be extracted by the gettext tools
 */
function NT_( $string )
{
	return $string;
}


/**
 * Temporarily switch to another locale
 *
 * Calls can be nested.
 *
 * {@internal locale_temp_switch(-)}}
 *
 * @param string locale to activate
 */
function locale_temp_switch( $locale )
{
	global $saved_locale, $current_locale;
	if( !isset( $saved_locale ) ) $saved_locale = array();
	array_push( $saved_locale, $current_locale );
	locale_activate( $locale );
}


/**
 * Restore the locale in use before the switch
 *
 * {@internal locale_restore_previous(-)}}
 */
function locale_restore_previous()
{
	global $saved_locale;
	$locale = array_pop( $saved_locale );
	locale_activate( $locale );
}


/**
 * locale_activate(-)
 *
 * returns true if locale has been changed
 *
 * @param string locale to activate
 */
function locale_activate( $locale )
{
	global $use_l10n, $locales, $current_locale, $current_messages, $current_charset, $weekday, $month;

	if( $locale == $current_locale || empty( $locale ) )
	{
		return false;
	}

	// Memorize new locale:
	$current_locale = $locale;
	// Memorize new charset:
	$current_charset = $locales[ $locale ][ 'charset' ];
	$current_messages = $locales[ $locale ][ 'messages' ];

	// Activate translations in gettext:
	if( ($use_l10n == 1) && function_exists( 'bindtextdomain' ) )
	{ // Only if we are using GETTEXT ( if not, look into T_(-) ...)
		# Activate the locale->language in gettext:

		// Note: default of safe_mode_allowed_env_vars is "PHP_ ",
		// so you need to add "LC_" by editing php.ini.
		putenv('LC_ALL='.$current_messages);

		// Specify location of translation tables and bind to domain
		bindtextdomain( 'messages', dirname(__FILE__).'/../locales' );
		textdomain('messages');

		# Activate the charset for conversions in gettext:
		if( function_exists( 'bind_textdomain_codeset' ) )
		{ // Only if this gettext supports code conversions
			bind_textdomain_codeset( 'messages', $current_charset );
		}
	}

	# Set locale for default language:
	# This will influence the way numbers are displayed, etc.
	// We are not using this right now, the default 'C' locale seems just fine
	// setlocale( LC_ALL, $locale );

	# Use this to check locale: (not relevant)
	// echo setlocale( LC_ALL, 0 );

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
 * {@internal locale_lang(-)}}
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
 *
 * {@internal locale_charset(-)}}
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
 *
 * {@internal locale_datefmt(-)}}
 */
function locale_datefmt()
{
	global $locales, $current_locale;

	return $locales[$current_locale]['datefmt'];
}


/**
 * Returns the current locale's default time format
 *
 * {@internal locale_timefmt(-)}}
 */
function locale_timefmt()
{
	global $locales, $current_locale;

	return $locales[$current_locale]['timefmt'];
}


/**
 * Template function: Display locale flag
 *
 * {@internal locale_flag(-)}}
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
	global $locales, $current_locale, $core_dirout, $img_subdir, $img_url;

	if( empty($locale) ) $locale = $current_locale;

	// extract flag name:
	$country = strtolower(substr( $locale, 3, 2 ));

	if( ! is_file(dirname(__FILE__).'/'.$core_dirout.$img_subdir.'flags/'.$collection.'/'.$country.'.gif') )
	{ // File does not exist
		$country = 'default';
	}

	if( $absoluteurl !== true )
	{
		$iurl = $absoluteurl;
	}
	else
	{
		$iurl = $img_url.'flags';
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


/*
 * locale_options(-)
 *
 *	Outputs a <option> set with default locale selected
 *
 * was: lang_options(-)
 *
 */
function locale_options( $default = '' )
{
	global $locales, $default_locale;

	if( empty( $default ) ) $default = $default_locale;


	foreach( $locales as $this_localekey => $this_locale )
		if( $this_locale['enabled'] || $this_localekey == $default )
		{
			echo '<option value="'. $this_localekey. '"';
			if( $this_localekey == $default ) echo ' selected="selected"';
			echo '>'. T_($this_locale['name']). '</option>';
		}

}


/**
 * Detect language from HTTP_ACCEPT_LANGUAGE
 *
 * First matched full locale code in HTTP_ACCEPT_LANGUAGE will win
 * Otherwise, first locale in table matching a lang code will win
 *
 * {@internal locale_from_httpaccept(-)}}
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
		// pre_dump($accept, 'http_accept_language');
		$selected_locale = '';
		$selected_match_pos = 10000;
		$selected_full_match = false;

		foreach( $locales as $localekey => $locale )
		{ // Check each locale
			if( ! $locale['enabled'] )
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
 */
function locale_overwritefromDB()
{
	global $DB, $locales, $default_locale, $Settings;

	$usedprios = array();  // remember which priorities are used already.
	$priocounter = 0;
	$query = 'SELECT
						loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name,
						loc_messages, loc_priority, loc_enabled
						FROM T_locales ORDER BY loc_priority';
	$rows = $DB->get_results( $query, ARRAY_A );

	if( count( $rows ) ) foreach( $rows as $row )
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
																			'charset'  => $row[ 'loc_charset' ],
																			'datefmt'  => $row[ 'loc_datefmt' ],
																			'timefmt'  => $row[ 'loc_timefmt' ],
																			'name'     => $row[ 'loc_name' ],
																			'messages' => $row[ 'loc_messages' ],
																			'priority' => $priocounter,
																			'enabled'  => $row[ 'loc_enabled' ],
																			'fromdb'   => 1
																		);
	}

	// set default priorities, if nothing was set in DB.
	// Missing "priority gaps" will get filled here.
	if( count($rows) != count($locales) )
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

	if( $locale_fromdb  )
	{
		/*
		if( $locales[$locale_fromdb]['enabled'] )
			$default_locale = $locale_fromdb;
		elseif( !$locales[$default_locale]['enabled'] )
			$default_locale = 'en-EU';
		*/
		$default_locale = $locale_fromdb;
	}
}


/**
 * write $locales array to DB table
 *
 * @author blueyed
 */
function locale_updateDB()
{
	global $locales, $DB;

	$templocales = $locales;

	$lnr = 0;
	foreach( $_POST as $pkey => $pval ) if( preg_match('/loc_(\d+)_(.*)/', $pkey, $matches) )
	{
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
			$templocales[ $plocale ][$lfield] = remove_magic_quotes( $pval );
		}

	}

	$locales = $templocales;

	$query = "REPLACE INTO T_locales ( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled ) VALUES ";
	foreach( $locales as $localekey => $lval )
	{
		if( empty($lval['messages']) )
		{ // if not explicit messages file is given we'll translate the locale
			$lval['messages'] = strtr($localekey, '-', '_');
		}
		$query .= "(
		'$localekey',
		'{$lval['charset']}',
		'{$lval['datefmt']}',
		'{$lval['timefmt']}',
		'{$lval['name']}',
		'{$lval['messages']}',
		'{$lval['priority']}',
		'{$lval['enabled']}'
		), ";
	}
	$query = substr($query, 0, -2);
	$q = $DB->query($query);

	return (bool)$q;
}
?>