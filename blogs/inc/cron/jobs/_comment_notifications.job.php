<?php
/**
 * This file implements the comment notifications Cron controller
 *
 * @author efy-asimo: Attila Simo
 *
 * @version $Id$
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

$except_moderators = false;
if( ! empty( $job_params['except_moderators'] ) )
{
	$except_moderators = $job_params['except_moderators'];
}

$comment_ID = $job_params['comment_ID'];

// Notify that we are going to take care of that comment's notifications:
$DB->query( 'UPDATE T_comments
								SET comment_notif_status = "started"
							WHERE comment_ID = '.$comment_ID.'
							  AND comment_notif_status = "todo"
							  AND comment_notif_ctsk_ID = '.$job_params['ctsk_ID'] );

if( $DB->rows_affected != 1 )
{	// We would not "lock" the requested post
	$result_message = sprintf( T_('Could not lock comment #%d. It may already be processed.'), $comment_ID );
	return 4;
}

// Get the Comment:
$CommentCache = & get_CommentCache();
/**
 * @var Comment
 */
$edited_Comment = & $CommentCache->get_by_ID( $comment_ID );

// Load required functions ( we need to load here, because in CLI mode it is not loaded )
load_funcs( '_core/_url.funcs.php' );

// Send email notifications now!
$edited_Comment->send_email_notifications( false, $except_moderators );

// Record that processing has been done:
$edited_Comment->set( 'notif_status', 'finished' );

// Save the new processing status to DB
$edited_Comment->dbupdate();

$edited_Comment = $Messages->get_string( '', '', "\n" );
if( empty( $result_message ) )
{
	$result_message = T_('Done.');
}

return 1; /* ok */

/*
 * $Log$
 * Revision 1.3  2013/11/06 08:04:07  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>