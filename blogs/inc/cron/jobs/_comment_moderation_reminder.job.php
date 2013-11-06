<?php
/**
 * This file implements the comments moderation reminder cron job
 *
 * @author attila: Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $Settings, $UserSettings;

global $servertimenow, $comment_moderation_reminder_threshold;

// Check if UserSettings exists because it must be initialized before email sending
if( empty( $UserSettings ) )
{ // initialize UserSettings, because in CLI mode is not initialized yet
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

// Only those blogs are selected for moderation where we can find at least one comment awaiting moderation which is older then the threshold date defined below
$threshold_date = date2mysql( $servertimenow - $comment_moderation_reminder_threshold );

$def_notify_moderation = $Settings->get( 'def_notify_comment_moderation' );
if( $def_notify_moderation )
{
	$notify_moderation_cond = '( ( uset_value IS NOT NULL AND uset_value <> \'0\' ) OR ( uset_value IS NULL ) )';
}
else
{
	$notify_moderation_cond = '( uset_value IS NOT NULL AND uset_value <> \'0\' )';
}

// Statuses defined in this array should be notified. This should be configurable, but this is the default value.
$notify_statuses = array( 'review', 'draft' );

// select blogs where are comments awaiting moderation more then x ( = configured threshold ) hours
$SQL = new SQL();
$SQL->SELECT( 'DISTINCT cat_blog_ID' );
$SQL->FROM( 'T_categories' );
$SQL->FROM_add( 'INNER JOIN T_items__item ON post_main_cat_ID = cat_ID AND post_status = '.$DB->quote( 'published' ) );
$SQL->FROM_add( 'INNER JOIN T_comments ON comment_post_ID = post_ID AND comment_status IN ('.$DB->quote( $notify_statuses ).') AND comment_date < '.$DB->quote( $threshold_date ) );

$moderation_blogs = $DB->get_col( $SQL->get() );

if( empty( $moderation_blogs ) )
{ // There are no blogs where exists draft comments older then the threshold ( 24 hours by default )
	$result_message = sprintf( T_('No comments have been awaiting moderation for more than %s.'), seconds_to_period( $comment_moderation_reminder_threshold ) );
	return 1;
}

// Select blocked and spam email addresses to prevent sending emails to them
$blocked_emails = $DB->get_col( 'SELECT emblk_address FROM T_email__blocked WHERE '.get_mail_blocked_condition() );
$blocked_emails_condition = ( count( $blocked_emails ) ) ? 'user_email NOT IN ( "'.implode( '","', $blocked_emails ).'" )' : NULL;

// select moderators with editall permissions
$SQL = new SQL();
$SQL->SELECT( 'user_ID' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'INNER JOIN T_groups ON grp_ID = user_grp_ID AND grp_perm_blogs = '.$DB->quote( 'editall' ) );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID AND uset_name = "notify_comment_moderation"' );
$SQL->WHERE( $notify_moderation_cond );
// check if user has an email address
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
// check that user email is not blocked
$SQL->WHERE_and( $blocked_emails_condition );

$global_moderators = $DB->get_col( $SQL->get() );
$not_global_moderator = ( count( $global_moderators ) ) ? 'user_ID NOT IN ( '.implode( ',', $global_moderators ).' )' : NULL;

// select blog owners, because they are moderators in their own blogs
$SQL = new SQL();
$SQL->SELECT( 'user_ID, GROUP_CONCAT( DISTINCT cast(blog_ID as CHAR) ORDER BY blog_ID SEPARATOR \',\') as blogs' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'LEFT JOIN T_blogs ON blog_owner_user_ID = user_ID' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = blog_owner_user_ID AND uset_name = "notify_comment_moderation"' );
$SQL->WHERE( 'blog_ID IN ('.implode( ',', $moderation_blogs ).' )' );
$SQL->WHERE_and( $notify_moderation_cond );
$SQL->WHERE_and( $not_global_moderator );
// check if user has an email address
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
// check that user email is not blocked
$SQL->WHERE_and( $blocked_emails_condition );
$SQL->GROUP_BY( 'user_ID' );
$SQL->ORDER_BY( 'user_ID' );

$blog_owners = $DB->get_assoc( $SQL->get() );

// select moderator users in the required blogs
// get those statuses binary value which should be notified
$statuses_permvalue = 0;
foreach( $notify_statuses as $comment_status )
{
	$statuses_permvalue += get_status_permvalue( $comment_status );
}
$draft_status_permvalue = get_status_permvalue( 'draft' );
$SQL = new SQL();
$SQL->SELECT( 'user_ID, GROUP_CONCAT( DISTINCT cast(blog_ID as CHAR) ORDER BY blog_ID SEPARATOR \',\') as blogs' );
$SQL->FROM( 'T_users' );
// Check that the user or the user group has advanced blog permission for specific statuses, and at least anon comment edit permission
$SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON bloguser_user_ID = user_ID AND ( bloguser_perm_cmtstatuses & '.$statuses_permvalue.' <> 0 ) AND bloguser_perm_edit_cmt NOT IN ( "no", "own" ) AND bloguser_blog_ID IN ( '.implode( ',', $moderation_blogs ).' )' );
$SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON bloggroup_group_ID = user_grp_ID AND ( bloggroup_perm_cmtstatuses & '.$statuses_permvalue.' <> 0 ) AND bloggroup_perm_edit_cmt NOT IN ( "no", "own" ) AND bloggroup_blog_ID IN ( '.implode( ',', $moderation_blogs ).' )' );
$SQL->FROM_add( 'INNER JOIN T_blogs ON bloggroup_blog_ID = blog_ID OR bloguser_blog_ID = blog_ID' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID AND uset_name = "notify_comment_moderation"' );
$SQL->WHERE( $notify_moderation_cond );
$SQL->WHERE_and( $not_global_moderator );
// check if user has an email address
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
// check that user email is not blocked
$SQL->WHERE_and( $blocked_emails_condition );
$SQL->GROUP_BY( 'user_ID' );
$SQL->ORDER_BY( 'user_ID' );

$specific_blog_moderators = $DB->get_assoc( $SQL->get() );

if( empty( $global_moderators ) && empty( $blog_owners ) && empty( $specific_blog_moderators ) )
{
	$result_message = sprintf( T_( 'Could not find any moderators wanting to receive comment moderation notifications for the blogs that have comments pending moderation!' ) );
	return 1;
}

// load all required Users
$all_required_users = array_unique( array_merge( $global_moderators, array_keys( $blog_owners ), array_keys( $specific_blog_moderators ) ) );
$UserCache = & get_UserCache();
$UserCache->load_list( $all_required_users );

// load all required Blogs
$BlogCache = & get_BlogCache();
$BlogCache->load_list( $moderation_blogs );

// count all comments awaiting for moderation in the required blogs
$SQL = new SQL();
$SQL->SELECT( 'cat_blog_ID, count( comment_ID )' );
$SQL->FROM( 'T_comments' );
$SQL->FROM_add( 'INNER JOIN T_items__item ON post_ID = comment_post_ID AND post_status = '.$DB->quote( 'published' ) );
$SQL->FROM_add( 'INNER JOIN T_categories ON cat_ID = post_main_cat_ID AND cat_blog_ID IN ('.implode( ',', $moderation_blogs ).')' );
$SQL->WHERE( 'comment_status IN ( '.$DB->quote( $notify_statuses ).' )' );
$SQL->GROUP_BY( 'cat_blog_ID' );
$blog_comments = $DB->get_assoc( $SQL->get() );

$mail_sent = 0;
$params = array(
	'comments' => $blog_comments
);

foreach( $all_required_users as $moderator_ID )
{
	if( isset( $blog_owners[$moderator_ID] ) )
	{
		$blogs = explode( ',', $blog_owners[$moderator_ID] );
		if( isset( $specific_blog_moderators[$moderator_ID] ) )
		{
			$blogs = array_unique( array_merge( $blogs, explode( ',', $specific_blog_moderators[$moderator_ID] ) ) );
		}
		$params['blogs'] = $blogs;
	}
	elseif( isset( $specific_blog_moderators[$moderator_ID] ) )
	{
		$params['blogs'] = explode( ',', $specific_blog_moderators[$moderator_ID] );
	}
	elseif( in_array( $moderator_ID, $global_moderators ) )
	{
		$params['blogs'] = $moderation_blogs;
	}
	else
	{ // This is an invalid state of the reminder
		continue;
	}

	$moderator_User = $UserCache->get_by_ID( $moderator_ID );
	// Change locale here to localize the email subject and content
	locale_temp_switch( $moderator_User->get( 'locale' ) );
	if( send_mail_to_User( $moderator_ID, T_( 'Comment moderation reminder' ), 'comments_unmoderated_reminder', $params, false ) )
	{
		$mail_sent++;
	}
	locale_restore_previous();
}

$result_message = sprintf( T_( '%d moderator have been notified!' ), $mail_sent );
return 1; /*OK*/
?>