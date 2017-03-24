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

?>
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title><?php echo T_( 'Quick unsubscribe' ) ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
	<?php

	// Default message:
	$msg = T_( 'You have successfully unsubscribed.' );

	if( empty( $edited_User ) )
	{	// User not found:
		$msg = T_( 'The user you are trying to unsubscribe does not seem to exist. You may already have deleted your account.' );
	}
	elseif( $key != md5( $user_ID.$edited_User->get( 'unsubscribe_key' ) ) ) 	// Security check
	{
		$msg = T_('Invalid unsubscribe link!');
	}
	else
	{

		switch( $type )
		{
			case 'coll_comment':
			case 'coll_post':
				// unsubscribe from blog
				if( $coll_ID == 0 )
				{
					$msg = T_('Invalid unsubscribe link!');
				}
				else
				{
					$BlogCache = & get_BlogCache();
					$Blog = $BlogCache->get_by_ID( $coll_ID );

					$subscription_name = ( ( $type == 'coll_comment' ) ? 'sub_comments' : 'sub_items' );

					// Get previous setting
					$sub_values = $DB->get_row( 'SELECT sub_items, sub_comments FROM T_subscriptions WHERE sub_coll_ID = '.$coll_ID.' AND sub_user_ID = '.$user_ID, ARRAY_A );

					$Blog = & $BlogCache->get_by_ID( $coll_ID );

					if( $Blog->get( 'advanced_perms' )
							&& ( ( $Blog->get_setting( 'allow_subscriptions' ) && $Blog->get_setting( 'opt_out_subscription' ) ) || ( $Blog->get_setting( 'allow_comment_subscriptions' ) && $Blog->get_setting( 'opt_out_comment_subscription' ) ) )
							&& $edited_User->check_perm( 'blog_ismember', 'view', true, $coll_ID ) )
					{ // opt-out collection
						if( $subscription_name == 'sub_items' )
						{
							$sub_items_value = 0;
							$sub_comments_value = empty( $sub_values ) ? 1 : $sub_values['sub_comments'];
						}
						elseif( $subscription_name == 'sub_comments' )
						{
							$sub_items_value = empty( $sub_values ) ? 1 : $sub_values['sub_items'];
							$sub_comments_value = 0;
						}
						else
						{
							$sub_items_value = 0;
							$sub_comments_value = 0;
						}

						$DB->query( 'REPLACE INTO T_subscriptions( sub_coll_ID, sub_user_ID, sub_items, sub_comments )
								VALUES ( '.$coll_ID.', '.$user_ID.', '.$sub_items_value.', '.$sub_comments_value.' )' );
					}
					else
					{
						$DB->query( 'UPDATE T_subscriptions SET '.$subscription_name.' = 0
									WHERE sub_user_ID = '.$user_ID.' AND sub_coll_ID = '.$coll_ID );
					}
				}
				break;

			case 'post':
				// unsubscribe from a specific post
				if( $post_ID == 0 )
				{
					$msg = T_('Invalid unsubscribe link!');
				}
				else
				{
					$ItemCache = & get_ItemCache();
					$BlogCache = & get_BlogCache();
					$Item = $ItemCache->get_by_ID( $post_ID );
					$blog_ID = $Item->get_blog_ID();
					$Blog = $BlogCache->get_by_ID( $blog_ID );

					if( $Blog->get( 'advanced_perms' )
							&& $Blog->get_setting( 'allow_item_subscriptions' )
							&& $Blog->get_setting( 'opt_out_item_subscription' )
							&& $edited_User->check_perm( 'blog_ismember', 'view', true, $blog_ID ) )
					{
						$DB->query( 'REPLACE INTO T_items__subscriptions( isub_item_ID, isub_user_ID, isub_comments )
								VALUES ( '.$post_ID.', '.$user_ID.', 0 )' );
					}
					else
					{
						$DB->query( 'DELETE FROM T_items__subscriptions
								WHERE isub_user_ID = '.$user_ID.' AND isub_item_ID = '.$post_ID );
					}
				}
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
				// unsubscribe from new comment may need moderation notifications:
				$UserSettings->set( 'notify_comment_moderation', '0', $edited_User->ID );
				$UserSettings->dbupdate();
				break;

			case 'comment_moderator_edit':
				// unsubscribe from updated comment may need moderation notifications:
				$UserSettings->set( 'notify_edit_cmt_moderation', '0', $edited_User->ID );
				$UserSettings->dbupdate();
				break;

			case 'comment_moderator_spam':
				// unsubscribe from spam comment may need moderation notifications:
				$UserSettings->set( 'notify_spam_cmt_moderation', '0', $edited_User->ID );
				$UserSettings->dbupdate();
				break;

			case 'pst_moderation_reminder':
				// unsubscribe from post moderation reminder notifications
				$UserSettings->set( 'send_pst_moderation_reminder', '0', $edited_User->ID );
				$UserSettings->dbupdate();
				break;

			case 'pst_stale_alert':
				// unsubscribe from stale posts alert notifications:
				$UserSettings->set( 'send_pst_stale_alert', '0', $edited_User->ID );
				$UserSettings->dbupdate();
				break;

			case 'post_moderator':
				// unsubscribe from new post moderation notifications:
				$UserSettings->set( 'notify_post_moderation', '0', $edited_User->ID );
				$UserSettings->dbupdate();
				break;

			case 'post_moderator_edit':
				// unsubscribe from updated post moderation notifications:
				$UserSettings->set( 'notify_edit_pst_moderation', '0', $edited_User->ID );
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

			case 'meta_comment':
				// unsubscribe from meta comment notifications
				$UserSettings->set( 'notify_meta_comments', '0', $edited_User->ID );
				$UserSettings->dbupdate();
				break;

			default:
				// DEFENSIVE programming:
				$msg = 'Unhandled unsubscribe type.';
		}
	}

	// Display message
	echo $msg;
	?>
	</body>
</html>
