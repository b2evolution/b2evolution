<?php
/**
 * This file implements the functions to work with WordPress importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get data to start import from wordpress XML/ZIP file
 *
 * @param string Path of XML/ZIP file
 * @return array Data array:
 *                 'error' - FALSE on success OR error message ,
 *                 'XML_file_path' - Path to XML file,
 *                 'attached_files_path' - Path to attachments folder,
 *                 'temp_zip_folder_path' - Path to temp extracted ZIP files
 */
function wpxml_get_import_data( $XML_file_path )
{
	// Start to collect all printed errors from buffer:
	ob_start();

	$XML_file_name = basename( $XML_file_path );
	$ZIP_folder_path = NULL;

	// Do NOT use first found folder for attachments:
	$use_first_folder_for_attachments = false;

	if( preg_match( '/\.(xml|txt)$/i', $XML_file_name ) )
	{	// XML format
		// Check WordPress XML file:
		wpxml_check_xml_file( $XML_file_path );
	}
	else if( preg_match( '/\.zip$/i', $XML_file_name ) )
	{	// ZIP format
		// Extract ZIP and check WordPress XML file
		global $media_path;

		// $ZIP_folder_path must be deleted after import!
		$ZIP_folder_path = $media_path.'import/temp-'.md5( rand() );

		if( unpack_archive( $XML_file_path, $ZIP_folder_path, true, $XML_file_name ) )
		{	// If ZIP archive is unpacked successfully:

			// 
			$XML_file_path = false;

			// Find valid XML file in ZIP package:
			$ZIP_files_list = scandir( $ZIP_folder_path );
			$xml_exists_in_zip = false;
			for( $i = 1; $i <= 2; $i++ )
			{	// Run searcher 1st time to find XML file in a root of ZIP archive
				// and 2nd time to find XML file in 1 level subfolders of the root:
				foreach( $ZIP_files_list as $ZIP_file )
				{
					if( $ZIP_file == '.' || $ZIP_file == '..' )
					{	// Skip reserved dir names of the current path:
						continue;
					}
					if( $i == 2 )
					{	// This is 2nd time to find XML file in 1 level subfolders of the root:
						if( is_dir( $ZIP_folder_path.'/'.$ZIP_file ) )
						{	// This is a subfolder, Scan it to find XML files inside:
							$ZIP_folder_current_path = $ZIP_folder_path.'/'.$ZIP_file;
							$ZIP_folder_files = scandir( $ZIP_folder_current_path );
						}
						else
						{	// Skip files:
							continue;
						}
					}
					else
					{	// This is a single file or folder:
						$ZIP_folder_files = array( $ZIP_file );
						$ZIP_folder_current_path = $ZIP_folder_path;
					}
					foreach( $ZIP_folder_files as $ZIP_file )
					{
						if( preg_match( '/\.(xml|txt)$/i', $ZIP_file ) )
						{	// XML file is found in ZIP package:
							$XML_file_path = $ZIP_folder_current_path.'/'.$ZIP_file;
							if( wpxml_check_xml_file( $XML_file_path ) )
							{	// XML file is valid:
								$xml_exists_in_zip = true;
								break 3;
							}
						}
					}
				}
			}
		}

		// Use first found folder for attachments when no reserved folders not found in ZIP before:
		$use_first_folder_for_attachments = true;
	}
	else
	{	// Unrecognized extension:
		echo '<p class="text-danger">'.sprintf( T_( '%s has an unrecognized extension.' ), '<code>'.$xml_file['name'].'</code>' ).'</p>';
	}

	if( $XML_file_path )
	{	// Get a path with attached files for the XML file:
		$attached_files_path = wpxml_get_attachments_folder( $XML_file_path, $use_first_folder_for_attachments );
	}
	else
	{	// Wrong source file:
		$attached_files_path = false;
	}

	if( isset( $xml_exists_in_zip ) && $xml_exists_in_zip === false && file_exists( $ZIP_folder_path ) )
	{	// No XML is detected in ZIP package:
		echo '<p class="text-danger">'.T_( 'Correct XML file is not detected in your ZIP package.' ).'</p>';
		// Delete temporary folder that contains the files from extracted ZIP package:
		rmdir_r( $ZIP_folder_path );
	}

	// Get all printed errors:
	$errors = ob_get_clean();

	return array(
			'errors'               => empty( $errors ) ? false : $errors,
			'XML_file_path'        => $XML_file_path,
			'attached_files_path'  => $attached_files_path,
			'temp_zip_folder_path' => $ZIP_folder_path,
		);
}


/**
 * Import WordPress data from XML file into b2evolution database
 *
 * @param string Path of XML file
 * @param string|boolean Path of folder with attachments, may be FALSE if folder is not found
 * @param string|NULL Temporary folder of unpacked ZIP archive
 */
function wpxml_import( $XML_file_path, $attached_files_path = false, $ZIP_folder_path = NULL )
{
	global $DB, $tableprefix, $media_path;

	// Load classes:
	load_class( 'regional/model/_country.class.php', 'Country' );
	load_class( 'regional/model/_region.class.php', 'Region' );
	load_class( 'regional/model/_subregion.class.php', 'Subregion' );
	load_class( 'regional/model/_city.class.php', 'City' );

	// Set Blog from request blog ID
	$wp_blog_ID = param( 'wp_blog_ID', 'integer', 0 );
	$BlogCache = & get_BlogCache();
	$wp_Blog = & $BlogCache->get_by_ID( $wp_blog_ID );

	// The import type ( replace | append )
	$import_type = param( 'import_type', 'string', 'replace' );
	// Should we delete files on 'replace' mode?
	$delete_files = param( 'delete_files', 'integer', 0 );
	// Should we try to match <img> tags with imported attachments based on filename in post content after import?
	$import_img = param( 'import_img', 'integer', 0 );
	$all_wp_attachments = array();

	// Parse WordPress XML file into array
	$xml_data = wpxml_parser( $XML_file_path );


	$DB->begin();

	if( $import_type == 'replace' )
	{ // Remove data from selected blog

		// Get existing categories
		$SQL = new SQL();
		$SQL->SELECT( 'cat_ID' );
		$SQL->FROM( 'T_categories' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $wp_blog_ID ) );
		$old_categories = $DB->get_col( $SQL->get() );
		if( !empty( $old_categories ) )
		{ // Get existing posts
			$SQL = new SQL();
			$SQL->SELECT( 'post_ID' );
			$SQL->FROM( 'T_items__item' );
			$SQL->WHERE( 'post_main_cat_ID IN ( '.implode( ', ', $old_categories ).' )' );
			$old_posts = $DB->get_col( $SQL->get() );
		}

		echo T_('Removing the comments... ');
		evo_flush();
		if( !empty( $old_posts ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'comment_ID' );
			$SQL->FROM( 'T_comments' );
			$SQL->WHERE( 'comment_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
			$old_comments = $DB->get_col( $SQL->get() );
			$DB->query( 'DELETE FROM T_comments WHERE comment_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
			if( !empty( $old_comments ) )
			{
				$DB->query( 'DELETE FROM T_comments__votes WHERE cmvt_cmt_ID IN ( '.implode( ', ', $old_comments ).' )' );
				$DB->query( 'DELETE FROM T_links WHERE link_cmt_ID IN ( '.implode( ', ', $old_comments ).' )' );
			}
		}
		echo T_('OK').'<br />';

		echo T_('Removing the posts... ');
		evo_flush();
		if( !empty( $old_categories ) )
		{
			$DB->query( 'DELETE FROM T_items__item WHERE post_main_cat_ID IN ( '.implode( ', ', $old_categories ).' )' );
			if( !empty( $old_posts ) )
			{ // Remove the post's data from related tables
				if( $delete_files )
				{ // Get the file IDs that should be deleted from hard drive
					$SQL = new SQL();
					$SQL->SELECT( 'DISTINCT link_file_ID' );
					$SQL->FROM( 'T_links' );
					$SQL->WHERE( 'link_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$deleted_file_IDs = $DB->get_col( $SQL->get() );
				}
				$DB->query( 'DELETE FROM T_items__item_settings WHERE iset_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$DB->query( 'DELETE FROM T_items__prerendering WHERE itpr_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$DB->query( 'DELETE FROM T_items__subscriptions WHERE isub_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$DB->query( 'DELETE FROM T_items__version WHERE iver_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$DB->query( 'DELETE FROM T_postcats WHERE postcat_post_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$DB->query( 'DELETE FROM T_slug WHERE slug_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$DB->query( 'DELETE l, lv FROM T_links AS l
											 LEFT JOIN T_links__vote AS lv ON lv.lvot_link_ID = l.link_ID
											WHERE l.link_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$DB->query( 'DELETE FROM T_items__user_data WHERE itud_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
			}
		}
		echo T_('OK').'<br />';

		echo T_('Removing the categories... ');
		evo_flush();
		$DB->query( 'DELETE FROM T_categories WHERE cat_blog_ID = '.$DB->quote( $wp_blog_ID ) );
		echo T_('OK').'<br />';

		echo T_('Removing the tags that are no longer used... ');
		evo_flush();
		if( !empty( $old_posts ) )
		{ // Remove the tags

			// Get tags from selected blog
			$SQL = new SQL();
			$SQL->SELECT( 'itag_tag_ID' );
			$SQL->FROM( 'T_items__itemtag' );
			$SQL->WHERE( 'itag_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
			$old_tags_this_blog = array_unique( $DB->get_col( $SQL->get() ) );

			if( !empty( $old_tags_this_blog ) )
			{
				// Get tags from other blogs
				$SQL = new SQL();
				$SQL->SELECT( 'itag_tag_ID' );
				$SQL->FROM( 'T_items__itemtag' );
				$SQL->WHERE( 'itag_itm_ID NOT IN ( '.implode( ', ', $old_posts ).' )' );
				$old_tags_other_blogs = array_unique( $DB->get_col( $SQL->get() ) );
				$old_tags_other_blogs_sql = !empty( $old_tags_other_blogs ) ? ' AND tag_ID NOT IN ( '.implode( ', ', $old_tags_other_blogs ).' )': '';

				// Remove the tags that are no longer used
				$DB->query( 'DELETE FROM T_items__tag
					WHERE tag_ID IN ( '.implode( ', ', $old_tags_this_blog ).' )'.
					$old_tags_other_blogs_sql );
			}

			// Remove the links of tags with posts
			$DB->query( 'DELETE FROM T_items__itemtag WHERE itag_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
		}
		echo T_('OK').'<br />';

		if( $delete_files )
		{ // Delete the files
			echo T_('Removing the files... ');

			if( ! empty( $deleted_file_IDs ) )
			{
				// Commit the DB changes before files deleting
				$DB->commit();

				// Get the deleted file IDs that are linked to other objects
				$SQL = new SQL();
				$SQL->SELECT( 'DISTINCT link_file_ID' );
				$SQL->FROM( 'T_links' );
				$SQL->WHERE( 'link_file_ID IN ( '.implode( ', ', $deleted_file_IDs ).' )' );
				$linked_file_IDs = $DB->get_col( $SQL->get() );
				// We can delete only the files that are NOT linked to other objects
				$deleted_file_IDs = array_diff( $deleted_file_IDs, $linked_file_IDs );

				$FileCache = & get_FileCache();
				foreach( $deleted_file_IDs as $deleted_file_ID )
				{
					if( ! ( $deleted_File = & $FileCache->get_by_ID( $deleted_file_ID, false, false ) ) )
					{ // Incorrect file ID
						echo '<p class="text-danger">'.sprintf( T_('No file #%s found in DB. It cannot be deleted.'), $deleted_file_ID ).'</p>';
					}
					if( ! $deleted_File->unlink() )
					{ // No permission to delete file
						echo '<p class="text-danger">'.sprintf( T_('Could not delete the file %s.'), '<code>'.$deleted_File->get_full_path().'</code>' ).'</p>';
					}
					// Clear cache to save memory
					$FileCache->clear();
				}

				// Start new transaction for the data inserting
				$DB->begin();
			}

			echo T_('OK').'<br />';
		}

		echo '<br />';
	}


	/* Import authors */
	$authors = array();
	$authors_IDs = array();
	if( isset( $xml_data['authors'] ) && count( $xml_data['authors'] ) > 0 )
	{
		global $Settings, $UserSettings;

		echo T_('Importing the users... ');
		evo_flush();

		// Get existing users
		$SQL = new SQL();
		$SQL->SELECT( 'user_login, user_ID' );
		$SQL->FROM( 'T_users' );
		$existing_users = $DB->get_assoc( $SQL->get() );

		$authors_count = 0;
		foreach( $xml_data['authors'] as $author )
		{
			// Replace unauthorized chars of username:
			$author_login = preg_replace( '/([^a-z0-9_])/i', '_', $author['author_login'] );
			$author_login = utf8_substr( utf8_strtolower( $author_login ), 0, 20 );

			if( empty( $existing_users[ $author_login ] ) )
			{	// Insert new user into DB if User doesn't exist with current login name

				$GroupCache = & get_GroupCache();
				if( !empty( $author['author_group'] ) )
				{	// Set user group from xml data
					if( ( $UserGroup = & $GroupCache->get_by_name( $author['author_group'], false ) ) === false )
					{	// If user's group doesn't exist yet, we should create new
						$UserGroup = new Group();
						$UserGroup->set( 'name', $author['author_group'] );
						$UserGroup->dbinsert();
					}
				}
				else
				{	// Set default user group is it is not defined in xml
					if( ( $UserGroup = & $GroupCache->get_by_name( 'Normal Users', false ) ) === false )
					{	// Exit from import of users, because we cannot set default user group
						break;
					}
				}

				unset( $author_created_from_country );
				if( !empty( $author['author_created_from_country'] ) )
				{	// Get country ID from DB by code
					$CountryCache = & get_CountryCache();
					if( ( $Country = & $CountryCache->get_by_name( $author['author_created_from_country'], false ) ) !== false )
					{
						$author_created_from_country = $Country->ID;
					}
				}

				// Get regional IDs by their names
				$author_regions = wp_get_regional_data( $author['author_country'], $author['author_region'], $author['author_subregion'], $author['author_city'] );

				$User = new User();
				$User->set( 'login', $author_login );
				$User->set( 'email', trim( $author['author_email'] ) );
				$User->set( 'firstname', $author['author_first_name'] );
				$User->set( 'lastname', $author['author_last_name'] );
				$User->set( 'pass', $author['author_pass'] );
				$User->set( 'salt', '' );
				$User->set( 'pass_driver', 'evo$md5' );
				$User->set_Group( $UserGroup );
				$User->set( 'status', !empty( $author['author_status'] ) ? $author['author_status'] : 'autoactivated' );
				$User->set( 'nickname', $author['author_nickname'] );
				$User->set( 'url', $author['author_url'] );
				$User->set( 'level', $author['author_level'] );
				$User->set( 'locale', $author['author_locale'] );
				$User->set( 'gender', ( $author['author_gender'] == 'female' ? 'F' : ( $author['author_gender'] == 'male' ? 'M' : '' ) ) );
				if( $author['author_age_min'] > 0 )
				{
					$User->set( 'age_min', $author['author_age_min'] );
				}
				if( $author['author_age_max'] > 0 )
				{
					$User->set( 'age_max', $author['author_age_max'] );
				}
				if( isset( $author_created_from_country ) )
				{	// User was created from this country
					$User->set( 'reg_ctry_ID', $author_created_from_country );
				}
				if( !empty( $author_regions['country'] ) )
				{	// Country
					$User->set( 'ctry_ID', $author_regions['country'] );
					if( !empty( $author_regions['region'] ) )
					{	// Region
						$User->set( 'rgn_ID', $author_regions['region'] );
						if( !empty( $author_regions['subregion'] ) )
						{	// Subregion
							$User->set( 'subrg_ID', $author_regions['subregion'] );
						}
						if( !empty( $author_regions['city'] ) )
						{	// City
							$User->set( 'city_ID', $author_regions['city'] );
						}
					}
				}
				$User->set( 'source', $author['author_source'] );
				$User->set_datecreated( empty( $author['author_created_ts'] ) ? time() : intval( $author['author_created_ts'] ) );
				$User->set( 'lastseen_ts', ( empty( $author['author_lastseen_ts'] ) ? NULL : $author['author_lastseen_ts'] ), true );
				$User->set( 'profileupdate_date', empty( $author['author_profileupdate_date'] ) ? date( 'Y-m-d H:i:s' ): $author['author_profileupdate_date'] );
				$User->dbinsert();
				$user_ID = $User->ID;
				if( !empty( $user_ID ) && !empty( $author['author_created_fromIPv4'] ) )
				{
					$UserSettings->set( 'created_fromIPv4', ip2int( $author['author_created_fromIPv4'] ), $user_ID );
				}
				$authors_count++;
			}
			else
			{	// Get ID of existing user
				$user_ID = $existing_users[ $author_login ];
			}
			// Save user ID of current author
			$authors[ $author_login ] = (string) $user_ID;
			$authors_IDs[ $author['author_id'] ] = (string) $user_ID;
		}

		$UserSettings->dbupdate();

		echo sprintf( T_('%d records'), $authors_count ).'<br />';
	}

	/* Import files, Copy them all to media folder */
	$files = array();
	if( isset( $xml_data['files'] ) && count( $xml_data['files'] ) > 0 )
	{
		echo T_('Importing the files... ');
		evo_flush();

		if( ! $attached_files_path || ! file_exists( $attached_files_path ) )
		{	// Display an error if files are attached but folder doesn't exist:
			echo '<p class="text-danger">'.sprintf( T_('No attachments folder %s found. It must exists to import the attached files properly.'), ( $attached_files_path ? '<code>'.$attached_files_path.'</code>' : '' ) ).'</p>';
		}
		else
		{	// Try to import files from the selected subfolder:
			$files_count = 0;

			foreach( $xml_data['files'] as $file )
			{
				switch( $file['file_root_type'] )
				{
					case 'shared':
						// Shared files
						$file['file_root_ID'] = 0;
						break;

					case 'user':
						// User's files
						if( isset( $authors_IDs[ $file['file_root_ID'] ] ) )
						{ // If owner of this file exists in our DB
							$file['file_root_ID'] = $authors_IDs[ $file['file_root_ID'] ];
							break;
						}
						// Otherwise we should upload this file into blog's folder:

					default: // 'collection', 'absolute', 'skins'
						// The files from other blogs and from other places must be moved in the folder of the current blog
						$file['file_root_type'] = 'collection';
						$file['file_root_ID'] = $wp_blog_ID;
						break;
				}

				// Source of the importing file:
				$file_source_path = $attached_files_path.$file['zip_path'].$file['file_path'];

				// Try to import file from source path:
				if( $File = & wpxml_create_File( $file_source_path, $file ) )
				{	// Store the created File in array because it will be linked to the Items below:
					$files[ $file['file_ID'] ] = $File;

					if( $import_img )
					{	// Collect file name in array to link with post below:
						$file_name = basename( $file['file_path'] );
						if( isset( $all_wp_attachments[ $file_name ] ) )
						{	// Don't use this file if more than one use same name, e.g. from different folders:
							$all_wp_attachments[ $file_name ] = false;
						}
						else
						{	// This is a first detected file with current name:
							$all_wp_attachments[ $file_name ] = $File->ID;
						}
					}

					$files_count++;
				}
			}

			echo sprintf( T_('%d records'), $files_count ).'<br />';
		}
	}

	/* Import categories */
	$category_default = 0;
	load_class( 'chapters/model/_chapter.class.php', 'Chapter' );

	// Get existing categories
	$SQL = new SQL();
	$SQL->SELECT( 'cat_urlname, cat_ID' );
	$SQL->FROM( 'T_categories' );
	$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $wp_blog_ID ) );
	$categories = $DB->get_assoc( $SQL->get() );

	if( isset( $xml_data['categories'] ) && count( $xml_data['categories'] ) > 0 )
	{
		echo T_('Importing the categories... ');
		evo_flush();

		load_funcs( 'locales/_charset.funcs.php' );

		$categories_count = 0;
		foreach( $xml_data['categories'] as $cat )
		{
			if( empty( $categories[ (string) $cat['category_nicename'] ] ) )
			{
				$Chapter = new Chapter( NULL, $wp_blog_ID );
				$Chapter->set( 'name', $cat['cat_name'] );
				$Chapter->set( 'urlname', $cat['category_nicename'] );
				$Chapter->set( 'description', $cat['category_description'] );
				if( !empty( $cat['category_parent'] ) && isset( $categories[ (string) $cat['category_parent'] ] ) )
				{	// Set category parent ID
					$Chapter->set( 'parent_ID', $categories[ (string) $cat['category_parent'] ] );
				}
				$Chapter->dbinsert();

				// Save new category
				$categories[ $cat['category_nicename'] ] = $Chapter->ID;
				if( empty( $category_default ) )
				{	// Set first category as default
					$category_default = $Chapter->ID;
				}
				$categories_count++;
			}
		}

		echo sprintf( T_('%d records'), $categories_count ).'<br />';
	}

	if( empty( $category_default ) )
	{ // No categories in XML file, Try to use first category(from DB) as default
		foreach( $categories as $category_name => $category_ID )
		{
			$category_default = $category_ID;
			break;
		}
	}

	if( empty( $category_default ) )
	{ // If category is still not defined then we should create default, because blog must has at least one category
		$new_Chapter = new Chapter( NULL, $wp_blog_ID );
		$new_Chapter->set( 'name', T_('Uncategorized') );
		$new_Chapter->set( 'urlname', $wp_Blog->get( 'urlname' ).'-main' );
		$new_Chapter->dbinsert();
		$category_default = $new_Chapter->ID;
	}

	/* Import tags */
	$tags = array();
	if( isset( $xml_data['tags'] ) && count( $xml_data['tags'] ) > 0 )
	{
		echo T_('Importing the tags... ');
		evo_flush();

		// Get existing tags
		$SQL = new SQL();
		$SQL->SELECT( 'tag_name, tag_ID' );
		$SQL->FROM( 'T_items__tag' );
		$tags = $DB->get_assoc( $SQL->get() );

		$tags_count = 0;
		foreach( $xml_data['tags'] as $tag )
		{
			$tag_name = substr( html_entity_decode( $tag['tag_name'] ), 0, 50 );
			if( empty( $tags[ $tag_name ] ) )
			{	// Insert new tag into DB if tag doesn't exist with current name
				$DB->query( 'INSERT INTO '.$tableprefix.'items__tag ( tag_name )
					VALUES ( '.$DB->quote( $tag_name ).' )' );
				$tag_ID = $DB->insert_id;
				// Save new tag
				$tags[ $tag_name ] = (string) $tag_ID;
				$tags_count++;
			}
		}
		echo sprintf( T_('%d records'), $tags_count ).'<br />';
	}


	/* Import posts */
	$posts = array();
	$comments = array();
	if( isset( $xml_data['posts'] ) && count( $xml_data['posts'] ) > 0 )
	{
		load_class( 'items/model/_item.class.php', 'Item' );

		// Set status's links between WP and b2evo names
		$post_statuses = array(
			// WP statuses => Their analogs in b2evolution
			'publish'    => 'published',
			'pending'    => 'review',
			'draft'      => 'draft',
			'inherit'    => 'draft',
			'trash'      => 'deprecated',
			// These statuses don't exist in WP, but we handle them if they will appear once
			'community'  => 'community',
			'deprecated' => 'deprecated',
			'protected'  => 'protected',
			'private'    => 'private',
			'review'     => 'review',
			'redirected' => 'redirected'
			// All other unknown statuses will be converted to 'review'
		);

		// Get post types
		$SQL = new SQL();
		$SQL->SELECT( 'LOWER( ityp_usage ), ityp_ID' );
		$SQL->FROM( 'T_items__type' );
		$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID' );
		$SQL->WHERE( 'itc_coll_ID = '.$DB->quote( $wp_blog_ID ) );
		$SQL->GROUP_BY( 'ityp_usage' );
		$SQL->ORDER_BY( 'ityp_ID' );
		$post_types = $DB->get_assoc( $SQL->get() );

		echo T_('Importing the files from attachment posts... ');
		evo_flush();

		$attachment_IDs = array();
		$attachments_count = 0;
		foreach( $xml_data['posts'] as $post )
		{	// Import ONLY attachment posts here, all other posts are imported below:
			if( $post['post_type'] != 'attachment' )
			{	// Skip not attachment post:
				continue;
			}

			echo '<p>'.sprintf( T_('Importing attachment: %s'), '#'.$post['post_id'].' - "'.$post['post_title'].'"' );

			if( isset( $post['postmeta'] ) )
			{	// Link the files to the Item from meta data:
				$attch_imported_files = array();
				foreach( $post['postmeta'] as $postmeta )
				{
					if( ! isset( $postmeta['key'] ) || ! isset( $postmeta['value'] ) )
					{	// Skip wrong meta data:
						continue;
					}
					$attch_file_name = '';
					$file_params = array(
							'file_root_type' => 'collection',
							'file_root_ID'   => $wp_blog_ID,
							'file_title'     => $post['post_title'],
							'file_desc'      => empty( $post['post_content'] ) ? $post['post_excerpt'] : $post['post_content'],
						);

					if( $postmeta['key'] == '_wp_attached_file' )
					{	// Get file name from the string meta data:
						$attch_file_name = $postmeta['value'];
					}
					elseif( $postmeta['key'] == '_wp_attachment_metadata' )
					{	// Try to get file name from the serialized meta data:
						$postmeta_value = @unserialize( $postmeta['value'] );
						if( isset( $postmeta_value['file'] ) )
						{	// Set file name:
							$attch_file_name = $postmeta_value['file'];
						}
					}
					if( empty( $attch_file_name ) || in_array( $attch_file_name, $attch_imported_files ) )
					{	// Skip empty file name or if it has been already imported:
						continue;
					}

					// Set file path where we should store the importing file relating to the collection folder:
					$file_params['file_path'] = preg_replace( '#^.+[/\\\\]#', '', $attch_file_name );

					// Source of the importing file:
					$file_source_path = $attached_files_path.$attch_file_name;

					// Try to import file from source path:
					if( $File = & wpxml_create_File( $file_source_path, $file_params ) )
					{	// Store the created File in array because it will be linked to the Items below:
						$attachment_IDs[ $post['post_id'] ] = $File->ID;
						$files[ $File->ID ] = $File;

						if( $import_img )
						{	// Collect file name in array to link with post below:
							$file_name = basename( $file_source_path );
							if( isset( $all_wp_attachments[ $file_name ] ) )
							{	// Don't use this file if more than one use same name, e.g. from different folders:
								$all_wp_attachments[ $file_name ] = false;
							}
							else
							{	// This is a first detected file with current name:
								$all_wp_attachments[ $file_name ] = $File->ID;
							}
						}

						$attachments_count++;
						// Break here because such post can contains only one file:
						break;
					}

					$attch_imported_files[] = $attch_file_name;
				}
			}

			echo '</p>';
			$attachments_count++;
		}

		echo sprintf( T_('%d records'), $attachments_count ).'<br />';

		echo T_('Importing the posts... ');
		evo_flush();

		$posts_count = 0;
		foreach( $xml_data['posts'] as $post )
		{
			if( $post['post_type'] == 'revision' )
			{	// Ignore post with type "revision":
				echo '<p class="text-warning">'.sprintf( T_('Ignore post "%s" because of post type is %s'),
						'#'.$post['post_id'].' - '.$post['post_title'],
						'<code>'.$post['post_type'].'</code>' )
					.'</p>';
				continue;
			}
			elseif( $post['post_type'] == 'attachment' )
			{	// Skip attachment post because it shoul be imported above:
				continue;
			}
			elseif( $post['post_type'] == 'page' && ! isset( $categories['standalone-pages'] ) )
			{	// Try to create special category "Standalone Pages" for pages only it doesn't exist:
				$page_Chapter = new Chapter( NULL, $wp_blog_ID );
				$page_Chapter->set( 'name', T_('Standalone Pages') );
				$page_Chapter->set( 'urlname', 'standalone-pages' );
				$page_Chapter->dbinsert();
				$categories['standalone-pages'] = $page_Chapter->ID;
				// Add new created chapter to cache to avoid error when this cache loaded all elements before:
				$ChapterCache = & get_ChapterCache();
				$ChapterCache->add( $page_Chapter );
			}

			echo '<p>'.sprintf( T_('Importing post: %s'), '#'.$post['post_id'].' - "'.$post['post_title'].'"' );

			$author_ID = isset( $authors[ (string) $post['post_author'] ] ) ? $authors[ (string) $post['post_author'] ] : 1;
			$last_edit_user_ID = isset( $authors[ (string) $post['post_lastedit_user'] ] ) ? $authors[ (string) $post['post_lastedit_user'] ] : $author_ID;

			$post_main_cat_ID = $category_default;
			$post_extra_cat_IDs = array();
			$post_tags = array();
			if( !empty( $post['terms'] ) )
			{ // Set categories and tags
				foreach( $post['terms'] as $term )
				{
					switch( $term['domain'] )
					{
						case 'category':
							if( isset( $categories[ (string) $term['slug'] ] ) )
							{
								if( $post_main_cat_ID == $category_default )
								{ // Set main category
									$post_main_cat_ID = $categories[ (string) $term['slug'] ];
								}
								// Set extra categories
								$post_extra_cat_IDs[] = $categories[ (string) $term['slug'] ];
							}
							break;

						case 'post_tag':
							$tag_name = substr( html_entity_decode( $tag['tag_name'] ), 0, 50 );
							if( isset( $tags[ $tag_name ] ) )
							{ // Set tag
								$post_tags[] = $tag_name;
							}
							break;
					}
				}
			}

			if( $post['post_type'] == 'page' )
			{	// Set static category "Standalone Pages" for pages because they have no categories in wordpress DB:
				$post_main_cat_ID = $categories['standalone-pages'];
				$post_extra_cat_IDs[] = $categories['standalone-pages'];
			}

			// Set post type ID
			$post_type_ID = isset( $post_types[ strtolower( $post['post_type'] ) ] ) ? $post_types[ strtolower( $post['post_type'] ) ] : '1';

			// Get regional IDs by their names
			$item_regions = wp_get_regional_data( $post['post_country'], $post['post_region'], $post['post_subregion'], $post['post_city'] );

			$post_content = $post['post_content'];

			$Item = new Item();
			$Item->set( 'main_cat_ID', $post_main_cat_ID );
			$Item->set( 'creator_user_ID', $author_ID );
			$Item->set( 'lastedit_user_ID', $last_edit_user_ID );
			$Item->set( 'title', $post['post_title'] );
			$Item->set( 'content', $post_content );
			$Item->set( 'datestart', $post['post_date'] );
			$Item->set( 'datecreated', !empty( $post['post_datecreated'] ) ? $post['post_datecreated'] : $post['post_date'] );
			$Item->set( 'datemodified', !empty( $post['post_datemodified'] ) ? $post['post_datemodified'] : $post['post_date'] );
			$Item->set( 'urltitle', !empty( $post['post_urltitle'] ) ? $post['post_urltitle'] : $post['post_title'] );
			$Item->set( 'url', $post['post_url'] );
			$Item->set( 'status', isset( $post_statuses[ (string) $post['status'] ] ) ? $post_statuses[ (string) $post['status'] ] : 'review' );
			// If 'comment_status' has the unappropriate value set it to 'open'
			$Item->set( 'comment_status', ( in_array( $post['comment_status'], array( 'open', 'closed', 'disabled' ) ) ? $post['comment_status'] : 'open' ) );
			$Item->set( 'ityp_ID', $post_type_ID );
			if( empty( $post['post_excerpt'] ) )
			{	// If excerpt is not provided:
				if( ! empty( $post_content ) )
				{	// Generate excerpt from content:
					$Item->set_param( 'excerpt', 'string', excerpt( $post_content ), true );
					$Item->set( 'excerpt_autogenerated', 1 );
				}
			}
			else
			{	// Set excerpt from given value:
				$Item->set_param( 'excerpt', 'string', $post['post_excerpt'], true );
			}
			$Item->set( 'extra_cat_IDs', $post_extra_cat_IDs );
			$Item->set( 'dateset', $post['post_date_mode'] == 'set' ? 1 : 0 );
			if( isset( $authors[ (string) $post['post_assigned_user'] ] ) )
			{
				$Item->set( 'assigned_user', $authors[ (string) $post['post_assigned_user'] ] );
			}
			$Item->set( 'datedeadline', $post['post_datedeadline'] );
			$Item->set( 'locale', $post['post_locale'] );
			$Item->set( 'excerpt_autogenerated', $post['post_excerpt_autogenerated'] );
			$Item->set( 'titletag', $post['post_titletag'] );
			$Item->set( 'notifications_status', empty( $post['post_notifications_status'] ) ? 'noreq' : $post['post_notifications_status'] );
			$Item->set( 'renderers', array( $post['post_renderers'] ) );
			$Item->set( 'priority', $post['post_priority'] );
			$Item->set( 'featured', $post['post_featured'] );
			$Item->set( 'order', $post['post_order'] );
			if( !empty( $item_regions['country'] ) )
			{	// Country
				$Item->set( 'ctry_ID', $item_regions['country'] );
				if( !empty( $item_regions['region'] ) )
				{	// Region
					$Item->set( 'rgn_ID', $item_regions['region'] );
					if( !empty( $item_regions['subregion'] ) )
					{	// Subregion
						$Item->set( 'subrg_ID', $item_regions['subregion'] );
					}
					if( !empty( $item_regions['city'] ) )
					{	// City
						$Item->set( 'city_ID', $item_regions['city'] );
					}
				}
			}

			if( count( $post_tags ) > 0 )
			{
				$Item->tags = $post_tags;
			}

			$Item->dbinsert();
			$posts[ $post['post_id'] ] = $Item->ID;

			$LinkOwner = new LinkItem( $Item );
			$updated_post_content = $post_content;
			$link_order = 1;

			if( ! empty( $files ) && ! empty( $post['links'] ) )
			{	// Link the files to the Item if it has them:
				foreach( $post['links'] as $link )
				{
					$file_is_linked = false;
					if( isset( $files[ $link['link_file_ID'] ] ) )
					{	// Link a File to Item:
						$File = $files[ $link['link_file_ID'] ];
						if( $File->link_to_Object( $LinkOwner, $link['link_order'], $link['link_position'] ) )
						{	// If file has been linked to the post
							echo '<p class="text-success">'.sprintf( T_('File %s has been linked to this post.'), '<code>'.$File->_adfp_full_path.'</code>' ).'</p>';
							$file_is_linked = true;
							// Update link order to the latest for two other ways([caption] and <img />) below:
							$link_order = $link['link_order'];
						}
					}
					if( ! $file_is_linked )
					{	// If file could not be linked to the post:
						echo '<p class="text-warning">'.sprintf( T_('Link %s could not be attached to this post because file %s is not found.'), '#'.$link['link_ID'], '#'.$link['link_file_ID'] ).'</p>';
					}
				}
			}

			// Try to extract files from content tag [caption ...]:
			if( preg_match_all( '#\[caption[^\]]+id="attachment_(\d+)"[^\]]+\].+?\[/caption\]#i', $updated_post_content, $caption_matches ) )
			{	// If [caption ...] tag is detected
				foreach( $caption_matches[1] as $caption_post_ID )
				{
					$file_is_linked = false;
					if( isset( $attachment_IDs[ $caption_post_ID ] ) && isset( $files[ $attachment_IDs[ $caption_post_ID ] ] ) )
					{
						$File = $files[ $attachment_IDs[ $caption_post_ID ] ];
						if( $link_ID = $File->link_to_Object( $LinkOwner, $link_order, 'inline' ) )
						{	// If file has been linked to the post
							echo '<p class="text-success">'.sprintf( T_('File %s has been linked to this post.'), '<code>'.$File->_adfp_full_path.'</code>' ).'</p>';
							// Replace this caption tag from content with b2evolution format:
							$updated_post_content = preg_replace( '#\[caption[^\]]+id="attachment_'.$caption_post_ID.'"[^\]]+\].+?\[/caption\]#i', ( $File->is_image() ? '[image:'.$link_ID.']' : '[file:'.$link_ID.']' ), $updated_post_content );
							$file_is_linked = true;
							$link_order++;
						}
					}
					if( ! $file_is_linked )
					{	// If file could not be linked to the post:
						echo '<p class="text-warning">'.sprintf( T_('Caption file %s could not be attached to this post because it is not found in the source attachments folder.'), '#'.$caption_post_ID ).'</p>';
					}
				}
			}

			// Try to extract files from html tag <img />:
			if( $import_img && count( $all_wp_attachments ) )
			{	// Only if it is requested and at least one attachment has been detected above:
				if( preg_match_all( '#<img[^>]+src="([^"]+)"[^>]+>#i', $updated_post_content, $img_matches ) )
				{	// If <img /> tag is detected
					foreach( $img_matches[1] as $img_url )
					{
						$file_is_linked = false;
						$img_file_name = basename( $img_url );
						if( isset( $all_wp_attachments[ $img_file_name ], $files[ $all_wp_attachments[ $img_file_name ] ] ) )
						{
							$File = $files[ $all_wp_attachments[ $img_file_name ] ];
							if( $link_ID = $File->link_to_Object( $LinkOwner, $link_order, 'inline' ) )
							{	// If file has been linked to the post
								echo '<p class="text-success">'.sprintf( T_('File %s has been linked to this post.'), '<code>'.$File->_adfp_full_path.'</code>' ).'</p>';
								// Replace this img tag from content with b2evolution format:
								$updated_post_content = preg_replace( '#<img[^>]+src="[^"]+'.preg_quote( $img_file_name ).'"[^>]+>#i', '[image:'.$link_ID.']', $updated_post_content );
								$file_is_linked = true;
								$link_order++;
							}
						}
						if( ! $file_is_linked )
						{	// If file could not be linked to the post:
							echo '<p class="text-warning">'.sprintf( T_('File of image url %s could not be attached to this post because it is not found in the source attachments folder.'), '<code>'.$img_url.'</code>' ).'</p>';
						}
					}
				}
			}

			if( $updated_post_content != $post_content )
			{	// Update new content:
				$Item->set( 'content', $updated_post_content );
				$Item->dbupdate();
			}

			if( !empty( $post['comments'] ) )
			{ // Set comments
				$comments[ $Item->ID ] = $post['comments'];
			}

			echo '</p>';
			$posts_count++;
		}

		foreach( $xml_data['posts'] as $post )
		{	// Set post parents
			if( !empty( $post['post_parent'] ) && isset( $posts[ (string) $post['post_parent'] ], $posts[ (string) $post['post_id'] ] ) )
			{
				$DB->query( 'UPDATE '.$tableprefix.'items__item
						  SET post_parent_ID = '.$DB->quote( $posts[ (string) $post['post_parent'] ] ).'
						WHERE post_ID = '.$DB->quote( $posts[ (string) $post['post_id'] ] ) );
			}
		}

		echo sprintf( T_('%d records'), $posts_count ).'<br />';
	}


	/* Import comments */
	if( !empty( $comments ) )
	{
		echo T_('Importing the comments... ');
		evo_flush();

		$comments_count = 0;
		$comments_IDs = array();
		foreach( $comments as $post_ID => $comments )
		{
			if( empty( $comments ) )
			{	// Skip if no comments
				continue;
			}

			foreach( $comments as $comment )
			{
				$comment_author_user_ID = 0;
				if( !empty( $comment['comment_user_id'] ) && isset( $authors_IDs[ (string) $comment['comment_user_id'] ] ) )
				{	// Author ID
					$comment_author_user_ID = $authors_IDs[ (string) $comment['comment_user_id'] ];
				}

				$comment_parent_ID = 0;
				if( !empty( $comment['comment_parent'] ) && isset( $comments_IDs[ (string) $comment['comment_parent'] ] ) )
				{	// Parent comment ID
					$comment_parent_ID = $comments_IDs[ (string) $comment['comment_parent'] ];
				}

				unset( $comment_IP_country );
				if( !empty( $comment['comment_IP_country'] ) )
				{	// Get country ID by code
					$CountryCache = & get_CountryCache();
					if( $Country = & $CountryCache->get_by_name( $comment['comment_IP_country'], false ) )
					{
						$comment_IP_country = $Country->ID;
					}
				}

				$Comment = new Comment();
				$Comment->set( 'item_ID', $post_ID );
				if( !empty( $comment_parent_ID ) )
				{
					$Comment->set( 'in_reply_to_cmt_ID', $comment_parent_ID );
				}
				$Comment->set( 'date', $comment['comment_date'] );
				if( !empty( $comment_author_user_ID ) )
				{
					$Comment->set( 'author_user_ID', $comment_author_user_ID );
				}
				$Comment->set( 'author', utf8_substr( $comment['comment_author'], 0, 100 ) );
				$Comment->set( 'author_IP', $comment['comment_author_IP'] );
				$Comment->set( 'author_email', $comment['comment_author_email'] );
				$Comment->set( 'content', $comment['comment_content'] );
				if( empty( $comment['comment_status'] ) )
				{	// If comment status is empty (the export of wordpress doesn't provide this field)
					$Comment->set( 'status', $comment['comment_approved'] == '1' ? 'published' : 'draft' );
				}
				else
				{	// Set status when we have predefined value
					$Comment->set( 'status', $comment['comment_status'] );
				}
				if( !empty( $comment_IP_country ) )
				{	// Country
					$Comment->set( 'IP_ctry_ID', $comment_IP_country );
				}
				$Comment->set( 'rating', $comment['comment_rating'] );
				$Comment->set( 'featured', $comment['comment_featured'] );
				$Comment->set( 'nofollow', $comment['comment_nofollow'] );
				$Comment->set( 'helpful_addvotes', $comment['comment_helpful_addvotes'] );
				$Comment->set( 'helpful_countvotes', $comment['comment_helpful_countvotes'] );
				$Comment->set( 'spam_addvotes', $comment['comment_spam_addvotes'] );
				$Comment->set( 'spam_countvotes', $comment['comment_spam_countvotes'] );
				$Comment->set( 'karma', $comment['comment_karma'] );
				$Comment->set( 'spam_karma', $comment['comment_spam_karma'] );
				$Comment->set( 'allow_msgform', $comment['comment_allow_msgform'] );
				$Comment->set( 'notif_status', empty( $comment['comment_notif_status'] ) ? 'noreq' : $comment['comment_notif_status'] );
				$Comment->dbinsert();

				$comments_IDs[ $comment['comment_id'] ] = $Comment->ID;
				$comments_count++;
			}
		}

		echo sprintf( T_('%d records'), $comments_count ).'<br />';
	}

	if( ! empty( $ZIP_folder_path ) && file_exists( $ZIP_folder_path ) )
	{	// This folder was created only to extract files from ZIP package, Remove it now:
		rmdir_r( $ZIP_folder_path );
	}

	echo '<br /><p class="text-success">'.T_('Import complete.').'</p>';

	$DB->commit();
}


/**
 * Parse WordPress XML file into array
 *
 * @param string File path
 * @return array XML data:
 *          authors
 *          posts
 *          categories
 *          tags
 *          terms
 *          base_url
 *          wxr_version
 */
function wpxml_parser( $file )
{
	$authors = array();
	$posts = array();
	$categories = array();
	$tags = array();
	$terms = array();
	$files = array();

	// Register filter to avoid wrong chars in XML content:
	stream_filter_register( 'xmlutf8', 'ValidUTF8XMLFilter' );

	// Load XML content from file with xmlutf8 filter:
	$xml = simplexml_load_file( 'php://filter/read=xmlutf8/resource='.$file );

	// Get WXR version
	$wxr_version = $xml->xpath( '/rss/channel/wp:wxr_version' );
	$wxr_version = (string) trim( $wxr_version[0] );

	$base_url = $xml->xpath( '/rss/channel/wp:base_site_url' );
	$base_url = (string) trim( $base_url[0] );

	// Check language
	global $evo_charset, $xml_import_convert_to_latin;
	$language = $xml->xpath( '/rss/channel/language' );
	$language = (string) trim( $language[0] );
	if( $evo_charset != 'utf-8' && ( strpos( $language, 'utf8' ) !== false ) )
	{ // We should convert the text values from utf8 to latin1
		$xml_import_convert_to_latin = true;
	}
	else
	{ // Don't convert, it is correct encoding
		$xml_import_convert_to_latin = false;
	}

	$namespaces = $xml->getDocNamespaces();
	if( !isset( $namespaces['wp'] ) )
	{
		$namespaces['wp'] = 'http://wordpress.org/export/1.1/';
	}
	if( !isset( $namespaces['evo'] ) )
	{
		$namespaces['evo'] = 'http://b2evolution.net/export/1.0/';
	}
	if( !isset( $namespaces['excerpt'] ) )
	{
		$namespaces['excerpt'] = 'http://wordpress.org/export/1.1/excerpt/';
	}

	// Get authors
	foreach( $xml->xpath('/rss/channel/wp:author') as $author_arr )
	{
		$a = $author_arr->children( $namespaces['wp'] );
		$ae = $author_arr->children( $namespaces['evo'] );
		$login = (string) $a->author_login;
		$authors[$login] = array(
			'author_id'                   => (int) $a->author_id,
			'author_login'                => $login,
			'author_email'                => (string) $a->author_email,
			'author_display_name'         => wpxml_convert_value( $a->author_display_name ),
			'author_first_name'           => wpxml_convert_value( $a->author_first_name ),
			'author_last_name'            => wpxml_convert_value( $a->author_last_name ),
			'author_pass'                 => (string) $ae->author_pass,
			'author_group'                => (string) $ae->author_group,
			'author_status'               => (string) $ae->author_status,
			'author_nickname'             => wpxml_convert_value( $ae->author_nickname ),
			'author_url'                  => (string) $ae->author_url,
			'author_level'                => (int) $ae->author_level,
			'author_locale'               => (string) $ae->author_locale,
			'author_gender'               => (string) $ae->author_gender,
			'author_age_min'              => (int) $ae->author_age_min,
			'author_age_max'              => (int) $ae->author_age_max,
			'author_created_from_country' => (string) $ae->author_created_from_country,
			'author_country'              => (string) $ae->author_country,
			'author_region'               => (string) $ae->author_region,
			'author_subregion'            => (string) $ae->author_subregion,
			'author_city'                 => (string) $ae->author_city,
			'author_source'               => (string) $ae->author_source,
			'author_created_ts'           => (string) $ae->author_created_ts,
			'author_lastseen_ts'          => (string) $ae->author_lastseen_ts,
			'author_created_fromIPv4'     => (string) $ae->author_created_fromIPv4,
			'author_profileupdate_date'   => (string) $ae->author_profileupdate_date,
		);
	}

	// Get files
	foreach( $xml->xpath('/rss/channel/file') as $file_arr )
	{
		$t = $file_arr->children( $namespaces['evo'] );
		$files[] = array(
			'file_ID'        => (int) $t->file_ID,
			'file_root_type' => (string) $t->file_root_type,
			'file_root_ID'   => (int) $t->file_root_ID,
			'file_path'      => (string) $t->file_path,
			'file_title'     => wpxml_convert_value( $t->file_title ),
			'file_alt'       => wpxml_convert_value( $t->file_alt ),
			'file_desc'      => wpxml_convert_value( $t->file_desc ),
			'zip_path'       => (string) $t->zip_path,
		);
	}

	// Get categories
	foreach( $xml->xpath('/rss/channel/wp:category') as $term_arr )
	{
		$t = $term_arr->children( $namespaces['wp'] );
		$categories[] = array(
			'term_id'              => (int) $t->term_id,
			'category_nicename'    => wpxml_convert_value( $t->category_nicename ),
			'category_parent'      => (string) $t->category_parent,
			'cat_name'             => wpxml_convert_value( $t->cat_name ),
			'category_description' => wpxml_convert_value( $t->category_description )
		);
	}

	// Get tags
	foreach( $xml->xpath('/rss/channel/wp:tag') as $term_arr )
	{
		$t = $term_arr->children( $namespaces['wp'] );
		$tags[] = array(
			'term_id'         => (int) $t->term_id,
			'tag_slug'        => (string) $t->tag_slug,
			'tag_name'        => wpxml_convert_value( $t->tag_name ),
			'tag_description' => wpxml_convert_value( $t->tag_description )
		);
	}

	// Get terms
	foreach( $xml->xpath('/rss/channel/wp:term') as $term_arr )
	{
		$t = $term_arr->children( $namespaces['wp'] );
		$terms[] = array(
			'term_id'          => (int) $t->term_id,
			'term_taxonomy'    => (string) $t->term_taxonomy,
			'slug'             => (string) $t->term_slug,
			'term_parent'      => (string) $t->term_parent,
			'term_name'        => wpxml_convert_value( $t->term_name ),
			'term_description' => wpxml_convert_value( $t->term_description )
		);
	}

	// Get posts
	foreach( $xml->channel->item as $item )
	{
		$post = array(
			'post_title' => wpxml_convert_value( $item->title ),
			'guid'       => (string) $item->guid,
		);

		$dc = $item->children( 'http://purl.org/dc/elements/1.1/' );
		$post['post_author'] = (string) $dc->creator;

		$content = $item->children( 'http://purl.org/rss/1.0/modules/content/' );
		$excerpt = $item->children( $namespaces['excerpt'] );
		$post['post_content'] = wpxml_convert_value( $content->encoded );
		$post['post_excerpt'] = wpxml_convert_value( $excerpt->encoded );

		$wp = $item->children( $namespaces['wp'] );
		$evo = $item->children( $namespaces['evo'] );

		$post['post_id']        = (int) $wp->post_id;
		$post['post_date']      = (string) $wp->post_date;
		$post['post_date_gmt']  = (string) $wp->post_date_gmt;
		$post['comment_status'] = (string) $wp->comment_status;
		$post['ping_status']    = (string) $wp->ping_status;
		$post['post_name']      = (string) $wp->post_name;
		$post['status']         = (string) $wp->status;
		$post['post_parent']    = (int) $wp->post_parent;
		$post['menu_order']     = (int) $wp->menu_order;
		$post['post_type']      = (string) $wp->post_type;
		$post['post_password']  = (string) $wp->post_password;
		$post['is_sticky']      = (int) $wp->is_sticky;
		$post['post_date_mode']     = (string) $evo->post_date_mode;
		$post['post_lastedit_user'] = (string) $evo->post_lastedit_user;
		$post['post_assigned_user'] = (string) $evo->post_assigned_user;
		$post['post_datedeadline']  = (string) $evo->post_datedeadline;
		$post['post_datecreated']   = (string) $evo->post_datecreated;
		$post['post_datemodified']  = (string) $evo->post_datemodified;
		$post['post_locale']        = (string) $evo->post_locale;
		$post['post_excerpt_autogenerated'] = (int) $evo->post_excerpt_autogenerated;
		$post['post_urltitle']      = (string) $evo->post_urltitle;
		$post['post_titletag']      = (string) $evo->post_titletag;
		$post['post_url']           = (string) $evo->post_url;
		$post['post_notifications_status'] = (string) $evo->post_notifications_status;
		$post['post_renderers']     = (string) $evo->post_renderers;
		$post['post_priority']      = (int) $evo->post_priority;
		$post['post_featured']      = (int) $evo->post_featured;
		$post['post_order']         = (int) $evo->post_order;
		$post['post_country']       = (string) $evo->post_country;
		$post['post_region']        = (string) $evo->post_region;
		$post['post_subregion']     = (string) $evo->post_subregion;
		$post['post_city']          = (string) $evo->post_city;

		if( isset( $wp->attachment_url ) )
		{
			$post['attachment_url'] = (string) $wp->attachment_url;
		}

		foreach ( $item->category as $c )
		{
			$att = $c->attributes();
			if( isset( $att['nicename'] ) )
			{
				$post['terms'][] = array(
					'name'   => (string) $c,
					'slug'   => wpxml_convert_value( $att['nicename'] ),
					'domain' => (string) $att['domain']
				);
			}
		}

		foreach( $wp->postmeta as $meta )
		{
			$post['postmeta'][] = array(
				'key'   => (string) $meta->meta_key,
				'value' => wpxml_convert_value( $meta->meta_value )
			);
		}

		foreach( $wp->comment as $comment )
		{
			$evo_comment = $comment->children( $namespaces['evo'] );

			$meta = array();
			if( isset( $comment->commentmeta ) )
			{
				foreach( $comment->commentmeta as $m )
				{
					$meta[] = array(
						'key'   => (string) $m->meta_key,
						'value' => wpxml_convert_value( $m->meta_value )
					);
				}
			}

			$post['comments'][] = array(
				'comment_id'           => (int) $comment->comment_id,
				'comment_author'       => wpxml_convert_value( $comment->comment_author ),
				'comment_author_email' => (string) $comment->comment_author_email,
				'comment_author_IP'    => (string) $comment->comment_author_IP,
				'comment_author_url'   => (string) $comment->comment_author_url,
				'comment_date'         => (string) $comment->comment_date,
				'comment_date_gmt'     => (string) $comment->comment_date_gmt,
				'comment_content'      => wpxml_convert_value( $comment->comment_content ),
				'comment_approved'     => (string) $comment->comment_approved,
				'comment_type'         => (string) $comment->comment_type,
				'comment_parent'       => (string) $comment->comment_parent,
				'comment_user_id'      => (int) $comment->comment_user_id,
				'comment_status'             => (string) $evo_comment->comment_status,
				'comment_IP_country'         => (string) $evo_comment->comment_IP_country,
				'comment_rating'             => (int) $evo_comment->comment_rating,
				'comment_featured'           => (int) $evo_comment->comment_featured,
				'comment_nofollow'           => (int) $evo_comment->comment_nofollow,
				'comment_helpful_addvotes'   => (int) $evo_comment->comment_helpful_addvotes,
				'comment_helpful_countvotes' => (int) $evo_comment->comment_helpful_countvotes,
				'comment_spam_addvotes'      => (int) $evo_comment->comment_spam_addvotes,
				'comment_spam_countvotes'    => (int) $evo_comment->comment_spam_countvotes,
				'comment_karma'              => (int) $evo_comment->comment_comment_karma,
				'comment_spam_karma'         => (int) $evo_comment->comment_spam_karma,
				'comment_allow_msgform'      => (int) $evo_comment->comment_allow_msgform,
				'comment_notif_status'       => (string) $evo_comment->comment_notif_status,
				'commentmeta'                => $meta,
			);
		}

		foreach( $evo->link as $link )
		{ // Get the links
			$evo_link = $link->children( $namespaces['evo'] );

			$post['links'][] = array(
				'link_ID'               => (int) $link->link_ID,
				'link_datecreated'      => (string) $link->link_datecreated,
				'link_datemodified'     => (string) $link->link_datemodified,
				'link_creator_user_ID'  => (int) $link->link_creator_user_ID,
				'link_lastedit_user_ID' => (int) $link->link_lastedit_user_ID,
				'link_itm_ID'           => (int) $link->link_itm_ID,
				'link_cmt_ID'           => (int) $link->link_cmt_ID,
				'link_usr_ID'           => (int) $link->link_usr_ID,
				'link_file_ID'          => (int) $link->link_file_ID,
				'link_ltype_ID'         => (int) $link->link_ltype_ID,
				'link_position'         => (string) $link->link_position,
				'link_order'            => (int) $link->link_order,
			);
		}

		$posts[] = $post;
	}

	return array(
		'authors'    => $authors,
		'files'      => $files,
		'posts'      => $posts,
		'categories' => $categories,
		'tags'       => $tags,
		'terms'      => $terms,
		'base_url'   => $base_url,
		'version'    => $wxr_version
	);
}


/**
 * Check WordPress XML file for correct format
 *
 * @param string File path
 * @param boolean TRUE to halt process of error, FALSE to print out error
 * @return boolean TRUE on success, FALSE or HALT on errors
 */
function wpxml_check_xml_file( $file, $halt = false )
{
	// Enable XML error handling:
	$internal_errors = libxml_use_internal_errors( true );

	// Clear error of previous XML file (e.g. when ZIP archive has several XML files):
	libxml_clear_errors();

	// Register filter to avoid wrong chars in XML content:
	stream_filter_register( 'xmlutf8', 'ValidUTF8XMLFilter' );

	// Load XML content from file with xmlutf8 filter:
	$xml = simplexml_load_file( 'php://filter/read=xmlutf8/resource='.$file );

	if( ! $xml )
	{	// Halt/Display if loading produces an error:
		$errors = array();
		if( $halt )
		{	// Halt on error:
			foreach( libxml_get_errors() as $error )
			{
				$errors[] = 'Line '.$error->line.' - "'.format_to_output( $error->message, 'htmlspecialchars' ).'"';
			}
			debug_die( 'There was an error when reading XML file "'.$file.'".'
				.' Error: '.implode( ', ', $errors ) );
		}
		else
		{	// Display error:
			foreach( libxml_get_errors() as $error )
			{
				$errors[] = sprintf( T_('Line %s'), '<code>'.$error->line.'</code>' ).' - '.'"'.format_to_output( $error->message, 'htmlspecialchars' ).'"';
			}
			echo '<p class="text-danger">'.sprintf( T_('There was an error when reading XML file %s.'), '<code>'.$file.'</code>' ).'<br />'
				.sprintf( T_('Error: %s'), implode( ',<br />', $errors ) ).'</p>';
			return false;
		}
	}

	// Check WXR version for correct format:
	$r = false;
	if( $wxr_version = $xml->xpath( '/rss/channel/wp:wxr_version' ) )
	{
		$wxr_version = (string) trim( $wxr_version[0] );
		$r = preg_match( '/^\d+\.\d+$/', $wxr_version );
	}

	if( ! $r )
	{	// If file format is wrong:
		if( $halt )
		{	// Halt on error:
			debug_die( 'This does not appear to be a XML file, missing/invalid WXR version number.' );
		}
		else
		{	// Display error:
			echo '<p class="text-danger">'.T_('This does not appear to be a XML file, missing/invalid WXR version number.').'</p>';
			return false;
		}
	}

	return true;
}


/**
 * Get the unique url name
 *
 * @param string Source text
 * @param string Table name
 * @param string Field name
 * @return string category's url name
 */
function wp_unique_urlname( $source, $table, $field )
{
	global $DB;

	// Replace special chars/umlauts, if we can convert charsets:
	load_funcs( 'locales/_charset.funcs.php' );
	$url_name = strtolower( replace_special_chars( $source ) );

	$url_number = 1;
	$url_name_correct = $url_name;
	do
	{	// Check for unique url name in DB
		$SQL = new SQL();
		$SQL->SELECT( $field );
		$SQL->FROM( $table );
		$SQL->WHERE( $field.' = '.$DB->quote( $url_name_correct ) );
		$category = $DB->get_var( $SQL->get() );
		if( $category )
		{	// Category already exists with such url name; Change it
			$url_name_correct = $url_name.'-'.$url_number;
			$url_number++;
		}
	}
	while( !empty( $category ) );

	return $url_name_correct;
}


/**
 * Get regional data (Used to get regional IDs for user & item by regional names)
 *
 * @param string Country code
 * @param string Region name
 * @param string Subregion name
 * @param string City name
 * @return array Regional data
 */
function wp_get_regional_data( $country_code, $region, $subregion, $city )
{
	$data = array(
			'country' => 0,
			'region' => 0,
			'subregion' => 0,
			'city' => 0,
		);

	if( !empty( $country_code ) )
	{	// Get country ID from DB by code
		$CountryCache = & get_CountryCache();
		if( $Country = & $CountryCache->get_by_name( $country_code, false ) )
		{
			$data['country'] = $Country->ID;

			if( !empty( $region ) )
			{	// Get region ID from DB by name
				$RegionCache = & get_RegionCache();
				if( $Region = & $RegionCache->get_by_name( $region, false ) )
				{
					if( $Region->ctry_ID == $data['country'] )
					{
						$data['region'] = $Region->ID;

						if( !empty( $subregion ) )
						{	// Get subregion ID from DB by name
							$SubregionCache = & get_SubregionCache();
							if( $Subregion = & $SubregionCache->get_by_name( $subregion, false ) )
							{
								if( $Subregion->rgn_ID == $data['region'] )
								{
									$data['subregion'] = $Subregion->ID;
								}
							}
						}

						if( !empty( $city ) )
						{	// Get city ID from DB by name
							$CityCache = & get_CityCache();
							if( $City = & $CityCache->get_by_name( $city, false ) )
							{
								if( $City->rgn_ID == $data['region'] )
								{
									$data['city'] = $City->ID;
								}
							}
						}
					}
				}
			}
		}
	}

	return $data;
}


/**
 * Get available files to import from the folder /media/import/
 * 
 * @return array Files
 */
function wpxml_get_import_files()
{
	global $media_path;

	// Get all files from the import folder
	$files = get_filenames( $media_path.'import/', array(
			'flat' => false
		) );

	$import_files = array();

	if( empty( $files ) )
	{ // No access to the import folder OR it is empty
		return $import_files;
	}

	foreach( $files as $file )
	{
		$file_paths = array();
		$file_type = '';
		if( is_array( $file ) )
		{	// It is a folder, Find xml files inside:
			foreach( $file as $key => $sub_file )
			{
				if( is_string( $sub_file ) && preg_match( '/\.(xml|txt)$/i', $sub_file ) )
				{
					$file_paths[] = $sub_file;
				}
			}
		}
		elseif( is_string( $file ) )
		{	// File in the root:
			$file_paths[] = $file;
		}

		foreach( $file_paths as $file_path )
		{
			if( ! empty( $file_path ) && preg_match( '/\.(xml|txt|zip)$/i', $file_path, $file_matches ) )
			{	// This file can be a file with import data
				if( empty( $file_type ) )
				{	// Set type from file extension
					if( $file_matches[1] == 'zip' )
					{
						$file_type = T_('Compressed Archive');
					}
					else
					{
						if( $file_attachments_folder = wpxml_get_attachments_folder( $file_path ) )
						{	// Probably it is a file with attachments folder:
							$file_type = sprintf( T_('Complete export (attachments folder: %s)'), '<code>'.basename( $file_attachments_folder ).'</code>' );
						}
						else
						{	// Single XML file without attachments folder:
							$file_type = T_('Basic export (no attachments folder found)');
						}
					}
				}
				$import_files[] = array(
						'path' => $file_path,
						'type' => $file_type,
					);
			}
		}
	}

	return $import_files;
}


/**
 * Convert string value to normal encoding
 *
 * @param string Value
 * @return string A converted value
 */
function wpxml_convert_value( $value )
{
	global $xml_import_convert_to_latin;

	$value = (string) $value;

	if( $xml_import_convert_to_latin )
	{ // We should convert a value from utf8 to latin1
		if( function_exists( 'iconv' ) )
		{ // Convert by iconv extenssion
			$value = iconv( 'utf-8', 'iso-8859-1', $value );
		}
		elseif( function_exists( 'mb_convert_encoding' ) )
		{ // Convert by mb extenssion
			$value = mb_convert_encoding( $value, 'iso-8859-1', 'utf-8' );
		}
	}

	return $value;
}


/**
 * Create object File from source path
 *
 * @param string Source file path
 * @param array Params
 * @return object|boolean File or FALSE
 */
function & wpxml_create_File( $file_source_path, $params )
{
	$params = array_merge( array(
			'file_root_type' => 'collection',
			'file_root_ID'   => '',
			'file_path'      => '',
			'file_title'     => '',
			'file_alt'       => '',
			'file_desc'      => '',
		), $params );

	// Set false to return failed result by reference
	$File = false;

	if( ! file_exists( $file_source_path ) )
	{	// File doesn't exist
		echo '<p class="text-warning">'.sprintf( T_('Unable to copy file %s, because it does not exist.'), '<code>'.$file_source_path.'</code>' ).'</p>';
		// Skip it:
		return $File;
	}

	// Get FileRoot by type and ID
	$FileRootCache = & get_FileRootCache();
	$FileRoot = & $FileRootCache->get_by_type_and_ID( $params['file_root_type'], $params['file_root_ID'] );

	// Get file name with a fixed name if file with such name already exists in the destination path:
	$dest_file = basename( $params['file_path'] );
	$dest_folder = dirname( $params['file_path'] );
	if( $dest_folder == '.' )
	{
		$dest_folder = '/';
	}
	list( $File, $old_file_thumb ) = check_file_exists( $FileRoot, $dest_folder, $dest_file );

	if( ! $File || ! copy_r( $file_source_path, $File->get_full_path() ) )
	{	// No permission to copy to the destination folder
		if( is_dir( $file_source_path ) )
		{	// Folder
			echo '<p class="text-warning">'.sprintf( T_('Unable to copy folder %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$file_destination_path.'</code>' ).'</p>';
		}
		else
		{	// File
			echo '<p class="text-warning">'.sprintf( T_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$file_destination_path.'</code>' ).'</p>';
		}
		// Skip it:
		return $File;
	}

	// Set additional params for new creating File object:
	$File->set( 'title', $params['file_title'] );
	$File->set( 'alt', $params['file_alt'] );
	$File->set( 'desc', $params['file_desc'] );
	$File->dbsave();

	echo '<p class="text-success">'.sprintf( T_('File %s has been imported to %s successfully.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ).'</p>';

	evo_flush();

	return $File;
}


/**
 * Find attachments folder path for given XML file path
 *
 * @param string File path
 * @param boolean TRUE to use first found folder if no reserved folders not found before
 * @return string Folder path
 */
function wpxml_get_attachments_folder( $file_path, $first_folder = false )
{
	$file_name = basename( $file_path );
	$file_folder_path = dirname( $file_path ).'/';
	$folder_full_name = preg_replace( '#\.[^\.]+$#', '', $file_name );
	$folder_part_name = preg_replace( '#_[^_]+$#', '', $folder_full_name );

	// Find and get first existing folder with attachments:
	if( is_dir( $file_folder_path.$folder_full_name ) )
	{	// 1st priority folder:
		return $file_folder_path.$folder_full_name.'/';
	}
	if( is_dir( $file_folder_path.$folder_part_name.'_files' ) )
	{	// 2nd priority folder:
		return $file_folder_path.$folder_part_name.'_files/';
	}
	if( is_dir( $file_folder_path.$folder_part_name.'_attachments' ) )
	{	// 3rd priority folder:
		return $file_folder_path.$folder_part_name.'_attachments/';
	}
	if( is_dir( $file_folder_path.'b2evolution_export_files' ) )
	{	// 4th priority folder:
		return $file_folder_path.'b2evolution_export_files/';
	}
	if( is_dir( $file_folder_path.'export_files' ) )
	{	// 5th priority folder:
		return $file_folder_path.'export_files/';
	}
	if( is_dir( $file_folder_path.'import_files' ) )
	{	// 6th priority folder:
		return $file_folder_path.'import_files/';
	}
	if( is_dir( $file_folder_path.'files' ) )
	{	// 7th priority folder:
		return $file_folder_path.'files/';
	}
	if( is_dir( $file_folder_path.'attachments' ) )
	{	// 8th priority folder:
		return $file_folder_path.'attachments/';
	}

	if( $first_folder )
	{	// Try to use first found folder:
		$files = scandir( $file_folder_path );
		foreach( $files as $file )
		{
			if( $file == '.' || $file == '..' )
			{	// Skip reserved dir names of the current path:
				continue;
			}
			if( is_dir( $file_folder_path.$file ) )
			{	// 9th priority folder:
				return $file_folder_path.$file.'/';
			}
		}
	}

	// File has no attachments folder
	return false;
}


/**
 * This class is used to avoid wrong chars in XML files on import
 *
 * @see wpxml_parser()
 * @see wpxml_check_xml_file()
 */
class ValidUTF8XMLFilter extends php_user_filter
{
	protected static $pattern = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u';

	function filter( $in, $out, & $consumed, $closing )
	{
		while( $bucket = stream_bucket_make_writeable( $in ) )
		{
			$bucket->data = preg_replace( self::$pattern, '', $bucket->data );
			$consumed += $bucket->datalen;
			stream_bucket_append( $out, $bucket );
		}
		return PSFS_PASS_ON;
	}
}

?>