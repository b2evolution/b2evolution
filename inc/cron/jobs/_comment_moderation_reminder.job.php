<?php
/**
 * This file implements the comments moderation reminder cron job
 *
 * @author attila: Attila Simo
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

// Statuses defined in this array should be notified. This should be configurable, but this is the default value.
$notify_statuses = get_visibility_statuses( 'moderation' );

// Select blogs where are comments awaiting moderation more then x ( = configured threshold ) hours
$SQL = new SQL();
$SQL->SELECT( 'DISTINCT cat_blog_ID' );
$SQL->FROM( 'T_categories' );
$SQL->FROM_add( 'INNER JOIN T_items__item ON post_main_cat_ID = cat_ID AND post_status IN ('.$DB->quote( array( 'published', 'community', 'protected' ) ).')' );
$SQL->FROM_add( 'INNER JOIN T_comments ON comment_item_ID = post_ID AND comment_status IN ('.$DB->quote( $notify_statuses ).') AND comment_date < '.$DB->quote( $threshold_date ) );

$moderation_blogs = $DB->get_col( $SQL->get() );

if( empty( $moderation_blogs ) )
{ // There are no blogs where exists draft comments older then the threshold ( 24 hours by default )
	$result_message = sprintf( T_('No comments have been awaiting moderation for more than %s.'), seconds_to_period( $comment_moderation_reminder_threshold ) );
	return 1;
}

$moderation_blogs_cond = '%s IN ( '.implode( ',', $moderation_blogs ).' )';

// Select global moderators
$SQL = new SQL();
$SQL->SELECT( 'user_ID' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'LEFT JOIN T_groups ON grp_ID = user_grp_ID' );
$SQL->WHERE( 'grp_perm_blogs = '.$DB->quote( 'editall' ) );

$global_moderators = $DB->get_col( $SQL->get() );
$not_global_moderator = ( count( $global_moderators ) ) ? '%s NOT IN ( '.implode( ',', $global_moderators ).' )' : NULL;

// Select blog owners, because they are moderators in their own blogs
$SQL = new SQL();
$SQL->SELECT( 'blog_owner_user_ID, GROUP_CONCAT( DISTINCT cast(blog_ID as CHAR) ORDER BY blog_ID SEPARATOR \',\') as blogs' );
$SQL->FROM( 'T_blogs' );
$SQL->WHERE( sprintf( $not_global_moderator, 'blog_owner_user_ID' ) );
$SQL->WHERE_and( sprintf( $moderation_blogs_cond, 'blog_ID' ) );
$SQL->GROUP_BY( 'blog_owner_user_ID' );
// Get blog owner users with their blogs
$blog_owners = $DB->get_assoc( $SQL->get() );

// Select comment moderators based on the blogs advanced user permissions
$bloguser_SQL = new SQL();
$bloguser_SQL->SELECT( 'bloguser_user_ID as user_ID, bloguser_blog_ID as blog_ID, bloguser_perm_cmtstatuses + 0 as perm_cmtstatuses, bloguser_perm_edit_cmt as perm_edit_cmt' );
$bloguser_SQL->FROM( 'T_coll_user_perms' );
$bloguser_SQL->FROM_add( 'LEFT JOIN T_blogs ON blog_ID = bloguser_blog_ID' );
$bloguser_SQL->WHERE( sprintf( $not_global_moderator, 'bloguser_user_ID' ) );
$bloguser_SQL->WHERE_and( 'blog_advanced_perms <> 0' );
$bloguser_SQL->WHERE_and( sprintf( $moderation_blogs_cond, 'bloguser_blog_ID' ) );
$bloguser_SQL->WHERE_and( 'bloguser_perm_cmtstatuses <> "" AND bloguser_perm_edit_cmt <> "no" AND bloguser_perm_edit_cmt <> "own"' );
// Select comment moderators based on the blogs advanced group permissions
$bloggroup_SQL = new  SQL();
$bloggroup_SQL->SELECT( 'user_ID, bloggroup_blog_ID as blog_ID, bloggroup_perm_cmtstatuses + 0 as perm_cmtstatuses, bloggroup_perm_edit_cmt as perm_edit_cmt' );
$bloggroup_SQL->FROM( 'T_users' );
$bloggroup_SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( bloggroup_group_ID = user_grp_ID
	OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = user_ID ) )' );
$bloggroup_SQL->FROM_add( 'LEFT JOIN T_blogs ON blog_ID = bloggroup_blog_ID' );
$bloggroup_SQL->WHERE( sprintf( $not_global_moderator, 'user_ID' ) );
$bloggroup_SQL->WHERE_and( 'blog_advanced_perms <> 0' );
$bloggroup_SQL->WHERE_and( sprintf( $moderation_blogs_cond, 'bloggroup_blog_ID' ) );
$bloggroup_SQL->WHERE_and( 'bloggroup_perm_cmtstatuses <> "" AND bloggroup_perm_edit_cmt <> "no" AND bloggroup_perm_edit_cmt <> "own"' );
// Get specific moderator users with their permissions in each different blog
$specific_blog_moderators = $DB->get_results( '( '.$bloguser_SQL->get().' ) UNION ( '.$bloggroup_SQL->get().' ) ORDER BY user_ID, blog_ID' );

// Highest edit level
$max_perm_edit = 'all';
// Highest value of the comment statuses perm
$max_perm_statuses = 127;
// Create a general moderators array to collect all blog owners and different blog moderators
$moderators = array();
foreach( $specific_blog_moderators as $row )
{ // Loop through each different blog's moderator users and their permissions
	if( !isset( $moderators[$row->user_ID] ) )
	{ // Initialize user perm array
		$moderators[$row->user_ID] = array();
	}
	if( isset( $moderators[$row->user_ID][$row->blog_ID] ) )
	{ // Update user permissions on this blog
		if( $moderators[$row->user_ID][$row->blog_ID]['perm_edit'] < $row->perm_edit_cmt )
		{ // The user and the group advanced comment edit perm for this user are not the same, keep the higher perm value
			$moderators[$row->user_ID][$row->blog_ID]['perm_edit'] = $row->perm_edit_cmt;
		}
		$current_perm_statuses = $moderators[$row->user_ID][$row->blog_ID]['perm_statuses'];
		$row_perm_status = (int) $row->perm_cmtstatuses;
		if( $current_perm_statuses != $row_perm_status )
		{ // The advanced user and the group comment statuses perm for this user are not the same, the union of this perms must be accepted
			$moderators[$row->user_ID][$row->blog_ID]['perm_statuses'] = ( $current_perm_statuses | $row_perm_status );
		}
	}
	else
	{ // Initialize a new setting for this user / blog
		$moderators[$row->user_ID][$row->blog_ID] = array( 'perm_edit' => $row->perm_edit_cmt, 'perm_statuses' => (int) $row->perm_cmtstatuses );
	}
}
foreach( $blog_owners as $moderator_ID => $moderator_blogs )
{ // Loop through each blog owner users and set the highest permission in their own blogs
	$blogs = explode( ',', $moderator_blogs );
	foreach( $blogs as $blog_ID )
	{ // Loop through each blogs of this user
		if( !isset( $moderators[$moderator_ID] ) )
		{ // Init this user moderator perms if it was not initialized yet
			$moderators[$moderator_ID] = array();
		}
		$moderators[$moderator_ID][$blog_ID] = array( 'perm_edit' => $max_perm_edit, 'perm_statuses' => $max_perm_statuses );
	}
}

// Set notify moderation condition
$def_send_moderation_reminder = $UserSettings->get( 'send_cmt_moderation_reminder' );
if( $def_send_moderation_reminder )
{ // Send comment moderation reminder is set by default
	$send_moderation_reminder_cond = '( ( uset_value IS NOT NULL AND uset_value <> \'0\' ) OR ( uset_value IS NULL ) )';
}
else
{ // Send comment moderation reminder is NOT set by default
	$send_moderation_reminder_cond = '( uset_value IS NOT NULL AND uset_value <> \'0\' )';
}

// Select blocked and spam email addresses to prevent sending emails to them
$blocked_emails = $DB->get_col( 'SELECT emadr_address FROM T_email__address WHERE '.get_mail_blocked_condition() );
$blocked_emails_condition = ( count( $blocked_emails ) ) ? 'user_email NOT IN ( "'.implode( '","', $blocked_emails ).'" )' : NULL;

// load all required Users ( global moderators, blog owners and users with advanced blog perms )
$all_required_users = array_unique( array_merge( $global_moderators, array_keys( $moderators ) ) );
$SQL = new SQL();
$SQL->SELECT( 'T_users.*' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID AND uset_name = "send_cmt_moderation_reminder"' );
$SQL->WHERE( 'user_ID IN ('.implode( ',', $all_required_users ).')' );
$SQL->WHERE_and( 'user_status IN ( "activated", "autoactivated" )' );
$SQL->WHERE_and( $send_moderation_reminder_cond );
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
$SQL->WHERE_and( $blocked_emails_condition );

// Load moderator users who would like to get notificaions
$UserCache = & get_UserCache();
$UserCache->clear( true );
$UserCache->load_by_sql( $SQL );
$loaded_ids = $UserCache->get_ID_array();

if( empty( $loaded_ids ) )
{ // UserCache result is empty which means nobody wants to receive notifications
	$result_message = sprintf( T_( 'Could not find any moderators wanting to receive comment moderation notifications for the blogs that have comments pending moderation!' ) );
	return 1;
}

// load all required Blogs
$BlogCache = & get_BlogCache();
$BlogCache->load_list( $moderation_blogs );

// count all comments awaiting for moderation in the required blogs, group by blog/comment_status/author_level
$SQL = new SQL();
$SQL->SELECT( 'cat_blog_ID as blog_ID, comment_status, IF( comment_author_user_ID IS NULL, 0, user_level ) as author_level, count( comment_ID ) as cmt_count' );
$SQL->FROM( 'T_comments' );
$SQL->FROM_add( 'LEFT JOIN T_users ON user_ID = comment_author_user_ID' );
$SQL->FROM_add( 'LEFT JOIN T_items__item ON post_ID = comment_item_ID' );
$SQL->FROM_add( 'LEFT JOIN T_categories ON cat_ID = post_main_cat_ID' );
$SQL->WHERE( 'comment_status IN ( '.$DB->quote( $notify_statuses ).' )' );
$SQL->WHERE_and( 'post_status IN ( '.$DB->quote( array( 'published', 'community', 'protected' ) ).' )' );
$SQL->WHERE_and( 'cat_blog_ID IN ('.implode( ',', $moderation_blogs ).')' );
$SQL->GROUP_BY( 'cat_blog_ID, comment_status, author_level' );
$blog_comments = $DB->get_results( $SQL->get() );

// Create a comments map by blog_ID:comment_status:author_level:count. This way it will be much easier to get allowed comments for a specific permission
$comments_map = array();
$last_blog_ID = NULL;
foreach( $blog_comments as $row )
{
	if( $last_blog_ID != $row->blog_ID )
	{
		$Collection = $Blog = & $BlogCache->get_by_ID( $row->blog_ID );
		$blog_moderation_statuses = $Blog->get_setting( 'moderation_statuses' );
		$last_blog_ID = $row->blog_ID;
	}
	if( strpos( $blog_moderation_statuses, $row->comment_status ) === false )
	{ // This status shouldn't be notified on this blog
		continue;
	}
	if( isset( $comments_map[$row->blog_ID] ) )
	{ // This blog comments were already initialized
		if( isset( $comments_map[$row->blog_ID][$row->comment_status] ) )
		{ // Comments with this status were already initialized
			// Set new author level filter for this status and the corresponding amount of comments
			$comments_map[$row->blog_ID][$row->comment_status][$row->author_level] = $row->cmt_count;
		}
		else
		{ // Initialize blog comments with this status, and set the first element
			$comments_map[$row->blog_ID][$row->comment_status] = array( $row->author_level => $row->cmt_count );
		}
	}
	else
	{ // Initialize blog array, and set the first element
		$comments_map[$row->blog_ID] = array( $row->comment_status => array( $row->author_level => $row->cmt_count ) );
	}
}

$mail_sent = 0;
$params = array();

// Collect comments data for global moderators
$moderator_comments = array();
foreach( $moderation_blogs as $blog_ID )
{ // Collect the number of comments from all blogs
	if( !isset( $comments_map[$blog_ID] ) )
	{ // this blog doesn't contains comments awaiting moderation which statuses corresponds to this blog moderation statutes
		continue;
	}
	$cmt_count = 0;
	foreach( $comments_map[$blog_ID] as $status => $content )
	{ // collect the number of commetns with all statuses
		foreach( $content as $level => $count )
		{ // collect the number of commetns with all kind of users
			$cmt_count += $count;
		}
	}
	$moderator_comments[$blog_ID] = $cmt_count;
}

foreach( $loaded_ids as $moderator_ID )
{ // Loop trhough each moderators and send comment moderation emails if it is required
	$moderator_User = $UserCache->get_by_ID( $moderator_ID );
	$blog_comments = array();
	if( in_array( $moderator_ID, $global_moderators ) )
	{ // This is a global moderator user
		$blog_comments = $moderator_comments;
	}
	else
	{ // This moderator user may have different permission on different blogs, collect the comments corresponding to the perms
		foreach( $moderators[$moderator_ID] as $blog_ID => $perms )
		{
			if( !isset( $comments_map[$blog_ID] ) )
			{ // this blog doesn't contains comments awaiting moderation which statuses corresponds to this blog moderation statutes
				continue;
			}
			$cmt_count = 0;
			foreach( $comments_map[$blog_ID] as $status => $content )
			{
				$status_perm_value = get_status_permvalue( $status );
				if( $perms['perm_statuses'] & $status_perm_value )
				{ // User has permission to edit comments with this status
					// TODO asimo> Here probably we should also check if user is able to deprecate/recycle the comment.
					// Check if User has permission to raise comment status
					$ordered_statuses = get_visibility_statuses( 'ordered-index' );
					$raise_status_allowed = false;
					$current_status_found = false;
					foreach( $ordered_statuses as $ordered_status => $order_index )
					{
						if( $ordered_status == $status )
						{
							$current_status_found = true;
						}
						elseif( $current_status_found  && ( $order_index !== 0 ) )
						{ // This is a higher status then the currently checked status
							$ordered_status_perm_value = get_status_permvalue( $ordered_status );
							if( $perms['perm_statuses'] & $ordered_status_perm_value )
							{ // User has permission to a higher status, so the comment status can be raised
								$raise_status_allowed = true;
								break;
							}
						}
					}
					if( !$raise_status_allowed )
					{ // User is not allowed to raise these comment statuses
						continue;
					}
					// Check if the comment author level allows the edit permission:
					foreach( $content as $level => $count )
					{
						switch( $perms['perm_edit'] )
						{
							case 'all':
								$allowed = true;
								break;
							case 'le':
								$allowed = ( $level <= $moderator_User->level );
								break;
							case 'lt':
								$allowed = ( $level < $moderator_User->level );
								break;
							case 'anon':
								$allowed = ( $level == 0 );
								break;
							default:
								$allowed = false;
						}
						if( $allowed )
						{ // User has permission to edit comments with this author level
							$cmt_count += $count;
						}
					}
				}
			}
			if( $cmt_count > 0 )
			{ // There are comments awaiting moderation on this blog and user has permission to moderate
				$blog_comments[$blog_ID] = $cmt_count;
			}
		}
	}

	if( empty( $blog_comments ) )
	{ // There are no comments awaiting moderation that this user could moderate
		continue;
	}

	$params['blogs'] = array_keys( $blog_comments ); // This can be remvoved if this solution will remain
	$params['comments'] = $blog_comments;

	// Change locale here to localize the email subject and content
	locale_temp_switch( $moderator_User->get( 'locale' ) );
	if( send_mail_to_User( $moderator_ID, T_( 'Comment moderation reminder' ), 'comments_unmoderated_reminder', $params, false ) )
	{
		$mail_sent++;
	}
	locale_restore_previous();
}

$result_message = sprintf( T_( '%d moderators have been notified!' ), $mail_sent );
return 1; /*OK*/
?>
