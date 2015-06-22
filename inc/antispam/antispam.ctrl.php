<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}.
 *
 * @package admin
 *
 * @todo Allow applying / re-checking of the known data, not just after an update!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

param( 'display_mode', 'string' );

if( $display_mode != 'js' )
{
	$AdminUI->set_path( 'options', 'antispam' );
}
else
{	// This is an Ajax response
	// fp> TODO: have a more systematic way of handling AJAX responses
	header_content_type( 'text/html', $io_charset );
}

param_action( '' );
param( 'confirm', 'string' );
param( 'keyword', 'string', '', true );
param( 'domain', 'string' );
param( 'filteron', 'string', '', true );
param( 'filter', 'array:string', array() );

$tab = param( 'tab', 'string', '', true );
$tab3 = param( 'tab3', 'string', '', true );
$tool = param( 'tool', 'string', '', true );

if( isset($filter['off']) )
{
	unset( $filteron );
	forget_param( 'filteron' );
}

// Check permission:
$current_User->check_perm( 'options', 'view', true );
$current_User->check_perm( 'spamblacklist', 'view', true );


if( param( 'iprange_ID', 'integer', '', true) )
{	// Load IP Range object
	load_class( 'antispam/model/_iprange.class.php', 'IPRange' );
	$IPRangeCache = & get_IPRangeCache();
	if( ( $edited_IPRange = & $IPRangeCache->get_by_ID( $iprange_ID, false ) ) === false )
	{	// We could not find the goal to edit:
		unset( $edited_IPRange );
		forget_param( 'iprange_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('IP Range') ), 'error' );
	}
}

switch( $action )
{
	case 'ban': // only an action if further "actions" given
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true ); // TODO: This should become different for 'edit'/'add' perm level - check for 'add' here.

		$keyword = utf8_substr( $keyword, 0, 80 );
		param( 'delhits', 'integer', 0 );
		$all_statuses = get_visibility_statuses( 'keys', array( 'trash', 'redirected' ) );
		$delstatuses = array();
		foreach( $all_statuses as $status )
		{ // collect which comments should be delteded
			if( param( 'del'.$status, 'integer', 0 ) )
			{ // matching comments with this status should be deleted
				$delstatuses[] = $status;
			}
		}
		$delcomments = count( $delstatuses );
		param( 'blacklist_locally', 'integer', 0 );
		param( 'report', 'integer', 0 );

		// Check if the string is too short,
		// it has to be a minimum of 5 characters to avoid being too generic
		if( utf8_strlen( $keyword ) < 5 )
		{
			$Messages->add( sprintf( T_('The keyword &laquo;%s&raquo; is too short, it has to be a minimum of 5 characters!'), htmlspecialchars( $keyword ) ), 'error' );
			break;
		}

		if( empty( $confirm ) )
		{ // No confirmed action, Execute the ban actions only after confirmation
			break;
		}

		if( $delhits )
		{ // Delete all banned hit-log entries
			$r = $DB->query( 'DELETE FROM T_hitlog
												WHERE hit_referer LIKE '.$DB->quote( '%'.$keyword.'%' ),
												'Delete all banned hit-log entries' );

			$Messages->add( sprintf( T_('Deleted %d logged hits matching &laquo;%s&raquo;.'), $r, htmlspecialchars( $keyword ) ), 'success' );
		}

		if( $delcomments )
		{ // select banned comments
			$del_condition = blog_restrict( $delstatuses );
			$keyword_cond = '(comment_author LIKE '.$DB->quote( '%'.$keyword.'%' ).'
							OR comment_author_email LIKE '.$DB->quote( '%'.utf8_strtolower( $keyword ).'%' ).'
							OR comment_author_url LIKE '.$DB->quote( '%'.$keyword.'%' ).'
							OR comment_content LIKE '.$DB->quote( '%'.$keyword.'%' ).')';
			// asimo> we don't need transaction here 
			$query = 'SELECT comment_ID FROM T_comments
							WHERE '.$keyword_cond.$del_condition;
			$deleted_ids = $DB->get_col( $query, 0, 'Get comment ids awaiting for delete' );
			$r = count( $deleted_ids );
			$deleted_ids = implode( ',', $deleted_ids );

			// Delete all comments data from DB
			Comment::db_delete_where( 'Comment', $keyword_cond.$del_condition );

			$Messages->add( sprintf( T_('Deleted %d comments matching &laquo;%s&raquo;.'), $r, htmlspecialchars( $keyword ) ), 'success' );
		}

		if( $blacklist_locally )
		{ // Local blacklist:
			if( antispam_create( $keyword ) )
			{ // Success
				$Messages->add( sprintf( T_('The keyword &laquo;%s&raquo; has been blacklisted locally.'), htmlspecialchars( $keyword ) ), 'success' );
			}
			else
			{ // Failed
				$Messages->add( sprintf( T_('Failed to add the keyword %s to black list locally.'), '<b>'.htmlspecialchars( $keyword ).'</b>' ), 'error' );
			}
		}

		if( $report )
		{ // Report this keyword as abuse remotely:
			antispam_report_abuse( $keyword );
		}

		if( ! $blacklist_locally && ! $report && ! $delhits && ! $delcomments )
		{ // If no action has been selected
			$Messages->add( T_('Please select at least one action to ban the keyword.' ), 'error' );
		}

		if( $display_mode != 'js' && ! $Messages->has_errors() )
		{
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=antispam', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( $Messages->has_errors() )
		{ // Reset js display mode in order to display a correct view after confirmation
			$display_mode = '';
			$mode = '';
		}

		param( 'request', 'string', '' );
		if( $display_mode == 'js' && $request != 'checkban' )
		{
			if( $delcomments && $r ) // $r not null => means the commentlist was deleted successfully
			{
				send_javascript_message( array( 'refreshAfterBan' => array( $deleted_ids ), 'closeModalWindow' => array() ), true );
			}
			else
			{
				send_javascript_message( array( 'closeModalWindow' => array() ), true );
			}
		}

		// We'll ask the user later what to do, if no "sub-action" given.
		break;


	case 'remove':
		// Remove a domain from ban list:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		param( 'hit_ID', 'integer', true );	// Required!
		$Messages->add( sprintf( T_('Removing entry #%d from the ban list...'), $hit_ID), 'note' );
		antispam_delete( $hit_ID );
		break;


	case 'report':
		// Report an entry as abuse to centralized blacklist:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		// Report this keyword as abuse:
		antispam_report_abuse( $keyword );
		break;


	case 'poll':
		// request abuse list from central blacklist:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		ob_start();
		antispam_poll_abuse();
		$Debuglog->add( ob_get_contents(), 'antispam_poll' );
		ob_end_clean();
		break;

	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// fp> Restore defaults has been removed because it's extra maintenance work and no real benefit to the user.

		param_integer_range( 'antispam_threshold_publish', -100, 100, T_('The threshold must be between -100 and 100.') );
		$Settings->set( 'antispam_threshold_publish', $antispam_threshold_publish );

		param_integer_range( 'antispam_threshold_delete', -100, 100, T_('The threshold must be between -100 and 100.') );
		$Settings->set( 'antispam_threshold_delete', $antispam_threshold_delete );

		param( 'antispam_block_spam_referers', 'integer', 0 );
		$Settings->set( 'antispam_block_spam_referers', $antispam_block_spam_referers );

		param( 'antispam_report_to_central', 'integer', 0 );
		$Settings->set( 'antispam_report_to_central', $antispam_report_to_central );

		$changed_weight = false;
		param( 'antispam_plugin_spam_weight', 'array:integer', array() );
		foreach( $antispam_plugin_spam_weight as $l_plugin_ID => $l_weight )
		{
			if( ! is_numeric($l_weight) )
			{
				continue;
			}
			if( $l_weight < 0 || $l_weight > 100 )
			{
				param_error( 'antispam_plugin_spam_weight['.$l_plugin_ID.']', T_('Spam weight has to be in the range of 0-100.') );
				continue;
			}
			if( $DB->query( '
					UPDATE T_plugins
						 SET plug_spam_weight = '.$DB->quote($l_weight).'
					 WHERE plug_ID = '.(int)$l_plugin_ID ) )
			{
				$changed_weight = true;
			}
		}
		if( $changed_weight )
		{ // Reload plugins table (for display):
			$Plugins->loaded_plugins_table = false;
			$Plugins->load_plugins_table();
		}

		// Suspicious users
		$Settings->set( 'antispam_suspicious_group', param( 'antispam_suspicious_group', 'integer', 0 ) );

		// Trust groups
		$trust_groups = param( 'antispam_trust_groups', 'array:integer', array() );
		$Settings->set( 'antispam_trust_groups', implode( ',', $trust_groups ) );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();

			$Messages->add( T_('Settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=antispam&tab3=settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

	case 'find_spam_comments':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$keywords = $DB->get_col('SELECT aspm_string FROM T_antispam');
		$keywords = array_chunk( $keywords, 100 );
		$rows_affected = 0;

		@ignore_user_abort(true);
		set_max_execution_time(900);

		// Delete comments in chunks of 100 keywords per SQL query
		foreach( $keywords as $chunk )
		{
			$arr = array();
			foreach( $chunk as $word )
			{
				$arr[] = $DB->quote('%'.$word.'%');
			}

			$DB->query('DELETE FROM T_comments
						WHERE (comment_author LIKE '.implode(' OR comment_author LIKE ', $arr).')
						OR (comment_author_email LIKE '.implode(' OR comment_author_email LIKE ', $arr).')
						OR (comment_author_url LIKE '.implode(' OR comment_author_url LIKE ', $arr).')
						OR (comment_content LIKE '.implode(' OR comment_content LIKE ', $arr).')',
						'Delete spam comments');

			$rows_affected = $rows_affected + $DB->rows_affected;
		}
		$Messages->add( sprintf( T_('Deleted %d comments'), $rows_affected ), 'success' );
		break;

	case 'find_spam_referers':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$keywords = $DB->get_col('SELECT aspm_string FROM T_antispam');
		$keywords = array_chunk( $keywords, 100 );
		$rows_affected = 0;

		@ignore_user_abort(true);
		set_max_execution_time(900);

		// Delete hits in chunks of 100 keywords per SQL query
		foreach( $keywords as $chunk )
		{
			$arr = array();
			foreach( $chunk as $word )
			{
				$arr[] = $DB->quote('%'.$word.'%');
			}

			$DB->query('DELETE FROM T_hitlog
						WHERE hit_referer LIKE '.implode(' OR hit_referer LIKE ', $arr),
						'Delete all banned hit-log entries' );

			$rows_affected = $rows_affected + $DB->rows_affected;
		}
		$Messages->add( sprintf( T_('Deleted %d logged hits'), $rows_affected ), 'success' );
		break;

	case 'iprange_create':
		// Create new IP Range...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'iprange' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		$edited_IPRange = new IPRange();

		// load data from request
		if( $edited_IPRange->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_IPRange->dbinsert();
			$Messages->add( T_('New IP Range created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=antispam&tab='.$tab.'&tab3=ipranges', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'iprange_new';
		break;

	case 'iprange_update':
		// Update IP Range...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'iprange' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		// Make sure we got an iprange_ID:
		param( 'iprange_ID', 'integer', true );

		// load data from request
		if( $edited_IPRange->load_from_Request() )
		{	// We could load data from form without errors:
			// Update IP Range in DB:
			$edited_IPRange->dbupdate();
			$Messages->add( T_('IP Range updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=antispam&tab='.$tab.'&tab3=ipranges', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'iprange_edit';
		break;

	case 'iprange_delete':
		// Delete IP Range...

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'iprange' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		// Make sure we got an iprange_ID:
		param( 'iprange_ID', 'integer', true );

		if( $edited_IPRange->dbdelete() )
		{
			$Messages->add( T_('IP Range deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=antispam&tab='.$tab.'&tab3=ipranges', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'bankruptcy_delete':
		// Delete ALL comments from selected blogs

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'antispam' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$bankruptcy_blogs_IDs = param( 'bankruptcy_blogs', 'array:integer', array() );

		if( empty( $bankruptcy_blogs ) )
		{
			$Messages->add( T_('Please select at least one blog.'), 'error' );
		}

		if( !param_errors_detected() )
		{ // Try to obtain some serious time to do some serious processing (15 minutes)
			set_max_execution_time( 900 );
			// Turn off the output buffering to do the correct work of the function flush()
			@ini_set( 'output_buffering', 'off' );
			// Set this to start deleting in the template file
			$delete_bankruptcy_blogs = true;
		}
		break;
}

if( $display_mode != 'js' )
{
	if( $tab == 'stats' )
	{
		// We should activate toolbar menu items for this controller and tab
		$activate_collection_toolbar = true;

		if( isset( $collections_Module ) )
		{ // Display list of blogs:
			if( $current_User->check_perm( 'stats', 'view' ) )
			{
				$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'antispam', 'tab' => $tab, 'tab3' => $tab3 ), T_('All'),
								$admin_url.'?ctrl=antispam&amp;tab='.$tab.'&amp;tab3='.$tab3.'&amp;blog=0' );
			}
			else
			{ // No permission to view aggregated stats:
				$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'antispam', 'tab' => $tab, 'tab3' => $tab3 ) );
			}
		}
		$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Analytics'), 'url' => '?ctrl=stats&amp;blog=$blog$' ) );
		$AdminUI->breadcrumbpath_add( T_('IPs'), $admin_url.'?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab );
		$AdminUI->breadcrumbpath_add( T_('IP Ranges'), $admin_url.'?ctrl=stats&amp;blog=$blog$&amp;tab='.$tab.'&amp;tab3='.$tab3 );
		$AdminUI->set_path( 'stats', 'ips', 'ranges' );
	}
	else
	{
		$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
		$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
		$AdminUI->breadcrumbpath_add( T_('Antispam'), $admin_url.'?ctrl=antispam' );
	}

	if( empty( $tab3 ) )
	{
		$tab3 = 'blacklist';
	}
	switch( $tab3 )
	{
		case 'settings':
			$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=antispam&amp;tab3='.$tab3 );
			break;

		case 'tools':
			$AdminUI->breadcrumbpath_add( T_('Tools'), '?ctrl=antispam&amp;tab3='.$tab3 );
			break;

		case 'blacklist':
			$AdminUI->breadcrumbpath_add( T_('Blacklist'), '?ctrl=antispam' );
			break;

		case 'ipranges':
			if( empty( $action ) )
			{	// View a list of IP ranges
				require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
			}
			elseif( ! $current_User->check_perm( 'spamblacklist', 'edit' ) )
			{	// Check permission to create/edit IP range
				$Messages->add( T_('You have no permission to edit this IP range!'), 'error' );
				$action = '';
			}
			$AdminUI->breadcrumbpath_add( T_('IP Ranges'), '?ctrl=antispam&amp;tab3='.$tab3 );
			$AdminUI->set_page_manual_link( 'ip-ranges' );
			break;

		case 'countries':
			if( $current_User->check_perm( 'options', 'edit' ) )
			{
				require_js( 'jquery/jquery.jeditable.js' );
			}
			break;

		case 'domains':
			load_funcs('sessions/model/_hitlog.funcs.php');
			$AdminUI->breadcrumbpath_add( T_('Referring domains'), '?ctrl=antispam&amp;tab3='.$tab3 );
			if( $current_User->check_perm( 'stats', 'edit' ) )
			{
				require_js( 'jquery/jquery.jeditable.js' );
			}
			// Load jquery UI to highlight cell on change domain type
			require_js( '#jqueryUI#' );
			// Used for edit form
			$tab_from = 'antispam';
			$blog = 0; // Don't restrict domains by blog ID on this controller
			break;
	}

	if( !empty( $tab3 ) )
	{
		$AdminUI->append_path_level( $tab3 );
	}

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();
	
	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();

	// Begin payload block:
	$AdminUI->disp_payload_begin();
}

switch( $tab3 )
{
	case 'settings':
		$AdminUI->disp_view( 'antispam/views/_antispam_settings.form.php' );
		break;

	case 'tools':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		switch( $tool )
		{
			case 'bankruptcy':
				$comment_status = param( 'comment_status', 'string', 'draft' );
				$AdminUI->disp_view( 'antispam/views/_antispam_tools_bankruptcy.view.php' );
				break;

			default:
				$AdminUI->disp_view( 'antispam/views/_antispam_tools.view.php' );
				break;
		}
		break;

	case 'ipranges':
		switch( $action )
		{
			case 'iprange_new':
				if( ! isset( $edited_IPRange ) )
				{ // Define new IPRange object only when it was not defined before, e.g. in action 'iprange_create'
					$edited_IPRange = new IPRange();
				}
				// Set IP Start and End from _GET request
				$ip = param( 'ip', 'string', '' );
				if( ! empty( $ip ) && is_valid_ip_format( $ip ) &&
				    ( $ip = explode( '.', $ip ) ) && count( $ip ) == 4 )
				{
					$edited_IPRange->set( 'IPv4start', ip2int( implode( '.', array( $ip[0], $ip[1], $ip[2], 0 ) ) ) );
					$edited_IPRange->set( 'IPv4end', ip2int( implode( '.', array( $ip[0], $ip[1], $ip[2], 255 ) ) ) );
				}
				$AdminUI->disp_view( 'antispam/views/_antispam_ipranges.form.php' );
				break;

			case 'iprange_edit':
				$AdminUI->disp_view( 'antispam/views/_antispam_ipranges.form.php' );
				break;

			default:	// View list of the IP Ranges
				$AdminUI->disp_view( 'antispam/views/_antispam_ipranges.view.php' );
				break;
		}
		break;

	case 'countries':
		$AdminUI->disp_view( 'regional/views/_country_list.view.php' );
		break;

	case 'domains':
		$AdminUI->disp_view( 'sessions/views/_stats_refdomains.view.php' );
		break;

	case 'blacklist':
	default:
		if( $action == 'ban' && ( ! $Messages->has_errors() || ! empty( $confirm ) ) && !( $delhits || $delcomments ) )
		{	// Nothing to do, ask user:
			$AdminUI->disp_view( 'antispam/views/_antispam_ban.form.php' );
		}
		else
		{	// Display blacklist:
			$AdminUI->disp_view( 'antispam/views/_antispam_list.view.php' );
		}
		break;
}

// End payload block:
if( $display_mode != 'js')
{
	$AdminUI->disp_payload_end();

	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}

?>