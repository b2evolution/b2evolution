<?php
/**
 * This file implements functions for handling charsets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
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
 * @todo Try to switch system locale to post locale
 * @author Tilman BLUMENBACH - tblue246
 * 
 * @param string The string to transliterate.
 * @return string|boolean The transliterated ASCII string on success or false on failure.
 */
function evo_iconv_transliterate( $str )
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

	if( setlocale( LC_CTYPE,
				str_replace( '-', '_', $current_locale ).'.'.$lc_evo_charset, // Try to use current b2evo locale
				str_replace( '-', '_', $default_locale ).'.'.$lc_evo_charset  // Fallback to default b2evo locale
		) === false )
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
 * @return string The input string with replaced chars.
 */
function replace_special_chars( $str )
{
	global $evo_charset;

	// Decode entities to be able to transliterate the associated chars:
	// Tblue> TODO: Check if this could have side effects.
	$str = html_entity_decode( $str, ENT_NOQUOTES, $evo_charset );

	if( ( $newstr = evo_iconv_transliterate( $str ) ) !== false )
	{	// iconv allows us to get nice URL titles by transliterating non-ASCII chars.
		// Tblue> htmlentities() does not know anything about ASCII?! ISO-8859-1 will work too, though.
		$newstr_charset = 'ISO-8859-1';
	}
	else if( can_convert_charsets('UTF-8', $evo_charset) && can_convert_charsets('UTF-8', 'ISO-8859-1') /* source */ )
	{	// Fallback to the limited old method: Transliterate only a few known chars.
		$newstr = convert_charset( $str, 'UTF-8', $evo_charset );
		$newstr_charset = 'UTF-8';

		// TODO: add more...?!
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
	$newstr = htmlentities( $newstr, ENT_NOQUOTES, $newstr_charset );

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


/*
 * $Log$
 * Revision 1.7  2009/11/17 21:09:38  blueyed
 * replace_special_chars: special handling of entities '&amp;', '&laquo;', '&raquo;': replace by dash.
 *
 * Revision 1.6  2009/11/14 20:44:32  tblue246
 * doc
 *
 * Revision 1.5  2009/11/14 14:47:47  tblue246
 * replace_special_chars(): Try to use iconv() to transliterate non-ASCII chars.
 *
 * Revision 1.4  2009/10/19 21:50:36  blueyed
 * doc
 *
 * Revision 1.3  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:32  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:36  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/04/26 00:11:02  fplanque
 * (c) 2007
 *
 * Revision 1.2  2006/12/15 23:31:21  fplanque
 * reauthorized _ in urltitles.
 * No breaking of legacy permalinks.
 * - remains the default placeholder though.
 *
 * Revision 1.1  2006/12/04 21:20:28  blueyed
 * Abstracted convert_special_charsets() out of urltitle_validate()
 */
?>
