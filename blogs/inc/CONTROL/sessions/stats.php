<?php
/**
 * This file implements the UI controller for browsing the (hitlog) statistics.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 * @author vegarg: Vegar BERG GULDAL
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The Hitlist class
 */
require_once $model_path.'sessions/_hitlist.class.php';


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

$tab = param( 'tab', 'string', 'summary', true );
$AdminUI->set_path( 'stats', $tab );
$AdminUI->title = T_('View Stats for Blog:');

param( 'action', 'string' );
param( 'blog', 'integer', 0 );

$blogListButtons = '<a href="'.regenerate_url( array('blog','page'), "blog=0" ).'" class="'.(( 0 == $blog ) ? 'CurrentBlog' : 'OtherBlog').'">'.T_('None').'</a> ';
for( $curr_blog_ID = blog_list_start();
			$curr_blog_ID != false;
			$curr_blog_ID = blog_list_next() )
{
	$blogListButtons .= '<a href="'.regenerate_url( array('blog','page'), "blog=$curr_blog_ID" ).'" class="'.(( $curr_blog_ID == $blog ) ? 'CurrentBlog' : 'OtherBlog').'">'.blog_list_iteminfo('shortname',false).'</a> ';
}

if( $blog )
{
	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = & $BlogCache->get_by_ID($blog); // "Exit to blogs.." link
}


// Check permission:
$current_User->check_perm( 'stats', 'view', true );


switch( $action )
{
	case 'changetype': // Change the type of a hit
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'hit_ID', 'integer', true );      // Required!
		param( 'new_hit_type', 'string', true ); // Required!

		Hitlist::change_type( $hit_ID, $new_hit_type );
		$Messages->add( sprintf( T_('Changed hit #%d type to: %s.'), $hit_ID, $new_hit_type), 'success' );
		break;


	case 'delete': // DELETE A HIT
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'hit_ID', 'integer', true ); // Required!

		if( Hitlist::delete( $hit_ID ) )
		{
			$Messages->add( sprintf( T_('Deleted hit #%d.'), $hit_ID ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_('Could not delete hit #%d.'), $hit_ID ), 'note' );
		}
		break;


	case 'prune': // PRUNE hits for a certain date
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'date', 'integer', true ); // Required!
		if( $r = Hitlist::prune( $date ) )
		{
			$Messages->add( sprintf( /* TRANS: %s is a date */ T_('Deleted %d hits for %s.'), $r, date( locale_datefmt(), $date) ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( /* TRANS: %s is a date */ T_('No hits deleted for %s.'), date( locale_datefmt(), $date) ), 'note' );
		}
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


switch( $AdminUI->get_path(1) )
{
	case 'sessions':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/_stats_sessions.view.php' );
		break;

	case 'summary':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/_stats_summary.view.php' );
		break;

	case 'other':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/_stats_direct.view.php' );
		break;

	case 'referers':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/_stats_referers.view.php' );
		break;

	case 'refsearches':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/_stats_refsearches.view.php' );
		break;

	case 'syndication':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/_stats_syndication.view.php' );
		break;

	case 'useragents':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/_stats_useragents.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.30  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.29  2006/07/12 20:18:20  fplanque
 * session stats + minor enhancements
 *
 * Revision 1.28  2006/07/12 18:07:06  fplanque
 * splitted stats into different views
 *
 * Revision 1.27  2006/07/11 22:41:42  fplanque
 * tweaked stats a little
 *
 * Revision 1.26  2006/07/11 19:56:20  fplanque
 * top indexing robots optimized some more.
 * reformatted queries
 *
 * Revision 1.25  2006/07/11 17:31:03  blueyed
 * Fixed ban link in "referers" (ctrl), thanks EdB.
 *
 * Revision 1.24  2006/07/10 15:14:57  blueyed
 * Fix for ban links for referers with subdomains (again).
 *
 * Revision 1.23  2006/07/06 19:59:08  fplanque
 * better logs, better stats, better pruning
 *
 * Revision 1.21  2006/07/03 18:58:56  blueyed
 * Optimized "top indexing robots" better (previous commit reverted)
 *
 * Revision 1.20  2006/07/03 18:25:55  blueyed
 * Optimized expensive count query away ("refsearches")
 *
 * Revision 1.19  2006/07/01 23:12:45  fplanque
 * rolled back unnecessary changes
 *
 * Revision 1.18  2006/06/05 17:10:17  blueyed
 * doc
 *
 * Revision 1.17  2006/06/01 17:52:15  fplanque
 * no message
 *
 * Revision 1.16  2006/05/12 21:53:37  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.15  2006/05/05 20:10:40  blueyed
 * Fix
 *
 * Revision 1.14  2006/05/05 19:52:46  blueyed
 * minor
 *
 * Revision 1.13  2006/05/02 18:07:13  blueyed
 * Set blog to be used for exit to blogs link
 *
 * Revision 1.12  2006/05/02 01:47:58  blueyed
 * Normalization
 *
 * Revision 1.11  2006/04/27 20:10:34  fplanque
 * changed banning of domains. Suggest a prefix by default.
 *
 * Revision 1.10  2006/04/25 00:19:25  blueyed
 * Also only count "browser" hits as referers in summary; added row with total numbers
 *
 * Revision 1.9  2006/04/20 19:14:03  blueyed
 * Link "Requested URI" columns to blog's baseurlroot+URI
 *
 * Revision 1.8  2006/04/20 17:59:01  blueyed
 * Removed "spam" from hit_referer_type (DB) and summary stats
 *
 * Revision 1.7  2006/04/19 20:03:04  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/04/19 17:20:07  blueyed
 * Prefix "ban" domains with "://"; do only count browser type hits as referer (not "rss"!); Whitespace!
 *
 * Revision 1.5  2006/03/17 20:48:16  blueyed
 * Do not restrict to "stub" type blogs
 *
 * Revision 1.4  2006/03/12 23:08:56  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/02 20:05:29  blueyed
 * Fixed/polished stats (linking T_useragents to T_hitlog, not T_sessions again). I've done this the other way around before, but it wasn't my idea.. :p
 *
 * Revision 1.2  2006/03/01 22:17:00  blueyed
 * Fixed table title
 *
 * Revision 1.1  2006/02/23 21:11:56  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.12  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.11  2005/12/03 12:35:02  blueyed
 * Fix displaying of Message when changing hit type to search. Closes: http://dev.b2evolution.net/todo.php/2005/12/02/changin_hit_type_to_search
 *
 * Revision 1.10  2005/11/23 23:14:50  blueyed
 * minor (translation)
 *
 * Revision 1.9  2005/11/05 01:53:53  blueyed
 * Linked useragent to a session rather than a hit;
 * SQL: moved T_hitlog.hit_agnt_ID to T_sessions.sess_agnt_ID
 *
 * Revision 1.8  2005/10/31 05:51:05  blueyed
 * Use rawurlencode() instead of urlencode()
 *
 * Revision 1.7  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.6  2005/10/14 21:00:08  fplanque
 * Stats & antispam have obviously been modified with ZERO testing.
 * Fixed a sh**load of bugs...
 *
 */
?>