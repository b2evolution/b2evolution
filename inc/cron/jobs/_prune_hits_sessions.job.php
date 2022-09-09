<?php
/**
 * This file implements the Hit and Session pruning Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $DB;

if( $Settings->get( 'auto_prune_stats_mode' ) != 'cron' )
{ // Autopruning is NOT requested
	cron_log_append( T_('Auto pruning is not set to run as a scheduled task') );
	return 2;
}

load_class( 'sessions/model/_hitlist.class.php', 'Hitlist' );

// Print all unknown errors on screen and save error message
ob_start();
$DB->save_error_state();
$DB->show_errors = true;
$DB->halt_on_error = false;

$result = Hitlist::dbprune( 'cron_job', true ); // will prune once per day, according to Settings

// Restore DB error states
$DB->restore_error_state();
// Get the unknown errors from screen
$unknown_errors = trim( ob_get_clean() );

// Make sure we have a result message:
$result_message = array( 'message' => 'Result Message:'."\n\n".(isset( $result['message'] ) ? $result['message'] : '' ) );

$result_message['message'] .= "\nResult so far:".(isset( $result['result'] ) ? "'".$result['result']."'" : '[Not set]' );

if( ! empty( $unknown_errors ) )
{ // Some errors were created, probably DB errors
	// Append the unknown error from screen to already generated message
	$result_message['message'] .= "\n\n".'Unknown errors: "'.$unknown_errors.'"';
	return 100; // error
}
else
{
	$result_message['message'] .= "\n\nNo unknown errors";
}

if( isset( $result['result'] ) && $result['result'] == 'ok' )
{
	return 1; // ok
}

return 100; // error
?>