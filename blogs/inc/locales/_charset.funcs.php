<?php
/**
 * This file implements functions for handling charsets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
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
 * @version $Id: _charset.funcs.php 6225 2014-03-16 10:01:05Z attila $
 *
 * @todo dh> Move this to some other directory?
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Use iconv() to transliterate non-ASCII chars in a string encoded with $evo_charset.
 *
 * This function will figure out a usable LC_CTYPE setting and revert it to the original value
 * after calling iconv().
 *
 * @author Tilman BLUMENBACH - tblue246
 * @todo Tblue> Try more locales.
 *
 * @param string The string to transliterate.
 * @param NULL|string The post locale. NULL to not try switching to it.
 * @return string|boolean The transliterated ASCII string on success or false on failure.
 */
function evo_iconv_transliterate( $str, $post_locale = NULL )
{
	global $evo_charset, $current_locale, $default_locale;

	if( ! function_exists( 'iconv' ) )
	{
		return false;
	}

	// iconv() needs a proper LC_CTYPE to work.
	// See http://www.php.net/manual/en/function.iconv.php#94481
	$orig_lc_ctype  = setlocale( LC_CTYPE, 0 );
	$lc_evo_charset = strtolower( str_replace( '-', '', $evo_charset ) );

	$locales_to_try = array(
		str_replace( '-', '_', $current_locale ).'.'.$lc_evo_charset, // Try to use current b2evo locale
		str_replace( '-', '_', $default_locale ).'.'.$lc_evo_charset, // Fallback to default b2evo locale
	);
	if( $post_locale !== NULL )
	{	// Try to switch to the post locale:
		array_unshift( $locales_to_try, str_replace( '-', '_', $post_locale ).'.'.$lc_evo_charset );
	}

	if( setlocale( LC_CTYPE, $locales_to_try ) === false )
	{	// The last thing we try is to use the system locale with our charset.
		if( ( $pos = strrpos( $orig_lc_ctype, '.' ) ) !== false )
		{	// Remove existing charset string:
			$syslocale = substr( $orig_lc_ctype, 0, $pos );
		}
		else
		{
			$syslocale = $orig_lc_ctype;
		}

		if( setlocale( LC_CTYPE, $syslocale.'.'.$lc_evo_charset ) === false )
		{	// We could not set a usable locale, giving up...
			return false;
		}
	}

	//pre_dump( setlocale( LC_CTYPE, 0 ) );

	// Transliterate the string:
	$newstr = iconv( $evo_charset, 'ASCII//TRANSLIT', $str );

	// Restore the original locale:
	setlocale( LC_CTYPE, $orig_lc_ctype );

	return $newstr;
}


/**
 * Convert special chars (like german umlauts) to ASCII characters.
 *
 * @param string Input string to operate on
 * @param NULL|string The post locale or NULL if there is no specific locale.
 *                    Gets passed to evo_iconv_transliterate().
 * @return string The input string with replaced chars.
 */
function replace_special_chars( $str, $post_locale = NULL )
{
	global $evo_charset, $default_locale, $current_locale, $locales;

	// Decode entities to be able to transliterate the associated chars:
	// Tblue> TODO: Check if this could have side effects.
	$str = evo_html_entity_decode( $str, ENT_NOQUOTES, $evo_charset );

	$our_locale = $post_locale;
	if( $our_locale === NULL )
	{	// post locale is not set, try to guess current locale
		if( !empty($default_locale) )
		{
			$our_locale = $default_locale;
		}
		if( !empty($current_locale) )
		{	// Override with current locale if available
			$our_locale = $current_locale;
		}
	}
	if( $our_locale !== NULL && isset($locales[$our_locale]) && !empty($locales[$our_locale]['transliteration_map']) )
	{	// Use locale 'transliteration_map' if present
		if( ! array_key_exists( '', $locales[$our_locale]['transliteration_map'] ) )
		{	// Make sure there's no empty string key, otherwise strtr() returns false
			if( $tmp_str = strtr( $str, $locales[$our_locale]['transliteration_map'] ) );
			{	// Use newly transliterated string
				$str = $tmp_str;
			}
		}
	}

	if( ( $newstr = evo_iconv_transliterate( $str, $post_locale ) ) !== false )
	{	// iconv allows us to get nice URL titles by transliterating non-ASCII chars.
		// Tblue> evo_htmlentities() does not know anything about ASCII?! ISO-8859-1 will work too, though.
		$newstr_charset = 'ISO-8859-1';
	}
	// TODO: sam2kb> convert this to 'transliteration_map'
	else if( can_convert_charsets('UTF-8', $evo_charset) && can_convert_charsets('UTF-8', 'ISO-8859-1') /* source */ )
	{	// Fallback to the limited old method: Transliterate only a few known chars.
		$newstr = convert_charset( $str, 'UTF-8', $evo_charset );
		$newstr_charset = 'UTF-8';

		$search = array( 'Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß', 'à', 'ç', 'è', 'é', 'ì', 'ò', 'ô', 'ù' ); // iso-8859-1
		$replace = array( 'Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss', 'a', 'c', 'e', 'e', 'i', 'o', 'o', 'u' );

		foreach( $search as $k => $v )
		{ // convert $search to UTF-8
			$search[$k] = convert_charset( $v, 'UTF-8', 'ISO-8859-1' );
		}

		$newstr = str_replace( $search, $replace, $newstr );
	}
	else
	{
		// Replace HTML entities only.
		$newstr = $str;
		$newstr_charset = $evo_charset;
	}

	// Replace HTML entities
	$newstr = evo_htmlentities( $newstr, ENT_NOQUOTES, $newstr_charset );

	// Handle special entities (e.g., use "-" instead of "a" for "&"):
	$newstr = str_replace(
		array( '&amp;', '&laquo;', '&raquo;' ),
		'-',
		$newstr );


	// Keep only one char in entities!
	$newstr = preg_replace( '/&(.).+?;/', '$1', $newstr );
	// Replace non acceptable chars
	$newstr = preg_replace( '/[^A-Za-z0-9_]+/', '-', $newstr );
	// Remove '-' at start and end:
	$newstr = preg_replace( '/^-+/', '', $newstr );
	$newstr = preg_replace( '/-+$/', '', $newstr );

	//pre_dump( $str, $newstr );

	return $newstr;
}

?>