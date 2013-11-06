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

global $servertimenow;

// Check if UserSettings exists because it must be initialized before email sending
if( empty( $UserSettings ) )
{ // initialize UserSettings, because in CLI mode is not initialized yet
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

$one_day_ago = date2mysql( $servertimenow - 86400/* 60*60*24 = 24 hour*/ );

$def_notify_moderation = $Settings->get( 'def_notify_comment_moderation' );
if( $def_notify_moderation )
{
	$notify_moderation_cond = '( ( uset_value IS NOT NULL AND uset_value <> \'0\' ) OR ( uset_value IS NULL ) )';
}
else
{
	$notify_moderation_cond = '( uset_value IS NOT NULL AND uset_value <> \'0\' )';
}

// select blogs where are comments awaiting moderation more then 24 hours
$SQL = new SQL();
$SQL->SELECT( 'DISTINCT cat_blog_ID' );
$SQL->FROM( 'T_categories' );
$SQL->FROM_add( 'INNER JOIN T_items__item ON post_main_cat_ID = cat_ID AND post_status = '.$DB->quote( 'published' ) );
$SQL->FROM_add( 'INNER JOIN T_comments ON comment_post_ID = post_ID AND comment_status = '.$DB->quote( 'draft' ).' AND comment_date < '.$DB->quote( $one_day_ago ) );

$moderation_blogs = $DB->get_col( $SQL->get() );

if( empty( $moderation_blogs ) )
{ // There are no blogs where exists draft comments older then 24 hours
	$result_message = sprintf( T_( 'There are no older comments then 24 hours awaiting moderation.' ) );
	return 1;
}

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
$SQL->WHERE_and( 'user_email NOT IN ( SELECT emblk_address FROM T_email__blocked WHERE '.get_mail_blocked_condition().' )' );

$global_moderators = $DB->get_col( $SQL->get() );

// select moderator users in the required blogs
// get draft statuse binary value
$draft_status_permvalue = get_status_permvalue( 'draft' );
$SQL = new SQL();
$SQL->SELECT( 'user_ID, GROUP_CONCAT( DISTINCT cast(blog_ID as CHAR) ORDER BY blog_ID SEPARATOR \',\') as blogs' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON bloguser_user_ID = user_ID AND ( bloguser_perm_cmtstatuses & '.$draft_status_permvalue.' <> 0 ) AND bloguser_blog_ID IN ( '.implode( ',', $moderation_blogs ).' )' );
$SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON bloggroup_group_ID = user_grp_ID AND ( bloggroup_perm_cmtstatuses & '.$draft_status_permvalue.' <> 0 ) AND bloggroup_blog_ID IN ( '.implode( ',', $moderation_blogs ).' )' );
$SQL->FROM_add( 'INNER JOIN T_blogs ON bloggroup_blog_ID = blog_ID OR bloguser_blog_ID = blog_ID' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID AND uset_name = "notify_comment_moderation"' );
$SQL->WHERE( $notify_moderation_cond );
if( count( $global_moderators ) )
{
	$SQL->WHERE_and( 'user_ID NOT IN ( '.implode( ',', $global_moderators ).' )' );
}
// check if user has an email address
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
// check that user email is not blocked
$SQL->WHERE_and( 'user_email NOT IN ( SELECT emblk_address FROM T_email__blocked WHERE emblk_status = "prmerror" OR emblk_status = "spammer" )' );
$SQL->GROUP_BY( 'user_ID' );
$SQL->ORDER_BY( 'user_ID' );

$specific_blog_moderators = $DB->get_assoc( $SQL->get() );

if( empty( $global_moderators ) && empty( $specific_blog_moderators ) )
{
	$result_message = sprintf( T_( 'Could not find any moderators who wants to receive notifications about comment moderation in the required blogs!' ) );
	return 2;
}

// Load required functions ( we need to load here, because in CLI mode it is not loaded )
load_funcs( '_core/_url.funcs.php' );

// load all required Users
$all_required_users = array_merge( $global_moderators, array_keys( $specific_blog_moderators ) );
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
$SQL->WHERE( 'comment_status = '.$DB->quote( 'draft' ) );
$SQL->GROUP_BY( 'cat_blog_ID' );
$blog_comments = $DB->get_assoc( $SQL->get() );

$mail_sent = 0;

// send mail to global moderators about all comments
$global_moderator_params = array(
		'blogs' => $moderation_blogs,
		'comments' => $blog_comments
	);
foreach( $global_moderators as $moderator_ID )
{
	if( send_mail_to_User( $moderator_ID, T_( 'Comment moderation reminder' ), 'comment_moderation_reminder', $global_moderator_params, false ) )
	{
		$mail_sent++;
	}
}

// send mail to specific blog moderators about comments in those specific blogs
foreach( $specific_blog_moderators as $moderator_ID => $blogs)
{
	if( send_mail_to_User( $moderator_ID, T_( 'Comment moderation reminder' ), 'comment_moderation_reminder', array( 'blogs' => explode( ',', $blogs ), 'comments' => $blog_comments ), false ) )
	{
		$mail_sent++;
	}
}

$result_message = sprintf( T_( '%d moderator was notified!' ), $mail_sent );
return 1; /*OK*/
?>