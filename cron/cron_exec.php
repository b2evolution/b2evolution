<?php
/**
 * Execute cron jobs.
 *
 * Example to use CLI:
 * >c:\php4\php cron_exec.php
 * >c:\php4\php-cli cron_exec.php
 */


/**
 * Include config
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * Include main initialization
 * Note: This will initialize only the required objects ( e.g. $Session or $Hit objects will NOT be initialized in cli mode ).
 */
require_once $inc_path .'_main.inc.php';

if( $Settings->get( 'system_lock' ) )
{ // System is locked down for maintenance, Stop cron execution
	echo 'The site is locked for maintenance. All scheduled jobs are postponed. No job was executed.';
	exit(0);
}

/**
 * Cron support functions
 */
load_funcs( 'cron/_cron.funcs.php' );

/**
 * @global integer Quietness.
 *         1 suppresses trivial/informative messages,
 *         2 suppresses success messages,
 *         3 suppresses errors.
 */
$quiet = 0;
if( $is_cli )
{ // called through Command Line Interface, handle args:

	if( empty( $secure_htsrv_url ) )
	{ // Initialize this url when it is empty:
		global $ReqHost;
		if( empty( $ReqHost ) )
		{ // In CLI mode the HOST is unknown, so use $baseurl from the config:
			global $baseurl;
			$ReqHost = $baseurl;
		}
		$secure_htsrv_url = get_secure_htsrv_url();
	}

	// Load required functions ( we need to load here, because in CLI mode it is not loaded )
	load_funcs( '_core/_url.funcs.php' );

	if( isset( $_SERVER['argc'], $_SERVER['argv'] ) )
	{
		$argc = $_SERVER['argc'];
		$argv = $_SERVER['argv'];
	}

	if( isset($argv) )
	{ // may not be set for CGI
		foreach( array_slice($argv, 1) as $v )
		{
			switch( $v )
			{
				case '-h':
				case '--help':
					// display help:
					echo $argv[0]." - Execute cron jobs for b2evolution\n";
					echo "\n";
					echo "Options:\n";
					echo " -q --quiet: Be quiet (do not output a message, if there are no jobs).\n";
					echo "             This is especially useful, when running as a cron job.\n";
					echo "             You can use this up to three times to increase quietness.\n";
					echo "             Successful runs can be made silent with \"-q -q\".\n";
					exit(0);
					break;

				case '-q':
				case '--quiet':
					// increase quietness:
					$quiet++;
					break;

				default:
					echo 'Invalid option "'.$v.'". Use "-h" or "--help" for a list of options.'."\n";
					die(1);
			}
		}
	}

	global $default_locale, $current_charset;

	// We don't load _init_session.inc.php in CLI mode, so we set locale and DB connection charset here
	locale_overwritefromDB();
	locale_activate( $default_locale );

	// Init charset handling - this will also set the encoding for MySQL connection
	init_charsets( $current_charset );
}
else
{ // This is a web request: (for testing purposes only. Not designed for production)

	// Make sure the response is never cached:
	header_nocache();
	header_content_type();

	// Add CSS:
	require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
	require_css( 'basic.css', 'rsc_url' ); // Basic styles
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<title>Cron exec</title>
		<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
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
$error_task = NULL;

if( empty( $task ) )
{
	cron_log( 'There is no task to execute yet.', 0 );
}
else
{
	$ctsk_ID = $task->ctsk_ID;
	$ctsk_name = cron_job_name( $task->ctsk_key, $task->ctsk_name, $task->ctsk_params );

	cron_log( 'Requesting lock on task #'.$ctsk_ID.' ['.$ctsk_name.']', 0 );

	$DB->halt_on_error = false;
	$DB->show_errors = false;
	$sql = 'INSERT INTO T_cron__log( clog_ctsk_ID, clog_realstart_datetime, clog_status)
					VALUES( '.$ctsk_ID.', '.$DB->quote( date2mysql($localtimenow) ).', "started" )';
	// Duplicate query for tests!
	// $DB->query( $sql, 'Request lock' );
	if( $DB->query( $sql, 'Request lock' ) != 1 )
	{ // This has no affected exactly ONE row: error! (probably locked -- duplicate key -- by a concurrent process)
		$DB->show_errors = true;
		$DB->halt_on_error = true;
		cron_log( 'Could not lock. Task is probably handled by another process.', 2 );
	}
	else
	{
		if( !empty( $task->ctsk_repeat_after ) )
		{	// This task wants to be repeated:
			// Note: we use the current time for 2 reasons: 1) prevent scheduling something in the past AND 2) introduce variety so that everyone doesn't run his repeated tasks at the same exact time, especially pings, pollings...
			if( $task->ctsk_key == 'poll-antispam-blacklist' )
			{	// THIS IS A HACK. Guess why we need that!? :P  Please do not override or you'll kill our server :(
				$new_start_datetime = $localtimenow + rand( 43200, 86400 ); // 12 to 24 hours
			}
			else
			{	// Normal
				$new_start_datetime = $localtimenow + $task->ctsk_repeat_after;
			}
			$ctsk_name_insert = empty( $task->ctsk_name ) ? 'NULL' : $DB->quote( $task->ctsk_name );
			$sql = 'INSERT INTO T_cron__task( ctsk_start_datetime, ctsk_repeat_after, ctsk_name, ctsk_key, ctsk_params )
							VALUES( '.$DB->quote(date2mysql($new_start_datetime)).', '.$DB->quote($task->ctsk_repeat_after).', '
												.$ctsk_name_insert.', '.$DB->quote($task->ctsk_key).', '.$DB->quote($task->ctsk_params).' )';
			$DB->query( $sql, 'Schedule repeated task.' );
		}

		$DB->show_errors = true;
		$DB->halt_on_error = true;
		cron_log( 'Starting task #'.$ctsk_ID.' ['.$ctsk_name.'] at '.date( 'H:i:s', $localtimenow ).'.', 1 );

		if( empty($task->ctsk_params) )
		{
			$cron_params = array();
		}
		else
		{
			$cron_params = unserialize( $task->ctsk_params );
		}

		// The job may need to know its ID and name (to set logical locks for example):
		$cron_params['ctsk_ID'] = $ctsk_ID;

		// EXECUTE
		$error_message = call_job( $task->ctsk_key, $cron_params );
		if( !empty( $error_message ) )
		{
			$error_task = array(
					'ID'      => $ctsk_ID,
					'name'    => $ctsk_name,
					'message' => $error_message,
				);
		}

		// Record task as finished:
		if( empty($timestop) )
		{
			$timestop = time() + $time_difference;
		}

		if( is_array( $result_message ) )
		{	// If result is array we should store it as serialized data
			$result_message = serialize( $result_message );
		}

		$sql = ' UPDATE T_cron__log
								SET clog_status = '.$DB->quote($result_status).',
										clog_realstop_datetime = '.$DB->quote( date2mysql( $timestop ) ).',
										clog_messages = '.$DB->quote( $result_message ) /* May be NULL */.'
							WHERE clog_ctsk_ID = '.$ctsk_ID;
		$DB->query( $sql, 'Record task as finished.' );
	}
}


//echo 'detecting timeouts...';
// Detect timed out tasks:
detect_timeout_cron_jobs( $error_task );



if( ! $is_cli )
{ // This is a web request:
	echo '<p><a href="cron_exec.php">Refresh Now!</a></p>';
	echo '<p>This page should refresh automatically in 15 seconds...</p>';
	echo '<!-- This is invalid HTML but it is SOOOOOO helpful! (Delay will be triggered when we reach that point -->';
	echo '<meta http-equiv="Refresh" content="15" />';
	?>
	</body>
	</html>
	<?php
}

?>