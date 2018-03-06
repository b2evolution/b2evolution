<?php
/**
 * This file implements the send newsletters of email campaign Cron controller
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Get the ID of the email campaign we are supposed notify:
if( empty( $job_params['ecmp_ID'] ) )
{
	cron_log_append( 'No ecmp_ID parameter received.', 'error' ); // No trans.
	return 3;
}

$ecmp_ID = $job_params['ecmp_ID'];

// Get the EmailCampaign:
$EmailCampaignCache = & get_EmailCampaignCache();
$EmailCampaign = & $EmailCampaignCache->get_by_ID( $ecmp_ID );

// Send newsletters:
$EmailCampaign->send_all_emails( 'cron_job' );

// Create a scheduled job to send newsletters to next chunk of waiting users:
$EmailCampaign->create_cron_job( true );

if( empty( $result_message ) )
{
	cron_log_append( T_('Done').'.' );
}

return 1; /* ok */

?>