<?php
/**
 * This file implements the UI controller for browsing the (hitlog) statistics.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('sessions/model/_hitlist.class.php', 'Hitlist' );
load_funcs('sessions/model/_hitlog.funcs.php');

/**
 * @var User
 */
global $current_User;

global $dispatcher;

global $collections_Module, $DB;
param_action();

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

// Do we have permission to view all stats (aggregated stats) ?
$perm_view_all = $current_User->check_perm( 'stats', 'view' );

// We set the default to -1 so that blog=0 will make its way into regenerate_url()s whenever watching global stats.
memorize_param( 'blog', 'integer', -1 );

$tab = param( 'tab', 'string', 'summary', true );
$tab3 = param( 'tab3', 'string', '', true );
$tab_from = param( 'tab_from', 'string', '', true );

if( in_array( $tab, array( 'settings', 'goals' ) ) )
{ // Change tab to default and blog to 'all' from other controllers
	$tab_real = $tab;
	$tab = 'summary';
}

param( 'action', 'string' );

if( $tab == 'domains' && $current_User->check_perm( 'stats', 'edit' ) )
{
	require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
}

if( $blog == 0 )
{
	if( ! $perm_view_all && isset( $collections_Module ) )
	{ // Find a blog we can view stats for:
		if( ! $selected = autoselect_blog( 'stats', 'view' ) )
		{ // No blog could be selected
			$Messages->add( T_('Sorry, there is no blog you have permission to view stats for.'), 'error' );
			$action = 'nil';
		}
		elseif( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
		{ // Selected a new blog:
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
	}
}

// Check permission to view current blog
$current_User->check_perm( 'stats', 'list', true, $blog );

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
		header_redirect( '?ctrl=stats&blog='.$blog, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'reset_counters':

		$current_User->check_perm( 'stats', 'edit', true );

		$sql = 'UPDATE T_track__keyphrase
				SET keyp_count_refered_searches = 0,
					keyp_count_internal_searches = 0';
		$DB->query( $sql, ' Reset keyphrases counters' );
		break;

	case 'update_settings':
		// UPDATE session settings:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'statssettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Hit & Session logs
		$Settings->set( 'log_public_hits', param( 'log_public_hits', 'integer', 0 ) );
		$Settings->set( 'log_admin_hits', param( 'log_admin_hits', 'integer', 0 ) );
		$Settings->set( 'log_spam_hits', param( 'log_spam_hits', 'integer', 0 ) );

		param( 'auto_prune_stats_mode', 'string', true );
		$Settings->set( 'auto_prune_stats_mode',  get_param('auto_prune_stats_mode') );

		// TODO: offer to set-up cron job if mode == 'cron' and to remove cron job if mode != 'cron'

		param( 'auto_prune_stats', 'integer', $Settings->get_default('auto_prune_stats'), false, false, true, false );
		$Settings->set( 'auto_prune_stats', get_param('auto_prune_stats') );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_( 'Settings updated.' ), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=stats&tab=settings&blog='.$blog, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'domain_new':
	case 'domain_edit':
		// Display form to create new domain

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		if( $action == 'domain_new' )
		{ // New Domain
			load_class( 'sessions/model/_domain.class.php', 'Domain' );
			$edited_Domain = new Domain();
			$edited_Domain->set( 'name', param( 'dom_name', 'string', '' ) );
			$edited_Domain->set( 'status', param( 'dom_status', 'string', 'unknown' ) );
		}
		else
		{ // Edit Domain
			param( 'dom_ID', 'integer', 0, true );
			$DomainCache = & get_DomainCache();
			if( ( $edited_Domain = & $DomainCache->get_by_ID( $dom_ID, false ) ) === false )
			{ // We could not find the goal to edit:
				unset( $edited_Domain );
				forget_param( 'dom_ID' );
				$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Domain') ), 'error' );
			}
		}
		break;

	case 'domain_update':
		// Create/Update Domain

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'domain' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		param( 'dom_ID', 'integer', 0, true );
		if( empty( $dom_ID ) )
		{ // Create Domain
			load_class( 'sessions/model/_domain.class.php', 'Domain' );
			$edited_Domain = new Domain();
		}
		else
		{ // Update Domain
			$DomainCache = & get_DomainCache();
			if( ( $edited_Domain = & $DomainCache->get_by_ID( $dom_ID, false ) ) === false )
			{ // We could not find the goal to edit:
				unset( $edited_Domain );
				forget_param( 'dom_ID' );
				$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Domain') ), 'error' );
			}
		}

		// load data from request
		if( $edited_Domain->load_from_Request() )
		{ // We could load data from form without errors:
			// Insert in DB:
			$edited_Domain->dbsave();
			$Messages->add( T_('New domain created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			if( $tab_from == 'antispam' )
			{ // Updating from antispam controller
				$redirect_to = $admin_url.'?ctrl=antispam&tab3=domains';
			}
			else
			{ // Updating from analitics collection page
				$redirect_to = $admin_url.'?ctrl=stats&tab=domains&tab3='.$tab3.'&blog='.$blog;
			}
			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'domain_new';
		break;
}

if( isset($collections_Module) && $tab_from != 'antispam' )
{ // Display list of blogs:
	if( $perm_view_all )
	{
		$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => $tab, 'tab3' => $tab3 ), T_('All'),
						$admin_url.'?ctrl=stats&amp;tab='.$tab.'&amp;tab3='.$tab3.'&amp;blog=0' );
	}
	else
	{	// No permission to view aggregated stats:
		$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => $tab, 'tab3' => $tab3 ) );
	}
}

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Analytics'), 'url' => '?ctrl=stats&amp;blog=$blog$' ) );
$AdminUI->set_page_manual_link( 'analytics-tab' );

if( isset( $tab_real ) )
{ // Restore real tab value
	$tab = $tab_real;
}

switch( $tab )
{
	case 'summary':
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Summary'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
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
				$AdminUI->breadcrumbpath_add( T_('Browsers'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'robot':
				$AdminUI->breadcrumbpath_add( T_('Robots'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'feed':
				$AdminUI->breadcrumbpath_add( T_('RSS/Atom'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		// Init jqPlot charts
		init_jqplot_js();
		break;

	case 'other':
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Direct hits'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'hits':
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('All Hits'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'referers':
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Referred by other sites'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'refsearches':
		$AdminUI->breadcrumbpath_add( T_('Hits'), '?ctrl=stats&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( T_('Incoming searches'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		if( empty($tab3) )
		{
			$tab3 = 'hits';
		}
		switch( $tab3 )
		{
			case 'hits':
				// $AdminUI->breadcrumbpath_add( T_('Latest'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'keywords':
				$AdminUI->breadcrumbpath_add( T_('Searched keywords'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'topengines':
				$AdminUI->breadcrumbpath_add( T_('Top search engines'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;
				
		}
		break;

	case 'ips':
		$AdminUI->breadcrumbpath_add( T_('IPs'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->breadcrumbpath_add( T_('Top IPs'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		$tab3 = 'top';
		break;

	case 'domains':
		$AdminUI->breadcrumbpath_add( T_('Referring domains'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		if( $action == 'domain_new' )
		{
			$AdminUI->breadcrumbpath_add( T_('Add domain'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;action=domain_new' );
		}
		if( empty( $tab3 ) )
		{
			$tab3 = 'all';
		}
		switch( $tab3 )
		{
			case 'top':
				$AdminUI->breadcrumbpath_add( T_('Top referrers'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;

			case 'all':
			default:
				$AdminUI->breadcrumbpath_add( T_('All referrers'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
				break;
		}
		break;

	case 'goals':
		$AdminUI->breadcrumbpath_add( T_('Goal tracking'), '?ctrl=goals&amp;blog=$blog$' );
		switch( $tab3 )
		{
			case 'hits':
				$AdminUI->breadcrumbpath_add( T_('Goal hits'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
				break;
		}
		$AdminUI->set_page_manual_link( 'goal-hits' );
		break;

	case 'settings':
		$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		break;

}

if( $tab_from == 'antispam' )
{ // User goes from antispam tab, Set the correct paths
	$AdminUI->set_path( 'options', 'antispam', 'domains' );
	$AdminUI->breadcrumbpath = array();
	$AdminUI->breadcrumb_titles = array();
	$AdminUI->breadcrumbpath_init( false );
	$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
	$AdminUI->breadcrumbpath_add( T_('Antispam'), $admin_url.'?ctrl=antispam' );
	$AdminUI->breadcrumbpath_add( T_('Referring domains'), $admin_url.'?ctrl=antispam&amp;tab3=domains' );
	$AdminUI->breadcrumbpath_add( T_('Add domain'), $admin_url.'?ctrl=stats&amp;tab=domains&amp;action=domain_new&amp;tab_from='.$tab_from );
}
else
{
	$AdminUI->set_path( 'stats', $tab, $tab3 );
}

if( $tab == 'domains' )
{ // Load jquery UI to highlight cell on change domain type
	require_js( '#jqueryUI#' );
}

if( in_array( $tab , array( 'hits', 'other', 'referers' ) ) ||
    ( $tab == 'refsearches' && in_array( $tab3 , array( 'hits', 'keywords' ) ) ) ||
    ( $tab == 'goals' && $tab3 == 'hits' ) )
{ // Initialize date picker for _stats_search_keywords.view.php and _stats_goalhits.view.php
	init_datepicker_js();
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

evo_flush();

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
		}
		break;

	case 'ips':
		// Display VIEW for Top IPs:
		$AdminUI->disp_view( 'sessions/views/_stats_topips.view.php' );
		break;

	case 'antispam':
	case 'domains':
		// Display VIEW for domains:
		switch( $action )
		{
			case 'domain_new':
			case 'domain_edit':
				if( isset( $edited_Domain ) )
				{
					$AdminUI->disp_view( 'sessions/views/_stats_refdomains.form.php' );
					break;
				}

			default:
				$AdminUI->disp_view( 'sessions/views/_stats_refdomains.view.php' );
				break;
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

	case 'settings':
		// Display VIEW:
		$AdminUI->disp_view( 'sessions/views/_stats_settings.form.php' );
		break;

}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>