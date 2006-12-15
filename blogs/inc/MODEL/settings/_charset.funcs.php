<?php
/**
 * This file implements functions for handling charsets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * Convert special chars (like german umlauts) to ASCII characters.
 *
 * @todo dh> IMHO this function should not be included in a file that gets used often/always.
 * @param string
 * @return string
 */
function replace_special_chars( $str )
{
	global $evo_charset;

	if( can_convert_charsets('UTF-8', $evo_charset) && can_convert_charsets('UTF-8', 'ISO-8859-1') /* source */ )
	{
		$str = convert_charset( $str, 'UTF-8', $evo_charset );

		// TODO: add more...?!
		$search = array( 'Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß', 'à', 'ç', 'è', 'é', 'ì', 'ò', 'ô', 'ù' ); // iso-8859-1
		$replace = array( 'Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss', 'a', 'c', 'e', 'e', 'i', 'o', 'o', 'u' );

		foreach( $search as $k => $v )
		{ // convert $search to UTF-8
			$search[$k] = convert_charset( $v, 'UTF-8', 'ISO-8859-1' );
		}
		$str = str_replace( $search, $replace, $str );

		// Replace HTML entities
		$str = htmlentities( $str, ENT_NOQUOTES, 'UTF-8' );
	}
	else
	{
		// Replace HTML entities only
		$str = htmlentities( $str, ENT_NOQUOTES, $evo_charset );
	}

	// Keep only one char in entities!
	$str = preg_replace( '/&(.).+?;/', '$1', $str );
	// Replace non acceptable chars
	$str = preg_replace( '/[^A-Za-z0-9_]+/', '-', $str );
	// Remove '-' at start and end:
	$str = preg_replace( '/^-+/', '', $str );
	$str = preg_replace( '/-+$/', '', $str );

	return $str;
}


/*
 * $Log$
 * Revision 1.2  2006/12/15 23:31:21  fplanque
 * reauthorized _ in urltitles.
 * No breaking of legacy permalinks.
 * - remains the default placeholder though.
 *
 * Revision 1.1  2006/12/04 21:20:28  blueyed
 * Abstracted convert_special_charsets() out of urltitle_validate()
 *
 */
?>