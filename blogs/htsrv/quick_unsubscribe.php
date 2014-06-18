<?php
/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'/_main.inc.php';

global $UserSettings;

param( 'type', 'string', true );
param( 'user_ID', 'integer', true );
param( 'key', 'string', true );
param( 'coll_ID', 'integer', 0 );
param( 'post_ID', 'integer', 0 );

$UserCache = & get_UserCache();
$edited_User = $UserCache->get_by_ID( $user_ID, false, false );

// User not found
if( empty( $edited_User ) )
{
	echo T_( 'The user you are trying to unsubscribe does not seem to exist. You may already have deleted your account.' );
	exit;
}

// Security check
if( $key != md5( $user_ID.$edited_User->get( 'unsubscribe_key' ) ) )
{
	echo 'Invalid unsubscribe link!';
	exit;
}

switch( $type )
{
	case 'coll_comment':
	case 'coll_post':
		// unsubscribe from blog
		if( $coll_ID == 0 )
		{
			echo 'Invalid unsubscribe link!';
			exit;
		}

		$subscription_name = ( ( $type == 'coll_comment' ) ? 'sub_comments' : 'sub_items' );
		$DB->query( 'UPDATE T_subscriptions SET '.$subscription_name.' = 0
						WHERE sub_user_ID = '.$user_ID.' AND sub_coll_ID = '.$coll_ID );
		break;

	case 'post':
		// unsubscribe from a specific post
		if( $post_ID == 0 )
		{
			echo 'Invalid unsubscribe link!';
			exit;
		}

		$DB->query( 'DELETE FROM T_items__subscriptions
						WHERE isub_user_ID = '.$user_ID.' AND isub_item_ID = '.$post_ID );
		break;

	case 'creator':
		// unsubscribe from the user own posts
		$UserSettings->set( 'notify_published_comments', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'cmt_moderation_reminder':
		// unsubscribe from comment moderation reminder notifications
		$UserSettings->set( 'send_cmt_moderation_reminder', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'comment_moderator':
	case 'moderator': // Note: This was not chaned to 'comment_moderator' to make sure old emails unsubscribe link are also work
		// unsubscribe from new comment may need moderation notifications
		$UserSettings->set( 'notify_comment_moderation', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'post_moderator':
		// unsubscribe from post moderation notifications
		$UserSettings->set( 'notify_post_moderation', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'unread_msg':
		// unsubscribe from unread messages reminder
		$UserSettings->set( 'notify_unread_messages', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'new_msg':
		// unsubscribe from new messages notification
		$UserSettings->set( 'notify_messages', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'account_activation':
		// unsubscribe from account activation reminder
		$UserSettings->set( 'send_activation_reminder', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'newsletter':
		// unsubscribe from newsletter
		$UserSettings->set( 'newsletter_news', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'user_registration':
		// unsubscribe from new user registration notifications
		$UserSettings->set( 'notify_new_user_registration', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'account_activated':
		// unsubscribe from account activated notifications
		$UserSettings->set( 'notify_activated_account', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'account_closed':
		// unsubscribe from account closed notifications
		$UserSettings->set( 'notify_closed_account', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'account_reported':
		// unsubscribe from account reported notifications
		$UserSettings->set( 'notify_reported_account', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'account_changed':
		// unsubscribe from account changed notifications
		$UserSettings->set( 'notify_changed_account', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'msgform':
		// turn off allow emails through b2evo message forms
		$UserSettings->set( 'enable_email', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;

	case 'cronjob_error':
		// unsubscribe from cron job error notifications
		$UserSettings->set( 'notify_cronjob_error', '0', $edited_User->ID );
		$UserSettings->dbupdate();
		break;
}

echo( T_( 'You have successfully unsubscribed.' ) );
exit;
?>