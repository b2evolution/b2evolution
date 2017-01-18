<?php
/**
 * This file implements the monthly alert on old contents
 *
 * @author fplanque: François PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $UserSettings;

// Check if UserSettings exists because it must be initialized before email sending:
if( empty( $UserSettings ) )
{	// initialize UserSettings, because in CLI mode is not initialized yet:
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

// Select collections where we should find old posts to alert for moderators:
$SQL = new SQL( 'Select collections which alert on old contents' );
$SQL->SELECT( 'T_blogs.*' );
$SQL->FROM( 'T_blogs' );
$SQL->FROM_add( 'INNER JOIN T_coll_settings ON blog_ID = cset_coll_ID' );
$SQL->WHERE( 'cset_name = "old_content_alert"' );
$SQL->WHERE_and( 'cset_value >= 1' );
$SQL->WHERE_and( 'cset_value <= 12' );

// Load collections by above SQL:
$BlogCache = & get_BlogCache();
$BlogCache->clear( true );
$BlogCache->load_by_sql( $SQL );

if( empty( $BlogCache->cache ) )
{	// There are no collections where we should find old posts to alert for moderators
	$result_message = 'No collection with alert on old contents.';
	return 1;
}

$alert_coll_IDs = array();
foreach( $BlogCache->cache as $alert_Blog )
{
	$alert_coll_IDs[] = $alert_Blog->ID;
}
$alert_colls_cond = '%s IN ( '.implode( ',', $alert_coll_IDs ).' )';

// Select global moderators:
$SQL = new SQL( 'Get global moderators of all collections' );
$SQL->SELECT( 'user_ID' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'LEFT JOIN T_groups ON grp_ID = user_grp_ID' );
$SQL->WHERE( 'grp_perm_blogs = "editall"' );
$global_moderator_IDs = $DB->get_col( $SQL->get(), 0, $SQL->title );

$not_global_moderators_cond = ( count( $global_moderator_IDs ) ) ? '%s NOT IN ( '.implode( ',', $global_moderator_IDs ).' )' : NULL;

// Select collection owners, because they are moderators in their own collections:
$SQL = new SQL( 'Get collection owners with their collection IDs' );
$SQL->SELECT( 'blog_owner_user_ID, GROUP_CONCAT( DISTINCT cast(blog_ID as CHAR) ORDER BY blog_ID SEPARATOR \',\') as blogs' );
$SQL->FROM( 'T_blogs' );
$SQL->WHERE( sprintf( $not_global_moderators_cond, 'blog_owner_user_ID' ) );
$SQL->WHERE_and( sprintf( $alert_colls_cond, 'blog_ID' ) );
$SQL->GROUP_BY( 'blog_owner_user_ID' );
$coll_owners = $DB->get_assoc( $SQL->get(), $SQL->title );
foreach( $coll_owners as $coll_owner_ID => $coll_IDs )
{
	$coll_owners[ $coll_owner_ID ] = explode( ',', $coll_IDs );
}

// Alert only on posts with the following statuses:
$alert_post_statuses = array( 'published', 'community', 'protected' );
$alert_post_statuses_cond = '';
foreach( $alert_post_statuses as $i => $alert_post_status )
{
	$alert_post_statuses_cond .= 'FIND_IN_SET( "'.$alert_post_status.'", $field_name$ ) > 0';
	if( $i < count( $alert_post_statuses ) - 1 )
	{
		$alert_post_statuses_cond .= ' OR ';
	}
}

// Select post moderators based on the collection advanced user permissions:
$bloguser_SQL = new SQL();
$bloguser_SQL->SELECT( 'bloguser_user_ID AS user_ID, bloguser_blog_ID AS blog_ID, bloguser_perm_poststatuses + 0 AS perm_poststatuses, bloguser_perm_edit AS perm_edit, bloguser_perm_edit + 0 AS perm_edit_num' );
$bloguser_SQL->FROM( 'T_coll_user_perms' );
$bloguser_SQL->FROM_add( 'LEFT JOIN T_blogs ON blog_ID = bloguser_blog_ID' );
$bloguser_SQL->WHERE( 'blog_advanced_perms <> 0' );
$bloguser_SQL->WHERE_and( sprintf( $not_global_moderators_cond, 'bloguser_user_ID' ) );
$bloguser_SQL->WHERE_and( sprintf( $alert_colls_cond, 'bloguser_blog_ID' ) );
$bloguser_SQL->WHERE_and( 'bloguser_perm_edit <> "no"' );
$bloguser_SQL->WHERE_and( str_replace( '$field_name$', 'bloguser_perm_poststatuses', $alert_post_statuses_cond ) );
// Select post moderators based on the collection advanced group permissions:
$bloggroup_SQL = new  SQL();
$bloggroup_SQL->SELECT( 'user_ID, bloggroup_blog_ID AS blog_ID, bloggroup_perm_poststatuses + 0 AS perm_poststatuses, bloggroup_perm_edit AS perm_edit, bloggroup_perm_edit + 0 AS perm_edit_num' );
$bloggroup_SQL->FROM( 'T_users' );
$bloggroup_SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( bloggroup_group_ID = user_grp_ID
	OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = user_ID ) )' );
$bloggroup_SQL->FROM_add( 'LEFT JOIN T_blogs ON blog_ID = bloggroup_blog_ID' );
$bloggroup_SQL->WHERE( 'blog_advanced_perms <> 0' );
$bloggroup_SQL->WHERE_and( sprintf( $not_global_moderators_cond, 'user_ID' ) );
$bloggroup_SQL->WHERE_and( sprintf( $alert_colls_cond, 'bloggroup_blog_ID' ) );
$bloggroup_SQL->WHERE_and( 'bloggroup_perm_edit <> "no"' );
$bloggroup_SQL->WHERE_and( str_replace( '$field_name$', 'bloggroup_perm_poststatuses', $alert_post_statuses_cond ) );
// Get specific moderators with their permissions in each different collection:
$specific_coll_moderators = $DB->get_results( '( '.$bloguser_SQL->get().' ) UNION ( '.$bloggroup_SQL->get().' ) ORDER BY user_ID, blog_ID', OBJECT,
	'Select post moderators based on the collection advanced user and group permissions' );

// Create a general moderators array:
$moderators = array();
foreach( $specific_coll_moderators as $row )
{	// Loop through each different collection's moderator users and their permissions:
	if( ! isset( $moderators[$row->user_ID] ) )
	{	// Initialize user perm array:
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
		{	// The advanced user and the group post statuses perm for this user are not the same, the union of this perms must be accepted:
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

// Select blocked and spam email addresses to prevent sending emails to them:
$blocked_emails = $DB->get_col( 'SELECT emadr_address FROM T_email__address WHERE '.get_mail_blocked_condition() );

// load all required Users ( global moderators, blog owners and users with advanced blog perms )
$all_required_user_IDs = array_unique( array_merge( $global_moderator_IDs, array_keys( $coll_owners ), array_keys( $moderators ) ) );
$SQL = new SQL( 'Get all users for alert on old contents' );
$SQL->SELECT( '*' );
$SQL->FROM( 'T_users' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID AND uset_name = "send_pst_stale_alert"' );
$SQL->WHERE( 'user_ID IN ('.implode( ',', $all_required_user_IDs ).')' );
$SQL->WHERE_and( 'LENGTH( TRIM( user_email ) ) > 0' );
// Set notify moderation condition:
if( $UserSettings->get( 'send_pst_stale_alert' ) )
{	// A send stale posts alerter is set by default
	$SQL->WHERE_and( '( ( uset_value IS NOT NULL AND uset_value <> \'0\' ) OR ( uset_value IS NULL ) )' );
}
else
{	// A send stale posts alerter is NOT set by default
	$SQL->WHERE_and( '( uset_value IS NOT NULL AND uset_value <> \'0\' )' );
}
if( count( $blocked_emails ) )
{	// Restrict users by blocked email addresses:
	$SQL->WHERE_and( 'user_email NOT IN ( "'.implode( '","', $blocked_emails ).'" )' );
}

// Load moderator users who would like to get notificaions
$UserCache = & get_UserCache();
$UserCache->clear( true );
$UserCache->load_by_sql( $SQL );

if( empty( $UserCache->cache ) )
{	// UserCache result is empty which means nobody wants to receive alert on old contents:
	$result_message = 'Could not find any moderators wanting to receive post moderation notifications for the blogs that have stale posts!';
	return 1;
}

$mail_moderator_sent = array();
$mail_coll_sent = 0;
foreach( $BlogCache->cache as $alert_Blog )
{
	// Get all old posts of the collection to alert for moderators:
	$SQL = new SQL( 'Get all old posts of the collection '.$alert_Blog->ID.' to alert for moderators' );
	$SQL->SELECT( 'post_ID, post_status, user_level as author_level, post_creator_user_ID' );
	$SQL->FROM( 'T_items__item' );
	$SQL->FROM_add( 'INNER JOIN T_users ON user_ID = post_creator_user_ID' );
	$SQL->FROM_add( 'INNER JOIN T_categories ON cat_ID = post_main_cat_ID' );
	$SQL->WHERE( 'cat_blog_ID = '.$alert_Blog->ID );
	$SQL->WHERE_and( 'post_status IN ( '.$DB->quote( $alert_post_statuses ).' )' );
	$x_months_ago = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, date( 'n' ) - $alert_Blog->get_setting( 'old_content_alert' ) ) );
	$SQL->WHERE_and( 'post_datemodified < '.$DB->quote( $x_months_ago ) );
	$old_posts = $DB->get_results( $SQL->get(), OBJECT, $SQL->title );

	if( empty( $old_posts ) )
	{	// No old posts on the collection:
		continue;
	}

	foreach( $UserCache->cache as $alert_User )
	{
		$moderator_old_posts = array();
		if( in_array( $alert_User->ID, $global_moderator_IDs ) ||
		    ( isset( $coll_owners[ $alert_User->ID ] ) && in_array( $alert_Blog->ID, $coll_owners[ $alert_User->ID ] ) ) )
		{	// Allow to send alert on all old post if the user is a global moderator or he is an owner of the collection:
			foreach( $old_posts as $old_post )
			{
				$moderator_old_posts[] = $old_post->post_ID;
			}
		}
		elseif( isset( $moderators[ $alert_User->ID ][ $alert_Blog->ID ] ) )
		{	// Check what post the moderator can edit:
			$perms = $moderators[ $alert_User->ID ][ $alert_Blog->ID ];
			foreach( $old_posts as $old_post )
			{
				$status_perm_value = get_status_permvalue( $old_post->post_status );
				if( $perms['perm_statuses'] & $status_perm_value )
				{	// If user has permission to edit posts with this status
					// Check if the post author level allows the edit permission:
					switch( $perms['perm_edit'] )
					{
						case 'all':
							$allowed = true;
							break;
						case 'le':
							$allowed = ( $old_post->author_level <= $alert_User->level );
							break;
						case 'lt':
							$allowed = ( $old_post->author_level < $alert_User->level );
							break;
						case 'own':
							$allowed = ( $old_post->post_creator_user_ID == $alert_User->ID );
							break;
						default:
							$allowed = false;
					}
					if( $allowed )
					{	// User has permission to edit the post with this author level:
						$moderator_old_posts[] = $old_post->post_ID;
					}
				}
			}
		}

		if( empty( $moderator_old_posts ) )
		{	// The user has no old posts to be alerted:
			continue;
		}

		$email_params = array(
				'months' => intval( $alert_Blog->get_setting( 'old_content_alert' ) ),
				'posts'  => $moderator_old_posts,
			);

		// Change locale here to localize the email subject and content:
		locale_temp_switch( $alert_User->get( 'locale' ) );

		// Send one email message for each moderator per collection:
		if( send_mail_to_User( $alert_User->ID, sprintf( T_('Stale contents found in %s'), $alert_Blog->get( 'shortname' ) ), 'posts_stale_alert', $email_params, false ) )
		{
			$mail_moderator_sent[ $alert_User->ID ] = 0;
		}

		// Restore locale:
		locale_restore_previous();
	}

	$mail_coll_sent++;
}

if( count( $mail_moderator_sent ) )
{
	$result_message = sprintf( '%s moderators have been alerted on old contents of %s collections!', count( $mail_moderator_sent ), $mail_coll_sent );
}
else
{
	$result_message = 'No moderators have been alerted on old contents!';
}

return 1; /*OK*/
?>