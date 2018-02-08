<?php
/**
 * This file implements the Automation controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $DB, $result_message, $servertimenow;

// Get IDs of active automations:
$AutomationCache = & get_AutomationCache();
$AutomationCache->load_where( 'autm_status = "active"' );

if( count( $AutomationCache->cache ) == 0 )
{	// No active automations
	$result_message = 'No active automations found.';
}
else
{	// At least one active automation exists:
	$AutomationStepCache = & get_AutomationStepCache();

	$result_message = sprintf( '%s active automations:', count( $AutomationCache->cache ) )."\n";

	foreach( $AutomationCache->cache as $Automation )
	{
		// Find what steps should be executed immediately:
		$automation_user_states = $Automation->get_user_states();

		// Preload all required steps by single query into cache:
		$AutomationStepCache->load_list( $automation_user_states );

		foreach( $automation_user_states as $automation_user_ID => $automation_step_ID )
		{
			if( $AutomationStep = & $AutomationStepCache->get_by_ID( $automation_step_ID, false, false ) )
			{
				// Execute Step action for given User:
				$process_log = "\n";
				$AutomationStep->execute_action( $automation_user_ID, $process_log );
				$result_message .= $process_log;
			}
		}
	}
}

return 1; /* ok */
?>