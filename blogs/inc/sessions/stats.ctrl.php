<?php
/**
 * This file implements the UI controller for browsing the (hitlog) statistics.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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

load_class('sessions/model/_hitlist.class.php', 'Hitlist' );
load_class('sessions/model/_internal_searches.class.php', 'Internalsearches' );
load_funcs('sessions/model/_hitlog.funcs.php');

/**
 * @var User
 */
global $current_User;

global $dispatcher;

global $collections_Module;
param_action();

// Do we have permission to view all stats (aggregated stats) ?
$perm_view_all = $current_User->check_perm( 'stats', 'view' );

// We set the default to -1 so that blog=0 will make its way into regenerate_url()s whenever watching global stats.
memorize_param( 'blog', 'integer', -1 );

$tab = param( 'tab', 'string', 'summary', true );
if( $tab == 'sessions' && (!$perm_view_all || $blog != 0) )
{	// Sessions tab is not narrowed down to blog level:
	$tab = 'summary';
}
$tab3 = param( 'tab3', 'string', '', true );

param( 'action', 'string' );
if( ($tab=="refsearches") && ($tab3=="intsearches")) 
{

	if( param( 'isrch_ID', 'integer', '', true) )
	{ // Load file type:  fp>al: please checl all your comments so they match the code
		$ISCache = & get_InternalSearchesCache();
		if( ($edited_intsearch = & $ISCache->get_by_ID( $isrch_ID, false )) === false )
		{	// We could not find the goal to edit:
			unset( $edited_intsearch );
			forget_param( 'isrch_ID' );
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('InternalSearch') ), 'error' );
			$action = 'nil';
		}
	}
	
	switch( $action )
	{
	
		case 'new':
		case 'copy':
// fp>al: do we use these?
			// Check permission:
			$sessions_Module->check_perm( 'edit' );
			if( ! isset($edited_intsearch) )
			{	// We don't have a model to use, start with blank object:
				$edited_intsearch = new InternalSearches();
			}
			else
			{	// Duplicate object in order no to mess with the cache:
				$edited_intsearch = duplicate( $edited_intsearch ); // PHP4/5 abstraction
				$edited_intsearch->ID = 0;
			}
			break;
	
		case 'edit':
// fp>al: do we use this?
			// Edit file type form...:
	
			// Check permission:
			$sessions_Module->check_perm( 'edit' );
	
			// Make sure we got an ftyp_ID:
			param( 'isrch_ID', 'integer', true );
	 		break;
	
		case 'create': // Record new goal
		case 'create_new': // Record goal and create new
		case 'create_copy': // Record goal and create similar
			// Insert new file type...:
			$edited_intsearch = new InternalSearches();
	
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'internalsearches' );
	
			// Check permission:
			$sessions_Module->check_perm( 'edit' );
	
			// load data from request
			if( $edited_intsearch->load_from_Request() )
			{	// We could load data from form without errors:
	
				// Insert in DB:
				$DB->begin();
				$edited_intsearch->dbinsert();
				$Messages->add( T_('New internal search created.'), 'success' );
				$DB->commit();
	
				if( empty($q) )
				{	// What next?
					switch( $action )
					{
						case 'create_copy':
							// Redirect so that a reload doesn't write to the DB twice:
							header_redirect( '?ctrl=stats&tab=refsearches&tab3=intsearches&action=new&isrch_ID='.$edited_intsearch->ID, 303 ); // Will EXIT
							// We have EXITed already at this point!!
							break;
						case 'create_new':
							// Redirect so that a reload doesn't write to the DB twice:
							header_redirect( '?ctrl=stats&action=new&tab=refsearches&tab3=intsearches', 303 ); // Will EXIT
							// We have EXITed already at this point!!
							break;
						case 'create':
							// Redirect so that a reload doesn't write to the DB twice:
							header_redirect( '?ctrl=stats&tab=refsearches&tab3=intsearches', 303 ); // Will EXIT
							// We have EXITed already at this point!!
							break;
					}
				}
			}
			break;
	
		case 'update':
// fp>al: do we use this?
			// Edit file type form...:
	
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'internalsearches' );
	
			// Check permission:
			$sessions_Module->check_perm( 'edit' );
	
			// Make sure we got an ftyp_ID:
			param( 'isrch_ID', 'integer', true );
	
			// load data from request
			if( $edited_intsearch->load_from_Request() )
			{	// We could load data from form without errors:
	
				// Update in DB:
				$DB->begin();
				$edited_intsearch->dbupdate();
				$Messages->add( T_('Internal search updated.'), 'success' );
				$DB->commit();
	
				if( empty($q) )
				{
					$action = 'list';
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=stats&tab=refsearches&tab3=intsearches', 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
	
	
			break;
	
		case 'delete':
			// Delete file type:
	
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'internalsearches' );
	
			// Check permission:
			$sessions_Module->check_perm( 'edit' );
	
			// Make sure we got an ftyp_ID:
			param( 'isrch_ID', 'integer', true );
			
				
				$msg = sprintf( T_('Internal search &laquo;%s&raquo; deleted.'), $edited_intsearch->dget('name') );
				//print_r($edited_intsearch);
				$edited_intsearch->dbdelete( true );
				unset( $edited_intsearch );
				forget_param( 'isrch_ID' );
				$Messages->add( $msg, 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=stats&tab=refsearches&tab3=intsearches', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			break;
	
	}
		
	
}
else
{

if( $blog == 0 )
{
	if( (!$perm_view_all) && isset($collections_Module) )
	{	// Find a blog we can view stats for:
		if( ! $selected = autoselect_blog( 'stats', 'view' ) )
		{ // No blog could be selected
			$Messages->add( T_('Sorry, there is no blog you have permission to view stats for.'), 'error' );
			$action = 'nil';
		}
		elseif( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
		{	// Selected a new blog:
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
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
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'stats' );
		
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
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=stats', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}
}
if( $tab != 'sessions' )
{ // no need to show blogs list while displaying sessions

	if( isset($collections_Module) )
	{ // Display list of blogs:
		if( $perm_view_all )
		{
			$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => $tab, 'tab3' => $tab3 ), T_('All'),
							$dispatcher.'?ctrl=stats&amp;tab='.$tab.'&amp;tab3='.$tab3.'&amp;blog=0' );
		}
		else
		{	// No permission to view aggregated stats:
			$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => $tab, 'tab3' => $tab3 ) );
		}
	}
}

$AdminUI->breadcrumbpath_init();
switch( $tab )
{
	case 'summary':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Summary'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		if( empty($tab3) )
		{
			$tab3 = 'global';
		}
		switch( $tab3 )
		{
			case 'global':
				$AdminUI->breadcrumbpath_add( T_('All'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'browser':
				$AdminUI->breadcrumbpath_add( T_('Browsers'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'robot':
				$AdminUI->breadcrumbpath_add( T_('Robots'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'feed':
				$AdminUI->breadcrumbpath_add( T_('RSS/Atom'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		break;

	case 'other':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Direct hits'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		break;

	case 'referers':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Referred by other sites'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		break;

	case 'refsearches':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Incoming searches'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		if( empty($tab3) )
		{
			$tab3 = 'hits';
		}
		switch( $tab3 )
		{
			case 'hits':
				// $AdminUI->breadcrumbpath_add( T_('Latest'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'keywords':
				$AdminUI->breadcrumbpath_add( T_('Searched keywords'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'topengines':
				$AdminUI->breadcrumbpath_add( T_('Top search engines'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
				
			case 'intsearches':
				$AdminUI->breadcrumbpath_add( T_('Internal searches'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		break;

	case 'domains':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Referring domains'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		break;

	case 'sessions':
		$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
		$AdminUI->breadcrumbpath_add( T_('Sessions'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
		if( empty($tab3) )
		{
			$tab3 = 'login';
		}
		switch( $tab3 )
		{
			case 'login':
				$AdminUI->breadcrumbpath_add( T_('Session by user'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
			case 'sessid':
				// fp> TODO: include username in path if we have one
				$AdminUI->breadcrumbpath_add( T_('Recent sessions'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
			case 'hits':
				$AdminUI->breadcrumbpath_add( T_('Recent hits'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		break;

	case 'goals':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Goal tracking'), '?ctrl=goals' );
		switch( $tab3 )
		{
			case 'hits':
				$AdminUI->breadcrumbpath_add( T_('Goal hits'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
				break;
		}
		break;

}

if( $tab == 'sessions' )
{ // Show this sub-tab in Users tab
	$AdminUI->set_path( 'users', $tab, $tab3 );
	$AdminUI->title = T_('Stats');
}
else
{
	$AdminUI->set_path( 'stats', $tab, $tab3 );
	$AdminUI->title = T_('Stats');
}

if( ( $tab3 == 'keywords' ) || ( $tab == 'goals' && $tab3 == 'hits' ) )
{ // Load the data picker style for _stats_search_keywords.view.php and _stats_goalhits.view.php
	require_css( 'ui.datepicker.css' );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

flush();

if( ($tab=="refsearches") && ($tab3=="intsearches") ) 
{
	
	switch( $action )
	{
		case 'nil':
			// Do nothing
			break;


		case 'delete':
			// We need to ask for confirmation:
			$edited_Goal->confirm_delete(
					sprintf( T_('Delete internal search item &laquo;%s&raquo;?'), $edited_intsearch->dget('keywords') ),
					'internalsearch', $action, get_memorized( 'action' ) );
			/* no break */
		case 'new':
		case 'copy':
		case 'create':	// we return in this state after a validation error
		case 'create_new':	// we return in this state after a validation error
		case 'create_copy':	// we return in this state after a validation error
		case 'edit':
		case 'update':	// we return in this state after a validation error
			$AdminUI->disp_view( 'sessions/views/_internal_search.form.php' );
			break;


		default:
			// No specific request, list all file types:
			switch( $tab3 )
			{
				case 'intsearches':
					// Cleanup context:
					forget_param( 'isrch_ID' );
					// Display goals list:
					$AdminUI->disp_view( 'sessions/views/_stats_internal_searches.view.php' );
					break;

			}

	}

} 
else 
{
	
switch( $AdminUI->get_path(1) )
{
	case 'summary':
		// Display VIEW:
		switch( $tab3 )
		{
			case 'browser':
				$AdminUI->disp_view( 'sessions/views/_stats_browserhits.view.php' );
				break;

			case 'robot':
				$AdminUI->disp_view( 'sessions/views/_stats_robots.view.php' );
				break;

			case 'feed':
				$AdminUI->disp_view( 'sessions/views/_stats_syndication.view.php' );
				break;

			case 'global':
			default:
				$AdminUI->disp_view( 'sessions/views/_stats_summary.view.php' );
		}
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
		switch( $tab3 )
		{
			case 'hits':
				$AdminUI->disp_view( 'sessions/views/_stats_refsearches.view.php' );
				break;

			case 'keywords':
				$AdminUI->disp_view( 'sessions/views/_stats_search_keywords.view.php' );
				break;

			case 'topengines':
				$AdminUI->disp_view( 'sessions/views/_stats_search_engines.view.php' );
				break;
				
			case 'intsearches':
				$AdminUI->disp_view( 'sessions/views/_stats_internal_searches.view.php' );
				break;
		}
		break;

	case 'domains':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_refdomains.view.php' );
		break;

	case 'sessions':
		// Display VIEW:
		switch( $tab3 )
		{
			case 'sessid':
				$AdminUI->disp_view( 'sessions/views/_stats_sessions_list.view.php' );
				break;

			case 'hits':
				$AdminUI->disp_view( 'sessions/views/_stats_hit_list.view.php' );
				break;

			case 'login':
				$AdminUI->disp_view( 'sessions/views/_stats_sessions.view.php' );
		}
		break;

	case 'goals':
		// Display VIEW for Goal HITS:
		switch( $tab3 )
		{
			case 'hits':
				$AdminUI->disp_view( 'sessions/views/_stats_goalhits.view.php' );
				break;
		}
		break;

}
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.29  2011/09/09 21:48:51  fplanque
 * indenting cleanp
 *
 * Revision 1.28  2011/09/09 21:45:57  fplanque
 * doc
 *
 * Revision 1.27  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.26  2011/09/04 22:13:18  fplanque
 * copyright 2011
 *
 * Revision 1.25  2010/10/22 15:09:57  efy-asimo
 * Remove autoloading datepciker css, instead load before every usage, also remove jquery-ui.css load
 *
 * Revision 1.24  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.23  2010/01/16 12:47:37  efy-yury
 * update stats: crumbs and redirect
 *
 * Revision 1.22  2009/12/12 01:13:08  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.21  2009/12/08 22:38:13  fplanque
 * User agent type is now saved directly into the hits table instead of a costly lookup in user agents table
 *
 * Revision 1.20  2009/12/06 22:55:21  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.19  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.18  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.17  2009/09/20 00:27:08  fplanque
 * cleanup/doc/simplified
 *
 * Revision 1.16  2009/09/19 21:49:03  efy-sergey
 * Moved Stats>User Sessions tab to Users>Sessions
 *
 * Revision 1.15  2009/09/14 11:24:02  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.14  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.13  2009/07/06 23:52:25  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.12  2009/05/16 00:31:45  fplanque
 * AFAICS this only appears in the title tag
 *
 * Revision 1.11  2009/04/12 09:29:47  tblue246
 * minor
 *
 * Revision 1.10  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.9  2008/05/26 19:30:33  fplanque
 * enhanced analytics
 *
 * Revision 1.8  2008/05/10 22:59:10  fplanque
 * keyphrase logging
 *
 * Revision 1.7  2008/04/17 11:53:19  fplanque
 * Goal editing
 *
 * Revision 1.6  2008/03/20 14:20:52  fplanque
 * no message
 *
 * Revision 1.5  2008/02/19 11:11:18  fplanque
 * no message
 *
 * Revision 1.4  2008/01/21 09:35:32  fplanque
 * (c) 2008
 *
 * Revision 1.3  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 *
 * Revision 1.2  2007/09/19 09:41:57  yabs
 * minor bug fix
 *
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