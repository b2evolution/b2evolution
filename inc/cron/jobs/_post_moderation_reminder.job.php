<?php
/**
 * This file implements the posts moderation reminder cron job
 *
 * @author yura: Yura Bakhtin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $Settings, $UserSettings;

global $servertimenow, $post_moderation_reminder_threshold;

// Check if UserSettings exists because it must be initialized before email sending
if( empty( $UserSettings ) )
{ // initialize UserSettings, because in CLI mode is not initialized yet
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

// Only those blogs are selected for moderation where we can find at least one post awaiting moderation which is older then the threshold date defined below
$threshold_date = date2mysql( $servertimenow - $post_moderation_reminder_threshold );

// Statuses defined in this array should be notified. This should be configurable, but this is the default value.
$notify_statuses = get_visibility_statuses( 'moderation' );

// Select blogs where are posts awaiting moderation more then x ( = configured threshold ) hours
$SQL = new SQL();
$SQL->SELECT( 'DISTINCT cat_blog_ID' );
$SQL->FROM( 'T_categories' );
$SQL->FROM_add( 'INNER JOIN T_items__item ON post_main_cat_ID = cat_ID' );
$SQL->WHERE( 'post_status IN ('.$DB->quote( $notify_statuses ).')' );
$SQL->WHERE_and( 'post_datecreated < '.$DB->quote( $threshold_date ) );

$moderation_blogs = $DB->get_col( $SQL->get() );

if( empty( $moderation_blogs ) )
{ // There are no blogs where exists draft posts older then the threshold ( 24 hours by default )
	$result_message = sprintf( 'No posts have been awaiting moderation for more than %s.', seconds_to_period( $post_moderation_reminder_threshold ) );
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

// Select post moderators based on the blogs advanced user permissions
$bloguser_SQL = new SQL();
$bloguser_SQL->SELECT( 'bloguser_user_ID as user_ID, bloguser_blog_ID as blog_ID, bloguser_perm_poststatuses + 0 as perm_poststatuses, bloguser_perm_edit as perm_edit, bloguser_perm_edit + 0 AS perm_edit_num' );
$bloguser_SQL->FROM( 'T_coll_user_perms' );
$bloguser_SQL->FROM_add( 'LEFT JOIN T_blogs ON blog_ID = bloguser_blog_ID' );
$bloguser_SQL->WHERE( sprintf( $not_global_moderator, 'bloguser_user_ID' ) );
$bloguser_SQL->WHERE_and( 'blog_advanced_perms <> 0' );
$bloguser_SQL->WHERE_and( sprintf( $moderation_blogs_cond, 'bloguser_blog_ID' ) );
$bloguser_SQL->WHERE_and( 'bloguser_perm_poststatuses <> "" AND bloguser_perm_edit <> "no" AND bloguser_perm_edit <> "own"' );
// Select post moderators based on the blogs advanced group permissions
$bloggroup_SQL = new  SQL();
$bloggroup_SQL->SELECT( 'user_ID, bloggroup_blog_ID as blog_ID, bloggroup_perm_poststatuses + 0 as perm_poststatuses, bloggroup_perm_edit as perm_edit, bloggroup_perm_edit + 0 AS perm_edit_num' );
$bloggroup_SQL->FROM( 'T_users' );
$bloggroup_SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( bloggroup_group_ID = user_grp_ID
	OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = user_ID ) )' );
$bloggroup_SQL->FROM_add( 'LEFT JOIN T_blogs ON blog_ID = bloggroup_blog_ID' );
$bloggroup_SQL->WHERE( sprintf( $not_global_moderator, 'user_ID' ) );
$bloggroup_SQL->WHERE_and( 'blog_advanced_perms <> 0' );
$bloggroup_SQL->WHERE_and( sprintf( $moderation_blogs_cond, 'bloggroup_blog_ID' ) );
$bloggroup_SQL->WHERE_and( 'bloggroup_perm_poststatuses <> "" AND bloggroup_perm_edit <> "no" AND bloggroup_perm_edit <> "own"' );
// Get specific moderator users with their permissions in each different blog
$specific_blog_moderators = $DB->get_results( '( '.$bloguser_SQL->get().' ) UNION ( '.$bloggroup_SQL->get().' ) ORDER BY user_ID, blog_ID' );

// Highest edit level
$max_perm_edit = 'all';
// Highest value of the post statuses perm
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
	{	// Update user permissions on this collection:
		// perm_edit    : 'no', 'own', 'lt', 'le', 'all' (real value from DB)
		// perm_edit_num:  1,    2,     3,    4,    5    (index of the value from DB)
		if( $moderators[$row->user_ID][$row->blog_ID]['perm_edit_num'] < $row->perm_edit_num )
		{	// The user and the group advanced post edit perm for this user are not the same, keep the higher perm value:
			$moderators[$row->user_ID][$row->blog_ID]['perm_edit_num'] = intval( $row->perm_edit_num );
			$moderators[$row->user_ID][$row->blog_ID]['perm_edit'] = $row->perm_edit;
		}
		$current_perm_statuses = $moderators[$row->user_ID][$row->blog_ID]['perm_statuses'];
		$row_perm_status = intval( $row->perm_poststatuses );
		if( $current_perm_statuses != $row_perm_status )
		{ // The advanced user and the group post statuses perm for this user are not the same, the union of this perms must be accepted
			$moderators[$row->user_ID][$row->blog_ID]['perm_statuses'] = ( $current_perm_statuses | $row_perm_status );
		}
	}
	else
	{	// Initialize a new setting for the moderator per collection:
		$moderators[$row->user_ID][$row->blog_ID] = array(
				'perm_edit'     => $row->perm_edit,
				'perm_edit_num' => intval( $row->perm_edit_num ),
				'perm_statuses' => intval( $row->perm_poststatuses ),
			);
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
$def_send_moderation_reminder = $UserSettings->get( 'send_pst_moderation_reminder' );
if( $def_send_moderation_reminder )
{ // Send post moderation reminder is set by default
	$send_moderation_reminder_cond = '( ( uset_value IS NOT NULL AND uset_value <> \'0\' ) OR ( uset_value IS NULL ) )';
}
else
{ // Send post moderation reminder is NOT set by default
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
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID AND uset_name = "send_pst_moderation_reminder"' );
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
	$result_message = sprintf( 'Could not find any moderators wanting to receive post moderation notifications for the blogs that have posts pending moderation!' );
	return 1;
}

// load all required Blogs
$BlogCache = & get_BlogCache();
$BlogCache->load_list( $moderation_blogs );

// count all posts awaiting for moderation in the required blogs, group by blog/post_status/author_level
$SQL = new SQL();
$SQL->SELECT( 'cat_blog_ID as blog_ID, post_status, user_level as author_level, count( post_ID ) as post_count' );
$SQL->FROM( 'T_items__item' );
$SQL->FROM_add( 'LEFT JOIN T_users ON user_ID = post_creator_user_ID' );
$SQL->FROM_add( 'LEFT JOIN T_categories ON cat_ID = post_main_cat_ID' );
$SQL->WHERE( 'post_status IN ( '.$DB->quote( $notify_statuses ).' )' );
$SQL->WHERE_and( 'cat_blog_ID IN ('.implode( ',', $moderation_blogs ).')' );
$SQL->GROUP_BY( 'cat_blog_ID, post_status, author_level' );
$blog_posts = $DB->get_results( $SQL->get() );

// Create a posts map by blog_ID:post_status:author_level:count. This way it will be much easier to get allowed posts for a specific permission
$posts_map = array();
$last_blog_ID = NULL;
foreach( $blog_posts as $row )
{
	if( $last_blog_ID != $row->blog_ID )
	{
		$Collection = $Blog = & $BlogCache->get_by_ID( $row->blog_ID );
		$blog_moderation_statuses = $Blog->get_setting( 'post_moderation_statuses' );
		$last_blog_ID = $row->blog_ID;
	}
	if( strpos( $blog_moderation_statuses, $row->post_status ) === false )
	{ // This status shouldn't be notified on this blog
		continue;
	}
	if( isset( $posts_map[$row->blog_ID] ) )
	{ // This blog posts were already initialized
		if( isset( $posts_map[$row->blog_ID][$row->post_status] ) )
		{ // Comments with this status were already initialized
			// Set new author level filter for this status and the corresponding amount of posts
			$posts_map[$row->blog_ID][$row->post_status][$row->author_level] = $row->post_count;
		}
		else
		{ // Initialize blog posts with this status, and set the first element
			$posts_map[$row->blog_ID][$row->post_status] = array( $row->author_level => $row->post_count );
		}
	}
	else
	{ // Initialize blog array, and set the first element
		$posts_map[$row->blog_ID] = array( $row->post_status => array( $row->author_level => $row->post_count ) );
	}
}

$mail_sent = 0;
$params = array();

// Collect posts data for global moderators
$moderator_posts = array();
foreach( $moderation_blogs as $blog_ID )
{ // Collect the number of posts from all blogs
	if( !isset( $posts_map[$blog_ID] ) )
	{ // this blog doesn't contains posts awaiting moderation which statuses corresponds to this blog moderation statutes
		continue;
	}
	$post_count = 0;
	foreach( $posts_map[$blog_ID] as $status => $content )
	{ // collect the number of posts with all statuses
		foreach( $content as $level => $count )
		{ // collect the number of posts with all kind of users
			$post_count += $count;
		}
	}
	$moderator_posts[$blog_ID] = $post_count;
}

foreach( $loaded_ids as $moderator_ID )
{ // Loop trhough each moderators and send post moderation emails if it is required
	$moderator_User = $UserCache->get_by_ID( $moderator_ID );
	$blog_posts = array();
	if( in_array( $moderator_ID, $global_moderators ) )
	{ // This is a global moderator user
		$blog_posts = $moderator_posts;
	}
	else
	{ // This moderator user may have different permission on different blogs, collect the posts corresponding to the perms
		foreach( $moderators[$moderator_ID] as $blog_ID => $perms )
		{
			if( !isset( $posts_map[$blog_ID] ) )
			{ // this blog doesn't contains posts awaiting moderation which statuses corresponds to this blog moderation statutes
				continue;
			}
			$post_count = 0;
			foreach( $posts_map[$blog_ID] as $status => $content )
			{
				$status_perm_value = get_status_permvalue( $status );
				if( $perms['perm_statuses'] & $status_perm_value )
				{ // User has permission to edit posts with this status
					// TODO asimo> Here probably we should also check if user is able to deprecate/recycle the post.
					// Check if User has permission to raise post status
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
							{ // User has permission to a higher status, so the post status can be raised
								$raise_status_allowed = true;
								break;
							}
						}
					}
					if( !$raise_status_allowed )
					{ // User is not allowed to raise these post statuses
						continue;
					}
					// Check if the post author level allows the edit permission:
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
						{ // User has permission to edit posts with this author level
							$post_count += $count;
						}
					}
				}
			}
			if( $post_count > 0 )
			{ // There are posts awaiting moderation on this blog and user has permission to moderate
				$blog_posts[$blog_ID] = $post_count;
			}
		}
	}

	if( empty( $blog_posts ) )
	{ // There are no posts awaiting moderation that this user could moderate
		continue;
	}

	$params['blogs'] = array_keys( $blog_posts ); // This can be remvoved if this solution will remain
	$params['posts'] = $blog_posts;

	// Change locale here to localize the email subject and content
	locale_temp_switch( $moderator_User->get( 'locale' ) );
	if( send_mail_to_User( $moderator_ID, T_( 'Post moderation reminder' ), 'posts_unmoderated_reminder', $params, false ) )
	{
		$mail_sent++;
	}
	locale_restore_previous();
}

$result_message = sprintf( '%d moderators have been notified!', $mail_sent );
return 1; /*OK*/
?>