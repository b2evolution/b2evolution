<?php
/**
 * This file implements the post notifications Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @todo dh> Should this also handle feedback notifications (according to the "outbound_notifications_mode" setting)?
 * fp> No. The feedback notifications should have their own job.
 *
 * @version $Id: _post_notifications.job.php 5557 2014-01-03 04:13:43Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $Messages;

if( $Settings->get( 'outbound_notifications_mode' ) != 'cron' )
{ // Autopruning is NOT requested
	$result_message = T_('Post notifications are not set to run as a scheduled task.');
	return 2;
}

// Get the ID of the post we are supposed to post-process:
if( empty( $job_params['item_ID'] ) )
{
	$result_message = 'No item_ID parameter received.'; // No trans.
	return 3;
}

$item_ID = $job_params['item_ID'];


// Notify that we are going to take care of that post's post processing:
$DB->query( 'UPDATE T_items__item
								SET post_notifications_status = "started"
							WHERE post_ID = '.$item_ID.'
							  AND post_notifications_status = "todo"
							  AND post_notifications_ctsk_ID = '.$job_params['ctsk_ID'] );
if( $DB->rows_affected != 1 )
{	// We would not "lock" the requested post
	$result_message = sprintf( T_('Could not lock post #%d. It may already be processed.'), $item_ID );
	return 4;
}

// Get the Item:
$ItemCache = & get_ItemCache();
/**
 * @var Item
 */
$edited_Item = & $ItemCache->get_by_ID( $item_ID );

// send outbound pings:
if( ! $edited_Item->send_outbound_pings() )
{
	$result_message = $Messages->get_string( '', '', "\n" );
	return 5;
}

// Send email notifications now!
$edited_Item->send_email_notifications( false );

// Record that processing has been done:
$edited_Item->set( 'notifications_status', 'finished' );

// Save the new processing status to DB
$edited_Item->dbupdate();

$result_message = $Messages->get_string( '', '', "\n" );
if( empty( $result_message ) )
{
	$result_message = T_('Done.');
}

return 1; /* ok */
?>