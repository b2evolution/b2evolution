<?php
/**
 * This file implements the UI controller for Cron table.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'cron/_cron.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'options', 'cron' );

param( 'action', 'string', 'list' );

// We want to remember these params from page to page:
param( 'ctst_pending', 'integer', 0, true );
param( 'ctst_started', 'integer', 0, true );
param( 'ctst_timeout', 'integer', 0, true );
param( 'ctst_error', 'integer', 0, true );
param( 'ctst_finished', 'integer', 0, true );
param( 'results_crontab_order', 'string', '-D', true );
param( 'results_crontab_page', 'integer', 1, true );


if( param( 'ctsk_ID', 'integer', '', true) )
{// Load cronjob from cache:
	$CronjobCache = & get_CronjobCache();
	if( ( $edited_Cronjob = & $CronjobCache->get_by_ID( $ctsk_ID, false ) ) === false )
	{
		unset( $edited_Cronjob );
		forget_param( 'ctsk_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Scheduled job') ), 'error' );
		$action = 'list';
	}
}

switch( $action )
{
	case 'new':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		load_class( 'cron/model/_cronjob.class.php', 'Cronjob' );
		$edited_Cronjob = new Cronjob();
		break;

	case 'edit':
	case 'copy':
		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		if( ( $action == 'edit' && $edited_Cronjob->get_status() != 'pending' ) ||
		    ( $action == 'copy' && $edited_Cronjob->get_status() != 'error' ) )
		{	// Don't edit cron jobs with not "pending" status
			header_redirect( '?ctrl=crontab', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( $action == 'copy' )
		{	// Reset time to now for copied cron job
			global $localtimenow;
			$edited_Cronjob->start_timestamp = $localtimenow;
		}

		break;

	case 'create':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'crontask' );

		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		if( !empty( $edited_Cronjob ) )
		{ // It is a copy action, we should save the fields "key" & "params"
			$ctsk_key = $edited_Cronjob->get( 'key' );
			$ctsk_params = $edited_Cronjob->get( 'params' );
		}

		// CREATE OBJECT:
		load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
		$edited_Cronjob = new Cronjob();

		if( $edited_Cronjob->load_from_Request() )
		{	// We could load data from form without errors:

			if( !empty( $ctsk_key ) )
			{	// Save controller field from copied object
				$edited_Cronjob->set( 'key', $ctsk_key );
			}

			if( !empty( $ctsk_params ) )
			{	// Save params field from copied object
				$edited_Cronjob->set( 'params', $ctsk_params );
			}

			// Save to DB:
			$edited_Cronjob->dbinsert();

			$Messages->add( T_('New job has been scheduled.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=crontab', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'crontask' );

		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		if( $edited_Cronjob->load_from_Request() )
		{	// We could load data from form without errors:

			if( $edited_Cronjob->dbupdate() )
			{	// The job was updated successfully
				$Messages->add( T_('The scheduled job has been updated.'), 'success' );
			}
			else
			{	// Errors on updating, probably this job has not "pending" status
				$Messages->add( T_('This scheduled job can not be updated.'), 'error' );
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=crontab', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;


	case 'delete':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'crontask' );

		// Make sure we got an ord_ID:
		param( 'ctsk_ID', 'integer', true );

		// Check that we have permission to edit options:
		$current_User->check_perm( 'options', 'edit', true, NULL );

		// TODO: prevent deletion of running tasks.
		$DB->begin();

		$tsk_status =	$DB->get_var(
			'SELECT clog_status
				 FROM T_cron__log
				WHERE clog_ctsk_ID= '.$ctsk_ID,
			0, 0, 'Check that task is not running' );

		if( $tsk_status == 'started' )
		{
			$DB->rollback();

			$Messages->add(  sprintf( T_('Job #%d is currently running. It cannot be deleted.'), $ctsk_ID ), 'error' );
		}
		else
		{
			// Delete task:
			$DB->query( 'DELETE FROM T_cron__task
										WHERE ctsk_ID = '.$ctsk_ID );

			// Delete log (if exists):
			$DB->query( 'DELETE FROM T_cron__log
										WHERE clog_ctsk_ID = '.$ctsk_ID );

			$DB->commit();

			$Messages->add(  sprintf( T_('Scheduled job #%d deleted.'), $ctsk_ID ), 'success' );
		}

		//forget_param( 'ctsk_ID' );
		//$action = 'list';
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( 'action,ctsk_ID', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;


	case 'view':
		$cjob_ID = param( 'cjob_ID', 'integer', true );

		$sql =  'SELECT *
							 FROM T_cron__task LEFT JOIN T_cron__log ON ctsk_ID = clog_ctsk_ID
							WHERE ctsk_ID = '.$cjob_ID;
		$cjob_row = $DB->get_row( $sql, OBJECT, 0, 'Get cron job and log' );
		if( empty( $cjob_row ) )
		{
			$Messages->add( sprintf( T_('Job #%d does not exist any longer.'), $cjob_ID ), 'error' );
			$action = 'list';
		}
		break;

	case 'list':
		// Detect timed out tasks:
		detect_timeout_cron_jobs();

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Scheduler'), $admin_url.'?ctrl=crontab' );

if( in_array( $action, array( 'new', 'create', 'edit', 'update', 'copy', 'list' ) ) )
{ // Initialize date picker for cronjob.form.php
	init_datepicker_js();
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

switch( $action )
{
	case 'new':
	case 'create':
	case 'edit':
	case 'update':
	case 'copy':
		// Display VIEW:
		$AdminUI->disp_view( 'cron/views/_cronjob.form.php' );
		break;

	case 'view':
		// Display VIEW:
		$AdminUI->disp_view( 'cron/views/_cronjob.view.php' ); // uses $cjob_row
		break;

	default:
		// Display VIEW:
		$AdminUI->disp_view( 'cron/views/_cronjob_list.view.php' );
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>