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
		$full_url = $tmp_Blog->get_baseurl_root().$hit_uri;
	}
	else
	{
		$full_url = $hit_uri;
	}
	
	$int_search_uri = urldecode($hit_uri);
	if( ( evo_strpos( $int_search_uri , '?s=' ) !== false ) 
	 || ( evo_strpos( $int_search_uri , '&s=' ) !== false ) ) 
	{	// This is an internal search:
		ereg( '[\\?&]s=([^&#]*)', $int_search_uri, $res );
		$hit_uri = 'Internal search : '.$res[1];
	} 
	elseif( evo_strlen($hit_uri) > $max_len )
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

	return get_user_identity_link( $login, NULL, 'admin' );
}


/**
 * Display user sessions
 * 
 * @param string user login
 * @param string link text
 */
function stat_user_sessions( $login,  $link_text )
{
	return '<strong><a href="?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;user='.$login.'">'.$link_text.'</a></strong>';
}


/**
 * Display session hits
 * 
 * @param string session ID
 * @param string link text
 */
function stat_session_hits( $sess_ID,  $link_text )
{
        global $blog;
	return '<strong><a href="?&ctrl=stats&tab=hits&colselect_submit=Filter+list&sess_ID='.$sess_ID.'&remote_IP=&blog='.$blog.'">'.$link_text.'</a></strong>';
}

/**
 * Display clickable sessID
 *
 * @param string session ID
 */
function disp_clickable_log_sessID( $hit_sess_ID)
{
	global $current_User, $blog;
	static $perm = NULL;

	if (empty($perm))
	{
	$perm = $current_User->check_perm( 'stats', 'view' );
	}
	if ($perm == true)
	{
		return '<strong><a href="?&ctrl=stats&tab=hits&colselect_submit=Filter+list&sess_ID='.$hit_sess_ID.'&remote_IP=&blog='.$blog.'">'.$hit_sess_ID.'</a></strong>';
	}
	else
	{
		return "$hit_sess_ID";
	}

}

/**
 * Display clickable log IP address
 *
 * @param string remote adress IP
 */
function disp_clickable_log_IP( $hit_remote_addr )
{
	global $current_User, $blog;
	static $perm = NULL;

	if (empty($perm))
	{
	$perm = $current_User->check_perm( 'stats', 'view' );
	}
	if ($perm == true)
	{
		return '<a href="?&ctrl=stats&tab=hits&colselect_submit=Filter+list&sess_ID=&remote_IP='.$hit_remote_addr.'&blog='.$blog.'">'.$hit_remote_addr.'</a>';
	}
	else
	{
		return "$hit_remote_addr";
	}

}



/**
 * Display color referer
 *
 * @param hit referer type
 */
function disp_color_referer( $hit_referer_type )
{
	global $referer_type_color;
	if (!empty ($referer_type_color[$hit_referer_type]))
	{
		return '<span style="background-color: #'.$referer_type_color[$hit_referer_type].'" >'.$hit_referer_type.'</span>';
	}
	else
	{
		return "$hit_referer_type";
	}

}

/**
 * Display color agent type
 *
 * @param hit agent type
 */
function disp_color_agent( $hit_agent_type )
{
	global $agent_type_color;
	if (!empty ($agent_type_color[$hit_agent_type]))
	{
		return '<span style="background-color: #'.$agent_type_color[$hit_agent_type].'" >'.$hit_agent_type.'</span>';
	}
	else
	{
		return "$hit_agent_type";
	}

}
/*
 * $Log$
 * Revision 1.19  2011/10/03 10:41:25  efy-vitalij
 * add colors to statistic
 *
 * Revision 1.18  2011/10/01 10:05:31  efy-vitalij
 * fix tab=hits links
 *
 * Revision 1.17  2011/09/28 11:33:57  efy-vitalij
 * add IDs & IPs clickable to direct stat
 *
 * Revision 1.16  2011/09/23 07:41:57  efy-asimo
 * Unified usernames everywhere in the app - first part
 *
 * Revision 1.15  2011/09/17 02:31:59  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.14  2011/09/14 21:04:06  fplanque
 * cleanup
 *
 * Revision 1.13  2011/09/13 23:28:35  lxndral
 * users sessions -> Hitlist update
 *
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