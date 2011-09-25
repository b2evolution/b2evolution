<?php
/**
 * This file implements Blog handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Update the advanced user/group permissions for edited blog
 *
 * @param int Blog ID
 * @param string 'user' or 'group'
 */
function blog_update_perms( $blog, $context = 'user' )
{
	global $DB;

  /**
	 * @var User
	 */
	global $current_User;

	if( $context == 'user' )
	{
		$table = 'T_coll_user_perms';
		$prefix = 'bloguser_';
		$ID_field = 'bloguser_user_ID';
	}
	else
	{
		$table = 'T_coll_group_perms';
		$prefix = 'bloggroup_';
		$ID_field = 'bloggroup_group_ID';
	}

	// Get affected user/group IDs:
	$IDs = param( $context.'_IDs', '/^[0-9]+(,[0-9]+)*$/', '' );
	$ID_array = explode( ',', $IDs );
	// pre_dump( $ID_array );

	// Can the current user touch advanced admin permissions?
	if( ! $current_User->check_perm( 'blog_admin', 'edit', false, $blog ) )
	{	// We have no permission to touch advanced admins!
		// echo 'restrict';

		// Get the users/groups which are adavnced admins
		$admins_ID_array = $DB->get_col( "SELECT {$ID_field}
																				FROM $table
																			 WHERE {$ID_field} IN (".implode(',',$ID_array).")
																							AND {$prefix}blog_ID = $blog
																							AND {$prefix}perm_admin <> 0" );

		// Take the admins out of the list:
		$ID_array = array_diff( $ID_array, $admins_ID_array );
		// pre_dump( $ID_array );
	}
	// else echo 'adv admin';

	if( empty( $ID_array ) )
	{
		return;
	}

	// Delete old perms for this blog:
	$DB->query( "DELETE FROM $table
								WHERE {$ID_field} IN (".implode(',',$ID_array).")
											AND {$prefix}blog_ID = ".$blog );

	$inserted_values = array();
	foreach( $ID_array as $loop_ID )
	{ // Check new permissions for each user:
		// echo "<br/>getting perms for $ID_field : $loop_ID <br />";

		$easy_mode = param( 'blog_perm_easy_'.$loop_ID, 'string', 'nomember' );

		if( $easy_mode != 'nomember' && $easy_mode != 'custom' )
		{
			$easy_perms = array(
				'bloguser_ismember' => 0,
				'bloguser_perm_poststatuses' => array(),
				'bloguser_perm_delpost' => 0,
				'bloguser_perm_edit_ts' => 0,
				'bloguser_perm_draft_cmts' => 0,
				'bloguser_perm_publ_cmts' => 0,
				'bloguser_perm_depr_cmts' => 0,
				'bloguser_perm_media_upload' => 0,
				'bloguser_perm_media_browse' => 0,
				'bloguser_perm_media_change' => 0,
				'bloguser_perm_admin' => 0,
				'bloguser_perm_properties' => 0,
				'bloguser_perm_cats' => 0,
				'bloguser_perm_page' => 0,
				'bloguser_perm_intro' => 0,
				'bloguser_perm_podcast' => 0,
				'bloguser_perm_sidebar' => 0,
				'bloguser_perm_vote_spam' => 0
			);

			if( ! $current_User->check_perm( 'blog_admin', 'edit', false, $blog )
				 && $easy_mode == 'admin' )
			{	// We have no permission to give advanced admins perm!
				$easy_mode = 'owner';
			}

			// Select option
			switch( $easy_mode )
			{
				case 'admin':
				case 'owner':
					$easy_perms['bloguser_perm_edit'] = 'all';
					break;

				case 'moderator':
					$easy_perms['bloguser_perm_edit'] = 'lt';
					break;

				case 'editor':
				case 'contrib':
					$easy_perms['bloguser_perm_edit'] = 'own';
					break;

				case 'member':
				default:
					$easy_perms['bloguser_perm_edit'] = 'no';
					break;
			}

			switch( $easy_mode )
			{
				case 'admin':
					$easy_perms['bloguser_perm_admin'] = 1;
					$easy_perms['bloguser_perm_edit_ts'] = 1;
					$easy_perms['bloguser_perm_vote_spam'] = 1;

				case 'owner':
					$easy_perms['bloguser_perm_properties'] = 1;
					$easy_perms['bloguser_perm_cats'] = 1;
					$easy_perms['bloguser_perm_delpost'] = 1;
					$easy_perms['bloguser_perm_intro'] = 1;
					$easy_perms['bloguser_perm_sidebar'] = 1;

				case 'moderator':
					$easy_perms['bloguser_perm_poststatuses'][] = 'redirected';
					$easy_perms['bloguser_perm_draft_cmts'] = 1;
					$easy_perms['bloguser_perm_publ_cmts'] = 1;
					$easy_perms['bloguser_perm_depr_cmts'] = 1;
					$easy_perms['bloguser_perm_media_upload'] = 1;
					$easy_perms['bloguser_perm_media_browse'] = 1;
					$easy_perms['bloguser_perm_media_change'] = 1;

				case 'editor':
					$easy_perms['bloguser_perm_poststatuses'][] = 'deprecated';
					$easy_perms['bloguser_perm_poststatuses'][] = 'protected';
					$easy_perms['bloguser_perm_poststatuses'][] = 'published';
					$easy_perms['bloguser_perm_podcast'] = 1;
					$easy_perms['bloguser_perm_page'] = 1;

				case 'contrib':
					$easy_perms['bloguser_perm_poststatuses'][] = 'draft';
					$easy_perms['bloguser_perm_poststatuses'][] = 'private';
					$easy_perms['bloguser_perm_media_upload'] = 1;
					$easy_perms['bloguser_perm_media_browse'] = 1;

				case 'member':
					$easy_perms['bloguser_ismember'] = 1;
					break;

				default:
					die( 'unhandled easy mode' );
			}

			$easy_perms['bloguser_perm_poststatuses'] = implode( ',', $easy_perms['bloguser_perm_poststatuses'] );

			$inserted_values[] = " ( $blog, $loop_ID, ".$easy_perms['bloguser_ismember']
														.', '.$DB->quote($easy_perms['bloguser_perm_poststatuses'])
														.', '.$DB->quote($easy_perms['bloguser_perm_edit'])
														.', '.$easy_perms['bloguser_perm_delpost'].', '.$easy_perms['bloguser_perm_edit_ts']
														.', '.$easy_perms['bloguser_perm_draft_cmts'].', '.$easy_perms['bloguser_perm_publ_cmts']
														.', '.$easy_perms['bloguser_perm_depr_cmts'].', '.$easy_perms['bloguser_perm_cats']
														.', '.$easy_perms['bloguser_perm_properties'].', '.$easy_perms['bloguser_perm_admin']
														.', '.$easy_perms['bloguser_perm_media_upload'].', '.$easy_perms['bloguser_perm_media_browse']
														.', '.$easy_perms['bloguser_perm_media_change'].', '.$easy_perms['bloguser_perm_page']
														.', '.$easy_perms['bloguser_perm_intro'].', '.$easy_perms['bloguser_perm_podcast']
														.', '.$easy_perms['bloguser_perm_sidebar'].', '.$easy_perms['bloguser_perm_vote_spam'].' ) ';
		}
		else
		{	// Use checkboxes
			$perm_post = array();

			$ismember = param( 'blog_ismember_'.$loop_ID, 'integer', 0 );

			$perm_published = param( 'blog_perm_published_'.$loop_ID, 'string', '' );
			if( !empty($perm_published) ) $perm_post[] = 'published';

			$perm_protected = param( 'blog_perm_protected_'.$loop_ID, 'string', '' );
			if( !empty($perm_protected) ) $perm_post[] = 'protected';

			$perm_private = param( 'blog_perm_private_'.$loop_ID, 'string', '' );
			if( !empty($perm_private) ) $perm_post[] = 'private';

			$perm_draft = param( 'blog_perm_draft_'.$loop_ID, 'string', '' );
			if( !empty($perm_draft) ) $perm_post[] = 'draft';

			$perm_deprecated = param( 'blog_perm_deprecated_'.$loop_ID, 'string', '' );
			if( !empty($perm_deprecated) ) $perm_post[] = 'deprecated';

			$perm_redirected = param( 'blog_perm_redirected_'.$loop_ID, 'string', '' );
			if( !empty($perm_redirected) ) $perm_post[] = 'redirected';

			$perm_page    = param( 'blog_perm_page_'.$loop_ID, 'integer', 0 );
			$perm_intro   = param( 'blog_perm_intro_'.$loop_ID, 'integer', 0 );
			$perm_podcast = param( 'blog_perm_podcast_'.$loop_ID, 'integer', 0 );
			$perm_sidebar = param( 'blog_perm_sidebar_'.$loop_ID, 'integer', 0 );

			$perm_edit = param( 'blog_perm_edit_'.$loop_ID, 'string', 'no' );

			$perm_delpost = param( 'blog_perm_delpost_'.$loop_ID, 'integer', 0 );
			$perm_edit_ts = param( 'blog_perm_edit_ts_'.$loop_ID, 'integer', 0 );

			$perm_draft_comments = param( 'blog_perm_draft_cmts_'.$loop_ID, 'integer', 0 );
			$perm_publ_comments = param( 'blog_perm_publ_cmts_'.$loop_ID, 'integer', 0 );
			$perm_depr_comments = param( 'blog_perm_depr_cmts_'.$loop_ID, 'integer', 0 );
			$perm_cats = param( 'blog_perm_cats_'.$loop_ID, 'integer', 0 );
			$perm_properties = param( 'blog_perm_properties_'.$loop_ID, 'integer', 0 );

			if( $current_User->check_perm( 'blog_admin', 'edit', false, $blog ) )
			{	// We have permission to give advanced admins perm!
				$perm_admin = param( 'blog_perm_admin_'.$loop_ID, 'integer', 0 );
			}
			else
			{
				$perm_admin = 0;
			}

			$perm_media_upload = param( 'blog_perm_media_upload_'.$loop_ID, 'integer', 0 );
			$perm_media_browse = param( 'blog_perm_media_browse_'.$loop_ID, 'integer', 0 );
			$perm_media_change = param( 'blog_perm_media_change_'.$loop_ID, 'integer', 0 );

			$perm_vote_spam = param( 'blog_perm_vote_spam_'.$loop_ID, 'integer', 0 );

			// Update those permissions in DB:

			if( $ismember || count($perm_post) || $perm_delpost || $perm_edit_ts || $perm_draft_comments || $perm_publ_comments || $perm_publ_comments || 
				$perm_cats || $perm_properties || $perm_admin || $perm_media_upload || $perm_media_browse || $perm_media_change || $perm_vote_spam )
			{ // There are some permissions for this user:
				$ismember = 1;	// Must have this permission

				// insert new perms:
				$inserted_values[] = " ( $blog, $loop_ID, $ismember, ".$DB->quote(implode(',',$perm_post)).",
																	".$DB->quote($perm_edit).",
																	$perm_delpost, $perm_edit_ts, $perm_draft_comments, $perm_publ_comments,
																	$perm_depr_comments, $perm_cats, $perm_properties, $perm_admin, $perm_media_upload, 
																	$perm_media_browse, $perm_media_change, $perm_page,	$perm_intro, $perm_podcast, 
																	$perm_sidebar, $perm_vote_spam )";
			}
		}
	}

	// Proceed with insertions:
	if( count( $inserted_values ) )
	{
		$DB->query( "INSERT INTO $table( {$prefix}blog_ID, {$ID_field}, {$prefix}ismember,
											{$prefix}perm_poststatuses, {$prefix}perm_edit, {$prefix}perm_delpost, {$prefix}perm_edit_ts,
											{$prefix}perm_draft_cmts, {$prefix}perm_publ_cmts, {$prefix}perm_depr_cmts,
											{$prefix}perm_cats, {$prefix}perm_properties, {$prefix}perm_admin,
											{$prefix}perm_media_upload, {$prefix}perm_media_browse, {$prefix}perm_media_change,
											{$prefix}perm_page, {$prefix}perm_intro, {$prefix}perm_podcast, {$prefix}perm_sidebar, {$prefix}perm_vote_spam )
									VALUES ".implode( ',', $inserted_values ) );
	}
}


/**
 * Translates an given array of permissions to an "easy group".
 *
 * USES OBJECT ROW
 *
 * - nomember
 * - member
 * - editor (member+edit posts+delete+edit comments+all filemanager rights)
 * - administrator (editor+edit cats+edit blog)
 * - custom
 *
 * @param array indexed, as the result row from "SELECT * FROM T_coll_user_perms"
 * @return string one of the five groups (nomember, member, editor, admin, custom)
 */
function blogperms_get_easy2( $perms, $context = 'user' )
{
	if( !isset($perms->{'blog'.$context.'_ismember'}) )
	{
		return 'nomember';
	}

	if( !empty( $perms->{'blog'.$context.'_perm_poststatuses'} ) )
	{
		$perms_post = explode( ',', $perms->{'blog'.$context.'_perm_poststatuses'} );
	}
	else
	{
		$perms_post = array();
	}

	$perms_contrib =  (in_array( 'draft', $perms_post ) ? 1 : 0)
									+ (in_array( 'private', $perms_post ) ? 1 : 0)
									+(int)$perms->{'blog'.$context.'_perm_media_upload'}
									+(int)$perms->{'blog'.$context.'_perm_media_browse'};

	$perms_editor =   (in_array( 'deprecated', $perms_post ) ? 1 : 0)
									+ (in_array( 'protected', $perms_post ) ? 1 : 0)
									+ (in_array( 'published', $perms_post ) ? 1 : 0);

	$perms_moderator = (in_array( 'redirected', $perms_post ) ? 1 : 0)
									+(int)$perms->{'blog'.$context.'_perm_draft_cmts'}
									+(int)$perms->{'blog'.$context.'_perm_publ_cmts'}
									+(int)$perms->{'blog'.$context.'_perm_depr_cmts'}
									+(int)$perms->{'blog'.$context.'_perm_media_change'};

	$perms_owner =   (int)$perms->{'blog'.$context.'_perm_properties'}
									+(int)$perms->{'blog'.$context.'_perm_cats'}
									+(int)$perms->{'blog'.$context.'_perm_delpost'};

	$perms_admin =   (int)$perms->{'blog'.$context.'_perm_admin'}
									+(int)$perms->{'blog'.$context.'_perm_edit_ts'}
									+(int)$perms->{'blog'.$context.'_perm_vote_spam'};

	$perm_edit = $perms->{'blog'.$context.'_perm_edit'};

	// echo "<br> $perms_contrib $perms_editor $perms_moderator $perms_admin $perm_edit ";

	if( $perms_contrib == 4 && $perms_editor == 3 && $perms_moderator == 5 && $perms_owner == 3 && $perms_admin == 2 && $perm_edit == 'all' )
	{ // has full admin rights
		return 'admin';
	}

	if( $perms_contrib == 4 && $perms_editor == 3 && $perms_moderator == 5 && $perms_owner == 3 && $perms_admin == 0 && $perm_edit == 'all' )
	{ // has full editor rights
		return 'owner';
	}

	if( $perms_contrib == 4 && $perms_editor == 3 && $perms_moderator == 5 && $perms_owner == 0 && $perms_admin == 0 && $perm_edit == 'lt' )
	{ // moderator
		return 'moderator';
	}

	if( $perms_contrib == 4 && $perms_editor == 3 && $perms_moderator == 0 && $perms_owner == 0 && $perms_admin == 0 && $perm_edit == 'own' )
	{ // publisher
		return 'editor';
	}

	if( $perms_contrib == 4 && $perms_editor == 0 && $perms_moderator == 0 && $perms_owner == 0 && $perms_admin == 0 && $perm_edit == 'own' )
	{ // contributor
		return 'contrib';
	}

	if( $perms_contrib == 0 && $perms_editor == 0 && $perms_moderator == 0 && $perms_owner == 0  && $perms_admin == 0 && $perm_edit == 'no' )
	{
		return 'member';
	}

	return 'custom';
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
		$blog_array = $BlogCache->load_user_blogs( $permname, $permlevel, $current_User->ID, 'ID', 1 );
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
	global $Blog, $Messages;
	if( empty( $Blog ) )
	{	// The requested blog does not exist
		$Messages->add( T_('The requested blog does not exist (any more?)'), 'error' );
		return false;
	}
	return true;
}


/**
 * Set working blog to a new value and memorize it in user settings if needed.
 *
 * For use in admin
 *
 * @return boolean $blog changed?
 */
function set_working_blog( $new_blog_ID )
{
	global $blog, $UserSettings;

	if( $new_blog_ID != (int)$UserSettings->get('selected_blog') )
	{	// Save the new default blog.
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
 * @param string
 * @return array|string
 */
function get_collection_kinds( $kind = NULL )
{
	global $Plugins;
	
	$kinds = array(
		'std' => array(
				'name' => T_('Standard blog'),
				'desc' => T_('A standard blog with the most common features.'),
			),
		'photo' => array(
				'name' => T_('Photoblog'),
				'desc' => T_('A blog optimized to publishing photos.'),
			),
		'group' => array(
				'name' => T_('Group blog'),
				'desc' => T_('A blog optimized for team/collaborative editing. Posts can be assigned to different reviewers before being published. Look for the workflow properties at the bottom of the post editing form.'),
			),
		);
	
	// Define blog kinds, their names and description.
	$plugin_kinds = $Plugins->trigger_collect( 'GetCollectionKinds', array('kinds' => & $kinds) );
	
	foreach( $plugin_kinds as $l_kinds )
	{
		$kinds = array_merge( $l_kinds, $kinds );
	}
	
	if( is_null($kind) )
	{	// Return kinds array
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
		$Blog = NULL;
		$old_cache_status = $Settings->get( $cache_key );
		$cache_name = T_( 'General' );
	}
	else
	{ // blog page cache
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $coll_ID );
		$old_cache_status = $Blog->get_setting( $cache_key );
		$cache_name = T_( 'Page' );
	}

	$PageCache = new PageCache( $Blog );
	if( $old_cache_status == false && $new_status == true )
	{ // Caching has been turned ON:
		if( $PageCache->cache_create( false ) )
		{ // corresponding cache folder was created  
			$result = array( 'success', sprintf( T_( '%s caching has been enabled.' ), $cache_name ) );
		}
		else
		{ // error creating cache folder
			$result = array( 'error', sprintf( T_( '%s caching could not be enabled. Check /cache/ folder file permissions.' ), $cache_name ) );
			$new_status = false;
		}
	}
	elseif( $old_cache_status == true && $new_status == false )
	{ // Caching has been turned OFF:
		$PageCache->cache_delete();
		$result = array( 'note',  sprintf( T_( '%s caching has been disabled. Cache contents have been purged.' ), $cache_name ) );
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


/*
 * $Log$
 * Revision 1.16  2011/09/25 07:06:21  efy-yurybakh
 * Implement new permission for spam voting
 *
 * Revision 1.15  2011/09/04 22:13:14  fplanque
 * copyright 2011
 *
 * Revision 1.14  2011/06/29 13:14:01  efy-asimo
 * Use ajax to display comment and contact forms
 *
 * Revision 1.13  2011/03/15 09:34:05  efy-asimo
 * have checkboxes for enabling caching in new blogs
 * refactorize cache create/enable/disable
 *
 * Revision 1.12  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 *
 * Revision 1.11  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.10  2010/06/01 02:44:44  sam2kb
 * New hooks added: GetCollectionKinds and InitCollectionKinds.
 * Use them to define new and override existing presets for new blogs.
 * See http://forums.b2evolution.net/viewtopic.php?t=21015
 *
 * Revision 1.9  2010/02/08 17:52:09  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.8  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.7  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.6  2009/08/31 18:07:12  fplanque
 * minor/doc
 *
 * Revision 1.5  2009/08/22 20:31:01  tblue246
 * New feature: Post type permissions
 *
 * Revision 1.4  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/22 18:47:32  fplanque
 * attempt to fix nasty thing about blog memorization.
 * praying there will be no side effects.
 *
 * Revision 1.2  2008/01/21 09:35:26  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:32  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.34  2007/06/18 21:12:25  fplanque
 * (no time for trying to fix something that works)
 *
 * Revision 1.32  2007/06/12 23:51:16  fplanque
 * non admins can no longer create blog admins
 *
 * Revision 1.31  2007/06/12 23:16:03  fplanque
 * non admins can no longer change admin blog perms
 *
 * Revision 1.30  2007/06/11 01:55:57  fplanque
 * level based user permissions
 *
 * Revision 1.29  2007/06/03 02:55:06  fplanque
 * no message
 *
 * Revision 1.28  2007/06/03 02:54:18  fplanque
 * Stuff for permission maniacs (admin part only, actual perms checks to be implemented)
 * Newbies will not see this complexity since advanced perms are now disabled by default.
 *
 * Revision 1.27  2007/05/29 01:17:20  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.26  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.25  2007/05/13 18:49:55  fplanque
 * made autoselect_blog() more robust under PHP4
 *
 * Revision 1.24  2007/05/09 00:58:55  fplanque
 * massive cleanup of old functions
 *
 * Revision 1.23  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.22  2007/03/11 22:48:19  fplanque
 * handling of permission to redirect posts
 *
 * Revision 1.21  2007/03/11 22:30:07  fplanque
 * cleaned up group perms
 *
 * Revision 1.20  2007/03/07 02:38:58  fplanque
 * do some recovery on incorrect $blog
 *
 * Revision 1.19  2006/12/28 18:30:30  fplanque
 * cleanup of obsolete var
 *
 * Revision 1.18  2006/12/18 13:14:34  fplanque
 * bugfix
 *
 * Revision 1.17  2006/12/18 03:20:41  fplanque
 * _header will always try to set $Blog.
 * controllers can use valid_blog_requested() to make sure we have one
 * controllers should call set_working_blog() to change $blog, so that it gets memorized in the user settings
 *
 * Revision 1.16  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.15  2006/11/13 20:49:52  fplanque
 * doc/cleanup :/
 *
 * Revision 1.14  2006/10/08 03:52:09  blueyed
 * Tell BlogCache that it has loaded all.
 */
?>