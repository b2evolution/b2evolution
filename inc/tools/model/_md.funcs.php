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

	// Options:
	$convert_md_links = param( 'convert_md_links', 'integer', 0 );

	$DB->begin();

	if( $import_type == 'replace' )
	{	// Remove data from selected collection:

		// Should we delete files on 'replace' mode?
		$delete_files = param( 'delete_files', 'integer', 0 );

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
	echo '<h3>'.T_('Importing the categories...').' </h3>';
	evo_flush();

	load_class( 'chapters/model/_chapter.class.php', 'Chapter' );
	$ChapterCache = & get_ChapterCache();

	$categories = array();
	$cat_results_num = array(
		'added_success'   => 0,
		'added_failed'    => 0,
		'updated_success' => 0,
		'updated_failed'  => 0,
		'no_changed'      => 0,
	);
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

		// Get name of current category:
		$last_index = strrpos( $relative_path, '/' );
		$category_name = $last_index === false ? $relative_path : substr( $relative_path, $last_index + 1 );

		// Always reuse existing categories on "upgrade" mode:
		$reuse_cats = ( $import_type == 'upgrade' ||
			// Should we reuse existing categories on "append" mode?
			( $import_type == 'append' && param( 'reuse_cats', 'integer', 0 ) ) );
			// Don't try to use find existing categories on replace mode.

		if( $reuse_cats && $Chapter = & md_get_Chapter( $relative_path, $md_blog_ID ) )
		{	// Use existing category with same full url path:
			$categories[ $relative_path ] = $Chapter->ID;
			if( $category_name == $Chapter->get( 'name' ) )
			{	// Don't update category with same name:
				$cat_results_num['no_changed']++;
				echo T_('No change');
			}
			else
			{	// Try to update category with different name but same slug:
				$Chapter->set( 'name', $category_name );
				if( $Chapter->dbupdate() )
				{	// If category is updated successfully:
					echo '<span class="text-warning">'.T_('Updated').'</span>';
					$cat_results_num['updated_success']++;
				}
				else
				{	// Don't translate because it should not happens:
					echo '<span class="text-danger">Cannot be updated</span>';
					$cat_results_num['updated_failed']++;
				}
			}
		}
		else
		{	// Create new category:
			$Chapter = new Chapter( NULL, $md_blog_ID );

			// Get parent path:
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
				echo '<span class="text-success">'.T_('Added').'</span>';
				$cat_results_num['added_success']++;
				// Add new created Chapter into cache to avoid wrong main category ID in ItemLight::get_main_Chapter():
				$ChapterCache->add( $Chapter );
			}
			else
			{	// Don't translate because it should not happens:
				echo '<span class="text-danger">Cannot be inserted</span>';
				$cat_results_num['added_failed']++;
			}
		}
		echo '.</p>';
		evo_flush();

		// Unset folder in order to don't check it twice on creating posts below:
		unset( $files[ $f ] );
	}

	foreach( $cat_results_num as $cat_result_type => $cat_result_num )
	{
		if( $cat_result_num > 0 )
		{
			switch( $cat_result_type )
			{
				case 'added_success':
					$cat_msg_text = T_('%d categories imported');
					$cat_msg_class = 'text-success';
					break;
				case 'added_failed':
					// Don't translate because it should not happens:
					$cat_msg_text = '%d categories could not be inserted';
					$cat_msg_class = 'text-danger';
					break;
				case 'updated_success':
					$cat_msg_text = T_('%d categories updated');
					$cat_msg_class = 'text-warning';
					break;
				case 'updated_failed':
					// Don't translate because it should not happens:
					$cat_msg_text = '%d categories could not be updated';
					$cat_msg_class = 'text-danger';
					break;
				case 'no_changed':
					$cat_msg_text = T_('%d categories no changed');
					$cat_msg_class = '';
					break;
			}
			echo '<b'.( empty( $cat_msg_class ) ? '' : ' class="'.$cat_msg_class.'"').'>'.sprintf( $cat_msg_text, $cat_result_num ).'</b><br>';
		}
	}

	// Load Spyc library to parse YAML data:
	load_funcs( '_ext/spyc/Spyc.php' );

	/* Import posts: */
	echo '<h3>'.T_('Importing the posts...').'</h3>';
	evo_flush();

	load_class( 'items/model/_item.class.php', 'Item' );
	$ItemCache = get_ItemCache();

	$posts_count = 0;
	$post_results_num = array(
		'added_success'   => 0,
		'added_failed'    => 0,
		'updated_success' => 0,
		'updated_failed'  => 0,
		'no_changed'      => 0,
	);
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
		if( preg_match( '~^(---[\r\n]+(.+?)[\r\n]+---[\r\n]+)?(#+\s*(.+?)\s*#*\s*[\r\n]+)?(.+)$~s', $item_content, $content_match ) )
		{
			$item_data = trim( $content_match[2] );
			if( ! empty( $item_data ) )
			{	// Parse YAML data:
				$item_data = spyc_load( $item_data );
			}
			$item_title = isset( $item_data['title'] ) ? $item_data['title'] : $content_match[4];
			$item_content = $content_match[5];
		}
		else
		{
			$item_title = $item_slug;
		}

		// Limit title by max possible length:
		$item_title = utf8_substr( $item_title, 0, 255 );

		echo sprintf( T_('Importing post: %s'), '"<b>'.$item_title.'</b>" <code>'.$source_folder_zip_name.substr( $file_path, strlen( $folder_path ) ).'</code>: ' );
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
		    ! ( $Item = & md_get_Item( $item_slug, $md_blog_ID ) ) )
		{	// Create new Item for not upgrade mode or if it is not found by slug in the requested Collection:
			$Item = new Item();
			$Item->set( 'creator_user_ID', $current_User->ID );
			$Item->set( 'datestart', date2mysql( $localtimenow ) );
			$Item->set( 'datecreated', date2mysql( $localtimenow ) );
			$Item->set( 'status', 'published' );
			$Item->set( 'ityp_ID', $md_Blog->get_setting( 'default_post_type' ) );
			$Item->set( 'locale', $md_Blog->get( 'locale' ) );
			$Item->set( 'urltitle', urltitle_validate( $item_slug, $item_slug, 0, false, 'post_urltitle', 'post_ID', 'T_items__item' ) );
		}

		$prev_category_ID = $Item->get( 'main_cat_ID' );
		// Set new category for new Item or when post was moved to different category:
		$Item->set( 'main_cat_ID', $category_ID );
		$Item->set( 'extra_cat_IDs', array( $category_ID ) );

		$prev_last_import_hash = $Item->get_setting( 'last_import_hash' );
		$Item->set_setting( 'last_import_hash', $item_content_hash );
		if( $prev_last_import_hash != $item_content_hash )
		{	// Set new fields only when import hash(title + content) was really changed:
			if( $convert_md_links )
			{	// Convert Markdown relative links to b2evolution ShortLinks:
				$item_content = preg_replace_callback( '#\[([^\]]*)\]\((.+/)?(.+?)\.md\)#', 'md_callback_convert_links', $item_content );
			}
			$Item->set( 'lastedit_user_ID', $current_User->ID );
			$Item->set( 'title', $item_title );
			$Item->set( 'content', $item_content );
			$Item->set( 'datemodified', date2mysql( $localtimenow ) );
			if( isset( $item_data['excerpt'] ) )
			{	// Set content excerpt from yaml data:
				$Item->set( 'excerpt', $item_data['excerpt'], true );
				$Item->set( 'excerpt_autogenerated', 0 );
			}
			elseif( ! empty( $item_content ) )
			{	// Generate excerpt from content:
				$Item->set( 'excerpt', excerpt( $item_content ), true );
				$Item->set( 'excerpt_autogenerated', 1 );
			}
			if( isset( $item_data['short-title'] ) )
			{	// Set short title from yaml data:
				$Item->set( 'short_title', utf8_substr( $item_data['short-title'], 0, 50 ) );
			}
			if( isset( $item_data['description'] ) )
			{	// Set meta description from yaml data:
				$Item->set_setting( 'metadesc', $item_data['description'] );
			}
			if( isset( $item_data['description'] ) )
			{	// Set meta keywords from yaml data:
				$Item->set_setting( 'metakeywords', $item_data['keywords'] );
			}
		}

		if( empty( $Item->ID ) )
		{	// Insert new Item:
			if( $Item->dbinsert() )
			{	// If post is inserted successfully:
				echo '<span class="text-success">'.T_('Is new -> Added to DB').'</span>';
				$post_results_num['added_success']++;
			}
			else
			{	// Don't translate because it should not happens:
				echo '<span class="text-danger">Cannot be inserted</span>';
				$post_results_num['added_failed']++;
			}
		}
		else
		{	// Update existing Item:
			if( $prev_last_import_hash == $item_content_hash && $prev_category_ID == $category_ID )
			{	// Don't try to update item in DB because import hash(title + content) was not changed after last import:
				$post_results_num['no_changed']++;
				echo T_('No change');
			}
			elseif( $Item->dbupdate( true, true, true, $prev_last_import_hash != $item_content_hash/* Force to create new revision only when file hash(title+content) was changed after last import */ ) )
			{	// Item has been updated successfully:
				echo '<span class="text-warning">'.T_('Has changed');
				if( $prev_category_ID != $category_ID )
				{	// If moved to different category:
					echo ' -> '.T_('Moved to different category');
				}
				if( $prev_last_import_hash != $item_content_hash )
				{	// If content was changed:
					echo ' -> '.T_('New revision added to DB');
					if( $prev_last_import_hash === NULL )
					{	// Display additional warning when Item was edited manually:
						global $admin_url;
						echo '. <b>'.sprintf( T_('WARNING: this item has been manually edited. Check <a %s>changes history</a>'), 'href="'.$admin_url.'?ctrl=items&amp;action=history&amp;p='.$Item->ID.'" target="_blank"' ).'</b>';
					}
				}
				echo '</span>';
				$post_results_num['updated_success']++;
			}
			else
			{	// Failed update:
				// Don't translate because it should not happens:
				echo '<span class="text-danger">Cannot be updated</span>';
				$post_results_num['updated_failed']++;
			}
		}

		$files_imported = false;
		if( ! empty( $Item->ID ) )
		{
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
				echo ',<ul class="list-default">';
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
				echo '</ul>';
				$files_imported = true;

				if( $updated_item_content != $item_content )
				{	// Update new content:
					$Item->set( 'content', $updated_item_content );
					$Item->dbupdate();
				}
			}
		}

		if( ! $files_imported )
		{
			echo '.<br><br>';
		}
		evo_flush();
	}

	foreach( $post_results_num as $post_result_type => $post_result_num )
	{
		if( $post_result_num > 0 )
		{
			switch( $post_result_type )
			{
				case 'added_success':
					$post_msg_text = T_('%d posts added to DB');
					$post_msg_class = 'text-success';
					break;
				case 'added_failed':
					// Don't translate because it should not happens:
					$post_msg_text = '%d posts could not be inserted';
					$post_msg_class = 'text-danger';
					break;
				case 'updated_success':
					$post_msg_text = T_('%d posts updated');
					$post_msg_class = 'text-warning';
					break;
				case 'updated_failed':
					// Don't translate because it should not happens:
					$post_msg_text = '%d posts could not be updated';
					$post_msg_class = 'text-danger';
					break;
				case 'no_changed':
					$post_msg_text = T_('%d posts no changed');
					$post_msg_class = '';
					break;
			}
			echo '<b'.( empty( $post_msg_class ) ? '' : ' class="'.$post_msg_class.'"').'>'.sprintf( $post_msg_text, $post_result_num ).'</b><br>';
		}
	}

	if( $source_type == 'zip' && file_exists( $root_folder_path ) )
	{	// This folder was created only to extract files from ZIP package, Remove it now:
		rmdir_r( $root_folder_path );
	}

	echo '<h4 class="text-success">'.T_('Import completed.').'</h4>';

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
		echo '<li class="text-warning">'.sprintf( T_('Unable to copy file %s, because it does not exist.'), '<code>'.$file_source_path.'</code>' ).'</li>';
		evo_flush();
		// Skip it:
		return false;
	}

	global $DB;

	$FileCache = & get_FileCache();

	$file_source_name = basename( $file_source_path );
	$file_source_hash = md5_file( $file_source_path, true );

	// Try to find already existing File by hash in DB:
	$SQL = new SQL( 'Find file by hash' );
	$SQL->SELECT( 'file_ID, link_ID' );
	$SQL->FROM( 'T_files' );
	$SQL->FROM_add( 'LEFT JOIN T_links ON link_file_ID = file_ID AND link_itm_ID = '.$DB->quote( $LinkOwner->get_ID() ) );
	$SQL->WHERE( 'file_hash = '.$DB->quote( $file_source_hash ) );
	$SQL->ORDER_BY( 'link_itm_ID DESC, file_ID' );
	$SQL->LIMIT( '1' );
	$file_data = $DB->get_row( $SQL, ARRAY_A );
	if( ! empty( $file_data ) &&
	    ( $File = & $FileCache->get_by_ID( $file_data['file_ID'], false, false ) ) )
	{
		if( ! empty( $file_data['link_ID'] ) )
		{	// The found File is already linked to the Item:
			echo '<li>'.sprintf( T_('No file change, because %s is same as %s.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
			evo_flush();
			return $file_data['link_ID'];
		}
		else
		{	// Try to link the found File object to the Item:
			if( $link_ID = $File->link_to_Object( $LinkOwner, 0, 'inline' ) )
			{	// If file has been linked to the post
				echo '<li class="text-warning">'.sprintf( T_('File %s already exists in %s, it has been linked to this post.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
				evo_flush();
			}
			else
			{	// If file could not be linked to the post:
				echo '<li class="text-warning">'.sprintf( 'Existing file of %s could not be linked to this post.', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
				evo_flush();
				return false;
			}
			return $link_ID;
		}
	}

	// Get FileRoot by type and ID:
	$FileRootCache = & get_FileRootCache();
	$FileRoot = & $FileRootCache->get_by_type_and_ID( $params['file_root_type'], $params['file_root_ID'] );

	$replaced_File = NULL;
	$replaced_link_ID = NULL;

	if( $params['import_type'] == 'upgrade' )
	{	// Try to find existing and linked image File:
		$item_Links = $LinkOwner->get_Links();
		foreach( $item_Links as $item_Link )
		{
			if( ( $File = & $item_Link->get_File() ) &&
			    $file_source_name == $File->get( 'name' ) )
			{	// We found File with same name:
				if( $File->get( 'hash' ) != $file_source_hash )
				{	// Update only really changed file:
					$replaced_File = $File;
					$replaced_link_ID = $item_Link->ID;
				}
				else
				{	// No change for same file:
					echo '<li>'.sprintf( T_('No file change, because %s is same as %s.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
					evo_flush();
					return $item_Link->ID;
				}
			}
		}
	}

	if( $params['import_type'] != 'append' &&
	    $replaced_File === NULL )
	{	// Find an existing File on disk to replace with new:
		$File = & $FileCache->get_by_root_and_path( $FileRoot->type, $FileRoot->in_type_ID, trailing_slash( $params['folder_path'] ).$file_source_name, true );
		if( $File && $File->exists() )
		{	// If file already exists:
			$replaced_File = $File;
		}
	}

	if( $replaced_File !== NULL )
	{	// The found File must be replaced:
		if( empty( $replaced_File->ID ) )
		{	// Create new File in DB with additional params:
			$replaced_File->set( 'title', $params['file_title'] );
			$replaced_File->set( 'alt', $params['file_alt'] );
			if( ! $replaced_File->dbinsert() )
			{	// Don't translate
				echo '<li class="text-danger">'.sprintf( 'Cannot to create file %s in DB.', '<code>'.$replaced_File->get_full_path().'</code>' ).'</li>';
				evo_flush();
				return false;
			}
		}

		if( copy_r( $file_source_path, $replaced_File->get_full_path() ) )
		{	// If file has been updated successfully:
			// Clear evocache:
			$replaced_File->rm_cache();
			// Update file hash:
			$replaced_File->set_param( 'hash', 'string', md5_file( $replaced_File->get_full_path(), true ) );
			$replaced_File->dbupdate();
		}
		else
		{	// No permission to update file:
			echo '<li class="text-danger">'.sprintf( T_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$replaced_File->get_full_path().'</code>' ).'</li>';
			evo_flush();
			return false;
		}

		if( $replaced_link_ID === NULL )
		{	// Try to link new created File object to the Item:
			if( $replaced_link_ID = $replaced_File->link_to_Object( $LinkOwner, 0, 'inline' ) )
			{	// If file has been linked to the post
				echo '<li class="text-warning">'.sprintf( T_('File %s already exists in %s, it has been updated and linked to this post successfully.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$replaced_File->get_rdfs_rel_path().'</code>' ).'</li>';
			}
			else
			{	// If file could not be linked to the post:
				echo '<li class="text-danger">'.sprintf( 'Existing file of %s could not be linked to this post.', '<code>'.$replaced_File->get_rdfs_rel_path().'</code>' ).'</li>';
				evo_flush();
				return false;
			}
		}

		evo_flush();
		return $replaced_link_ID;
	}

	// Create new File:
	// - always for "append" mode,
	// - when File is not found above.

	// Get file name with a fixed name if file with such name already exists in the destination path:
	list( $File, $old_file_thumb ) = check_file_exists( $FileRoot, $params['folder_path'], $file_source_name );

	if( ! $File || ! copy_r( $file_source_path, $File->get_full_path() ) )
	{	// No permission to copy to the destination folder
		echo '<li class="text-danger">'.sprintf( T_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ).'</li>';
		evo_flush();
		return false;
	}

	// Set additional params and create new File:
	$File->set( 'title', $params['file_title'] );
	$File->set( 'alt', $params['file_alt'] );
	$File->dbsave();

	if( $link_ID = $File->link_to_Object( $LinkOwner, 0, 'inline' ) )
	{	// If file has been linked to the post
		echo '<li class="text-success">'.sprintf( T_('New file %s has been imported to %s successfully.'),
			'<code>'.$source_file_relative_path.'</code>',
			'<code>'.$File->get_rdfs_rel_path().'</code>'.
			( $file_source_name == $File->get( 'name' ) ? '' : '<span class="note">('.T_('Renamed').'!)</span>')
		).'</li>';
		evo_flush();
	}
	else
	{	// If file could not be linked to the post:
		echo '<li class="text-warning">'.sprintf( 'New file of %s could not be linked to this post.', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
		evo_flush();
		return false;
	}

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


/**
 * Get Item by slug in given Collection
 *
 * @param string Item slug
 * @param string Collection ID
 * @return object|NULL Item object
 */
function & md_get_Item( $item_slug, $coll_ID )
{
	global $DB;

	// Try to find Item by slug with suffix like "-123" in the requested Collection:
	$item_slug_base = preg_replace( '/-\d+$/', '', $item_slug );
	$SQL = new SQL( 'Find Item by slug base "'.$item_slug_base.'" in the Collection #'.$coll_ID );
	$SQL->SELECT( 'post_ID' );
	$SQL->FROM( 'T_slug' );
	$SQL->FROM_add( 'INNER JOIN T_items__item ON post_ID = slug_itm_ID AND slug_type = "item"' );
	$SQL->FROM_add( 'INNER JOIN T_categories ON cat_ID = post_main_cat_ID' );
	$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $coll_ID ) );
	$SQL->WHERE_and( 'slug_title REGEXP '.$DB->quote( '^'.$item_slug_base.'(-[0-9]+)?$' ) );
	$SQL->ORDER_BY( 'slug_title' );
	$SQL->LIMIT( '1' );
	$post_ID = intval( $DB->get_var( $SQL ) );

	if( $post_ID )
	{	// Load Item by ID:
		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $post_ID, false, false );
		return $Item;
	}

	$r = NULL;
	return $r;
}


/**
 * Callback function to Convert Markdown relative links to b2evolution ShortLinks
 *
 * @param array Match data
 * @return string Link in b2evolution ShortLinks format
 */
function md_callback_convert_links( $m )
{
	$item_slug = get_urltitle( $m[3] );
	$link_title = trim( $m[1] );

	return '(('.$item_slug.( empty( $link_title ) ? '' : ' '.$link_title ).'))';
}
?>