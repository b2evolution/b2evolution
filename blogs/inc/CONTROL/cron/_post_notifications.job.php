<?php
/**
 * This file implements the post notifications Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings;

global $model_path;


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
$DB->query( 'UPDATE T_posts
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
$ItemCache = & get_Cache( 'ItemCache' );
$edited_Item = & $ItemCache->get_by_ID( $item_ID );

// send outbound pings:
$edited_Item->send_outbound_pings( false );

// Send email notifications now!
$edited_Item->send_email_notifications( false );

// Record that processing has been done:
$edited_Item->set( 'notifications_status', 'finished' );

// Save the new processing status to DB
$edited_Item->dbupdate();

$result_message = T_('Done.');

return 1; /* ok */

/*
 * $Log$
 * Revision 1.2  2006/11/26 22:25:12  blueyed
 * MFB: Normalized messages (dot at end of full sentences)
 *
 * Revision 1.1  2006/08/24 00:43:28  fplanque
 * scheduled pings part 2
 *
 * Revision 1.1  2006/07/06 19:59:08  fplanque
 * better logs, better stats, better pruning
 *
 */
?>