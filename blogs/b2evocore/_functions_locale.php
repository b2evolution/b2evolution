<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */



/*
 * T_(-)
 * 
 * TRANSLATE!
 * Translated a text to the desired locale (b2evo localization only)
 * or to the current locale
 */
if( ($use_l10n==1) && function_exists('_') )
{	// We are going to use GETTEXT
	function T_( $string, $req_locale = '' )
	{
		global $current_locale;
		
		if( empty( $req_locale ) || $req_locale == $current_locale )
		{	// We have not asked for a different locale than the currently active one:
			return _($string);
		}
		// We have asked for a funky locale... we'll get english instead:
		return $string;
	}
}
elseif( $use_l10n==2 )
{	// We are going to use b2evo localization:
	function T_( $string, $req_locale = '' )
	{
		global $trans, $current_locale;
		
		// By default we use the current locale:
		if( empty($req_locale) ) $req_locale = $current_locale;

		$search = str_replace( "\n", '\n', $string );
		$search = str_replace( "\r", '', $search );
		// echo "Translating ", $search, " to $req_locale<br />";
		
		if( !isset($trans[$current_locale] ) )
		{	// Translations for current locale have not yet been loaded:
			@include_once( dirname(__FILE__).'/../locales/'.$req_locale.'/_global.php' );
			if( !isset($trans[$current_locale] ) )
			{	// Still not loaded... file doesn't exist, memorize that no translation are available
				$trans[$current_locale] = array();
			}
		}
				
		if( isset( $trans[$current_locale][$search] ) )
		{	// If the string has been translated:
			return str_replace( '\n', "\n", $trans[$current_locale][$search] );
		}
		
		// echo "Not found!";
		
		// Return the English string:
		return $string;
	}
}
else
{	// We are not localizing at all:
	function T_( $string, $req_locale = '' )
	{	
		return $string;
	}
}


/*
 * NT_(-)
 * 
 * No Translation
 * Nevertheless, the string will be extracted by the gettext tools
 */
function NT_($string)
{
	return $string;
}


/*
 * locale_activate(-)
 *
 * returns true if locale has been changed
 */
function locale_activate( $locale )
{
	global $use_l10n, $locales, $current_locale, $current_charset, $weekday, $month;

	if( $locale == $current_locale )
	{
		return false;
	}

	// Memorize new locale:
	$current_locale = $locale;
	// Memorize new charset:
	$current_charset = $locales[$locale]['charset'];

	// Activate translations in gettext:
	if( ($use_l10n==1) && function_exists( 'bindtextdomain' ) )
	{	// Only if we war eusing GETTEXT ( if not, look into T_(-) ...)
		# Activate the locale->language in gettext:
		putenv( 'LC_ALL='.$locale ); 
		// Note: default of safe_mode_allowed_env_vars is "PHP_ ", 
		// so you need to add "LC_" by editing php.ini. 

		
		# Activate the charset for conversions in gettext:
		if( function_exists( 'bind_textdomain_codeset' ) )
		{	// Only if this gettext supports code conversions
			bind_textdomain_codeset( 'messages', $current_charset );
		}
	}

	
	# Set locale for default language:
	# This will influence the way numbers are displayed, etc.
	// We are not using this right now, the default 'C' locale seems just fine
	// setlocale( LC_ALL, $locale );
	
	# Use this to check locale:
	// echo setlocale( LC_ALL, 0 );
	
	return true;
}


/*
 * locale_by_lang(-)
 *
 * Find first locale matching lang
 */
function locale_by_lang( $lang )
{
	global $locales, $default_locale;
	
	foreach( $locales as $locale => $locale_params )
	{
		if( substr( $locale, 0 ,2 ) == $lang )
		{	// found first matching locale
			return $locale;
		}
	}

	// Not found...
	return $default_locale;
}

/*
 * locale_lang(-)
 *
 * Returns the language code part of a locale name
 */
function locale_lang( $disp = true )
{
	global $current_locale;

	$current_language = substr( $current_locale, 0, 2 );
	
	if( $disp )
		echo $current_language;
	else
		return $current_language;
}

/*
 * locale_charset(-)
 */
function locale_charset( $disp = true )
{
	global $current_charset;
	
	if( $disp )
		echo $current_charset;
	else
		return $current_charset;
}

/*
 * locale_datefmt(-)
 */
function locale_datefmt()
{
	global $locales, $current_locale;
	
	return $locales[$current_locale]['datefmt'];
}

/*
 * locale_timefmt(-)
 */
function locale_timefmt()
{
	global $locales, $current_locale;
	
	return $locales[$current_locale]['timefmt'];
}



/*
 * lang_options(-)
 */
function lang_options( $default = '' )
{
	global $languages, $default_language;
	
	if( !isset( $default ) ) $default = $default_language;
	
	foreach( $languages as $this_lang => $this_lang_name )
	{
		echo '<option value="'.$this_lang.'"';
		if( $this_lang == $default ) echo ' selected="selected"';
		echo '>'.T_($this_lang_name).'</option>';
	}
}

?>
