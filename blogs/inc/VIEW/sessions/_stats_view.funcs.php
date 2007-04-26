<?php
/**
 * This file implements the UI view for the browser hits summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Return a formatted percentage (should probably go to _misc.funcs)
 */
function percentage( $hit_count, $hit_total, $decimals = 1, $dec_point = '.' )
{
	return number_format( $hit_count * 100 / $hit_total, $decimals, $dec_point, '' ).'&nbsp;%';
}


/**
 * Helper function for "Requested URI" column
 * @param integer Blog ID
 * @param string Requested URI
 * @return string
 */
function stats_format_req_URI( $hit_blog_ID, $hit_uri, $max_len = 40 )
{
	if( !empty( $hit_blog_ID ) )
	{
		$BlogCache = & get_Cache( 'BlogCache' );
		$tmp_Blog = & $BlogCache->get_by_ID( $hit_blog_ID );
		$full_url = $tmp_Blog->get('baseurlroot').$hit_uri;
	}
	else
	{
		$full_url = $hit_uri;
	}

	if( strlen($hit_uri) > $max_len )
	{
		$hit_uri = '...'.substr( $hit_uri, -$max_len );
	}

	return '<a href="'.$full_url.'">'.$hit_uri.'</a>';
}


/*
 * $Log$
 * Revision 1.2  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.1  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 */
?>