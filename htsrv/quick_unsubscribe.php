<?php
/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'/_main.inc.php';

if( empty( $params ) )
{
	$params = array();
}

global $UserSettings;

param( 'type', 'string', true );
param( 'user_ID', 'integer', true );
param( 'key', 'string', true );
param( 'coll_ID', 'integer', 0 );
param( 'post_ID', 'integer', 0 );
param( 'confirmed', 'integer', 0 );
param( 'action', 'string', NULL );

$UserCache = & get_UserCache();
$edited_User = $UserCache->get_by_ID( $user_ID, false, false );

if( empty( $edited_User ) )
{	// User not found:
	$error_msg = T_( 'The user you are trying to unsubscribe does not seem to exist. You may already have deleted your account.' );
}
elseif( $key != md5( $user_ID.$edited_User->get( 'unsubscribe_key' ) ) ) 	// Security check
{
	$error_msg = T_('Invalid unsubscribe link!');
}
elseif( $confirmed )
{ // Unsubscribe is confirmed let's proceed
	switch( $action )
	{
		case 'unsubscribe':
			$Session->assert_received_crumb( 'unsubscribe' );

			// Crumb should only be used once
			$Session->delete( 'crumb_latest_unsubscribe' );
			$Session->delete( 'crumb_prev_unsubscribe' );

			switch( $type )
			{
				case 'coll_comment':
				case 'coll_post':
					// unsubscribe from blog
					if( $coll_ID == 0 )
					{
						$error_msg = T_('Invalid unsubscribe link!');
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
						$error_msg = T_('Invalid unsubscribe link!');
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

					// Use first newsletter by default for old unsubscribe url when we had only one static newsletter,
					//    which was upgraded to Newsletter with ID = 1
					$newsletter_ID = param( 'newsletter', 'integer', 1 );

					if( ! $edited_User->unsubscribe( $newsletter_ID ) )
					{	// Display a message is the user is not subscribed on the requested newsletter:
						$error_msg = T_('You are not subscribed to this list.');
					}
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

				case 'automation_owner_notification':
					// unsubscribe from automation step owner notifications:
					$UserSettings->set( 'notify_automation_owner', '0', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'meta_comment':
					// unsubscribe from meta comment notifications
					$UserSettings->set( 'notify_meta_comments', '0', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				default:
					// DEFENSIVE programming:
					$error_msg = 'Unhandled unsubscribe type.';
			}

			if( ! isset( $error_msg ) )
			{
				$unsubscribed = true;
			}
			break;

		case 'resubscribe':
			$Session->assert_received_crumb( 'resubscribe' );

			// Crumb should only be used once
			$Session->delete( 'crumb_latest_resubscribe' );
			$Session->delete( 'crumb_prev_resubscribe' );

			switch( $type )
			{
				case 'coll_comment':
				case 'coll_post':
					// unsubscribe from blog
					if( $coll_ID == 0 )
					{
						$error_msg = T_('Invalid unsubscribe link!');
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
								$sub_items_value = 1;
								$sub_comments_value = empty( $sub_values ) ? 1 : $sub_values['sub_comments'];
							}
							elseif( $subscription_name == 'sub_comments' )
							{
								$sub_items_value = empty( $sub_values ) ? 1 : $sub_values['sub_items'];
								$sub_comments_value = 1;
							}
							else
							{ // should be impossible to go here
								$sub_items_value = $sub_values['sub_items'];
								$sub_comments_value = $sub_values['sub_comments'];
							}

							$DB->query( 'REPLACE INTO T_subscriptions( sub_coll_ID, sub_user_ID, sub_items, sub_comments )
									VALUES ( '.$coll_ID.', '.$user_ID.', '.$sub_items_value.', '.$sub_comments_value.' )' );
						}
						else
						{
							$DB->query( 'UPDATE T_subscriptions SET '.$subscription_name.' = 1
										WHERE sub_user_ID = '.$user_ID.' AND sub_coll_ID = '.$coll_ID );
						}
					}
					break;

				case 'post':
					// unsubscribe from a specific post
					if( $post_ID == 0 )
					{
						$error_msg = T_('Invalid unsubscribe link!');
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
									VALUES ( '.$post_ID.', '.$user_ID.', 1 )' );
						}
						else
						{
							$DB->query( 'INSERT INTO T_items__subscriptions( isub_item_ID, isub_user_ID, isub_comments )
									VALUES ( '.$post_ID.', '.$user_ID.', 1 )' );
						}
					}
					break;

				case 'creator':
					// unsubscribe from the user own posts
					$UserSettings->set( 'notify_published_comments', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'cmt_moderation_reminder':
					// unsubscribe from comment moderation reminder notifications
					$UserSettings->set( 'send_cmt_moderation_reminder', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'comment_moderator':
				case 'moderator': // Note: This was not chaned to 'comment_moderator' to make sure old emails unsubscribe link are also work
					// unsubscribe from new comment may need moderation notifications:
					$UserSettings->set( 'notify_comment_moderation', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'comment_moderator_edit':
					// unsubscribe from updated comment may need moderation notifications:
					$UserSettings->set( 'notify_edit_cmt_moderation', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'comment_moderator_spam':
					// unsubscribe from spam comment may need moderation notifications:
					$UserSettings->set( 'notify_spam_cmt_moderation', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'pst_moderation_reminder':
					// unsubscribe from post moderation reminder notifications
					$UserSettings->set( 'send_pst_moderation_reminder', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'pst_stale_alert':
					// unsubscribe from stale posts alert notifications:
					$UserSettings->set( 'send_pst_stale_alert', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'post_moderator':
					// unsubscribe from new post moderation notifications:
					$UserSettings->set( 'notify_post_moderation', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'post_moderator_edit':
					// unsubscribe from updated post moderation notifications:
					$UserSettings->set( 'notify_edit_pst_moderation', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'unread_msg':
					// unsubscribe from unread messages reminder
					$UserSettings->set( 'notify_unread_messages', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'new_msg':
					// unsubscribe from new messages notification
					$UserSettings->set( 'notify_messages', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'account_activation':
					// unsubscribe from account activation reminder
					$UserSettings->set( 'send_activation_reminder', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'newsletter':
					// unsubscribe from newsletter

					// Use first newsletter by default for old unsubscribe url when we had only one static newsletter,
					//    which was upgraded to Newsletter with ID = 1
					$newsletter_ID = param( 'newsletter', 'integer', 1 );

					if( ! $edited_User->subscribe( $newsletter_ID ) )
					{	// Display a message is the user is not subscribed on the requested newsletter:
						$error_msg = T_('You are already subscribed to this list.');
					}
					break;

				case 'user_registration':
					// unsubscribe from new user registration notifications
					$UserSettings->set( 'notify_new_user_registration', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'account_activated':
					// unsubscribe from account activated notifications
					$UserSettings->set( 'notify_activated_account', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'account_closed':
					// unsubscribe from account closed notifications
					$UserSettings->set( 'notify_closed_account', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'account_reported':
					// unsubscribe from account reported notifications
					$UserSettings->set( 'notify_reported_account', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'account_changed':
					// unsubscribe from account changed notifications
					$UserSettings->set( 'notify_changed_account', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'msgform':
					// turn off allow emails through b2evo message forms
					$UserSettings->set( 'enable_email', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'cronjob_error':
					// unsubscribe from cron job error notifications
					$UserSettings->set( 'notify_cronjob_error', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'automation_owner_notification':
					// unsubscribe from automation step owner notifications:
					$UserSettings->set( 'notify_automation_owner', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				case 'meta_comment':
					// unsubscribe from meta comment notifications
					$UserSettings->set( 'notify_meta_comments', '1', $edited_User->ID );
					$UserSettings->dbupdate();
					break;

				default:
					// DEFENSIVE programming:
					$error_msg = 'Unhandled unsubscribe type.';
			}

			if( ! isset( $error_msg ) )
			{
				$resubscribed = true;
			}
			break;
	}
}

$notification_prefix = T_( 'Notify me by email whenever' );
switch( $type )
{
	case 'coll_comment':
		// unsubscribe from new comment in collection notifications
		if( $coll_ID == 0 )
		{
			$error_msg = T_('Invalid unsubscribe link!');
		}
		else
		{
			$BlogCache = & get_BlogCache();
			$Blog = $BlogCache->get_by_ID( $coll_ID );

			$type_str = T_('Notify me of any new comment in this collection').':<br /><a href="'.$Blog->get( 'url' ).'">'.$Blog->get_shortname().'</a>';
		}
		break;

	case 'coll_post':
		// unsubscribe from new post in collection notifications
		if( $coll_ID == 0 )
		{
			$error_msg = T_('Invalid unsubscribe link!');
		}
		else
		{
			$BlogCache = & get_BlogCache();
			$Blog = $BlogCache->get_by_ID( $coll_ID );

			$type_str = T_('Notify me of any new post in this collection').':<br/><a href="'.$Blog->get( 'url' ).'">'.$Blog->get_shortname().'</a>';
		}
		break;

	case 'post':
		// unsubscribe from post update notifications
		if( $post_ID == 0 )
		{
			$error_msg = T_('Invalid unsubscribe link!');
		}
		else
		{
			$ItemCache = & get_ItemCache();
			$Item = $ItemCache->get_by_ID( $post_ID );

			$type_str = T_('Notify me by email when someone comments here.').'<br/>'.$Item->get_title();
		}
		break;

	case 'creator':
		// unsubscribe from the user own posts
		$type_str = $notification_prefix.': '.T_('a comment is published on one of <strong>my</strong> posts.');
		break;

	case 'cmt_moderation_reminder':
		// unsubscribe from comment moderation reminder notifications
		global $comment_moderation_reminder_threshold;
		$type_str = $notification_prefix.': '.sprintf( T_('comments are awaiting moderation for more than %s.'), seconds_to_period( $comment_moderation_reminder_threshold ) );
		break;

	case 'comment_moderator':
	case 'moderator': // Note: This was not chaned to 'comment_moderator' to make sure old emails unsubscribe link are also work
		// unsubscribe from new comment may need moderation notifications:
		$type_str = $notification_prefix.': '.T_('a comment is posted and I have permissions to moderate it.');
		break;

	case 'comment_moderator_edit':
		// unsubscribe from updated comment may need moderation notifications:
		$type_str = $notification_prefix.': '.T_('a comment is modified and I have permissions to moderate it.');
		break;

	case 'comment_moderator_spam':
		// unsubscribe from spam comment may need moderation notifications:
		$type_str = $notification_prefix.': '.T_('a comment is reported as spam and I have permissions to moderate it.');
		break;

	case 'pst_moderation_reminder':
		// unsubscribe from post moderation reminder notifications
		global $post_moderation_reminder_threshold;
		$type_str = $notification_prefix.': '.sprintf( T_('posts are awaiting moderation for more than %s.'), seconds_to_period( $post_moderation_reminder_threshold ) );
		break;

	case 'pst_stale_alert':
		// unsubscribe from stale posts alert notifications:
		$type_str = $notification_prefix.': '.T_('there are stale posts and I have permission to moderate them.');
		break;

	case 'post_moderator':
		// unsubscribe from new post moderation notifications:
		$type_str = $notification_prefix.': '.T_('a post is created and I have permissions to moderate it.');
		break;

	case 'post_moderator_edit':
		// unsubscribe from updated post moderation notifications:
		$type_str = $notification_prefix.': '.T_('a post is modified and I have permissions to moderate it.');
		break;

	case 'unread_msg':
		// unsubscribe from unread messages reminder
		global $unread_messsage_reminder_threshold;
		$type_str = $notification_prefix.': '.sprintf( T_('I have unread private messages for more than %s.'), seconds_to_period( $unread_messsage_reminder_threshold ) );
		break;

	case 'new_msg':
		// unsubscribe from new messages notification
		$type_str = $notification_prefix.': '.T_('I receive a private message.');
		break;

	case 'account_activation':
		// unsubscribe from account activation reminder
		global $activate_account_reminder_threshold;
		$type_str = $notification_prefix.': '.sprintf( T_('my account was deactivated or is not activated for more than %s.'), seconds_to_period( $activate_account_reminder_threshold ) );
		break;

	case 'newsletter':
		// unsubscribe from newsletter

		// Use first newsletter by default for old unsubscribe url when we had only one static newsletter,
		//    which was upgraded to Newsletter with ID = 1
		$newsletter_ID = param( 'newsletter', 'integer', 1 );
		$NewsletterCache = & get_NewsletterCache();
		$Newsletter = $NewsletterCache->get_by_ID( $newsletter_ID );

		$type_str = $Newsletter->get_name();
		break;

	case 'user_registration':
		// unsubscribe from new user registration notifications
		$type_str = $notification_prefix.': '.T_( 'a new user has registered.' );
		break;

	case 'account_activated':
		// unsubscribe from account activated notifications
		$type_str = $notification_prefix.': '.T_( 'an account was activated.' );
		break;

	case 'account_closed':
		// unsubscribe from account closed notifications
		$type_str = $notification_prefix.': '.T_( 'an account was closed.' );
		break;

	case 'account_reported':
		// unsubscribe from account reported notifications
		$type_str = $notification_prefix.': '.T_( 'an account was reported.' );
		break;

	case 'account_changed':
		// unsubscribe from account changed notifications
		$type_str = $notification_prefix.': '.T_( 'an account was changed.' );
		break;

	case 'msgform':
		// turn off allow emails through b2evo message forms
		$type_str = T_( 'emails through a message form that will NOT reveal my email address.' );
		break;

	case 'cronjob_error':
		// unsubscribe from cron job error notifications
		$type_str = $notification_prefix.': '.T_( 'a scheduled task ends with an error or timeout.' );
		break;

	case 'automation_owner_notification':
		// unsubscribe from automation step owner notifications:
		$type_str = $notification_prefix.': '.T_( 'one of my automations wants to notify me.' );
		break;

	case 'meta_comment':
		// unsubscribe from meta comment notifications
		$type_str = $notification_prefix.': '.T_('a meta comment is posted.');
		break;

	default:
		// DEFENSIVE programming:
		$type_str = T_('Unhandled unsubscribe type');
}

// Form template
$unsubscribe_form_params = array(
	'layout'         => 'fieldset',
	'formclass'     => 'form-horizontal',
	'formstart'      => '<div class="panel panel-default">'
												.'<div class="panel-heading">'
													.'<h3 class="panel-title">$form_title$</h3>'
												.'</div>'
												.'<div class="panel-body">',
	'formend'        => '</div></div>',
	'title_fmt'      => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
	'no_title_fmt'   => '<span style="float:right">$global_icons$</span>'."\n",
	'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$>'."\n"
											.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
	'fieldset_end'   => '</fieldset></div>'."\n",
	'fieldstart'     => '<div class="form-group" $ID$>'."\n",
	'fieldend'       => "</div>\n\n",
	'labelclass'     => 'control-label col-xs-3',
	'labelstart'     => '',
	'labelend'       => "\n",
	'labelempty'     => '<label class="control-label col-xs-3"></label>',
	'inputstart'     => '<div class="controls col-xs-9">',
	'inputend'       => "</div>\n",
	'infostart'      => '<div class="controls col-xs-9"><p class="form-control-static">',
	'infoend'        => "</p></div>\n",
	'buttonsstart'   => '<div class="form-group text-center"><div class="control-buttons col-xs-12">',
	'buttonsend'     => "</div></div>\n\n",
	'customstart'    => '<div class="custom_content">',
	'customend'      => "</div>\n",
	'note_format'    => ' <span class="help-inline">%s</span>',
	// Additional params depending on field type:
	// - checkbox
	'inputclass_checkbox'    => '',
	'inputstart_checkbox'    => '<div class="controls col-sm-9"><div class="checkbox"><label>',
	'inputend_checkbox'      => "</label></div></div>\n",
	'checkbox_newline_start' => '<div class="checkbox">',
	'checkbox_newline_end'   => "</div>\n",
	// - radio
	'fieldstart_radio'       => '<div class="form-group radio-group" $ID$>'."\n",
	'fieldend_radio'         => "</div>\n\n",
	'inputclass_radio'       => '',
	'radio_label_format'     => '$radio_option_label$',
	'radio_newline_start'    => '<div class="radio"><label>',
	'radio_newline_end'      => "</label></div>\n",
	'radio_oneline_start'    => '<label class="radio-inline">',
	'radio_oneline_end'      => "</label>\n",
);

// Default params:
$params = array_merge( array(
	'wrap_width'                => '580px',
	'skin_form_before'          => '',
	'skin_form_after'           => '',
	'unsubscribe_page_before'      => '<div class="evo_panel__unsubscribe">',
	'unsubscribe_page_after'       => '</div>',
	'unsubscribe_form_title'       => T_('Unsubscribe'),
	'form_class_unsubscribe'       => 'evo_form__unsubscribe',
	'unsubscribe_links_attrs'      => ' style="margin: 1em 0 1ex"',
	'unsubscribe_field_width'      => 140,
	'unsubscribe_form_footer'      => true,
	'unsubscribe_form_params'      => NULL,
	'unsubscribe_disp_home_button' => false, // Display button to go home when registration is disabled
	'display_form_messages'     => false,
), $params );

// Header
$page_title = $params['unsubscribe_form_title'];
$wrap_width = $params['wrap_width'];

require $adminskins_path.'/login/_html_header.inc.php';

$unsubscribe_form_params['formstart'] = str_replace( '$form_title$', $page_title, $unsubscribe_form_params['formstart'] );
$params['unsubscribe_form_params'] = $unsubscribe_form_params;

$Form = new Form( get_htsrv_url( true ).'quick_unsubscribe.php', 'unsubscribe_form', 'post' );

if( ! is_null( $params['unsubscribe_form_params'] ) )
{ // Use another template param from skin
	$Form->switch_template_parts( $params['unsubscribe_form_params'] );
}

// Display unsubscribe form
$Form->begin_form( $params['form_class_unsubscribe'] );

if( isset( $error_msg ) )
{
	echo '<p class="text-danger text-center">'.$error_msg.'</p>';
}
else
{
	$Form->hidden( 'type', $type );
	$Form->hidden( 'user_ID', $user_ID );
	$Form->hidden( 'key', $key );
	$Form->hidden( 'coll_ID', $coll_ID );
	$Form->hidden( 'post_ID', $post_ID );
	$Form->hidden( 'confirmed', 1 );

	if( $type == 'newsletter' && ! empty( $newsletter_ID ) )
	{
		$Form->hidden( 'newsletter', $newsletter_ID );
	}

	if( ! isset( $unsubscribed ) && ! isset( $resubscribed ) )
	{
		echo '<p class="text-center">';
		echo T_('You are about to unsubscribe');
		echo '</p>';
	}

	$avatar_tag = $edited_User->get_avatar_imgtag( 'crop-top-64x64', 'img-circle', '', true );
	echo '<h2 class="user_title text-center">'.$avatar_tag.' '.$edited_User->get_colored_login( array( 'login_text' => 'name' ) ).'</h2>';

	if( isset( $unsubscribed ) )
	{
		echo '<p class="text-center text-danger">';
		echo T_('has been unsubscribed from');
	}
	elseif( isset( $resubscribed ) )
	{
		echo '<p class="text-center text-success">';
		echo T_('has been re-subscribed to');
	}
	else
	{
		echo '<p class="text-center">';
		echo T_('from these emails').':';
	}
	echo '</p>';
	echo '<p class="text-center"><strong>'.$type_str.'</strong></p>';

	echo '<div style="margin-top: 2em;">';
	if( isset( $unsubscribed ) || isset( $resubscribed ) )
	{
		echo '<p class="text-center">'.T_('If this is a mistake you can click below').':</p>';
	}

	// Submit button:
	if( isset( $unsubscribed ) )
	{
		$Form->add_crumb( 'resubscribe' );
		$Form->hidden( 'action', 'resubscribe' );
		$submit_button = array( array( 'name' => 're-subscribe', 'value' => T_('Re-subscribe!'), 'class' => 'search btn-success btn-lg' ) );
	}
	else
	{
		$Form->add_crumb( 'unsubscribe' );
		$Form->hidden( 'action', 'unsubscribe' );
		$submit_button = array( array( 'name' => 'unsubscribe', 'value' => T_('Unsubscribe!'), 'class' => 'search btn-danger btn-lg' ) );
	}
	$Form->buttons_input( $submit_button );
	echo '</div>';
}
$Form->end_form();

echo $params['skin_form_after'];

echo $params['unsubscribe_page_after'];


// Footer
require $adminskins_path.'/login/_html_footer.inc.php';
?>