<?php
/**
 * This file implements the goals.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'sessions/model/_goal.class.php', 'Goal' );
load_class( 'sessions/model/_goalcat.class.php', 'GoalCategory' );
load_funcs('sessions/model/_hitlog.funcs.php');

/**
 * @var User
 */
global $current_User;

global $collections_Module;

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

// Do we have permission to view all stats (aggregated stats) ?
$perm_view_all = $current_User->check_perm( 'stats', 'view' );

$tab3 = param( 'tab3', 'string', 'goals', true );
$AdminUI->set_path( 'stats', 'goals', $tab3 );

if( isset( $collections_Module ) )
{ // Display list of blogs:
	if( $perm_view_all )
	{
		$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => 'summary', 'tab3' => 'global' ), T_('All'),
						$admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0' );
	}
	else
	{ // No permission to view aggregated stats:
		$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => 'summary', 'tab3' => $tab3 ) );
	}
}

param_action();

if( $blog == 0 )
{
	if( ! $perm_view_all && isset( $collections_Module ) )
	{ // Find a blog we can view stats for:
		if( ! $selected = autoselect_blog( 'stats', 'view' ) )
		{ // No blog could be selected
			$Messages->add( T_('Sorry, there is no blog you have permission to view stats for.'), 'error' );
			$action = 'nil';
		}
		elseif( set_working_blog( $selected ) ) // set $blog & memorize in user prefs
		{ // Selected a new blog:
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
	}
}

// Check permission to view current blog
$current_User->check_perm( 'stats', 'list', true, $blog );

if( param( 'goal_ID', 'integer', '', true) )
{ // Load goal:
	$GoalCache = & get_GoalCache();
	if( ($edited_Goal = & $GoalCache->get_by_ID( $goal_ID, false )) === false )
	{ // We could not find the goal to edit:
		unset( $edited_Goal );
		forget_param( 'goal_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Goal') ), 'error' );
		$action = 'nil';
	}
}

if( param( 'gcat_ID', 'integer', '', true) )
{ // Load goal category:
	$GoalCategoryCache = & get_GoalCategoryCache();
	if( ($edited_GoalCategory = & $GoalCategoryCache->get_by_ID( $gcat_ID, false )) === false )
	{ // We could not find the goal category to edit:
		unset( $edited_GoalCategory );
		forget_param( 'gcat_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Goal Category') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{

	case 'new':
	case 'copy':
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		if( ! isset($edited_Goal) )
		{	// We don't have a model to use, start with blank object:
			$edited_Goal = new Goal();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Goal = clone $edited_Goal;
			$edited_Goal->ID = 0;
		}
		break;

	case 'edit':
		// Edit goal form...:

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'goal_ID', 'integer', true );
		break;

	case 'create': // Record new goal
	case 'create_new': // Record goal and create new
	case 'create_copy': // Record goal and create similar
		// Insert new goal...:
		$edited_Goal = new Goal();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'goal' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// load data from request
		if( $edited_Goal->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$DB->begin();
			$q = $edited_Goal->dbexists();
			if($q)
			{	// We have a duplicate entry:

				param_error( 'goal_key',
					sprintf( T_('This goal already exists. Do you want to <a %s>edit the existing goal</a>?'),
						'href="?ctrl=goals&amp;action=edit&amp;blog='.$Blog->ID.'&amp;goal_ID='.$q.'"' ) );
			}
			else
			{
				$edited_Goal->dbinsert();
				$Messages->add( T_('New goal created.'), 'success' );
			}
			$DB->commit();

			if( empty($q) )
			{	// What next?
				switch( $action )
				{
					case 'create_copy':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=goals&action=new&blog='.$Blog->ID.'&goal_ID='.$edited_Goal->ID, 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create_new':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=goals&action=new&blog='.$Blog->ID, 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=goals&blog='.$Blog->ID, 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
				}
			}
		}
		break;

	case 'update':
		// Edit goal form...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'goal' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'goal_ID', 'integer', true );

		// load data from request
		if( $edited_Goal->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$DB->begin();
			$q = $edited_Goal->dbexists();
			if($q)
			{	// We have a duplicate entry:

				param_error( 'goal_key',
					sprintf( T_('This goal already exists. Do you want to <a %s>edit the existing goal</a>?'),
						'href="?ctrl=goals&amp;action=edit&amp;blog='.$Blog->ID.'&amp;goal_ID='.$q.'"' ) );
			}
			else
			{
				$edited_Goal->dbupdate();
				$Messages->add( T_('Goal updated.'), 'success' );
			}
			$DB->commit();

			if( empty($q) )
			{
				$action = 'list';
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=goals&blog='.$Blog->ID, 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}


		break;

	case 'delete':
		// Delete goal:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'goal' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'goal_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Goal &laquo;%s&raquo; deleted.'), $edited_Goal->dget('name') );
			$edited_Goal->dbdelete();
			unset( $edited_Goal );
			forget_param( 'goal_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=goals&blog='.$Blog->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Goal->check_delete( sprintf( T_('Cannot delete goal &laquo;%s&raquo;'), $edited_Goal->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

	// **** Categories **** //
	case 'cat_new':
	case 'cat_copy':
		// New goal category form...:

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		if( ! isset( $edited_GoalCategory ) )
		{ // We don't have a model to use, start with blank object:
			$edited_GoalCategory = new GoalCategory();
		}
		else
		{ // Duplicate object in order no to mess with the cache:
			$edited_GoalCategory = clone $edited_GoalCategory;
			$edited_GoalCategory->ID = 0;
		}
		break;

	case 'cat_edit':
		// Edit goal category form...:

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'gcat_ID', 'integer', true );
		break;

	case 'cat_create': // Record new goal category
	case 'cat_create_new': // Record goal category and create new
	case 'cat_create_copy': // Record goal category and create similar
		// Insert new goal category...:
		$edited_GoalCategory = new GoalCategory();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'goalcat' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// load data from request
		if( $edited_GoalCategory->load_from_Request() )
		{ // We could load data from form without errors:

			// Insert in DB:
			$DB->begin();
			$edited_GoalCategory->dbinsert();
			$Messages->add( T_('New goal category created.'), 'success' );
			$DB->commit();

			// What next?
			switch( $action )
			{
				case 'cat_create_copy':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=goals&tab3=cats&action=cat_new&blog='.$Blog->ID.'&gcat_ID='.$edited_GoalCategory->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'cat_create_new':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=goals&tab3=cats&action=cat_new&blog='.$Blog->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'cat_create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=goals&tab3=cats&blog='.$Blog->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'cat_update':
		// Edit goal category form...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'goalcat' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'gcat_ID', 'integer', true );

		// load data from request
		if( $edited_GoalCategory->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$DB->begin();
			$edited_GoalCategory->dbupdate();
			$Messages->add( T_('Goal category updated.'), 'success' );
			$DB->commit();

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=goals&tab3=cats&blog='.$Blog->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}


		break;

	case 'cat_delete':
		// Delete goal category:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'goalcat' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'gcat_ID', 'integer', true );

		if( $gcat_ID == 1 )
		{ // Deny to delete "Default" category
			$Messages->add( sprintf( T_('Cannot delete goal category &laquo;%s&raquo;'), $edited_GoalCategory->dget('name') ), 'error' );
			$action = 'view';
			break;
		}

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Goal category &laquo;%s&raquo; deleted.'), $edited_GoalCategory->dget('name') );
			$edited_GoalCategory->dbdelete();
			unset( $edited_GoalCategory );
			forget_param( 'gcat_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=goals&tab3=cats&blog='.$Blog->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // not confirmed, Check for restrictions:
			if( ! $edited_GoalCategory->check_delete( sprintf( T_('Cannot delete goal category &laquo;%s&raquo;'), $edited_GoalCategory->dget('name') ) ) )
			{ // There are restrictions:
				$action = 'view';
			}
		}
		break;

}

$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Analytics'), '?ctrl=stats&amp;blog=$blog$' );
$AdminUI->breadcrumbpath_add( T_('Goal tracking'), '?ctrl=goals&amp;blog=$blog$' );

$AdminUI->set_page_manual_link( 'analytics-tab' );

switch( $tab3 )
{
	case 'goals':
		$AdminUI->breadcrumbpath_add( T_('Goal definitions'), '?ctrl=goals&amp;blog=$blog$' );
		$AdminUI->set_page_manual_link( 'goal-settings' );
		break;
	case 'stats':
		$AdminUI->breadcrumbpath_add( T_('Goal hit stats'), '?ctrl=goals&amp;tab3=stats&amp;blog=$blog$' );
		$AdminUI->set_page_manual_link( 'goal-stats' );
		// Init jqPlot charts
		init_jqplot_js();
		break;
	case 'cats':
		$AdminUI->breadcrumbpath_add( T_('Goal categories'), '?ctrl=goals&amp;tab3=cats&amp;blog=$blog$' );
		$AdminUI->set_page_manual_link( 'goal-category-settings' );
		init_colorpicker_js();
		break;
}

if( in_array( $action, array( 'delete', 'new', 'copy', 'create', 'create_new', 'create_copy', 'edit', 'update' ) ) )
{ // Initialize date picker for _goal.form.php
	init_datepicker_js();
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'delete':
		// We need to ask for confirmation:
		$edited_Goal->confirm_delete(
				sprintf( T_('Delete goal &laquo;%s&raquo;?'), $edited_Goal->dget('name') ),
				'goal', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'copy':
	case 'create':	// we return in this state after a validation error
	case 'create_new':	// we return in this state after a validation error
	case 'create_copy':	// we return in this state after a validation error
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_view( 'sessions/views/_goal.form.php' );
		break;

	case 'cat_delete':
		// We need to ask for confirmation:
		$edited_GoalCategory->confirm_delete(
				sprintf( T_('Delete goal category &laquo;%s&raquo;?'), $edited_GoalCategory->dget('name') ),
				'goalcat', $action, get_memorized( 'action' ) );
		/* no break */
	case 'cat_new':
	case 'cat_copy':
	case 'cat_create':	// we return in this state after a validation error
	case 'cat_create_new':	// we return in this state after a validation error
	case 'cat_create_copy':	// we return in this state after a validation error
	case 'cat_edit':
	case 'cat_update':	// we return in this state after a validation error
		$AdminUI->disp_view( 'sessions/views/_stats_goalcats.form.php' );
		break;

	default:
		// No specific request, list all file types:
		switch( $tab3 )
		{
			case 'goals':
				// Cleanup context:
				forget_param( 'goal_ID' );
				// Display goals list:
				$AdminUI->disp_view( 'sessions/views/_stats_goals.view.php' );
				break;

			case 'cats':
				$AdminUI->disp_view( 'sessions/views/_stats_goalcats.view.php' );
				break;

			case 'stats':
				$AdminUI->disp_view( 'sessions/views/_goal_hitsummary.view.php' );
				break;
		}

}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>