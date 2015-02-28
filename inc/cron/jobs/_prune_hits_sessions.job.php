<?php
/**
 * This file implements the Hit and Session pruning Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $DB;

// Print all unknown errors on screen and save error message
ob_start();
$DB->save_error_state();
$DB->show_errors = true;
$DB->halt_on_error = false;

if( $Settings->get( 'auto_prune_stats_mode' ) != 'cron' )
{ // Autopruning is NOT requested
	$result_message = T_('Auto pruning is not set to run as a scheduled task');
	return 2;
}

load_class( 'sessions/model/_hitlist.class.php', 'Hitlist' );

$result = Hitlist::dbprune(); // will prune once per day, according to Settings

// Restore DB error states
$DB->restore_error_state();
// Get the unknown errors from screen
$unknown_errors = ob_get_clean();

if( ! empty( $unknown_errors ) )
{ // Some errors were created, probably DB errors
	if( ! is_array( $result ) )
	{ // Set result to array format
		$result = array();
	}
	// This result must have an error status
	$result['result'] = 'error';
	// Append the unknown error from screen to already generated message
	$result['message'] = ( isset( $result['message'] ) ? $result['message'] : '' )
		."\n".$unknown_errors;
}

if( empty( $result ) )
{
	return 1; /* ok */
}
elseif( isset( $result['message'] ) )
{ // Get a message from result for report
	$result_message = $result['message'];
	if( isset( $result['result'] ) && $result['result'] == 'ok' )
	{
		return 1; /* ok */
	}
}

return 100;
?>