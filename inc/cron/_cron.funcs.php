<?php
/**
 * This file implements cron (scheduled tasks) handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Log a message from cron.
 * @param string Message
 * @param integer Level of importance. The higher the more important.
 *        (if $quiet (number of "-q" params passed to cron_exec.php)
 *         is higher than this, the message gets skipped)
 */
function cron_log( $message, $level = 0 )
{
	global $is_web, $quiet;

	if( $quiet > $level )
	{
		return;
	}

	if( $is_web )
	{
		echo '<p>'.$message.'</p>';
	}
	else
	{
		echo "\n".$message."\n";
	}
}


/**
 * Call a cron job.
 *
 * @param string Key of the job
 * @param string Params for the job:
 *               'ctsk_ID'   - task ID
 *               'ctsk_name' - task name
 * @return string Error message
 */
function call_job( $job_key, $job_params = array() )
{
	global $DB, $inc_path, $Plugins, $admin_url;

	global $result_message, $result_status, $timestop, $time_difference;

	$error_message = '';
	$result_message = NULL;
	$result_status = 'error';

	$job_ctrl = get_cron_jobs_config( 'ctrl', $job_key );

	if( preg_match( '~^plugin_(\d+)_(.*)$~', $job_ctrl, $match ) )
	{ // Cron job provided by a plugin:
		if( ! is_object($Plugins) )
		{
			load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
			$Plugins = new Plugins();
		}

		$Plugin = & $Plugins->get_by_ID( $match[1] );
		if( ! $Plugin )
		{
			$result_message = 'Plugin for controller ['.$job_ctrl.'] could not get instantiated.';
			cron_log( $result_message, 2 );
			return $result_message;
		}

		// CALL THE PLUGIN TO HANDLE THE JOB:
		$tmp_params = array( 'ctrl' => $match[2], 'params' => $job_params );
		$sub_r = $Plugins->call_method( $Plugin->ID, 'ExecCronJob', $tmp_params );

		$error_code = (int)$sub_r['code'];
		$result_message = $sub_r['message'];
	}
	else
	{
		$controller = $inc_path.$job_ctrl;
		if( ! is_file( $controller ) )
		{
			$result_message = 'Controller ['.$job_ctrl.'] does not exist.';
			cron_log( $result_message, 2 );
			return $result_message;
		}

		// INCLUDE THE JOB FILE AND RUN IT:
		$error_code = require $controller;
	}

	if( is_array( $result_message ) )
	{	// If result is array (we should store it as serialized data later)
		// array keys: 'message' - Result message
		//             'table_cols' - Columns names of the table to display on the Execution details of the cron job log
		//             'table_data' - Array
		$result_message_text = $result_message['message'];
	}
	else
	{	// Result is text string
		$result_message_text = $result_message;
	}

	if( $error_code != 1 )
	{	// We got an error
		$result_status = 'error';
		$result_message_text = '[Error code: '.$error_code.' ] '.$result_message_text;
		if( is_array( $result_message ) )
		{ // If result is array
			$result_message['message'] = $result_message_text;
		}
		$cron_log_level = 2;

		$error_message = $result_message_text;
	}
	else
	{
		$result_status = 'finished';
		$cron_log_level = 1;
	}

	$timestop = time() + $time_difference;
	cron_log( 'Task finished at '.date( 'H:i:s', $timestop ).' with status: '.$result_status
		."\nMessage: $result_message_text", $cron_log_level );

	return $error_message;
}


/**
 * Get status color of sheduled job by status value
 *
 * @param string Status value
 * @return string Color value
 */
function cron_status_color( $status )
{
	$colors = array(
			'pending'  => '808080',
			'started'  => '4d77cb',
			'warning'  => 'dbdb57',
			'timeout'  => 'e09952',
			'error'    => 'cb4d4d',
			'finished' => '34b27d',
		);

	return isset( $colors[ $status ] ) ? '#'.$colors[ $status ] : 'none';
}


/**
 * Get the manual page link for the requested cron job, given by the key
 *
 * @param string job key
 * @return string Link to manual page of cron job task
 */
function cron_job_manual_link( $job_key )
{
	$help_config = get_cron_jobs_config( 'help', $job_key );

	if( empty( $help_config ) )
	{ // There was no 'help' topic defined for this job
		return '';
	}

	if( $help_config == '#' )
	{ // The 'help' topic is set to '#', use the default 'task-' + job_key
		return get_manual_link( 'task-'.$job_key );
	}

	if( is_url( $help_config ) )
	{ // This cron job help topic is an url
		return action_icon( T_('Open relevant page in online manual'), 'manual', $help_config, T_('Manual'), 5, 1, array( 'target' => '_blank', 'style' => 'vertical-align:top' ) );
	}

	return get_manual_link( $help_config );
}


/**
 * Get name of cron job
 *
 * @param string Job key
 * @param string Job name
 * @param string|array Job params
 * @return string Default value of job name of Name from DB
 */
function cron_job_name( $job_key, $job_name = '', $job_params = '' )
{
	if( empty( $job_name ) )
	{ // Get default name by key
		$job_name = get_cron_jobs_config( 'name', $job_key );
	}

	$job_params = is_string( $job_params ) ? unserialize( $job_params ) : $job_params;
	if( ! empty( $job_params ) )
	{ // Prepare job name with the specified params
		switch( $job_key )
		{
			case 'send-post-notifications':
				// Add item title to job name
				if( ! empty( $job_params['item_ID'] ) )
				{
					$ItemCache = & get_ItemCache();
					if( $Item = $ItemCache->get_by_ID( $job_params['item_ID'], false, false ) )
					{
						$job_name = sprintf( $job_name, $Item->get( 'title' ) );
					}
				}
				break;

			case 'send-comment-notifications':
				// Add item title of the comment to job name
				if( ! empty( $job_params['comment_ID'] ) )
				{
					$CommentCache = & get_CommentCache();
					if( $Comment = & $CommentCache->get_by_ID( $job_params['comment_ID'], false, false ) )
					{
						if( $Item = $Comment->get_Item() )
						{
							$job_name = sprintf( $job_name, $Item->get( 'title' ) );
						}
					}
				}
				break;

			case 'send-email-campaign':
				// Add email campaign title and chunk size to job name:
				global $Settings;
				$email_campaign_title = '';
				if( ! empty( $job_params['ecmp_ID'] ) )
				{
					$EmailCampaignCache = & get_EmailCampaignCache();
					if( $EmailCampaign = $EmailCampaignCache->get_by_ID( $job_params['ecmp_ID'], false, false ) )
					{
						$email_campaign_title = $EmailCampaign->get( 'email_title' );
					}
				}
				$job_name = sprintf( $job_name, $Settings->get( 'email_campaign_chunk_size' ), $email_campaign_title );
				break;
		}
	}

	return $job_name;
}


/**
 * Detect timed out cron jobs and Send notifications
 *
 * @param array Task with error
 *             'name'
 *             'message'
 */
function detect_timeout_cron_jobs( $error_task = NULL )
{
	global $DB, $time_difference, $cron_timeout_delay, $admin_url;

	$SQL = new SQL( 'Find cron timeouts' );
	$SQL->SELECT( 'ctsk_ID, ctsk_name, ctsk_key' );
	$SQL->FROM( 'T_cron__log' );
	$SQL->FROM_add( 'INNER JOIN T_cron__task ON ctsk_ID = clog_ctsk_ID' );
	$SQL->WHERE( 'clog_status = "started"' );
	$SQL->WHERE_and( 'clog_realstart_datetime < '.$DB->quote( date2mysql( time() + $time_difference - $cron_timeout_delay ) ) );
	$SQL->GROUP_BY( 'ctsk_ID' );
	$timeout_tasks = $DB->get_results( $SQL->get(), OBJECT, $SQL->title );

	$tasks = array();

	if( count( $timeout_tasks ) > 0 )
	{
		$cron_jobs_names = get_cron_jobs_config( 'name' );
		foreach( $timeout_tasks as $timeout_task )
		{
			if( ! empty( $timeout_task->ctsk_name ) )
			{ // Task name is defined in DB
				$task_name = $timeout_task->ctsk_name;
			}
			else
			{ // Try to get default task name by key:
				$task_name = ( isset( $cron_jobs_names[ $timeout_task->ctsk_key ] ) ? $cron_jobs_names[ $timeout_task->ctsk_key ] : $timeout_task->ctsk_key );
			}
			$tasks[ $timeout_task->ctsk_ID ] = array(
					'name'    => $task_name,
					'message' => NT_('Cron job has timed out.'),	// Here is not a good place to translate! We don't know the language of the recipient here.
				);
		}

		// Update timed out cron jobs:
		$DB->query( 'UPDATE T_cron__log
			  SET clog_status = "timeout"
			WHERE clog_ctsk_ID IN ( '.$DB->quote( array_keys( $tasks ) ).' )', 'Mark timeouts in cron jobs.' );
	}

	if( !is_null( $error_task ) )
	{ // Send notification with error task
		$tasks[ $error_task['ID'] ] = $error_task;
	}

	if( count( $tasks ) > 0 )
	{ // Send notification email about timed out and error cron jobs to users with edit options permission
		$email_template_params = array(
				'tasks' => $tasks,
			);
		send_admin_notification( NT_('Scheduled task error'), 'scheduled_task_error_report', $email_template_params );
	}
}


/**
 * Get config array with all available cron jobs
 *
 * @param string What param we should get from config: 'name', 'ctrl', 'params'
 * @param string Get one cron job by specified key
 * @return array|string Array of all cron jobs | Value of specified cron job
 */
function get_cron_jobs_config( $get_param = '', $get_by_key = '' )
{
	global $cron_jobs_config;

	if( isset( $cron_jobs_config ) )
	{ // Get config from global vaiable to don't initialize this var twice
		if( !empty( $get_by_key ) )
		{ // A specific job param(s) was requested
			if( empty( $get_param ) )
			{ // Return all params
				return $cron_jobs_config[ $get_by_key ];
			}
			// Return the requested job param if it is set or NULL otherwise
			return ( isset( $cron_jobs_config[ $get_by_key ][$get_param] ) ) ? $cron_jobs_config[ $get_by_key ][$get_param] : NULL;
		}

		if( empty( $get_param ) )
		{ // No specific param or job key was requested, return the whole config list
			return $cron_jobs_config;
		}

		// Get a specific config param for all jobs
		$restricted_config = array();
		foreach( $cron_jobs_config as $job_key => $job_config )
		{
			$restricted_config[ $job_key ] = isset( $job_config[ $get_param ] ) ? $job_config[ $get_param ] : NULL;
		}
		return $restricted_config;
	}

	// This array will contain the modules and the plugins cron jobs data
	$cron_jobs_config = array();

	// Get additional jobs from different modules and Plugins:
	global $modules, $Plugins;

	foreach( $modules as $module )
	{
		$Module = & $GLOBALS[$module.'_Module'];
		// Note: cron jobs with the same key will ovverride previously defined cron jobs data
		$cron_jobs_config = array_merge( $cron_jobs_config, $Module->get_cron_jobs() );
	}

	if( !empty( $Plugins ) )
	{
		foreach( $Plugins->trigger_collect( 'GetCronJobs' ) as $plug_ID => $jobs )
		{
			if( ! is_array($jobs) )
			{
				$Debuglog->add( sprintf('GetCronJobs() for plugin #%d did not return array. Ignoring its jobs.', $plug_ID), array('plugins', 'error') );
				continue;
			}
			foreach( $jobs as $job )
			{
				// Validate params from plugin:
				if( ! isset($job['params']) )
				{
					$job['params'] = NULL;
				}
				if( ! is_array($job) || ! isset($job['ctrl'], $job['name']) )
				{
					$Debuglog->add( sprintf('GetCronJobs() for plugin #%d did return invalid job. Ignoring.', $plug_ID), array('plugins', 'error') );
					continue;
				}
				if( isset($job['params']) && ! is_array($job['params']) )
				{
					$Debuglog->add( sprintf('GetCronJobs() for plugin #%d did return invalid job params (not an array). Ignoring.', $plug_ID), array('plugins', 'error') );
					continue;
				}
				$job['ctrl'] = 'plugin_'.$plug_ID.'_'.$job['ctrl'];

				add_cron_jobs_config( str_replace( '_', '-', $job['ctrl'] ), $job, true );
			}
		}
	}

	return get_cron_jobs_config( $get_param, $get_by_key );
}


/**
 * Add new cron job to config
 *
 * @param string Cron job key
 * @param array Cron job data, array with keys: 'name', 'ctrl', 'params'
 * @param boolean TRUE to rewrite previous key
 */
function add_cron_jobs_config( $key, $data, $force = false )
{
	global $cron_jobs_config;

	if( ! $force && isset( $cron_jobs_config[ $key ] ))
	{ // Add new cron job to config array
		return;
	}

	$cron_jobs_config[ $key ] = $data;
}


/**
 * Build cron job names SELECT query from crong_jobs_config array
 *
 * @param string What fields to get, separated by comma
 * @return string SQL query
 */
function cron_job_sql_query( $fields = 'key,name' )
{
	global $DB;

	$cron_jobs_config = get_cron_jobs_config();

	// We need to set the collation explicitly if the current db connection charset is utf-8 in order to avoid "Illegal mix of collation" issue
	// Basically this is a hack which should be reviewed when the charset issues are fixed generally.
	// TODO: asimo>Review this temporary solution after the charset issues were fixed.
	$default_collation = ( $DB->connection_charset == 'utf8' ) ? ' COLLATE utf8_general_ci' : '';

	$name_query = '';
	if( !empty( $cron_jobs_config ) )
	{
		$fields = explode( ',', $fields );

		$name_query = 'SELECT task_'.implode( ', task_', $fields ).' FROM ('."\n";
		$first_task = true;
		foreach( $cron_jobs_config as $ctsk_key => $ctsk_data )
		{
			$field_values = '';
			$field_separator = '';
			foreach( $fields as $field )
			{
				$field_values .= $field_separator.$DB->quote( $field == 'key' ? $ctsk_key : $ctsk_data[ $field ] ).$default_collation.' AS task_'.$field;
				$field_separator = ', ';
			}

			if( $first_task )
			{
				$name_query .= "\t".'SELECT '.$field_values."\n";
				$first_task = false;
			}
			else
			{
				$name_query .= "\t".'UNION SELECT '.$field_values."\n";
			}
		}
		$name_query .= ') AS inner_temp';
	}

	return $name_query;
}
?>