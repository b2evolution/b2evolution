<?php
/**
 * This file implements the UI controller for browsing the (hitlog) statistics.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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


load_class('sessions/model/_hitlist.class.php');

/**
 * @var User
 */
global $current_User;

// Do we have permission to view all stats (aggregated stats) ?
$perm_view_all = $current_User->check_perm( 'stats', 'view' );

// We set the default to -1 so that blog=0 will make its way into regenerate_url()s whenever watching global stats.
memorize_param( 'blog', 'integer', -1 );

$tab = param( 'tab', 'string', 'summary', true );
if( $tab == 'sessions' && (!$perm_view_all || $blog != 0) )
{	// Sessions tab is not narrowed down to blog level:
	$tab = 'summary';
}
$AdminUI->set_path( 'stats', $tab );
$AdminUI->title = T_('View Stats for Blog:');

param( 'action', 'string' );

if( $blog == 0 && $perm_view_all )
{	// We want to view aggregate stats
}
else
{	// Find a blog we can view stats for:
	if( ! $selected = autoselect_blog( 'stats', 'view' ) )
	{ // No blog could be selected
		$Messages->add( T_('Sorry, there is no blog you have permission to view stats for.'), 'error' );
		$action = 'nil';
	}
	elseif( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
	{	// Selected a new blog:
		$BlogCache = & get_Cache( 'BlogCache' );
		$Blog = & $BlogCache->get_by_ID( $blog );
	}
}

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

if( $perm_view_all )
{
	$blogListButtons = $AdminUI->get_html_collection_list( 'stats', 'view',
					'admin.php?ctrl=stats&amp;tab='.$tab.'&amp;blog=%d', T_('All'),
					'admin.php?ctrl=stats&amp;tab='.$tab.'&amp;blog=0' );
}
else
{	// No permission to view aggregated stats:
	$blogListButtons = $AdminUI->get_html_collection_list( 'stats', 'view',
					'admin.php?ctrl=stats&amp;tab='.$tab.'&amp;blog=%d' );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


switch( $AdminUI->get_path(1) )
{
	case 'summary':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_summary.view.php' );
		break;

	case 'browserhits':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_browserhits.view.php' );
		break;

	case 'other':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_direct.view.php' );
		break;

	case 'referers':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_referers.view.php' );
		break;

	case 'refsearches':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_refsearches.view.php' );
		break;

	case 'robots':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_robots.view.php' );
		break;

	case 'syndication':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_syndication.view.php' );
		break;

	case 'useragents':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_useragents.view.php' );
		break;

	case 'domains':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_refdomains.view.php' );
		break;

	case 'sessions':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_sessions.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.1  2007/06/25 11:00:56  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.37  2007/05/13 18:49:55  fplanque
 * made autoselect_blog() more robust under PHP4
 *
 * Revision 1.36  2007/04/26 00:11:16  fplanque
 * (c) 2007
 *
 * Revision 1.35  2007/03/20 09:55:06  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 * Revision 1.33  2007/03/02 01:36:51  fplanque
 * small fixes
 *
 * Revision 1.32  2006/12/07 23:21:00  fplanque
 * dashboard blog switching
 */
?>