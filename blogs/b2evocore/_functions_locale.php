<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */



if(!function_exists('_'))
{
	/*
	 * _(-)
	 * 
	 * Replacement for when gettext is not available.
	 */
	function _($string)
	{
		return $string;
	}
}

if(!function_exists('N_'))
{
	/*
	 * N_(-)
	 * 
	 * Replacement for when gettext_noop is not available.
	 */
	function N_($string)
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
	global $locales, $current_locale, $current_charset, $weekday, $month;

	if( $locale == $current_locale )
	{
		return false;
	}

	$current_locale = $locale;

	# Activate the default language in gettext:
	// putenv( 'LANGUAGE='.$default_language );  // doc says to use this... but it doesn't work
	putenv( 'LC_ALL='.$locale ); 
	
	# Activate the charset for default language in gettext:
	$current_charset = $locales[$locale]['charset'];
	bind_textdomain_codeset( 'messages', $current_charset );
	
	# Set locale for default language:
	# This will influence the way numbers are displayed, etc.
	setlocale( LC_ALL, $locale );
	
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
		echo '>'._($this_lang_name).'</option>';
	}
}

?>
