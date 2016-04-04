<?php
/**
 * This file implements t
 he post notifications Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @todo dh> Should this also handle feedback notifications (according to the "outbound_notifications_mode" setting)?
 * fp> No. The feedback notifications should have their own job.
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

// TRY TO OBTAIN A UNIQUE LOCK for processing the task.
// This is to avoid that 2 cron jobs process the same post at the same time:
// Notify that this is the job that is going to take care of sending notifications for this post:
$DB->query( 'UPDATE T_items__item
								SET post_notifications_status = "started"
							WHERE post_ID = '.$item_ID.'
							  AND post_notifications_status = "todo"
							  AND post_notifications_ctsk_ID = '.$job_params['ctsk_ID'] );
if( $DB->rows_affected != 1 )
{	// We could not "lock" the requested post
	$result_message = sprintf( T_('Could not lock post #%d. It is probably being processed or has already been processed by another scheduled task.'), $item_ID );
	return 4;
}

// Get the Item:
$ItemCache = & get_ItemCache();
/**
 * @var Item
 */
$edited_Item = & $ItemCache->get_by_ID( $item_ID );

$previous_Item_visibility_status = '';
$current_Item_visibility_status = $edited_Item->get( 'status' );

// Make a loop here because the visibility status of the post may evolve between the beginning and the end of sending the notifications (which may last minutes or even hours...):
while( $current_Item_visibility_status != $previous_Item_visibility_status )
{
	// Send outbound pings: (will only do something if visibility is 'public')
	$edited_Item->send_outbound_pings();

	// Send email notifications to users who want to receive them for the collection of this item: (will be different recipients depending on visibility)
	$is_new_item = empty( $job_params['is_new_item'] ) ? true : $job_params['is_new_item'];
	$already_notified_user_IDs = empty( $job_params['already_notified_user_IDs'] ) ? NULL : $job_params['already_notified_user_IDs'];
	$edited_Item->send_email_notifications( $is_new_item, $already_notified_user_IDs );

	// Check if visibility status has changed:
	$previous_Item_visibility_status = $current_Item_visibility_status;
	// Destroy current item
	unset( $edited_Item );
	// TODO: GET MOST RECENT ITEM FROM DB
	$edited_Item = ...;
	$current_Item_visibility_status = $edited_Item->get( 'status' );
}

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