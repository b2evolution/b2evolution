<?php
/**
 * Example to use CLI:
 * >c:\php4\php cron_exec.php
 * >c:\php4\php-cli cron_exec.php
 */

require_once dirname(__FILE__).'/../conf/_config.php';

// This MIGHT be overkill. TODO: check...
require_once $inc_path .'_main.inc.php';

/**
 * Cron support functions
 */
require_once $model_path.'cron/_cron.funcs.php';

if( ! $is_cli )
{ // This is a web request:
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<title>Cron exec</title>
		<link rel="stylesheet" type="text/css" href="<?php echo $rsc_url ?>css/basic.css">
	</head>
	<body>
		<h1>Cron exec</h1>
		<p>This script will execute the next task in the cron queue.
		You should normally call it with the CLI (command line interface) version of PHP
		and automate that call through a cron.</p>
	<?php
}

/*
 * The following will feel a little bloated...
 * BUT it is actually a pretty nifty design to prevent double execution of tasks without any transaction!
 * The trick is to rely on the primary key of the cron__log table.
 */

// Get next task to run in queue which has not started execution yet:
$sql = 'SELECT *
					FROM T_cron__task LEFT JOIN T_cron__log ON ctsk_ID = clog_ctsk_ID
				 WHERE clog_ctsk_ID IS NULL
					 AND ctsk_start_datetime <= '.$DB->quote( date2mysql($localtimenow) ).'
				 ORDER BY ctsk_start_datetime ASC, ctsk_ID ASC
				 LIMIT 1';
$task = $DB->get_row( $sql, OBJECT, 0, 'Get next task to run in queue which has not started execution yet' );

if( empty( $task ) )
{
	if( ! $is_cli )
	{ // Do not be too chatty with CLI, as it would trigger cron to send this as mail always
		cron_log( 'There is no task to execute yet.' );
	}
}
else
{
	$ctsk_ID = $task->ctsk_ID;
	$ctsk_name = $task->ctsk_name;

	cron_log( 'Requesting lock on task #'.$ctsk_ID.' ['.$ctsk_name.']' );

	$DB->halt_on_error = false;
	$DB->show_errors = false;
	$sql = 'INSERT INTO T_cron__log( clog_ctsk_ID, clog_realstart_datetime, clog_status)
					VALUES( '.$ctsk_ID.', '.$DB->quote( date2mysql($localtimenow) ).', "started" )';
	// Duplicate query for tests!
	// $DB->query( $sql, 'Request lock' );
	if( $DB->query( $sql, 'Request lock' ) != 1 )
	{ // This has no affected exactly ONE row: error! (probably locked -- duplicate ket -- by a concurrent process)
		$DB->show_errors = true;
		$DB->halt_on_error = true;
		cron_log( 'Could not lock. Task is probably handled by another process.' );
	}
	else
	{
		if( !empty( $task->ctsk_repeat_after ) )
		{	// This task wants to be repeated:
			// Note: we use the current time for 2 reasons: 1) prevent scheduling something in the past AND 2) introduce variety so that everyone doesn't run his repeated tasks at the same exact time, especially pings, pollings...
			if( $task->ctsk_controller == 'cron/_antispam_poll.job.php' )
			{	// THIS IS A HACK. Guess why we need that!? :P  Please do not override or you'll kill our server :(
				$new_start_datetime = $localtimenow + rand( 43200, 86400 ); // 12 to 24 hours
			}
			else
			{	// Normal
				$new_start_datetime = $localtimenow + $task->ctsk_repeat_after;
			}
			$sql = 'INSERT INTO T_cron__task( ctsk_start_datetime, ctsk_repeat_after, ctsk_name, ctsk_controller, ctsk_params )
							VALUES( '.$DB->quote(date2mysql($new_start_datetime)).', '.$DB->quote($task->ctsk_repeat_after).', '
												.$DB->quote($ctsk_name).', '.$DB->quote($task->ctsk_controller).', '.$DB->quote($task->ctsk_params).' )';
			$DB->query( $sql, 'Schedule repeated task.' );
		}

		$DB->show_errors = true;
		$DB->halt_on_error = true;
		cron_log( 'Stating task #'.$ctsk_ID.' ['.$ctsk_name.'] at '.date( 'H:i:s', $localtimenow ).'.' );

		if( empty($task->ctsk_params) )
		{
			$cron_params = array();
		}
		else
		{
			$cron_params = unserialize( $task->ctsk_params );
		}

		// EXECUTE
		call_job( $task->ctsk_controller, $cron_params );

		// Record task as finished:
		if( empty($timestop) )
		{
			$timestop = time() + $time_difference;
		}
		$sql = ' UPDATE T_cron__log
								SET clog_status = '.$DB->quote($result_status).',
										clog_realstop_datetime = '.$DB->quote( date2mysql($timestop) ).',
										clog_messages = '.$DB->quote($result_message) /* May be NULL */.'
							WHERE clog_ctsk_ID = '.$ctsk_ID;
		$DB->query( $sql, 'Record task as finished.' );
	}
}


//echo 'detecting timeouts...';
// Detect timed out tasks:
$sql = ' UPDATE T_cron__log
						SET clog_status = "timeout"
					WHERE clog_status = "started"
								AND clog_realstart_datetime < '.$DB->quote( date2mysql( time() + $time_difference - $cron_timeout_delay ) );
$DB->query( $sql, 'Detect cron timeouts.' );



if( ! $is_cli )
{ // This is a web request:
	echo '<p><a href="cron_exec.php">Refresh Now!</a></p>';
	echo '<p>This page should refresh automatically in 15 seconds...</p>';
	echo '<!-- This is invalid HTML but it is SOOOOOO helpful! (Delay will be triggered when we reach that point -->';
	echo '<meta http-equiv="Refresh" content="15" />';

	debug_info();
	?>
	</body>
	</html>
	<?php
}
?>
