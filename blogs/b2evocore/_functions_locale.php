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
if( ($use_l10n == 1) && function_exists('_') )
{	// We are going to use GETTEXT
	function T_( $string, $req_locale = '' )
	{
		global $current_messages;
		
		if( empty( $req_locale ) || $req_locale == $current_messages )
		{	// We have not asked for a different locale than the currently active one:
			return _($string);
		}
		// We have asked for a funky locale... we'll get english instead:
		return $string;
	}
}
elseif( $use_l10n == 2 )
{	// We are going to use b2evo localization:
	function T_( $string, $req_locale = '' )
	{
		global $trans, $current_messages;
		
		// By default we use the current locale:
		if( empty($req_locale) ) $req_locale = $current_messages;

		$search = str_replace( "\n", '\n', $string );
		$search = str_replace( "\r", '', $search );
		// echo "Translating ", $search, " to $req_locale<br />";
		
		#echo locale_messages($req_locale); exit;
		if( !isset($trans[ $current_messages ] ) )
		{	// Translations for current locale have not yet been loaded:
			@include_once( dirname(__FILE__). '/../locales/'. locale_fromdb($req_locale, 'messages'). '/_global.php' );
			if( !isset($trans[ $current_messages ] ) )
			{	// Still not loaded... file doesn't exist, memorize that no translation are available
				$trans[ $current_messages ] = array();
			}
		}
				
		if( isset( $trans[ $current_messages ][ $search ] ) )
		{	// If the string has been translated:
			return $trans[ $current_messages ][ $search ];
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
 * locale_activate(-)
 *
 * returns true if locale has been changed
 */
function locale_activate( $locale )
{
	global $use_l10n, $locales, $current_locale, $current_messages, $current_charset, $weekday, $month;

	if( $locale == $current_locale )
	{
		return false;
	}

	// Memorize new locale:
	$current_locale = $locale;
	// Memorize new charset:
	#$current_charset = $locales[ $locale ][ 'charset' ];
	$current_charset = locale_fromdb( $locale, 'charset' );
	$current_messages = locale_fromdb( $locale, 'messages' );

	// Activate translations in gettext:
	if( ($use_l10n == 1) && function_exists( 'bindtextdomain' ) )
	{	// Only if we war eusing GETTEXT ( if not, look into T_(-) ...)
		# Activate the locale->language in gettext:
		putenv( 'LC_ALL='. $current_messages );
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
	
	if( !isset( $default ) ) $default = $default_locale;
	
	foreach( $locales as $this_localekey => $this_locale )
	{
		echo '<option value="'. $this_localekey. '"';
		if( $this_localekey == $default ) echo ' selected="selected"';
		echo '>'. T_($this_locale['language']). '</option>';
	}
}

/*
 * locale_fromdb(-)
 *
 *	@return returns given property from DB
 *	
 *	blueyed: created. Temporary. Should consider using a class for locales.
 */
function locale_fromdb( $locale, $what )
{
	return false;  // damn, no db-connection.
	
	global $tablelocales, $querycount;
	$query = "SELECT loc_$what FROM $tablelocales WHERE loc_locale = '$locale'";
	$result = mysql_query( $query ) or mysql_oops( $query );
	$querycount++;
	if( $row = mysql_fetch_array( $result, MYSQL_ASSOC ) )
	{
		return $row[ 'loc_'. $what ];
	}
	else
	{
		return false;
	}
	
}

/*
 * locale_from_httpaccept(-)
 *
 *	@return locale made out of HTTP_ACCEPT_LANGUAGE or $default_locale, if no match
 *	
 *	blueyed: created
 */
function locale_from_httpaccept()
{
	global $locales, $default_locale;
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
	{
		#pre_dump($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'http_accept_language');
		// look for each language in turn in the preferences, which we saved in $langs
		foreach( $locales as $localekey => $v ) {
			#echo 'checking '. $localekey;
			$checklang = substr($localekey, 0, 2);
			$pos = strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $checklang);
			if( $pos !== false )
			{
				$text[] = str_pad($pos, 3, '0', STR_PAD_LEFT). '-'. $checklang. '-'. $localekey;
			}
		}
		if( sizeof($text) != 0 ) sort( $text );
		
		#var_dump($matches);
		// the preferred locale/language should be in $text[0]
		if( preg_match('/\d\d\d\-([a-z]{2})\-(.*)/', $text[0], $matches) )
			return $matches[2];
	}
	return $default_locale;
}

?>
