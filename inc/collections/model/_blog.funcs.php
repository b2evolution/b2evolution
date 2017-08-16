<?php
/**
 * This file implements Blog handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Update the advanced user/group permissions for edited blog
 *
 * @param integer Blog ID or Group ID
 * @param string 'user', 'group' or 'coll'
 */
function blog_update_perms( $object_ID, $context = 'user' )
{
	global $DB;

	/**
	 * @var User
	 */
	global $current_User;

	// Get affected user/group IDs:
	$IDs = param( $context.'_IDs', '/^[0-9]+(,[0-9]+)*$/', '' );
	$ID_array = explode( ',', $IDs );

	switch( $context )
	{
		case 'user':
			$table = 'T_coll_user_perms';
			$prefix = 'bloguser_';
			$ID_field_main = 'bloguser_blog_ID';
			$ID_field_edit = 'bloguser_user_ID';
			$blog = $object_ID;
			$coll_IDs = array( $blog );
			break;

		case 'coll':
			$table = 'T_coll_group_perms';
			$prefix = 'bloggroup_';
			$ID_field_main = 'bloggroup_group_ID';
			$ID_field_edit = 'bloggroup_blog_ID';
			$group_ID = $object_ID;
			$coll_IDs = $ID_array;
			break;

		case 'group':
		default:
			$table = 'T_coll_group_perms';
			$prefix = 'bloggroup_';
			$ID_field_main = 'bloggroup_blog_ID';
			$ID_field_edit = 'bloggroup_group_ID';
			$blog = $object_ID;
			$coll_IDs = array( $blog );
			break;
	}

	foreach( $coll_IDs as $coll_ID )
	{
		// Can the current user touch advanced admin permissions?
		if( ! $current_User->check_perm( 'blog_admin', 'edit', false, $coll_ID ) )
		{ // We have no permission to touch advanced admins!

			// Get the users/groups which are advanced admins
			$admins_ID_array = $DB->get_col( "SELECT {$ID_field_edit}
																					FROM $table
																				 WHERE {$ID_field_edit} IN (".implode( ',',$ID_array ).")
																								AND {$ID_field_main} = $object_ID
																								AND {$prefix}perm_admin <> 0" );

			// Take the admins out of the list:
			$ID_array = array_diff( $ID_array, $admins_ID_array );
		}
	}

	if( empty( $ID_array ) )
	{
		return;
	}

	// Delete old perms for the edited collection/group:
	$DB->query( "DELETE FROM $table
								WHERE {$ID_field_edit} IN (".implode( ',',$ID_array ).")
											AND {$ID_field_main} = ".$object_ID );

	$inserted_values = array();
	foreach( $ID_array as $loop_ID )
	{ // Check new permissions for each user:

		// Get collection/object ID depedning on request:
		$coll_ID = ( $context == 'coll' ? $loop_ID : $blog );
		$main_object_ID = ( $context == 'coll' ? $group_ID : $blog );

		// Use checkboxes
		$perm_post = array();

		$ismember = param( 'blog_ismember_'.$loop_ID, 'integer', 0 );
		$can_be_assignee = param( 'blog_can_be_assignee_'.$loop_ID, 'integer', 0 );

		$perm_published = param( 'blog_perm_published_'.$loop_ID, 'string', '' );
		if( !empty($perm_published) ) $perm_post[] = 'published';

		$perm_community = param( 'blog_perm_community_'.$loop_ID, 'string', '' );
		if( !empty($perm_community) ) $perm_post[] = 'community';

		$perm_protected = param( 'blog_perm_protected_'.$loop_ID, 'string', '' );
		if( !empty($perm_protected) ) $perm_post[] = 'protected';

		$perm_private = param( 'blog_perm_private_'.$loop_ID, 'string', '' );
		if( !empty($perm_private) ) $perm_post[] = 'private';

		$perm_review = param( 'blog_perm_review_'.$loop_ID, 'string', '' );
		if( !empty($perm_review) ) $perm_post[] = 'review';

		$perm_draft = param( 'blog_perm_draft_'.$loop_ID, 'string', '' );
		if( !empty($perm_draft) ) $perm_post[] = 'draft';

		$perm_deprecated = param( 'blog_perm_deprecated_'.$loop_ID, 'string', '' );
		if( !empty($perm_deprecated) ) $perm_post[] = 'deprecated';

		$perm_redirected = param( 'blog_perm_redirected_'.$loop_ID, 'string', '' );
		if( !empty($perm_redirected) ) $perm_post[] = 'redirected';

		$perm_item_type = param( 'blog_perm_item_type_'.$loop_ID, 'string', 'standard' );
		$perm_edit = param( 'blog_perm_edit_'.$loop_ID, 'string', 'no' );

		$perm_delpost = param( 'blog_perm_delpost_'.$loop_ID, 'integer', 0 );
		$perm_edit_ts = param( 'blog_perm_edit_ts_'.$loop_ID, 'integer', 0 );

		$perm_delcmts = param( 'blog_perm_delcmts_'.$loop_ID, 'integer', 0 );
		$perm_recycle_owncmts = param( 'blog_perm_recycle_owncmts_'.$loop_ID, 'integer', 0 );
		$perm_vote_spam_comments = param( 'blog_perm_vote_spam_cmts_'.$loop_ID, 'integer', 0 );
		$perm_cmtstatuses = 0;
		$perm_cmtstatuses += param( 'blog_perm_published_cmt_'.$loop_ID, 'integer', 0 ) ? get_status_permvalue( 'published' ) : 0;
		$perm_cmtstatuses += param( 'blog_perm_community_cmt_'.$loop_ID, 'integer', 0 ) ? get_status_permvalue( 'community' ) : 0;
		$perm_cmtstatuses += param( 'blog_perm_protected_cmt_'.$loop_ID, 'integer', 0 ) ? get_status_permvalue( 'protected' ) : 0;
		$perm_cmtstatuses += param( 'blog_perm_private_cmt_'.$loop_ID, 'integer', 0 ) ? get_status_permvalue( 'private' ) : 0;
		$perm_cmtstatuses += param( 'blog_perm_review_cmt_'.$loop_ID, 'integer', 0 ) ? get_status_permvalue( 'review' ) : 0;
		$perm_cmtstatuses += param( 'blog_perm_draft_cmt_'.$loop_ID, 'integer', 0 ) ? get_status_permvalue( 'draft' ) : 0;
		$perm_cmtstatuses += param( 'blog_perm_deprecated_cmt_'.$loop_ID, 'integer', 0 ) ? get_status_permvalue( 'deprecated' ) : 0;
		$perm_edit_cmt = param( 'blog_perm_edit_cmt_'.$loop_ID, 'string', 'no' );

		$perm_meta_comments = param( 'blog_perm_meta_comment_'.$loop_ID, 'integer', 0 );
		$perm_cats = param( 'blog_perm_cats_'.$loop_ID, 'integer', 0 );
		$perm_properties = param( 'blog_perm_properties_'.$loop_ID, 'integer', 0 );

		if( $current_User->check_perm( 'blog_admin', 'edit', false, $coll_ID ) )
		{ // We have permission to give advanced admins perm!
			$perm_admin = param( 'blog_perm_admin_'.$loop_ID, 'integer', 0 );
		}
		else
		{
			$perm_admin = 0;
		}

		$perm_media_upload = param( 'blog_perm_media_upload_'.$loop_ID, 'integer', 0 );
		$perm_media_browse = param( 'blog_perm_media_browse_'.$loop_ID, 'integer', 0 );
		$perm_media_change = param( 'blog_perm_media_change_'.$loop_ID, 'integer', 0 );

		$perm_analytics = param( 'blog_perm_analytics_'.$loop_ID, 'integer', 0 );

		// Update those permissions in DB:

		if( $ismember || $can_be_assignee || count($perm_post) || $perm_delpost || $perm_edit_ts || $perm_delcmts || $perm_recycle_owncmts || $perm_vote_spam_comments || $perm_cmtstatuses ||
			$perm_meta_comments || $perm_cats || $perm_properties || $perm_admin || $perm_media_upload || $perm_media_browse || $perm_media_change || $perm_analytics )
		{ // There are some permissions for this user:
			$ismember = 1;	// Must have this permission

			// insert new perms:
			$inserted_values[] = " ( $main_object_ID, $loop_ID, $ismember, $can_be_assignee, ".$DB->quote( implode( ',',$perm_post ) ).",
																".$DB->quote( $perm_item_type ).", ".$DB->quote( $perm_edit ).",
																$perm_delpost, $perm_edit_ts, $perm_delcmts, $perm_recycle_owncmts, $perm_vote_spam_comments, $perm_cmtstatuses,
																".$DB->quote( $perm_edit_cmt ).",
																$perm_meta_comments, $perm_cats, $perm_properties, $perm_admin, $perm_media_upload,
																$perm_media_browse, $perm_media_change, $perm_analytics )";
		}
	}

	// Proceed with insertions:
	if( count( $inserted_values ) )
	{
		$DB->query( "INSERT INTO $table( {$ID_field_main}, {$ID_field_edit}, {$prefix}ismember, {$prefix}can_be_assignee,
											{$prefix}perm_poststatuses, {$prefix}perm_item_type, {$prefix}perm_edit, {$prefix}perm_delpost, {$prefix}perm_edit_ts,
											{$prefix}perm_delcmts, {$prefix}perm_recycle_owncmts, {$prefix}perm_vote_spam_cmts, {$prefix}perm_cmtstatuses, {$prefix}perm_edit_cmt,
											{$prefix}perm_meta_comment, {$prefix}perm_cats, {$prefix}perm_properties, {$prefix}perm_admin,
											{$prefix}perm_media_upload, {$prefix}perm_media_browse, {$prefix}perm_media_change, {$prefix}perm_analytics )
									VALUES ".implode( ',', $inserted_values ) );
	}

	// Unassign users that no longer can be assignees from the items of the collection:
	$DB->query( 'UPDATE T_items__item
			SET post_assigned_user_ID = NULL
		WHERE post_main_cat_ID IN
		  (
		    SELECT cat_ID
		      FROM T_categories
		     WHERE cat_blog_ID IN ( '.$DB->quote( $coll_IDs ).' )
		  )
		  AND post_assigned_user_ID NOT IN
		  (
		    SELECT bloguser_user_ID
		      FROM T_coll_user_perms
		     WHERE bloguser_can_be_assignee = 1
		       AND bloguser_blog_ID IN ( '.$DB->quote( $coll_IDs ).' )
		  )
		  AND post_assigned_user_ID NOT IN
		  (
		    SELECT user_ID
		      FROM T_users INNER JOIN T_coll_group_perms ON user_grp_ID = bloggroup_group_ID
		     WHERE bloggroup_can_be_assignee = 1
		       AND bloggroup_blog_ID IN ( '.$DB->quote( $coll_IDs ).' )
		  )
		  AND post_assigned_user_ID NOT IN
		  (
		    SELECT sug_user_ID
		      FROM T_users__secondary_user_groups
		     INNER JOIN T_coll_group_perms ON sug_grp_ID = bloggroup_group_ID
		     WHERE bloggroup_can_be_assignee = 1
		       AND bloggroup_blog_ID IN ( '.$DB->quote( $coll_IDs ).' )
		  )' );

	if( $DB->rows_affected > 0 )
	{
		global $Messages;
		$Messages->add( sprintf( '%d tasks have lost their assignee due to new permissions (this may include fixes to older inconsistencies in the DB).', $DB->rows_affected ), 'warning' );
	}

	// BLOCK CACHE INVALIDATION:
	foreach( $coll_IDs as $coll_ID )
	{
		BlockCache::invalidate_key( 'set_coll_ID', $coll_ID ); // Settings have changed
	}
	BlockCache::invalidate_key( 'set_coll_ID', 'any' ); // Settings of a have changed (for widgets tracking a change on ANY blog)

	// cont_coll_ID  // Content has not changed
}


/**
 * Check permissions on a given blog (by ID) and autoselect an appropriate blog
 * if necessary.
 *
 * For use in admin
 *
 * NOTE: we no longer try to set $Blog inside of the function because later global use cannot be safely guaranteed in PHP4.
 *
 * @param string Permission name that must be given to the {@link $current_User} object.
 * @param string Permission level that must be given to the {@link $current_User} object.
 * @return integer new selected blog
 */
function autoselect_blog( $permname, $permlevel = 'any' )
{
	global $blog;

  /**
	 * @var User
	 */
	global $current_User;

	$autoselected_blog = $blog;

	if( $autoselected_blog )
	{ // a blog is already selected
		if( !$current_User->check_perm( $permname, $permlevel, false, $autoselected_blog ) )
		{ // invalid blog
		 	// echo 'current blog was invalid';
			$autoselected_blog = 0;
		}
	}

	if( !$autoselected_blog )
	{ // No blog is selected so far (or selection was invalid)...
		// Let's try to find another one:

    /**
		 * @var BlogCache
		 */
		$BlogCache = & get_BlogCache();

		// Get first suitable blog
		$blog_array = $BlogCache->load_user_blogs( $permname, $permlevel, $current_User->ID, 'ID', 'ASC', 1 );
		if( !empty($blog_array) )
		{
			$autoselected_blog = $blog_array[0];
		}
	}

	return $autoselected_blog;
}


/**
 * Check that we have received a valid blog param
 *
 * For use in admin
 */
function valid_blog_requested()
{
	global $Collection, $Blog, $Messages;
	if( empty( $Blog ) )
	{ // The requested blog does not exist, Try to get other available blog for the current User
		$blog_ID = get_working_blog();
		if( $blog_ID )
		{
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}
	}

	if( empty( $Blog ) )
	{ // The requested blog does not exist
		$Messages->add( T_('The requested blog does not exist (any more?)'), 'error' );
		return false;
	}
	return true;
}


/**
 * Get working blog
 *
 * For use in backoffice
 *
 * @return integer|FALSE Blog ID or FALSE when no available blog to view by current User
 */
function get_working_blog()
{
	global $blog, $current_User, $UserSettings;

	if( ! is_logged_in() )
	{ // User must be logged in to view the blogs
		return false;
	}

	if( ! empty( $blog ) )
	{ // Use a blog from GET request
		$blog_ID = intval( $blog );
		if( $blog_ID > 0 && $current_User->check_perm( 'blog_ismember', 'view', false, $blog_ID ) )
		{ // Allow to use this blog only when current user has an access to view it
			return $blog_ID;
		}
	}

	$BlogCache = & get_BlogCache();

	// Try to use the blog which is selected by current user last time
	$blog_ID = intval( $UserSettings->get( 'selected_blog' ) );
	// Check if it really exists in DB
	$selected_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
	if( $selected_Blog && $current_User->check_perm( 'blog_ismember', 'view', false, $selected_Blog->ID ) )
	{ // Allow to use this blog only when current user is a member of it
		return $blog_ID;
	}

	// Use first blog from DB which current user can views
	if( $blog_array = $BlogCache->load_user_blogs( 'blog_ismember', 'view', NULL, '', '', 1 ) )
	{
		foreach( $blog_array as $blog_ID )
		{
			return $blog_ID;
		}
	}

	return false;
}


/**
 * Set working blog to a new value and memorize it in user settings if needed.
 *
 * For use in backoffice
 *
 * @return boolean $blog changed?
 */
function set_working_blog( $new_blog_ID )
{
	global $blog, $UserSettings;

	if( $new_blog_ID != (int)$UserSettings->get('selected_blog') )
	{ // Save the new default blog.
		// fp> Test case 1: dashboard without a blog param should go to last selected blog
		// fp> Test case 2: uploading to the default blog may actually upload into another root (sev)
		$UserSettings->set( 'selected_blog', $blog );
		$UserSettings->dbupdate();
	}

	if( $new_blog_ID == $blog )
	{
		return false;
	}

	$blog = $new_blog_ID;

	return true;
}


/**
 * Get collection kinds
 * (might be extended by plugins)
 *
 * @param string Blog type
 * @return array|string
 */
function get_collection_kinds( $kind = NULL )
{
	global $Plugins;

	$kinds = array(
		'main' => array(
				'name' => T_('Home / Main'),
				'class' => 'btn-primary',
				'desc' => T_('A collection optimized to be used as a site homepage and/or for generic functions such as messaging, user profiles, etc.'),
				'note' => T_('Besides displaying a nice homepage, this can also be used as a central home for cross-collection features such as private messaging, user profile editing, etc.'),
			),
		'std' => array(
				'name' => T_('Blog'), // NOTE: this is a REAL usage of the word 'Blog'. Do NOT change to 'collection'.
				'class' => 'btn-info',
				'desc' => T_('A collection optimized to be used as a standard blog (with the most common features).'),
				'note' => T_('Many users start with a blog and add other features later.'),
			),
		'photo' => array(
				'name' => T_('Gallery'),
				'desc' => T_('A collection optimized for publishing photo albums.'),
				'note' => T_('Use this if you want to publish images without much text.'),
			),
		'forum' => array(
				'name' => T_('Forum'),
				'desc' => T_('A collection optimized to be used as a forum. (This should be used with a forums skin)'),
				'note' => T_('Use this if you want a place for your user community to interact.'),
			),
		'manual' => array(
				'name' => T_('Manual'),
				'desc' => T_('A collection optimized to be used as an online manual, book or guide. (This should be used with a manual skin)'),
				'note' => T_('Use this if you want to publish organized information with chapters, sub-chapters, etc.'),
			),
		'group' => array(
				'name' => T_('Tracker'),
				'desc' => T_('A collection optimized for issue tracking or collaborative editing. Look for the workflow properties on the post editing form.'),
				'note' => T_('Use this if several users need to collaborate on resolving issues or publishing articles...'),
			),
		);

	// Define blog kinds, their names and description.
	$plugin_kinds = $Plugins->trigger_collect( 'GetCollectionKinds', array('kinds' => & $kinds) );

	foreach( $plugin_kinds as $l_kinds )
	{
		$kinds = array_merge( $l_kinds, $kinds );
	}

	if( is_null( $kind ) )
	{ // Return kinds array
		return $kinds;
	}

	if( array_key_exists( $kind, $kinds ) && !empty($kinds[$kind]['name']) )
	{
		return $kinds[$kind]['name'];
	}
	else
	{	// Use default collection kind
		return $kinds['std']['name'];
	}
}


/**
 * Enable/Disable the given cache
 *
 * @param string cache key name, 'general_cache_enabled', blogs 'cache_enabled'
 * @param boolean status to set
 * @param integer the id of the blog, if we want to set a blog's cache. Let it NULL to set general caching.
 * @param boolean true to save db changes, false if db update will be called outside from this function
 */
function set_cache_enabled( $cache_key, $new_status, $coll_ID = NULL, $save_setting = true )
{
	load_class( '_core/model/_pagecache.class.php', 'PageCache' );
	global $Settings;

	if( empty( $coll_ID ) )
	{ // general cache
		$Collection = $Blog = NULL;
		$old_cache_status = $Settings->get( $cache_key );
	}
	else
	{ // blog page cache
		$BlogCache = & get_BlogCache();
		$Collection = $Blog = $BlogCache->get_by_ID( $coll_ID );
		$old_cache_status = $Blog->get_setting( $cache_key );
	}

	$PageCache = new PageCache( $Blog );
	if( $old_cache_status == false && $new_status == true )
	{ // Caching has been turned ON:
		if( $PageCache->cache_create( false ) )
		{ // corresponding cache folder was created
			if( empty( $coll_ID ) )
			{ // general cache
				$result = array( 'success', T_( 'General caching has been enabled.' ) );
			}
			else
			{ // blog page cache
				$result = array( 'success', T_( 'Page caching has been enabled.' ) );
			}
		}
		else
		{ // error creating cache folder
			if( empty( $coll_ID ) )
			{ // general cache
				$result = array( 'error', T_( 'General caching could not be enabled. Check /cache/ folder file permissions.' ) );
			}
			else
			{ // blog page cache
				$result = array( 'error', T_( 'Page caching could not be enabled. Check /cache/ folder file permissions.' ) );
			}
			$new_status = false;
		}
	}
	elseif( $old_cache_status == true && $new_status == false )
	{ // Caching has been turned OFF:
		$PageCache->cache_delete();
		if( empty( $coll_ID ) )
		{ // general cache
			$result = array( 'note',  T_( 'General caching has been disabled. Cache contents have been purged.' ) );
		}
		else
		{ // blog page cache
			$result = array( 'note',  T_( 'Page caching has been disabled. Cache contents have been purged.' ) );
		}
	}
	else
	{ // nothing was changed
		// check if ajax_form_enabled has correct state after b2evo upgrade
		if( ( $Blog != NULL ) && ( $new_status ) && ( !$Blog->get_setting( 'ajax_form_enabled' ) ) )
		{ // if page cache is enabled, ajax form must be enabled to
			$Blog->set_setting( 'ajax_form_enabled', true );
			$Blog->dbupdate();
		}
		return NULL;
	}

	// set db changes
	if( $Blog == NULL )
	{
		$Settings->set( 'general_cache_enabled', $new_status );
		if( $save_setting )
		{ // save
			$Settings->dbupdate();
		}
	}
	else
	{
		$Blog->set_setting( $cache_key, $new_status );
		if( ( $cache_key == 'cache_enabled' ) && $new_status )
		{ // if page cache is enabled, ajax form must be enabled to
			$Blog->set_setting( 'ajax_form_enabled', true );
		}
		if( $save_setting )
		{ // save
			$Blog->dbupdate();
		}
	}
	return $result;
}


/**
 * Initialize global $blog variable to the requested blog
 *
 * @return boolean true if $blog was initialized successful, false otherwise
 */
function init_requested_blog( $use_blog_param_first = true )
{
	global $blog, $ReqHost, $ReqPath, $baseurl;
	global $Settings;
	global $Debuglog;

	if( !empty( $blog ) )
	{ // $blog was already initialized (maybe through a stub file)
		return true;
	}

	// If we want to give priority to ?blog=123..
	if( $use_blog_param_first == true )
	{	// Check if a specific blog has been requested in the URL:
		$Debuglog->add( 'Checking for epxlicit "blog" param', 'detectblog' );
		$blog = param( 'blog', 'integer', '', true );

		if( !empty($blog) )
		{ // a specific blog has been requested in the URL:
			return true;
		}
	}

	$Debuglog->add( 'No blog param received, checking extra path...', 'detectblog' );

	// No blog requested by URL param, let's try to match something in the URL:
	$BlogCache = & get_BlogCache();

	$re = "/^https?(.*)/i";
	$str = preg_quote( $baseurl );
	$subst = "https?$1";

	$baseurl_regex = preg_replace($re, $subst, $str);

	if( preg_match( '#^'.$baseurl_regex.'(index.php/)?([^/]+)#', $ReqHost.$ReqPath, $matches ) )
	{ // We have an URL blog name:
		$Debuglog->add( 'Found a potential URL collection name: '.$matches[2].' (in: '.$ReqHost.$ReqPath.')', 'detectblog' );
		if( strpos( $matches[2], '.' ) !== false )
		{	// There is an extension (like .php) in the collection name, ignore...
			$Debuglog->add( 'Ignoring because it contains a dot.', 'detectblog' );
		}
		elseif( ( $Collection = $Blog = & $BlogCache->get_by_urlname( $matches[2], false ) ) !== false ) /* SQL request '=' */
		{ // We found a matching blog:
			$blog = $Blog->ID;
			$Debuglog->add( 'Found matching blog: '.$blog, 'detectblog' );
			return true;
		}
		else
		{
			$Debuglog->add( 'No match.', 'detectblog' );
		}
	}

	// No blog identified by URL name, let's try to match the absolute URL: (remove optional index.php)
	if( preg_match( '#^(.+?)index.php#', $ReqHost.$ReqPath, $matches ) )
	{ // Remove what's not part of the absolute URL:
		$ReqAbsUrl = $matches[1];
	}
	else
	{	// Match on the whole URL (we'll try to find the base URL at the beginning)
		$ReqAbsUrl = $ReqHost.$ReqPath;
	}
	$Debuglog->add( 'Looking up absolute url : '.$ReqAbsUrl, 'detectblog' );
	// SQL request 'LIKE':
	if( ( $Collection = $Blog = & $BlogCache->get_by_url( $ReqAbsUrl, false ) ) !== false )
	{ // We found a matching blog:
		$blog = $Blog->ID;
		$Debuglog->add( 'Found matching blog: '.$blog, 'detectblog' );
		return true;
	}

	// If we did NOT give priority to ?blog=123, check for param now:
	if( $use_blog_param_first == false )
	{	// Check if a specific blog has been requested in the URL:
		$Debuglog->add( 'Checking for epxlicit "blog" param', 'detectblog' );
		$blog = param( 'blog', 'integer', '', true );

		if( !empty($blog) )
		{ // a specific blog has been requested in the URL:
			return true;
		}
	}

	// Still no blog requested, use default:
	$blog = $Settings->get( 'default_blog_ID' );
	$Collection = $Blog = & $BlogCache->get_by_ID( $blog, false, false );
	if( $Blog !== false && $Blog !== NULL )
	{ // We found a matching blog:
		$Debuglog->add( 'Using default blog '.$blog, 'detectblog' );
		return true;
	}

	// No collection has been selected (we'll probably display the default.php page):
	$blog = NULL;
	return false;
}


/**
 * Activate the blog locale and the corresponding charset
 *
 * @param integer the blog Id
 */
function activate_blog_locale( $blog )
{
	global $current_charset;

	if( empty( $blog ) || ( $blog <= 0 ) )
	{ // $blog is not a valid blog ID
		return;
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = $BlogCache->get_by_ID( $blog, false, false );
	if( !empty( $Blog ) )
	{ // Activate the blog locale
		locale_activate( $Blog->get('locale') );

		// Re-Init charset handling, in case current_charset has changed:
		init_charsets( $current_charset );
	};
}


/**
 * Initialize blog enabled widgets. It will call every enabled widget request_required_files() function.
 *
 * @param integer blog ID
 */
function init_blog_widgets( $blog_id )
{
	/**
	 * @var EnabledWidgetCache
	 */
	$EnabledWidgetCache = & get_EnabledWidgetCache();
	$container_Widget_array = & $EnabledWidgetCache->get_by_coll_ID( $blog_id );

	if( !empty($container_Widget_array) )
	{
		foreach( $container_Widget_array as $container=>$Widget_array )
		{
			foreach( $Widget_array as $ComponentWidget )
			{	// Let the Widget initialize itself:
				$ComponentWidget->request_required_files();
			}
		}
	}
}


/**
 * Check if user is activated and status allow to display the requested form ($disp).
 * Do nothing if status is activated or can't be activated or display is not allowed, add error message to activate the account otherwise.
 * asimo>TODO: We may find a better name and a better place for this function ( maybe user.funcs.php )
 *
 * @param string the requested view name
 */
function check_allow_disp( $disp )
{
	global $Collection, $Blog, $Messages, $Settings, $current_User;

	if( !check_user_status( 'can_be_validated' ) )
	{ // we don't have the case when user is logged in and the account is not active
		return;
	}

	$messages_content = $Messages->get_string( '', '', '', 'raw' );
	if( ( strstr( $messages_content, 'disp=activateinfo' ) !== false ) || ( strstr( $messages_content, 'action=req_activate_email' ) !== false ) )
	{ // If there is already a message to display activateinfo link, then don't add this message again
		return;
	}

	switch( $disp )
	{
		case 'activateinfo':
			// don't display activate account error notification in activate info page
			return;
			break; // already exited before this
		case 'contacts':
			if( !$current_User->check_status( 'can_view_contacts' ) )
			{ // contacts view display is not allowed
				return;
			}
			break;
		case 'edit':
			if( !$current_User->check_status( 'can_edit_post' ) )
			{ // edit post is not allowed
				return;
			}
			break;
		case 'messages':
			if( !$current_User->check_status( 'can_view_messages' ) )
			{ // messages view display is not allowed
				return;
			}
			break;
		case 'msgform':
			if( !$current_User->check_status( 'can_view_msgform' ) )
			{ // msgform display is not allowed
				return;
			}
			break;
		case 'threads':
			if( !$current_User->check_status( 'can_view_threads' ) )
			{ // threads view display is not allowed
				return;
			}
			break;
		case 'user':
			$user_ID = param( 'user_ID', 'integer', '', true );
			if( !$current_User->check_status( 'can_view_user', $user_ID ) )
			{ // user profile display is not allowed
				return;
			}
			break;
		case 'users':
			if( !$current_User->check_status( 'can_view_users' ) )
			{ // not active user can't see users list
				return;
			}
			break;
		default:
			break;
	}

	// User is allowed to see the requested view, but show an account activation error message
	$activateinfo_link = 'href="'.get_activate_info_url( NULL, '&amp;' ).'"';
	$Messages->add( sprintf( T_( 'IMPORTANT: your account is not active yet! Activate your account now by clicking on the activation link in the email we sent you. <a %s>More info &raquo;</a>' ), $activateinfo_link ) );
}


/**
 * Get the highest public status and action button label of a new post or comment in the given blog what the current User may create.
 * We assume here that the User should be able to create a post/comment with at least the lowest level status.
 *
 * @param string 'post' or 'comment'
 * @param integer blog ID
 * @param boolean set false to get only the status without the action button label
 * @param string Restrict max collection allowed status by this. Used for example to restrict a comment status with its post status
 * @return mixed string status if with_label is false, array( status, label ) if with_label is true
 */
function get_highest_publish_status( $type, $blog, $with_label = true, $restrict_max_allowed_status = '' )
{
	global $current_User;

	if( ( $type != 'post' ) && ( $type != 'comment' ) )
	{ // $type is invalid
		debug_die( 'Invalid type parameter!' );
	}

	if( $restrict_max_allowed_status == 'redirected' && $type == 'comment' )
	{	// Comment cannot have a status "redirected", force this to "deprecated":
		$restrict_max_allowed_status = 'deprecated';
	}

	$BlogCache = & get_BlogCache();
	$requested_Blog = $BlogCache->get_by_ID( $blog );
	$default_status = ( $type == 'post' ) ? $requested_Blog->get_setting( 'default_post_status' ) : $requested_Blog->get_setting( 'new_feedback_status' );

	if( $requested_Blog->get_setting( 'allow_access' ) == 'members' )
	{	// The collection is restricted for members or only for owner:
		if( ! $requested_Blog->get( 'advanced_perms' ) )
		{	// If advanced permissions are NOT enabled then only owner has an access for the collection
			// Set max allowed visibility status to "Private":
			$max_allowed_status = 'private';
		}
		else
		{	// Otherwise all members of this collection have an access for the collection
			// Set max allowed visibility status to "Members":
			$max_allowed_status = 'protected';
		}
	}
	elseif( $requested_Blog->get_setting( 'allow_access' ) == 'users' )
	{	// The collection is restricted for logged-in users only:
		// Set max allowed visibility status to "Community":
		$max_allowed_status = 'community';
	}
	else
	{	// The collection has no restriction for visibility statuses
		// Set max allowed visibility status to "Public":
		$max_allowed_status = 'published';
	}

	if( empty( $current_User ) || ( ( !$requested_Blog->get( 'advanced_perms' ) ) && ( !$current_User->check_perm_blog_global( $blog, 'editall' ) ) ) )
	{	// current User is not set or collection advanced perms are not enabled and user has no global perms on the given blog, set status to the default status
		$curr_status = $default_status;
		if( $max_allowed_status != 'published' )
		{	// If max allowed status is not "published" then we should check what status we can return here instead of default:
			$statuses = get_visibility_statuses();
			foreach( $statuses as $status_key => $status_title )
			{
				if( $curr_status == $status_key || $max_allowed_status == $status_key || $status_key == $restrict_max_allowed_status )
				{	// Allow to use this status because only this is max allowed for the requested collection:
					// Use min of max allowed statuses:
					$allowed_curr_status = $status_key;
				}
			}
			// Force default status to max allowed:
			$curr_status = ( empty( $allowed_curr_status ) ? '' : $allowed_curr_status );
		}
		return ( $with_label ? array( $curr_status, '' ) : $curr_status );
	}

	$indexed_statuses = array_reverse( get_visibility_statuses( 'ordered-index' ) );
	$result = false;
	// Set this flag to know if we should not allow $max_allowed_status and find next status with lower level:
	$restricted_status_is_allowed = empty( $restrict_max_allowed_status );
	// Set this flag to false in order to find first allowed status below:
	$status_is_allowed = false;
	foreach( $indexed_statuses as $curr_status => $status_index )
	{
		if( $curr_status == $restrict_max_allowed_status )
		{	// Set this var to TRUE to make all next statuses below are allowed because it is a max allowed status:
			$restricted_status_is_allowed = true;
		}
		if( $curr_status == $max_allowed_status )
		{	// This is first allowed status, then all next statuses are also allowed:
			$status_is_allowed = true;
		}
		if( $restricted_status_is_allowed && $status_is_allowed && $current_User->check_perm( 'blog_'.$type.'!'.$curr_status, 'create', false, $blog ) )
		{	// The highest available publish status has been found:
			$result = $curr_status;
			break;
		}
	}

	if( ! $result )
	{	// There are no available public status:
		if( $current_User->check_perm( 'blog_'.$type.'!private', 'create', false, $blog ) )
		{	// Check private status:
			$result = 'private';
		}
		else
		{	// None of the statuses were allowed above the 'draft' status, so we return the lowest level status:
			$result = 'draft';
		}
	}

	if( $with_label )
	{	// Get label for status updating action:
		if( $result == 'private' )
		{	// Set special label for private status because it is not defined in get_visibility_statuses( 'ordered-array' ):
			$result_label = T_('Make private!');
		}
		else
		{	// Get label by status key:
			$ordered_statuses = get_visibility_statuses( 'ordered-array' );
			foreach( $ordered_statuses as $index => $ordered_status )
			{
				if( $ordered_status[0] == $result )
				{
					$result_label = $ordered_status[1];
					break;
				}
			}
		}
		return array( $result, empty( $result_label ) ? '' : $result_label );
	}

	// Return only the highest available visibility status without label:
	return $result;
}


/**
 * Retrieves all tags from published posts
 *
 * @param mixed id of the collection or array of collection ids. Set to NULL to use current blog or '*' to use all collections
 * @param integer maximum number of returned tags
 * @param string a comma separated list of tags to ignore/exclude
 * @param bool true to skip tags from pages, intro posts and sidebar stuff
 * @return array of tags
 */
function get_tags( $blog_ids, $limit = 0, $filter_list = NULL, $skip_intro_posts = false, $post_statuses = array( 'published' ), $get_cat_blog_ID = false )
{
	global $DB, $localtimenow;

	$BlogCache = & get_BlogCache();

	if( is_null( $blog_ids ) )
	{
		global $blog;
		$blog_ids = $blog;
	}

	if( $blog_ids == '*' )
	{ // All collections
		$where_cat_clause = '1';
	}
	elseif( is_array( $blog_ids ) )
	{ // Get quoted ID list
		$where_cat_clause = 'cat_blog_ID IN ( '.$DB->quote( $blog_ids ).' )';
	}
	else
	{ // Get list of relevant/ aggregated collections
		$Blog = & $BlogCache->get_by_ID( $blog_ids );
		$where_cat_clause = trim( $Blog->get_sql_where_aggregate_coll_IDs( 'cat_blog_ID' ) );

		if( $Blog->get_setting( 'aggregate_coll_IDs' ) == '*' )
		{
			$blog_ids = '*';
		}
	}

	// Build query to get the tags:
	$tags_SQL = new SQL();

	if( $blog_ids != '*' || $get_cat_blog_ID )
	{
		$tags_SQL->SELECT( 'tag_name, COUNT( DISTINCT itag_itm_ID ) AS tag_count, tag_ID, cat_blog_ID' );
	}
	else
	{
		$tags_SQL->SELECT( 'tag_name, COUNT( DISTINCT itag_itm_ID ) AS tag_count, tag_ID' );
	}

	$tags_SQL->FROM( 'T_items__tag' );
	$tags_SQL->FROM_add( 'INNER JOIN T_items__itemtag ON itag_tag_ID = tag_ID' );
	$tags_SQL->FROM_add( 'INNER JOIN T_items__item ON itag_itm_ID = post_ID' );

	if( $blog_ids != '*' || $get_cat_blog_ID )
	{
		$tags_SQL->FROM_add( 'INNER JOIN T_postcats ON itag_itm_ID = postcat_post_ID' );
		$tags_SQL->FROM_add( 'INNER JOIN T_categories ON postcat_cat_ID = cat_ID' );
	}

	if( $skip_intro_posts )
	{
		$tags_SQL->FROM_add( 'LEFT JOIN T_items__type ON post_ityp_ID = ityp_ID' );
	}

	$tags_SQL->WHERE( $where_cat_clause );
	$tags_SQL->WHERE_and( 'post_status IN ("'.implode( '", "', $post_statuses ).'")' );
	$tags_SQL->WHERE_and( 'post_datestart < '.$DB->quote( remove_seconds( $localtimenow ) ) );

	if( $skip_intro_posts )
	{	// Skip "Intro", "Page" and other special posts:
		$tags_SQL->WHERE_and( 'post_ityp_ID IS NULL OR ityp_usage = "post"' );
	}

	if( ! empty( $filter_list ) )
	{ // Filter tags
		$tags_SQL->WHERE_and( 'tag_name NOT IN ( '.$DB->quote( explode( ', ', $filter_list ) ).' )' );
	}

	$tags_SQL->GROUP_BY( 'tag_name' );

	$tags_SQL->ORDER_BY( 'tag_count DESC' );

	if( ! empty( $limit ) )
	{ // Limit
		$tags_SQL->LIMIT( $limit );
	}

	return $DB->get_results( $tags_SQL->get(), OBJECT, 'Get tags' );
}


/**
 * Get a list of those statuses which can be displayed in the front office
 *
 * @param integer blog ID
 * @param string get setting for 'post' or 'comment'
 * @return array front office statuses in the given blog
 */
function get_inskin_statuses( $blog_ID, $type )
{
	if( empty( $blog_ID ) )
	{ // When blog is not set return the default value
		return array( 'published', 'community', 'protected', 'private', 'review' );
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = $BlogCache->get_by_ID( $blog_ID );
	$inskin_statuses = trim( $Blog->get_setting( ( $type == 'comment' ) ? 'comment_inskin_statuses' : 'post_inskin_statuses' ) );

	return empty( $inskin_statuses ) ? array() : explode( ',', $inskin_statuses );
}


/**
 * Get post/comment inskin statuses option list
 *
 * @param string type = 'post' or 'comment'
 * @return array checklist options
 */
function get_inskin_statuses_options( & $edited_Blog, $type )
{
	$checklist_options = array();
	if( $type != 'post' && $type != 'comment' )
	{
		return $checklist_options;
	}

	// Get all available statuses except 'deprecated', 'trash' and 'redirected'
	$statuses = get_visibility_statuses( '', array( 'deprecated', 'trash', 'redirected' ) );
	$status_icons = get_visibility_statuses( 'icons', array( 'deprecated', 'trash', 'redirected' ) );

	// Get current selected visibility statuses:
	$inskin_statuses = $edited_Blog->get_setting( $type.'_inskin_statuses' );

	// Get max allowed visibility status:
	$max_allowed_status = get_highest_publish_status( $type, $edited_Blog->ID, false );

	$status_is_hidden = true;
	foreach( $statuses as $status => $status_text )
	{	// Add a checklist option for each possible front office post/comment status:
		if( $max_allowed_status == $status )
		{	// This is max allowed status, Then display all next statuses with
			$status_is_hidden = false;
		}

		$checklist_options[] = array(
				$type.'_inskin_'.$status, // Field name of checkbox
				1, // Field value
				$status_icons[ $status ].' '.$status_text, // Text
				( strpos( $inskin_statuses, $status ) !== false ), // Checked?
				'', // Disabled?
				'', // Note
				'', // Class
				$status_is_hidden, // Hidden field instead of checkbox?
				array(
					'data-toggle' => 'tooltip',
					'data-placement' => 'top',
					'title' => get_status_tooltip_title( $status ) )
			);
	}

	return $checklist_options;
}


/**
 * Get available post statuses
 *
 * @param string Statuses format, defaults to translated statuses
 * @param array Statuses to exclude. Unused 'trash' status excluded by default
 * @param array Check if current user has a permission for each status
 * @param integer Blog ID
 * @return array of statuses
 */
function get_visibility_statuses( $format = '', $exclude = array('trash'), $check_perms = false, $blog_ID = NULL )
{
	switch( $format )
	{
		case 'notes-array':
		case 'notes-string':
		case 'radio-options':
			// Array notes for visibility_select()
			$r = array(
					'published'  => array( T_('Public'),     '('.T_('Everyone').')' ),
					'community'  => array( T_('Community'),  '('.T_('Logged in users only').')' ),
					'protected'  => array( T_('Members'),    '('.T_('Blog members only').')' ),
					'review'     => array( T_('Review'),     '('.T_('Moderators only (+You)').')' ),
					'private'    => array( T_('Private'),    '('.T_('You only').')' ),
					'draft'      => array( T_('Draft'),      '('.T_('You only (+backoffice users)').')' ),
					'deprecated' => array( T_('Deprecated'), '('.T_('Not published!').')' ),
					'redirected' => array( T_('Redirected'), '(301)' ),
					'trash'      => array( T_('Recycled'),   '' )
				);

			if( $format == 'notes-string' )
			{	// String notes
				$r = array_map( create_function('$v', 'return implode(" ", $v);'), $r );
			}
			break;

		case 'moderation-titles':
			$change_status = T_('Change status to').': ';
			$visible_by = ' ('.T_('Visible by').': ';
			$r = array(
					'published'  => $change_status.T_('Public').$visible_by.T_('Everyone').')',
					'community'  => $change_status.T_('Community').$visible_by.T_('Logged in users only').')',
					'protected'  => $change_status.T_('Members').$visible_by.T_('Blog members only').')',
					'review'     => $change_status.T_('Review').$visible_by.T_('Moderators only (+You)').')',
					'private'    => $change_status.T_('Private').$visible_by.T_('You only').')',
					'draft'      => $change_status.T_('Draft').$visible_by.T_('You only (+backoffice users)').')',
					'deprecated' => $change_status.T_('Deprecated').' ('.T_('Not published!').')',
					'redirected' => '',
					'trash'      => ''
				);
			break;

		case 'legend-titles':
			$r = array(
					'published' => T_('Visible by anyone'),
					'community' => T_('Visible by logged-in users only'),
					'protected' => T_('Visible by members only'),
					'review'    => T_('Waiting for moderator review'),
					'private'   => T_('Visible by you only'),
					'draft'     => T_('Unfinished post'),
				);
			break;

		case 'button-titles':
			$r = array(
					'published'  => NT_('Save as Public!'),
					'community'  => NT_('Save for Community!'),
					'protected'  => NT_('Save for Members!'),
					'review'     => NT_('Save for Review!'),
					'private'    => NT_('Save as Private!'),
					'draft'      => NT_('Save as Draft!'),
					'deprecated' => NT_('Save as Deprecated!'),
					'redirected' => NT_('Save as Redirected!'),
				);
			break;

		case 'tooltip-titles':
			$r = array(
					'published'  => T_('This is visible by everyone.'),
					'community'  => T_('This is visible by logged-in users only.'),
					'protected'  => T_('This is visible by members only.'),
					'review'     => T_('This is waiting for review and is visible by moderators only.'),
					'private'    => T_('This is visible only by the owner/author of the post and collection administrators.'),
					'draft'      => is_admin_page() ? T_('This is a draft.') : T_('This is a draft and is visible only by the owner/author of the post and collection administrators.'),
					'deprecated' => T_('This is deprecated and visible in the Back-Office only.'),
					'redirected' => T_('This will redirect to another page when accessed from the Front-Office.'),
					'trash'      => T_('This is a recycled.'),
				);
			break;

		case 'ordered-array': // indexed array, ordered from the lowest to the highest public level
			$r = array(
				0 => array( 'deprecated', '', T_('Deprecate').'!', 'grey' ),
				1 => array( 'review', T_('Open to moderators!'), T_('Restrict to moderators!'), 'magenta' ),
				2 => array( 'protected', T_('Open to members!'), T_('Restrict to members!'), 'orange' ),
				3 => array( 'community', T_('Open to community!'), T_('Restrict to community!'), 'blue' ),
				4 => array( 'published', T_('Make public!'), '', 'green' )
			);
			return $r;

		case 'ordered-index': // gives each status index in the statuses ordered array
			$r = array(
				'trash'      => 0,
				'redirected' => 0,
				'deprecated' => 0,
				'draft'      => 0,
				'private'    => 0,
				'review'     => 1,
				'protected'  => 2,
				'community'  => 3,
				'published'  => 4,
			);
			break;

		case 'moderation': // these statuses may need moderation
			$r = array( 'community', 'protected', 'review', 'private', 'draft' );
			return $r;

		case 'icons': // colored icons
			$r = array (
					'published'  => '<span class="fa fa-circle status_color_published"></span>',
					'community'  => '<span class="fa fa-circle status_color_community"></span>',
					'protected'  => '<span class="fa fa-circle status_color_protected"></span>',
					'review'     => '<span class="fa fa-circle status_color_review"></span>',
					'private'    => '<span class="fa fa-circle status_color_private"></span>',
					'draft'      => '<span class="fa fa-circle status_color_draft"></span>',
					'deprecated' => '<span class="fa fa-circle status_color_deprecated"></span>',
					'redirected' => '<span class="fa fa-circle status_color_redirected"></span>',
				);
			break;

		case 'raw':
		default:
			$r = array (
					'published'  => NT_('Public'),
					'community'  => NT_('Community'),
					'protected'  => NT_('Members'),
					'review'     => NT_('Review'),
					'private'    => NT_('Private'),
					'draft'      => NT_('Draft'),
					'deprecated' => NT_('Deprecated'),
					'redirected' => NT_('Redirected'),
					'trash'      => NT_('Recycled'),
				);

			if( $format != 'keys' && $format != 'raw' )
			{	// Translate statuses (default format)
				$r = array_map( 'T_', $r );
			}
	}

	if( !empty($exclude) )
	{
		// PHP 5.1 array_diff_key( $r, $exclude );
		foreach( $exclude as $ex )
		{
			if( isset($r[$ex]) )
			{
				unset($r[$ex]);
			}
		}
	}

	if( $check_perms && ! is_null( $blog_ID ) )
	{ // Check what status is available for current user
		global $current_User;
		foreach( $r as $status_key => $status_title )
		{
			if( ! $current_User->check_perm( 'blog_post!'.$status_key, 'create', false, $blog_ID ) )
			{ // Unset this status from list because current user has no perms to use this status
				unset( $r[ $status_key ] );
			}
		}
	}

	if( $format == 'keys' )
	{ // Return status keys for 'visibility_array'
		$r = array_keys( $r );
	}

	if( $format == 'radio-options' )
	{ // Return options for radio buttons
		$radio_options = array();
		foreach( $r as $status => $labels )
		{
			$radio_options[] = array( $status, $labels[0].' <span class="notes">'.$labels[1].'</span>' );
		}
		return $radio_options;
	}

	return $r;
}


/**
 * Compare two visibility status in the point of public level
 *
 * @param string first_status
 * @param string second_status
 * @return integer
 *   0 if the two statuses have the same public level
 *   1 if the first status has higher public level
 *   -1 if it first status has lower public level
 */
function compare_visibility_status( $first_status, $second_status )
{
	$status_index = get_visibility_statuses( 'ordered-index', array() );
	if( !isset( $status_index[$first_status] ) || !isset( $status_index[$second_status] ) )
	{ // At least one of the given statuses doesn't exist
		debug_die( 'Invalid status given to compare!' );
	}

	$first_status_index = $status_index[$first_status];
	$second_status_index = $status_index[$second_status];
	if( $first_status_index == $second_status_index )
	{ // The two status public level is equal, but note this doesn't mean that the two status must be same!
		return 0;
	}

	return ( $first_status_index > $second_status_index ) ? 1 : -1;
}


/**
 * Get restricted visibility statuses for the current User in the given blog
 *
 * @param integer blog ID
 * @param string permission prefix: 'blog_post!' or 'blog_comment!'
 * @param string permlevel: 'view'/'edit' depending on where we would like to use it
 * @param string Status; Don't restrict this status by max allowed status, for example, if it is already used for the post/comment
 * @param string Restrict max collection allowed status by this. Used for example to restrict a comment status with its post status
 * @param object Permission object: Item or Comment
 * @return array of restricted statuses
 */
function get_restricted_statuses( $blog_ID, $prefix, $permlevel = 'view', $allow_status = '', $restrict_max_allowed_status = '', $perm_target = NULL )
{
	global $current_User;

	$result = array();

	// Get max allowed visibility status:
	$max_allowed_status = get_highest_publish_status( ( $prefix == 'blog_post!' ? 'post' : 'comment' ), $blog_ID, false, $restrict_max_allowed_status );

	// This statuses are allowed to view/edit only for those users who may create post/comment with these statuses
	$restricted = array( 'published', 'community', 'protected', 'review', 'private', 'draft', 'deprecated' );
	$status_is_allowed = false;
	foreach( $restricted as $status )
	{
		if( $status == $max_allowed_status )
		{	// Set this var to TRUE to make all next statuses below are allowed because it is a max allowed status:
			$status_is_allowed = true;
		}
		if( in_array( $status, array( 'published', 'community', 'protected' ) ) )
		{	// Keep these statuses in array only to set $status_is_allowed in order to know when we can start allow the statuses:
			continue;
		}
		if( ( $allow_status != $status && ! $status_is_allowed ) || ! ( is_logged_in() && $current_User->check_perm( $prefix.$status, 'create', false, $blog_ID ) ) )
		{	// This status is not allowed
			$result[] = $status;
		}
	}

	// 'redirected' status is allowed to view/edit only in case of posts, and only if user has permission
	if( $prefix == 'blog_comment!' ||
	    ( $prefix == 'blog_post!' && ! ( is_logged_in() && $current_User->check_perm( $prefix.'redirected', 'create', false, $blog_ID ) ) ) )
	{ // not allowed
		$result[] = 'redirected';
	}

	// 'trash' status is allowed only in case of comments, and only if user has a permission to delete a comment from the given collection
	if( $prefix == 'blog_comment!' && ! ( is_logged_in() && ! empty( $perm_target ) && $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $perm_target ) ) )
	{ // not allowed
		$result[] = 'trash';
	}

	// The other statuses are always allowed to view in backoffice
	if( $permlevel != 'view' )
	{ // in case of other then 'view' action we must check the permissions
		$restricted = array( 'published', 'community', 'protected' );
		$status_is_allowed = false;
		foreach( $restricted as $status )
		{
			if( $status == $max_allowed_status )
			{	// Set this var to TRUE to make all next statuses below are allowed because it is a max allowed status:
				$status_is_allowed = true;
			}
			if( ( $allow_status != $status && ! $status_is_allowed ) ||
			    ! ( is_logged_in() && $current_User->check_perm( $prefix.$status, 'create', false, $blog_ID ) ) )
			{	// This status is not allowed
				$result[] = $status;
			}
		}
	}

	return $result;
}


function get_status_tooltip_title( $status )
{
	$visibility_statuses = get_visibility_statuses( 'tooltip-titles', array() );

	if( isset( $visibility_statuses[$status] ) )
	{
		return $visibility_statuses[$status];
	}
	else
	{
		debug_die( 'Invalid status value' );
	}
}

/**
 * Check if item/comment can be displayed with status for current user on front-office
 *
 * @param string Status
 * @param string Type: 'item', 'comment'
 * @param integer Blog ID
 * @param integer Creator user ID
 * @return boolean
 */
function can_be_displayed_with_status( $status, $type, $blog_ID, $creator_user_ID )
{
	// Get statuses which are visible on front-office:
	$show_statuses = get_inskin_statuses( $blog_ID, $type );

	if( ! in_array( $status, $show_statuses ) )
	{	// This Item has a status which cannot be displayed on front-office:
		return false;
	}

	global $current_User;
	$is_logged_in = is_logged_in( false );

	$permname = ( $type == 'item' ? 'blog_post!' : 'blog_comment!' ).$status;

	switch( $status )
	{
		case 'published':
			// Published items/comments are always allowed:
			$allowed = true;
			break;

		case 'community':
			// It is always allowed for logged in users:
			$allowed = $is_logged_in;
			break;

		case 'protected':
			// It is always allowed for members:
			$allowed = ( $is_logged_in && $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) );
			break;

		case 'private':
			// It is allowed for users who has global 'editall' permission:
			$allowed = ( $is_logged_in && $current_User->check_perm( 'blogs', 'editall' ) );
			if( ! $allowed && $is_logged_in && $current_User->check_perm( $permname, 'create', false, $blog_ID ) )
			{	// Own private items/comments are allowed if user can create private items/comments:
				$allowed = ( $current_User->ID == $creator_user_ID );
			}
			break;

		case 'review':
			// It is allowed for users who have at least 'lt' items/comments edit permission :
			$allowed = ( $is_logged_in && $current_User->check_perm( $permname, 'moderate', false, $blog_ID ) );
			if( ! $allowed && $is_logged_in && $current_User->check_perm( $permname, 'create', false, $blog_ID ) )
			{	// Own items/comments with 'review' status are allowed if user can create items/comments with 'review' status
				$allowed = ( $current_User->ID == $creator_user_ID );
			}
			break;

		case 'draft':
			// In front-office only authors may see their own draft items/comments, but only if the have permission to create draft items/comments:
			$allowed = ( $is_logged_in && $current_User->check_perm( $permname, 'create', false, $blog_ID )
				&& $current_User->ID == $creator_user_ID );
			break;

		default:
			// Decide the unknown item/comment statuses as not visible for front-office:
			$allowed = false;
	}

	return $allowed;
}


/**
 * Get Blog object from general setting
 *
 * @param string Setting name: 'default_blog_ID', 'info_blog_ID', 'login_blog_ID', 'msg_blog_ID'
 * @param object|NULL Current collection, Used for additional checking
 * @param boolean true if function $BlogCache->get_by_ID() should die on error
 * @param boolean true if function $BlogCache->get_by_ID() should die on empty/null
 * @return object|NULL|false
 */
function & get_setting_Blog( $setting_name, $current_Blog = NULL, $halt_on_error = false, $halt_on_empty = false )
{
	global $Settings;

	$setting_Blog = false;

	if( ! isset( $Settings ) )
	{
		return $setting_Blog;
	}

	if( $setting_name == 'login_blog_ID' && $current_Blog !== NULL && $current_Blog->get( 'access_type' ) == 'absolute' )
	{	// Don't allow to use main login collection if current collection has an external domain:
		return $setting_Blog;
	}

	$blog_ID = intval( $Settings->get( $setting_name ) );
	if( $blog_ID > 0 )
	{ // Check if blog really exists in DB
		$BlogCache = & get_BlogCache();
		$setting_Blog = & $BlogCache->get_by_ID( $blog_ID, $halt_on_error, $halt_on_empty );
	}

	return $setting_Blog;
}


/**
 * Display collection favorite icon
 *
 * @param integer Blog ID
 */
function get_coll_fav_icon( $blog_ID, $params = array() )
{
	global $admin_url, $current_User;

	$params = array_merge( array(
			'title' => '',
			'class' => '',
		), $params );

	$BlogCache = & get_BlogCache();
	$edited_Blog = $BlogCache->get_by_ID( $blog_ID );
	$is_favorite = $edited_Blog->favorite() > 0;
	if( $is_favorite )
	{
		$icon = 'star_on';
		$action = 'disable_setting';
		$title = T_('The collection is a favorite');
	}
	else
	{
		$icon = 'star_off';
		$action = 'enable_setting';
		$title = T_('The collection is not a favorite');
	}

	return '<a class="evo_post_fav_btn" href="'.$admin_url.'?ctrl=coll_settings'
			.'&amp;tab=general'
			.'&amp;action='.$action
			.'&amp;setting=fav'
			.'&amp;blog='.$blog_ID
			.'&amp;'.url_crumb('collection').'" '
			.'data-coll="'.$edited_Blog->urlname.'" '
			.'data-favorite="'.( $edited_Blog->favorite() ? '0' : '1' ).'">'
			.get_icon( $icon, 'imgtag', $params )
			.'</a>';
}

/**
 * Display blogs results table
 *
 * @param array Params
 */
function blogs_user_results_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'edited_User'          => NULL,
			'results_param_prefix' => 'actv_blog_',
			'results_title'        => T_('Blogs owned by the user'),
			'results_no_text'      => T_('User does not own any blogs'),
		), $params );

	if( !is_logged_in() )
	{	// Only logged in users can access to this function
		return;
	}

	global $current_User;
	if( !$current_User->check_perm( 'users', 'moderate' ) || !$current_User->check_perm( 'blogs', 'view' ) )
	{	// Check minimum permission:
		return;
	}

	$edited_User = $params['edited_User'];
	if( !$edited_User )
	{	// No defined User, probably the function is calling from AJAX request
		$user_ID = param( 'user_ID', 'integer', 0 );
		if( empty( $user_ID ) )
		{	// Bad request, Exit here
			return;
		}
		$UserCache = & get_UserCache();
		if( ( $edited_User = & $UserCache->get_by_ID( $user_ID, false ) ) === false )
		{	// Bad request, Exit here
			return;
		}
	}

	global $DB, $AdminUI;

	param( 'user_tab', 'string', '', true );
	param( 'user_ID', 'integer', 0, true );

	$SQL = new SQL();
	$SQL->SELECT( '*' );
	$SQL->FROM( 'T_blogs' );
	$SQL->WHERE( 'blog_owner_user_ID = '.$DB->quote( $edited_User->ID ) );

	// Create result set:
	$blogs_Results = new Results( $SQL->get(), $params['results_param_prefix'] );
	$blogs_Results->Cache = & get_BlogCache();
	$blogs_Results->title = $params['results_title'];
	$blogs_Results->no_results_text = $params['results_no_text'];

	// Get a count of the blogs which current user can delete
	$deleted_blogs_count = count( $edited_User->get_deleted_blogs() );
	if( $blogs_Results->get_total_rows() > 0 && $deleted_blogs_count > 0 )
	{	// Display action icon to delete all records if at least one record exists & user can delete at least one blog
		$blogs_Results->global_icon( sprintf( T_('Delete all blogs owned by %s'), $edited_User->login ), 'delete', '?ctrl=user&amp;user_tab=activity&amp;action=delete_all_blogs&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete all'), 3, 4 );
	}

	// Initialize Results object
	blogs_results( $blogs_Results, array(
			'display_type'    => false,
			'display_owner'   => false,
			'display_plist'   => false,
			'display_order'   => false,
			'display_caching' => false,
			'display_fav'     => false,
		) );

	if( is_ajax_content() )
	{	// init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$blogs_Results->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	$results_params = $AdminUI->get_template( 'Results' );
	$display_params = array(
		'before' => str_replace( '>', ' style="margin-top:25px" id="owned_blogs_result">', $results_params['before'] ),
	);
	$blogs_Results->display( $display_params );

	if( !is_ajax_content() )
	{	// Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$params['results_param_prefix'].'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}
}


/**
 * Display all blogs results table
 *
 * @param array Params
 */
function blogs_all_results_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'results_param_prefix' => 'blog_',
			'results_title'        => T_('List of Collections configured on this system').get_manual_link('site-collection-list'),
			'results_no_text'      => T_('No blog has been created yet!'),
			'results_no_perm_text' => T_('Sorry, you have no permission to edit/view any blog\'s properties.'),
		), $params );

	if( !is_logged_in() )
	{ // Only logged in users can access to this function
		return;
	}

	global $current_User;

	if( is_ajax_content() )
	{
		$order_action = param( 'order_action', 'string' );

		if( $order_action == 'update' )
		{ // Update an order to new value
			$new_value = (int)param( 'new_value', 'string', 0 );
			$order_data = param( 'order_data', 'string' );
			$order_obj_ID = (int)str_replace( 'order-blog-', '', $order_data );
			if( $order_obj_ID > 0 )
			{ // Update blog order
				$BlogCache = & get_BlogCache();
				if( $updated_Blog = & $BlogCache->get_by_ID( $order_obj_ID, false ) )
				{
					if( $current_User->check_perm( 'blog_properties', 'edit', false, $updated_Blog->ID ) )
					{ // Check permission to edit this Blog
						$updated_Blog->set( 'order', $new_value );
						$updated_Blog->dbupdate();
						$BlogCache->clear();
					}
				}
			}
		}
	}

	$SQL = new SQL();
	$SQL->SELECT( 'DISTINCT blog_ID, T_blogs.*, user_login, IF( cufv_user_id IS NULL, 0, 1 ) AS blog_favorite' );
	$SQL->FROM( 'T_blogs INNER JOIN T_users ON blog_owner_user_ID = user_ID' );
	$SQL->FROM_add( 'LEFT JOIN T_coll_user_favs ON ( cufv_blog_ID = blog_ID AND cufv_user_ID = '.$current_User->ID.' )' );

	if( ! $current_User->check_perm( 'blogs', 'view' ) )
	{ // We do not have perm to view all blogs... we need to restrict to those we're a member of:

		$SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON ( blog_advanced_perms <> 0 AND blog_ID = bloguser_blog_ID'
			. ' AND bloguser_user_ID = ' . $current_User->ID . ' )' );
		$SQL->FROM_add( ' LEFT JOIN T_coll_group_perms ON ( blog_advanced_perms <> 0 AND blog_ID = bloggroup_blog_ID'
			. ' AND ( bloggroup_group_ID = ' . $current_User->grp_ID
			. '       OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = '.$current_User->ID.' ) ) )' );
		$SQL->WHERE( 'blog_owner_user_ID = ' . $current_User->ID
			. ' OR bloguser_ismember <> 0'
			. ' OR bloggroup_ismember <> 0' );

		$no_results = $params['results_no_perm_text'];
	}
	else
	{
		$no_results = $params['results_no_text'];
	}

	// Create result set:
	$blogs_Results = new Results( $SQL->get(), $params['results_param_prefix'], '---------A' );
	$blogs_Results->Cache = & get_BlogCache();
	$blogs_Results->title = $params['results_title'];
	$blogs_Results->no_results_text = $no_results;

	if( $current_User->check_perm( 'blogs', 'create' ) )
	{
		global $admin_url;
		$blogs_Results->global_icon( T_('New Collection').'...', 'new', url_add_param( $admin_url, 'ctrl=collections&amp;action=new' ), T_('New Collection').'...', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	}

	// Initialize Results object
	blogs_results( $blogs_Results );

	if( is_ajax_content() )
	{ // init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$blogs_Results->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	$blogs_Results->display( NULL, 'session' );

	if( !is_ajax_content() )
	{ // Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$params['results_param_prefix'].'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}
}


/**
 * Initialize Results object for blogs list
 *
 * @param object Results
 * @param array Params
 */
function blogs_results( & $blogs_Results, $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'display_id'       => true,
			'display_name'     => true,
			'display_type'     => true,
			'display_fullname' => true,
			'display_owner'    => true,
			'display_url'      => true,
			'display_locale'   => true,
			'display_plist'    => true,
			'display_fav'      => true,
			'display_order'    => true,
			'display_caching'  => true,
			'display_actions'  => true,
		), $params );

	if( $params['display_id'] )
	{	// Display ID column
		$blogs_Results->cols[] = array(
				'th' => T_('ID'),
				'order' => 'blog_ID',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '$blog_ID$',
			);
	}

	if( $params['display_fav'] )
	{ // Display Favorite column
		$blogs_Results->cols[] = array(
				'th' => T_('Fav'),
				'th_title' => T_('Favorite'),
				'order' => 'blog_favorite',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%blog_row_setting( #blog_ID#, "fav", #blog_favorite# )%',
			);
	}

	if( $params['display_name'] )
	{ // Display Name column
		$blogs_Results->cols[] = array(
				'th' => T_('Name'),
				'order' => 'blog_shortname',
				'td' => '<strong>%blog_row_name( #blog_shortname#, #blog_ID# )%</strong>',
			);
	}

	if( $params['display_type'] )
	{ // Display Type column
		$blogs_Results->cols[] = array(
				'th' => T_('Type'),
				'order' => 'blog_type',
				'td' => '%blog_row_type( #blog_type#, #blog_ID# )%',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
			);
	}

	if( $params['display_fullname'] )
	{ // Display Full Name column
		$blogs_Results->cols[] = array(
				'th' => T_('Full Name'),
				'order' => 'blog_name',
				'td' => '%blog_row_fullname( #blog_name#, #blog_ID# )%',
			);
	}

	if( $params['display_owner'] )
	{ // Display Owner column
		$blogs_Results->cols[] = array(
				'th' => T_('Owner'),
				'order' => 'user_login',
				'td' => '%get_user_identity_link( #user_login# )%',
			);
	}

	if( $params['display_url'] )
	{ // Display Blog URL column
		$blogs_Results->cols[] = array(
				'th' => T_('Blog URL'),
				'td' => '<a href="@get(\'url\')@">@get(\'url\')@</a>',
			);
	}

	if( $params['display_locale'] )
	{ // Display Locale column
		$blogs_Results->cols[] = array(
				'th' => T_('Locale'),
				'order' => 'blog_locale',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%blog_row_locale( #blog_locale#, #blog_ID# )%',
			);
	}

	if( $params['display_plist'] )
	{ // Display Listed column
		$blogs_Results->cols[] = array(
				'th' => T_('Listed'),
				'th_title' => T_('Public List'),
				'order' => 'blog_in_bloglist',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%blog_row_listed( #blog_in_bloglist#, #blog_ID# )%',
			);
	}

	if( $params['display_order'] )
	{ // Display Order column
		$blogs_Results->cols[] = array(
				'th' => T_('Order'),
				'order' => 'blog_order',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%blog_row_order( #blog_ID#, #blog_order# )%',
			);
	}

	if( $params['display_caching'] )
	{ // Display Order column
		$blogs_Results->cols[] = array(
				'th' => T_('Caching'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%blog_row_caching( {Obj} )%',
			);
	}

	if( $params['display_actions'] )
	{ // Display Actions column
		$blogs_Results->cols[] = array(
				'th' => T_('Actions'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%blog_row_actions( {Obj} )%',
			);
	}
}


/**
 * Helper functions to display Blogs results.
 * New ( not display helper ) functions must be created above blogs_results function
 */

/**
 * Get a blog name with link to edit
 *
 * @param string Blog name
 * @param integer Blog ID
 * @return string Link
 */
function blog_row_name( $coll_name, $coll_ID )
{
	global $current_User, $ctrl, $admin_url;
	if( $ctrl == 'dashboard' )
	{ // Dashboard
		$edit_url = $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog='.$coll_ID;
		$r = '<a href="'.$edit_url.'">';
		$r .= $coll_name;
		$r .= '</a>';
	}
	elseif( $current_User->check_perm( 'blog_properties', 'edit', false, $coll_ID ) )
	{ // Blog setting & can edit
		$edit_url = $admin_url.'?ctrl=coll_settings&amp;blog='.$coll_ID;
		$r = '<a href="'.$edit_url.'" title="'.T_('Edit properties...').'">';
		$r .= $coll_name;
		$r .= '</a>';
	}
	else
	{
		$r = $coll_name;
	}
	return $r;
}


/**
 * Get a blog full name with link to edit
 *
 * @param string Blog full name
 * @param integer Blog ID
 * @return string Link
 */
function blog_row_fullname( $coll_fullname, $coll_ID )
{
	global $current_User, $admin_url;

	$coll_fullname = strmaxlen( $coll_fullname, 40, NULL, 'raw' );

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $coll_ID ) )
	{ // Blog setting & can edit
		$edit_url = $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$coll_ID;
		$r = '<a href="'.$edit_url.'" title="'.T_('Edit properties...').'">';
		$r .= $coll_fullname;
		$r .= '</a>';
	}
	else
	{
		$r = $coll_fullname;
	}

	return $r;
}


/**
 * Get a blog type with link to edit
 *
 * @param string Blog type
 * @param integer Blog ID
 * @return string Link
 */
function blog_row_type( $coll_type, $coll_ID )
{
	global $current_User, $admin_url, $Settings;

	$type_titles = array(
			'main'   => T_('Main'),
			'std'    => T_('Blog'),
			'photo'  => T_('Gallery'),
			'group'  => T_('Collab'),
			'forum'  => T_('Forum'),
			'manual' => T_('Manual'),
		);

	$type_title = isset( $type_titles[ $coll_type ] ) ? $type_titles[ $coll_type ] : $coll_type;

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $coll_ID ) )
	{ // Blog setting & can edit
		$edit_url = $admin_url.'?ctrl=coll_settings&tab=general&action=type&blog='.$coll_ID;
		$r = '<a href="'.$edit_url.'" title="'.T_('Edit properties...').'">';
		$r .= $type_title;
		$r .= '</a>';
	}
	else
	{
		$r = $type_title;
	}

	// Display the icons depending on how blog is used as default
	$r .= ' ';
	if( $coll_ID == $Settings->get( 'default_blog_ID' ) )
	{ // This blog is default
		$r .= action_icon( T_('Default collection to display'), 'coll_default', $admin_url.'?ctrl=collections&amp;tab=site_settings' );
	}
	if( $coll_ID == $Settings->get( 'info_blog_ID' ) )
	{ // This blog is used for info
		$r .= action_icon( T_('Collection for info pages'), 'coll_info', $admin_url.'?ctrl=collections&amp;tab=site_settings' );
	}
	if( $coll_ID == $Settings->get( 'login_blog_ID' ) )
	{ // This blog is used for login actions
		$r .= action_icon( T_('Collection for login/registration'), 'coll_login', $admin_url.'?ctrl=collections&amp;tab=site_settings' );
	}
	if( $coll_ID == $Settings->get( 'msg_blog_ID' ) )
	{ // This blog is used for messaging
		$r .= action_icon( T_('Collection for profiles/messaging'), 'coll_message', $admin_url.'?ctrl=collections&amp;tab=site_settings' );
	}

	return $r;
}


/**
 * Get a blog locale with link to edit
 *
 * @param string Blog locale
 * @param integer Blog ID
 * @return string Link
 */
function blog_row_locale( $coll_locale, $coll_ID )
{
	global $current_User, $admin_url;

	$coll_locale = locale_flag( $coll_locale, NULL, NULL, NULL, false );

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $coll_ID ) )
	{ // Blog setting & can edit
		$edit_url = $admin_url.'?ctrl=coll_settings&amp;blog='.$coll_ID;
		$r = '<a href="'.$edit_url.'" title="'.T_('Edit properties...').'">';
		$r .= $coll_locale;
		$r .= '</a>';
	}
	else
	{
		$r = $coll_locale;
	}

	return $r;
}


/**
 * Get a blog order with link to edit
 *
 * @param integer Blog ID
 * @param integer Blog order
 * @return string Link or Text
 */
function blog_row_order( $blog_ID, $blog_order )
{
	global $current_User, $admin_url;

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $blog_ID ) )
	{ // Blog setting & can edit
		$edit_url = $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog_ID.'#blog_order';
		$r = '<a href="'.$edit_url.'" id="order-blog-'.$blog_ID.'" style="display:block;">';
		$r .= $blog_order;
		$r .= '</a>';
	}
	else
	{
		$r = $blog_order;
	}
	return $r;
}


/**
 * Get the action icons to toggle caching settings of blog
 *
 * @param object Blog
 * @return string
 */
function blog_row_caching( $Blog )
{
	global $current_User, $admin_url;

	// Get icon and title for page caching status
	if( $Blog->get_setting( 'cache_enabled' ) )
	{ // Page cache is enabled
		$page_cache_icon = 'page_cache_on';
		$page_cache_title = T_('Page caching is on. Anonymous users may not see the latest content for 10 minutes.');
		$page_cache_action = 'disable_setting';
	}
	else
	{ // Page cache is disabled
		$page_cache_icon = 'page_cache_off';
		$page_cache_title = T_('Page caching is off. Server performance will not be optimal.');
		$page_cache_action = 'enable_setting';
	}

	// Get icon and title for widget/block caching status
	if( $Blog->get_setting( 'cache_enabled_widgets' ) )
	{ // Widget/block cache is enabled
		$block_cache_icon = 'block_cache_on';
		$block_cache_title = T_('Block caching is on. Some widgets may not update immediately.');
		$block_cache_action = 'disable_setting';
	}
	else
	{ // Widget/block cache is disabled
		$block_cache_icon = 'block_cache_off';
		$block_cache_title = T_('Block caching is off. Server performance will not be optimal.');
		$block_cache_action = 'enable_setting';
	}

	$r = '';
	$before = '<span class="column_icon">';
	$after = '</span>';

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
	{ // User has a permission to edit blog settings
		$toggle_url = $admin_url.'?ctrl=coll_settings'
			.'&amp;tab=general'
			.'&amp;action=%s'
			.'&amp;setting=%s'
			.'&amp;blog='.$Blog->ID
			.'&amp;'.url_crumb( 'collection' );
		$r .= $before.action_icon( $page_cache_title, $page_cache_icon, sprintf( $toggle_url, $page_cache_action, 'page_cache' ) ).$after;
		$r .= $before.action_icon( $block_cache_title, $block_cache_icon, sprintf( $toggle_url, $block_cache_action, 'block_cache' ) ).$after;
	}
	else
	{ // No permissions to edit
		$r .= $before.get_icon( $page_cache_icon, 'imgtag', array( 'title' => $page_cache_title ) ).$after;
		$r .= $before.get_icon( $block_cache_icon, 'imgtag', array( 'title' => $block_cache_title ) ).$after;
	}

	return $r;
}


/**
 * Gat title of value for the blog field "blog_in_bloglist"
 *
 * @param integer Value
 * @return string Title
 */
function blog_row_listed( $value, $coll_ID )
{
	global $current_User, $admin_url;

	switch( $value )
	{
		case 'public':
			$title = T_('Always');
			break;
		case 'logged':
			$title = T_('Logged in');
			break;
		case 'member':
			$title = T_('Members');
			break;
		case 'never':
			$title = T_('Never');
			break;
		default:
			$title = $value;
			break;
	}

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $coll_ID ) )
	{ // Blog setting & can edit
		$edit_url = $admin_url.'?ctrl=coll_settings&amp;blog='.$coll_ID;
		$r = '<a href="'.$edit_url.'" title="'.T_('Edit properties...').'">';
		$r .= $title;
		$r .= '</a>';
	}
	else
	{
		$r = $title;
	}

	return $r;
}


/**
 * Get an icon to show that blog setting is enabled or disabled
 * Make a link to switch setting value if user has permissions to edit blog settings
 *
 * @param integer Blog ID
 * @param boolean Blog setting name: 'fav'
 * @param boolean Blog setting value: 0, 1
 * @return string Icon or Link to change setting
 */
function blog_row_setting( $blog_ID, $setting_name, $setting_value )
{
	global $current_User, $admin_url;

	switch( $setting_name )
	{
		case'fav':
			return get_coll_fav_icon( $blog_ID, array( 'class' => 'coll-fav' ) );

		default:
			// Incorrect setting name
			return;
	}
}


/**
 * Get available actions for current blog
 *
 * @param object Blog
 * @return string Action links
 */
function blog_row_actions( $Blog )
{
	global $current_User, $admin_url;
	$r = '';

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
	{
		$r .= '<a href="'.$Blog->get( 'url' ).'" class="action_icon btn btn-info btn-xs" title="'.T_('View this collection').'">'.T_('View').'</a>';
		$r .= '<a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog='.$Blog->ID.'" class="action_icon btn btn-primary btn-xs" title="'.T_('Manage this collection...').'">'.T_('Manage').'</a>';
		$r .= action_icon( T_('Duplicate this collection...'), 'copy', $admin_url.'?ctrl=collections&amp;action=copy&amp;blog='.$Blog->ID );
		$r .= action_icon( T_('Delete this blog...'), 'delete', $admin_url.'?ctrl=collections&amp;action=delete&amp;blog='.$Blog->ID.'&amp;'.url_crumb('collection').'&amp;redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ) ) );
	}

	if( empty($r) )
	{ // for IE
		$r = '&nbsp;';
	}

	return $r;
}

/**
 * End of helper functions block to display Blogs results.
 * New ( not display helper ) functions must be created above blogs_results function
 */

?>