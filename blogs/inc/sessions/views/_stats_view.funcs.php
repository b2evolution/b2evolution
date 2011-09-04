<?php
/**
 * This file implements the UI view for the browser hits summary.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
		$BlogCache = & get_BlogCache();
		$tmp_Blog = & $BlogCache->get_by_ID( $hit_blog_ID );
		$full_url = $tmp_Blog->get('baseurlroot').$hit_uri;
	}
	else
	{
		$full_url = $hit_uri;
	}

	if( evo_strlen($hit_uri) > $max_len )
	{
		$hit_uri = '...'.evo_substr( $hit_uri, -$max_len );
	}

	return '<a href="'.$full_url.'">'.$hit_uri.'</a>';
}


/**
 * display avatar and login linking to sessions list for user
 *
 * @param mixed $login
 */
function stat_session_login( $login )
{
	if( empty($login) )
	{
		return '<span class="note">'.T_('Anon.').'</span>';
	}

	return '<span class="nowrap">'.get_avatar_imgtag( $login, false )
			.' <strong><a href="?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;user='.$login.'">'.$login.'</a></strong></span>';
}


/*
 * $Log$
 * Revision 1.12  2011/09/04 22:13:18  fplanque
 * copyright 2011
 *
 * Revision 1.11  2011/02/15 06:13:49  sam2kb
 * strlen replaced with evo_strlen to support utf-8 logins and domain names
 *
 * Revision 1.10  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.9  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.8  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.7  2009/09/20 00:27:08  fplanque
 * cleanup/doc/simplified
 *
 * Revision 1.6  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.5  2008/09/28 05:05:07  fplanque
 * minor
 *
 * Revision 1.4  2008/05/26 19:30:39  fplanque
 * enhanced analytics
 *
 * Revision 1.3  2008/02/19 11:11:19  fplanque
 * no message
 *
 * Revision 1.2  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:10  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.2  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.1  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 */
?>