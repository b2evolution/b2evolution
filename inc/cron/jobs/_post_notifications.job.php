<?php
/**
 * This file implements the post notifications Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @todo dh> Should this also handle feedback notifications (according to the "outbound_notifications_mode" setting)?
 * fp> No. The feedback notifications should have their own job.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Messages;

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

$job_params = array_merge( array(
		'executed_by_userid'        => NULL,
		'is_new_item'               => true,
		'already_notified_user_IDs' => NULL,
		'force_members'             => false,
		'force_community'           => false,
		'force_pings'               => false,
	), $job_params );

$previous_item_visibility_status = '';

// Make a loop here because the visibility status of the post may evolve between the beginning and the end of sending the notifications (which may last minutes or even hours...):
while( $edited_Item->get( 'status' ) != $previous_item_visibility_status )
{
	// Send outbound pings: (will only do something if visibility is 'public')
	$edited_Item->send_outbound_pings( $job_params['force_pings'] );

	// Send email notifications to users who want to receive them for the collection of this item: (will be different recipients depending on visibility)
	$notified_flags = $edited_Item->send_email_notifications( $job_params['executed_by_userid'], $job_params['is_new_item'], $job_params['already_notified_user_IDs'], $job_params['force_members'], $job_params['force_community'] );

	// Record that we have just notified the members and/or community:
	$edited_Item->set( 'notifications_flags', $notified_flags );

	// Record that processing has been done:
	$edited_Item->set( 'notifications_status', 'finished' );

	// Save the new processing status to DB, but do not update last edited by user, slug or post excerpt:
	$edited_Item->dbupdate( false, false, false );

	// Check if visibility status has been changed:
	$previous_item_visibility_status = $edited_Item->get( 'status' );
	// Destroy current Item to get most recent item from DB:
	unset( $ItemCache->cache[ $edited_Item->ID ] );
	$edited_Item = & $ItemCache->get_by_ID( $item_ID );
}

$result_message = $Messages->get_string( '', '', "\n" );
if( empty( $result_message ) )
{
	$result_message = T_('Done.');
}

return 1; /* ok */
?>