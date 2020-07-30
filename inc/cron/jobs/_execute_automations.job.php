<?php
/**
 * This file implements the Automation controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Get IDs of active automations:
$AutomationCache = & get_AutomationCache();
$AutomationCache->load_where( 'autm_status = "active"' );

if( count( $AutomationCache->cache ) == 0 )
{	// No active automations
	cron_log_append( 'No active automations found.' );
}
else
{	// At least one active automation exists:
	$AutomationStepCache = & get_AutomationStepCache();

	cron_log_append( sprintf( '%s active automations:', count( $AutomationCache->cache ) ) );

	foreach( $AutomationCache->cache as $Automation )
	{
		// Find what steps should be executed immediately:
		$automation_user_states = $Automation->get_user_states();

		cron_log_append( "\n".'<b>Automation #'.$Automation->ID.'</b>('.$Automation->get( 'name' ).'): '.count( $automation_user_states ).' users are ready to execute a new step (delay between steps has been reached)'.get_cron_log_time() );

		// Preload all required steps by single query into cache:
		$AutomationStepCache->load_list( $automation_user_states );

		foreach( $automation_user_states as $automation_user_ID => $automation_step_ID )
		{
			if( $AutomationStep = & $AutomationStepCache->get_by_ID( $automation_step_ID, false, false ) )
			{
				if( ! check_cron_job_emails_limit() )
				{	// Stop execution for cron job because max number of emails has been already sent:
					break;
				}

				// Execute Step action for given User:
				$step_log_message = $AutomationStep->execute_action( $automation_user_ID );
				// Append a step log message to cron job log and count this as action:
				cron_log_action_end( $step_log_message );
			}
		}

		cron_log_append( 'All users successfully processed in <b>Automation #'.$Automation->ID.'</b>' );
	}

	cron_log_append( "\n".'All automations successfully processed' );
}

return 1; /* ok */
?>