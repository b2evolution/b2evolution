<?php
/**
 * This file implements the comment notifications Cron controller
 *
 * @author efy-asimo: Attila Simo
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $Messages, $UserSettings;

// Get the ID of the comment we are supposed notify:
if( empty( $job_params['comment_ID'] ) )
{
	$result_message = 'No comment_ID parameter received.'; // No trans.
	return 3;
}

if( empty( $UserSettings ) )
{ // initialize UserSettings, because in CLI mode is not initialized yet
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

$comment_ID = $job_params['comment_ID'];

// TRY TO OBTAIN A UNIQUE LOCK for processing the task.
// This is to avoid that 2 cron jobs process the same comment at the same time:
// Notify that this is the job that is going to take care of sending notifications for this comment:
$DB->query( 'UPDATE T_comments
								SET comment_notif_status = "started"
							WHERE comment_ID = '.$comment_ID.'
							  AND comment_notif_status = "todo"
							  AND comment_notif_ctsk_ID = '.$job_params['ctsk_ID'] );

if( $DB->rows_affected != 1 )
{	// We would not "lock" the requested post
	$result_message = sprintf( T_('Could not lock comment #%d. It is probably being processed or has already been processed by another scheduled task.'), $comment_ID );
	return 4;
}

// Get the Comment:
$CommentCache = & get_CommentCache();
/**
 * @var Comment
 */
$edited_Comment = & $CommentCache->get_by_ID( $comment_ID );

$job_params = array_merge( array(
		'executed_by_userid'        => NULL,
		'is_new_comment'            => true,
		'already_notified_user_IDs' => NULL,
		'force_members'             => false,
		'force_community'           => false,
	), $job_params );

$previous_comment_visibility_status = '';

// Make a loop here because the visibility status of the post may evolve between the beginning and the end of sending the notifications (which may last minutes or even hours...):
while( $edited_Comment->get( 'status' ) != $previous_comment_visibility_status )
{
	// Send email notifications to users who want to receive them for the collection of this comment: (will be different recipients depending on visibility)
	$notified_flags = $edited_Comment->send_email_notifications( $job_params['executed_by_userid'], $job_params['is_new_comment'], $job_params['already_notified_user_IDs'], $job_params['force_members'], $job_params['force_community'] );

	// Record that we have just notified the members and/or community:
	$edited_Comment->set( 'notif_flags', $notified_flags );

	// Record that processing has been done:
	$edited_Comment->set( 'notif_status', 'finished' );

	// Save the new processing status to DB:
	$edited_Comment->dbupdate();

	// Check if visibility status has been changed:
	$previous_comment_visibility_status = $edited_Comment->get( 'status' );
	// Destroy current Comment to get most recent comment from DB:
	unset( $CommentCache->cache[ $edited_Comment->ID ] );
	$edited_Comment = & $CommentCache->get_by_ID( $comment_ID );
}

$result_message = $Messages->get_string( '', '', "\n" );
if( empty( $result_message ) )
{
	$result_message = T_('Done').'.';
}

return 1; /* ok */

?>