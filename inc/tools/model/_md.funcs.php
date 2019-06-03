<?php
/**
 * This file implements the functions to work with Markdown importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get data to start import from markdown folder or ZIP file
 *
 * @param string Path of folder or ZIP file
 * @return array Data array:
 *                 'error' - FALSE on success OR error message ,
 *                 'folder_path' - Path to folder with markdown files,
 *                 'source_type' - 'folder', 'zip'.
 */
function md_get_import_data( $source_path )
{
	// Start to collect all printed errors from buffer:
	ob_start();

	$ZIP_folder_path = NULL;

	$folder_path = NULL;
	if( is_dir( $source_path ) )
	{	// Use a folder:
		$folder_path = $source_path;
	}
	elseif( preg_match( '/\.zip$/i', $source_path ) )
	{	// Extract ZIP and check if it contians at least one markdown file:
		global $media_path;

		// $ZIP_folder_path must be deleted after import!
		$ZIP_folder_path = $media_path.'import/temp-'.md5( rand() );

		if( unpack_archive( $source_path, $ZIP_folder_path, true, basename( $source_path ) ) )
		{	// If ZIP archive is unpacked successfully:
			$folder_path = $ZIP_folder_path;
		}
	}

	if( $folder_path === NULL || ! check_folder_with_extensions( $folder_path, 'md' ) )
	{	// No markdown is detected in ZIP package:
		echo '<p class="text-danger">'.T_('No markdown file is detected in the selected source.').'</p>';
		if( $ZIP_folder_path !== NULL && file_exists( $ZIP_folder_path ) )
		{	// Delete temporary folder that contains the files from extracted ZIP package:
			rmdir_r( $ZIP_folder_path );
		}
	}

	// Get all printed errors:
	$errors = ob_get_clean();

	return array(
			'errors'      => empty( $errors ) ? false : $errors,
			'folder_path' => $folder_path,
			'source_type' => ( $ZIP_folder_path === NULL ? 'dir' : 'zip' ),
		);
}


/**
 * Import WordPress data from XML file into b2evolution database
 *
 * @param string Source folder path
 * @param string Source type: 'dir', 'zip'
 * @param string Name of source folder or ZIP archive
 */
function md_import( $folder_path, $source_type, $source_folder_zip_name )
{
	global $Blog, $DB, $tableprefix, $media_path, $current_User, $localtimenow;

	// Set Collection by requested ID:
	$md_blog_ID = param( 'md_blog_ID', 'integer', 0 );
	$BlogCache = & get_BlogCache();
	$md_Blog = & $BlogCache->get_by_ID( $md_blog_ID );
	// Set current collection because it is used inside several functions like urltitle_validate():
	$Blog = $md_Blog;

	// The import type ( replace | append )
	$import_type = param( 'import_type', 'string', 'replace' );
	// Should we delete files on 'replace' mode?
	$delete_files = param( 'delete_files', 'integer', 0 );

	$DB->begin();

	if( $import_type == 'replace' )
	{ // Remove data from selected blog

		// Get existing categories
		$SQL = new SQL( 'Get existing categories of collection #'.$md_blog_ID );
		$SQL->SELECT( 'cat_ID' );
		$SQL->FROM( 'T_categories' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $md_blog_ID ) );
		$old_categories = $DB->get_col( $SQL );
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
		$DB->query( 'DELETE FROM T_categories WHERE cat_blog_ID = '.$DB->quote( $md_blog_ID ) );
		$ChapterCache = & get_ChapterCache();
		$ChapterCache->clear();
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

	// Check if we should skip a single folder in ZIP archive root which is the same as ZIP file name:
	$root_folder_path = $folder_path;
	if( ! empty( $source_folder_zip_name ) )
	{	// This is an import from ZIP archive
		$zip_file_name = preg_replace( '#\.zip$#i', '', $source_folder_zip_name );
		if( file_exists( $folder_path.'/'.$zip_file_name ) )
		{	// If folder exists in the root with same name as ZIP file name:
			$skip_single_zip_root_folder = true;
			if( $folder_path_handler = @opendir( $folder_path ) )
			{
				while( ( $file = readdir( $folder_path_handler ) ) !== false )
				{
					if( ! preg_match( '#^([\.]{1,2}|__MACOSX|'.preg_quote( $zip_file_name ).')$#i', $file ) )
					{	// This is a different file or folder than ZIP file name:
						$skip_single_zip_root_folder = false;
						break;
					}
				}
				closedir( $folder_path_handler );
			}
			if( $skip_single_zip_root_folder )
			{	// Skip root folder with same name as ZIP file name:
				$folder_path .= '/'.$zip_file_name;
				$source_folder_zip_name .= '/'.$zip_file_name;
			}
		}
	}

	// Get all subfolders and files from the source folder:
	$files = get_filenames( $folder_path );
	$folder_path_length = strlen( $folder_path );

	/* Import categories: */
	echo '<p><b>'.T_('Importing the categories...').' </b>';
	evo_flush();

	load_class( 'chapters/model/_chapter.class.php', 'Chapter' );
	$ChapterCache = & get_ChapterCache();

	$categories = array();
	$categories_count = 0;
	foreach( $files as $f => $file_path )
	{
		$file_path = str_replace( '\\', '/', $file_path );

		if( ! is_dir( $file_path ) ||
		    preg_match( '#/((.*\.)?assets|__MACOSX)(/|$)#i', $file_path ) )
		{	// Skip a not folder or reserved folder:
			continue;
		}

		$relative_path = substr( $file_path, $folder_path_length + 1 );

		echo '<p>'.sprintf( T_('Importing category: %s'), '"<b>'.$relative_path.'</b>"...' );
		evo_flush();

		if( $import_type != 'replace' &&
		    $Chapter = & md_get_Chapter( $relative_path, $md_blog_ID ) )
		{	// Use existing category with same full url path:
			$categories[ $relative_path ] = $Chapter->ID;
			$categories_count++;
			echo '<span class="text-success">'.T_('OK').'</span>';
		}
		else
		{	// Create new category:
			$Chapter = new Chapter( NULL, $md_blog_ID );

			// Get names of current category and parent path:
			$last_index = strrpos( $relative_path, '/' );
			$category_name = $last_index === false ? $relative_path : substr( $relative_path, $last_index + 1 );//$paths_folders[ $paths_folders_num - 1 ];
			$parent_path = substr( $relative_path, 0, $last_index );

			$Chapter->set( 'name', $category_name );
			$Chapter->set( 'urlname', urltitle_validate( $category_name, $category_name, 0, false, 'cat_urlname', 'cat_ID', 'T_categories' ) );
			if( ! empty( $parent_path ) && isset( $categories[ $parent_path ] ) )
			{	// Set category parent ID:
				$Chapter->set( 'parent_ID', $categories[ $parent_path ] );
			}
			if( $Chapter->dbinsert() )
			{	// If category is inserted successfully:
				// Save new category in cache:
				$categories[ $relative_path ] = $Chapter->ID;
				$categories_count++;
				echo '<span class="text-success">'.T_('OK').'</span>';
				// Add new created Chapter into cache to avoid wrong main category ID in ItemLight::get_main_Chapter():
				$ChapterCache->add( $Chapter );
			}
			else
			{
				echo '<span class="text-warning">'.T_('Failed').'</span>';
			}
		}
		echo '.</p>';
		evo_flush();

		// Unset folder in order to don't check it twice on creating posts below:
		unset( $files[ $f ] );
	}
	echo '<b>'.sprintf( T_('%d records'), $categories_count ).'</b></p>';

	/* Import posts: */
	echo '<p><b>'.T_('Importing the posts...').' </b>';
	evo_flush();

	load_class( 'items/model/_item.class.php', 'Item' );
	$ItemCache = get_ItemCache();

	$posts_count = 0;
	foreach( $files as $file_path )
	{
		$file_path = str_replace( '\\', '/', $file_path );

		if( ! preg_match( '#([^/]+)\.md$#i', $file_path, $file_match ) ||
		    preg_match( '#/(\.[^/]*$|((.*\.)?assets|__MACOSX)/)#i', $file_path ) )
		{	// Skip a not markdown file,
			// and if file name is started with . (dot),
			// and files from *.assets and __MACOSX folders:
			continue;
		}

		// Use file name as slug for new Item:
		$item_slug = $file_match[1];

		// Extract title from content:
		$item_content = trim( file_get_contents( $file_path ) );
		$item_content_hash = md5( $item_content );
		if( preg_match( '~^#(.+?)\n(.+)$~s', $item_content, $content_match ) )
		{
			$item_title = trim( $content_match[1] );
			$item_content = $content_match[2];
		}
		else
		{
			$item_title = $item_slug;
		}

		echo '<p>'.sprintf( T_('Importing post: %s'), '"<b>'.$item_title.'</b>" <code>'.$source_folder_zip_name.substr( $file_path, strlen( $folder_path ) ).'</code>' );
		evo_flush();

		$relative_path = substr( $file_path, $folder_path_length + 1 );

		// Try to get a category ID:
		$category_path = substr( $relative_path, 0, strrpos( $relative_path, '/' ) );
		if( isset( $categories[ $category_path ] ) )
		{	// Use existing category:
			$category_ID = $categories[ $category_path ];
		}
		else
		{	// Use default category:
			if( ! isset( $default_category_ID ) )
			{	// If category is still not defined then we should create default, because blog must has at least one category
				$new_Chapter = new Chapter( NULL, $md_blog_ID );
				$new_Chapter->set( 'name', T_('Uncategorized') );
				$default_category_name = $md_Blog->get( 'urlname' ).'-main';
				$new_Chapter->set( 'urlname', urltitle_validate( $default_category_name, $default_category_name, 0, false, 'cat_urlname', 'cat_ID', 'T_categories' ) );
				$new_Chapter->dbinsert();
				$default_category_ID = $new_Chapter->ID;
				// Add new created Chapter into cache to avoid wrong main category ID in ItemLight::get_main_Chapter():
				$ChapterCache->add( $new_Chapter );
			}
			$category_ID = $default_category_ID;
		}

		$item_slug = get_urltitle( $item_slug );
		if( $import_type != 'upgrade' ||
		    ! ( $Item = & $ItemCache->get_by_urltitle( $item_slug, false, false ) ) )
		{	// Create new Item for not upgrade mode or if it is not found by slug:
			$Item = new Item();
			$Item->set( 'main_cat_ID', $category_ID );
			$Item->set( 'extra_cat_IDs', array( $category_ID ) );
			$Item->set( 'creator_user_ID', $current_User->ID );
			$Item->set( 'datestart', date2mysql( $localtimenow ) );
			$Item->set( 'datecreated', date2mysql( $localtimenow ) );
			$Item->set( 'status', 'published' );
			$Item->set( 'ityp_ID', $md_Blog->get_setting( 'default_post_type' ) );
			$Item->set( 'locale', $md_Blog->get( 'locale' ) );
			$Item->set( 'urltitle', urltitle_validate( $item_slug, $item_slug, 0, false, 'post_urltitle', 'post_ID', 'T_items__item' ) );
		}
		$Item->set( 'lastedit_user_ID', $current_User->ID );
		$Item->set( 'title', $item_title );
		$Item->set( 'content', $item_content );
		$Item->set( 'datemodified', date2mysql( $localtimenow ) );
		if( ! empty( $item_content ) )
		{	// Generate excerpt from content:
			$Item->set_param( 'excerpt', 'string', excerpt( $item_content ), true );
			$Item->set( 'excerpt_autogenerated', 1 );
		}
		$prev_last_import_hash = $Item->get_setting( 'last_import_hash' );
		$Item->set_setting( 'last_import_hash', $item_content_hash );
		if( empty( $Item->ID ) )
		{	// Insert new Item:
			$Item->dbinsert();
		}
		else
		{	// Update Item:
			// Create new revision only when file hash was changed after last import:
			$create_revision = ( $prev_last_import_hash == $Item->get_setting( 'last_import_hash' ) ? 'no': false );
			$Item->dbupdate( true, true, true, $create_revision );
		}

		if( ! empty( $Item->ID ) )
		{
			$posts_count++;

			// Link files:
			if( preg_match_all( '#\!\[([^\]]*)\]\(([^\)"]+)\s*("[^"]*")?\)#', $item_content, $image_matches ) )
			{
				$updated_item_content = $item_content;
				$LinkOwner = new LinkItem( $Item );
				$file_params = array(
						'file_root_type' => 'collection',
						'file_root_ID'   => $md_blog_ID,
						'folder_path'    => 'quick-uploads/'.$Item->get( 'urltitle' ),
						'import_type'    => $import_type,
					);
				foreach( $image_matches[2] as $i => $image_relative_path )
				{
					$file_params['file_alt'] = trim( $image_matches[1][$i] );
					if( strtolower( $file_params['file_alt'] ) == 'img' ||
					    strtolower( $file_params['file_alt'] ) == 'image' )
					{	// Don't use this default text for alt image text:
						$file_params['file_alt'] = '';
					}
					$file_params['file_title'] = trim( $image_matches[3][$i], ' "' );
					// Try to find existing and linked image File or create, copy and link image File:
					if( $link_ID = md_link_file( $LinkOwner, $folder_path, $category_path.'/'.rtrim( $image_relative_path ), $file_params ) )
					{	// Replace this img tag from content with b2evolution format:
						$updated_item_content = str_replace( $image_matches[0][$i], '[image:'.$link_ID.']', $updated_item_content );
					}
				}

				if( $updated_item_content != $item_content )
				{	// Update new content:
					$Item->set( 'content', $updated_item_content );
					$Item->dbupdate();
				}
			}
		}

		echo '</p>';
		evo_flush();
	}
	echo '<b>'.sprintf( T_('%d records'), $posts_count ).'</b></p>';

	if( $source_type == 'zip' && file_exists( $root_folder_path ) )
	{	// This folder was created only to extract files from ZIP package, Remove it now:
		rmdir_r( $root_folder_path );
	}

	echo '<p class="text-success">'.T_('Import complete.').'</p>';

	$DB->commit();
}


/**
 * Create object File from source path
 *
 * @param object LinkOwner
 * @param string Source folder absolute path
 * @param string Source file relative path
 * @param array Params
 * @return boolean|integer FALSE or Link ID on success
 */
function md_link_file( $LinkOwner, $source_folder_absolute_path, $source_file_relative_path, $params )
{
	$params = array_merge( array(
			'file_root_type' => 'collection',
			'file_root_ID'   => '',
			'file_title'     => '',
			'file_alt'       => '',
			'folder_path'    => '',
			'import_type'    => 'replace',
		), $params );

	$file_source_path = $source_folder_absolute_path.'/'.$source_file_relative_path;

	if( ! file_exists( $file_source_path ) )
	{	// File doesn't exist
		echo '<p class="text-warning">'.sprintf( T_('Unable to copy file %s, because it does not exist.'), '<code>'.$file_source_path.'</code>' ).'</p>';
		evo_flush();
		// Skip it:
		return false;
	}

	if( $params['import_type'] == 'upgrade' )
	{	// Try to find existing and linked image File:
		$file_name = basename( $file_source_path );
		$item_Links = $LinkOwner->get_Links();
		foreach( $item_Links as $item_Link )
		{
			if( ( $File = & $item_Link->get_File() ) &&
			    $file_name == $File->get( 'name' ) )
			{	// We found File with same name:
				if( copy_r( $file_source_path, $File->get_full_path() ) )
				{	// If file has been updated successfully:
					echo '<p class="text-success">'.sprintf( T_('File %s has been imported to %s successfully.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</p>';
					// Clear evocache:
					$File->rm_cache();
				}
				else
				{	// No permission to update file:
					if( is_dir( $file_source_path ) )
					{	// Folder
						echo '<p class="text-warning">'.sprintf( T_('Unable to copy folder %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ).'</p>';
					}
					else
					{	// File
						echo '<p class="text-warning">'.sprintf( T_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ).'</p>';
					}
				}
				return $item_Link->ID;
			}
		}
	}

	// Get FileRoot by type and ID
	$FileRootCache = & get_FileRootCache();
	$FileRoot = & $FileRootCache->get_by_type_and_ID( $params['file_root_type'], $params['file_root_ID'] );

	// Get file name with a fixed name if file with such name already exists in the destination path:
	list( $File, $old_file_thumb ) = check_file_exists( $FileRoot, $params['folder_path'], basename( $file_source_path ) );

	if( ! $File || ! copy_r( $file_source_path, $File->get_full_path() ) )
	{	// No permission to copy to the destination folder
		if( is_dir( $file_source_path ) )
		{	// Folder
			echo '<p class="text-warning">'.sprintf( T_('Unable to copy folder %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ).'</p>';
		}
		else
		{	// File
			echo '<p class="text-warning">'.sprintf( T_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ).'</p>';
		}
		evo_flush();
		// Skip it:
		return false;
	}

	// Set additional params and create new File:
	$File->set( 'title', $params['file_title'] );
	$File->set( 'alt', $params['file_alt'] );
	$File->dbsave();

	if( $link_ID = $File->link_to_Object( $LinkOwner, 0, 'inline' ) )
	{	// If file has been linked to the post
		echo '<p class="text-success">'.sprintf( T_('File %s has been imported to %s successfully.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</p>';
	}
	else
	{	// If file could not be linked to the post:
		echo '<p class="text-warning">'.sprintf( T_('File of image url %s could not be attached to this post because it is not found in the source attachments folder.'), '<code>'.$file_source_path.'</code>' ).'</p>';
		evo_flush();
		return false;
	}

	evo_flush();

	return $link_ID;
}


/**
 * Get category by provided folder path
 *
 * @param string Category folder path
 * @param string Collection ID
 * @return object|NULL Chapter object
 */
function & md_get_Chapter( $cat_folder_path, $blog_ID )
{
	global $DB;

	$cat_full_url_path = explode( '/', $cat_folder_path );
	foreach( $cat_full_url_path as $c => $cat_slug )
	{	// Convert title text to slug format:
		$cat_full_url_path[ $c ] = get_urltitle( $cat_slug );
	}
	// Get base of url name without numbers at the end:
	$cat_urlname_base = preg_replace( '/-\d+$/', '', $cat_full_url_path[ count( $cat_full_url_path ) - 1 ] );

	$SQL = new SQL( 'Find categories by path "'.implode( '/', $cat_full_url_path ).'/"' );
	$SQL->SELECT( 'cat_ID' );
	$SQL->FROM( 'T_categories' );
	$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $blog_ID ) );
	$SQL->WHERE_and( 'cat_urlname REGEXP '.$DB->quote( '^('.$cat_urlname_base.')(-[0-9]+)?$' ) );
	$cat_IDs = $DB->get_col( $SQL );

	$ChapterCache = & get_ChapterCache();
	foreach( $cat_IDs as $cat_ID )
	{
		if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false, false ) )
		{
			$cat_curr_url_path = explode( '/', substr( $Chapter->get_url_path(), 0 , -1 ) );
			$full_match = true;
			foreach( $cat_full_url_path as $c => $cat_full_url_folder )
			{
				// Decide slug is same without number at the end:
				if( ! preg_match( '/^'.preg_quote( $cat_full_url_folder, '/' ).'(-\d+)?$/', $cat_curr_url_path[ $c ] ) )
				{
					$full_match = false;
					break;
				}
			}
			if( $full_match )
			{	// We found category with same full url path:
				return $Chapter;
			}
		}
	}

	$r = NULL;
	return $r;
}
?>