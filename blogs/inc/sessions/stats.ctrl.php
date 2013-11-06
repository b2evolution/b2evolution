<?php
/**
 * This file implements the UI controller for browsing the (hitlog) statistics.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
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

global $collections_Module, $DB;
param_action();

// Do we have permission to view all stats (aggregated stats) ?
$perm_view_all = $current_User->check_perm( 'stats', 'view' );

// We set the default to -1 so that blog=0 will make its way into regenerate_url()s whenever watching global stats.
memorize_param( 'blog', 'integer', -1 );

$tab = param( 'tab', 'string', 'summary', true );
$tab3 = param( 'tab3', 'string', '', true );

param( 'action', 'string' );

if( ($tab=='refsearches') && ($tab3=='intsearches') ) 
{

	if( param( 'isrch_ID', 'integer', '', true) )
	{ 
		$ISCache = & get_InternalSearchesCache();
		if( ($edited_intsearch = & $ISCache->get_by_ID( $isrch_ID, false )) === false )
		{	// We could not find the internal search to edit:
			unset( $edited_intsearch );
			forget_param( 'isrch_ID' );
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Internal Search') ), 'error' );
			$action = 'nil';
		}
	}
	
	switch( $action )
	{
	
		case 'delete':
			// Delete file type:
	
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'internalsearches' );
	
			// Check permission:
			$current_User->check_perm( 'stats', 'edit', true );
	
			// Make sure we got an isrch_ID:
			param( 'isrch_ID', 'integer', true );
			
				
				$msg = sprintf( T_('Internal search &laquo;%s&raquo; deleted.'), $edited_intsearch->dget('name') );
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

if ($tab == 'domains' && $current_User->check_perm( 'stats', 'edit' ))
{
	require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
}

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

	case 'reset_counters':

		$current_User->check_perm( 'stats', 'edit', true );

		$sql = 'UPDATE T_track__keyphrase
				SET keyp_count_refered_searches = 0,
					keyp_count_internal_searches = 0';
		$DB->query( $sql, ' Reset keyphrases counters' );
		break;

}
}

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

	case 'hits':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('All Hits'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
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
				
		}
		break;

	case 'domains':
		$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Referring domains'), '?ctrl=stats&amp;blog=$blog$&tab='.$tab );
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

$AdminUI->set_path( 'stats', $tab, $tab3 );

if( in_array( $tab , array( 'hits', 'other', 'referers' ) ) ||
    ( $tab == 'refsearches' && in_array( $tab3 , array( 'hits', 'keywords' ) ) ) ||
    ( $tab == 'goals' && $tab3 == 'hits' ) )
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
	case 'hits':
	case 'referers':
		// Display hits results table:
		hits_results_block();
		break;

	case 'refsearches':
		// Display VIEW:
		switch( $tab3 )
		{
			case 'hits':
				// Display hits results table:
				hits_results_block();
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
 * Revision 1.37  2013/11/06 08:04:45  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>