<?php
/**
 * This file implements the Markdown Import class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Markdown Import Class
 *
 * @package evocore
 */
class MarkdownImport
{
	var $coll_ID;
	var $source;
	var $data;
	var $options;
	var $options_defs;
	var $yaml_fields;

	/**
	 * Initialize data for markdown import
	 */
	function __construct()
	{
		// Options definitions:
		$this->options_defs = array(
			// Import mode:
			'import_type' => array(
				'group'   => 'mode',
				'title'   => T_('Import mode'),
				'options' => array(
					'update'  => array( 'title' => T_('Update existing contents'), 'note' => T_('Existing Categories & Posts will be re-used (based on slug).') ),
					'append'  => array( 'title' => T_('Append to existing contents') ),
					'replace' => array( 'title' => T_('Replace existing contents'), 'note' => T_('WARNING: this option will permanently remove existing posts, comments, categories and tags from the selected collection.') ),
				),
				'type'    => 'string',
				'default' => 'update',
			),
			'reuse_cats' => array(
				'group'    => 'import_type',
				'subgroup' => 'append',
				'title'    => T_('Reuse existing categories'),
				'note'     => '('.T_('based on folder name = slug name').')',
				'type'     => 'integer',
				'default'  => 1,
			),
			'delete_files' => array(
				'group'    => 'import_type',
				'subgroup' => 'replace',
				'title'    => T_('Also delete media files that will no longer be referenced in the destination collection after replacing its contents'),
				'type'     => 'integer',
				'default'  => 0,
			),
			// Options:
			'convert_md_links' => array(
				'group'   => 'options',
				'title'   => T_('Convert Markdown links to b2evolution ShortLinks'),
				'type'    => 'integer',
				'default' => 1,
			),
			'check_links' => array(
				'group'   => 'options',
				'title'   => T_('Check all internal links (slugs) to see if they link to a page of the same language (if not, log a Warning)'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 1,
			),
			'diff_lang_suggest' => array(
				'group'   => 'options',
				'title'   => T_('If different language, use the "linked languages/versions" table to find the equivalent in the same language (and log the suggestion)'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 2,
			),
			'same_lang_replace_link' => array(
				'group'   => 'options',
				'title'   => T_('If a same language match was found, replace the link slug in the post while importing'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 3,
			),
			'same_lang_update_file' => array(
				'group'   => 'options',
				'title'   => T_('If a same language match was found, replace the link slug in the original <code>.md</code> file on disk so it doesn’t trigger warnings next times (and can be versioned into Git). This requires using a directory to import, not a ZIP file.'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 3,
			),
			'force_item_update' => array(
				'group'   => 'options',
				'title'   => T_('Force Item update, even if file hash has not changed'),
				'type'    => 'integer',
				'default' => 0,
			),
		);

		// Supported YAML fields:
		$this->yaml_fields = array(
				'title',
				'description',
				'keywords',
				'excerpt',
				'short-title',
				'tags',
				'extra-cats',
			);
	}


	/**
	 * Check source folder or zip archive
	 *
	 * @return boolean|string TRUE on success, Error message of error
	 */
	function check_source()
	{
		if( empty( $this->source ) )
		{	// File is not selected:
			return T_('Please select file or folder to import.');
		}
		elseif( is_dir( $this->source ) )
		{
			if( ! check_folder_with_extensions( $this->source, 'md' ) )
			{	// Folder has no markdown files:
				return sprintf( T_('Folder %s has no markdown files.'), '<code>'.$this->source.'</code>' );
			}
		}
		elseif( ! preg_match( '/\.zip$/i', $this->source ) )
		{	// Extension is incorrect:
			return sprintf( T_('%s has an unrecognized extension.'), '<code>'.$this->source.'</code>' );
		}

		return true;
	}


	/**
	 * Unzip archive
	 *
	 * @return boolean
	 */
	function unzip()
	{
		if( isset( $this->unzip_result ) )
		{	// Don't unzip archive twice:
			return $this->unzip_result;
		}

		if( is_dir( $this->source ) )
		{	// Source is a folder:
			$this->unzip_result = false;
			$this->unzip_errors = '';
			return false;
		}

		if( ! preg_match( '/\.zip$/i', $this->source ) )
		{	// Wrong source:
			$this->unzip_result = false;
			$this->unzip_errors = '';
			return false;
		}

		evo_flush();

		// Extract ZIP and check if it contians at least one markdown file:
		global $media_path;

		// $ZIP_folder_path must be deleted after import!
		$this->unzip_folder_path = $media_path.'import/temp-'.md5( rand() );

		// Try to unpack:
		$this->unzip_result = unpack_archive( $this->source, $this->unzip_folder_path, true, basename( $this->source ), false );

		if( $this->unzip_result !== true )
		{	// Store unzip error from unpack_archive():
			$this->unzip_errors = $this->unzip_result;
			$this->unzip_result = false;
		}

		return $this->unzip_result;
	}


	/**
	 * Get data to start import from markdown folder or ZIP file
	 *
	 * @param string Key of data
	 * @return array|string|NULL Value of the requested data, NULL - ,
	 *                Full data array:
	 *                 'error' - FALSE on success OR error message ,
	 *                 'path'  - Path to folder with markdown files,
	 *                 'type'  - 'folder', 'zip'.
	 */
	function get_data( $key = NULL )
	{
		if( ! isset( $this->data ) )
		{	// Load data in cache:

			$errors = '';

			$folder_path = NULL;
			if( is_dir( $this->source ) )
			{	// Use a folder:
				$folder_path = $this->source;
			}
			else
			{	// Try to extract ZIP:
				if( $this->unzip() )
				{	// Set folder on success unzipping:
					$folder_path = $this->unzip_folder_path;
				}
				else
				{	// Display errors:
					$errors .= $this->unzip_errors;
				}
			}

			// Check if folder contians at least one markdown file:
			if( empty( $this->unzip_errors ) &&
			    ( $folder_path === NULL || ! check_folder_with_extensions( $folder_path, 'md' ) ) )
			{	// No markdown is detected in ZIP package:
				$errors .= '<p class="text-danger">'.T_('No markdown file is detected in the selected source.').'</p>';
				if( ! empty( $this->unzip_folder_path ) && file_exists( $this->unzip_folder_path ) )
				{	// Delete temporary folder that contains the files from extracted ZIP package:
					rmdir_r( $this->unzip_folder_path );
				}
			}

			// Cache data:
			$this->data = array(
				'errors' => empty( $errors ) ? false : $errors,
				'path'   => $folder_path,
				'type'   => ( empty( $this->unzip_folder_path ) ? 'dir' : 'zip' ),
			);
		}

		if( $key === NULL )
		{
			return $this->data;
		}
		else
		{
			return isset( $this->data[ $key ] ) ? $this->data[ $key ] : NULL;
		}
	}


	/**
	 * Set option
	 *
	 * @param string Option name
	 * @param string Option value
	 */
	function set_option( $option_name, $option_value )
	{
		$this->options[ $option_name ] = $option_value;
	}


	/**
	 * Set option
	 *
	 * @param string Option name
	 * @return string|NULL Option value, NULL - unknown option
	 */
	function get_option( $option_name )
	{
		if( isset( $this->options[ $option_name ] ) )
		{	// Use custom value:
			return $this->options[ $option_name ];
		}

		if( isset( $this->options_defs[ $option_name ] ) )
		{	// Use default value:
			return $this->options_defs[ $option_name ]['default'];
		}

		return NULL;
	}


	/**
	 * Load import data from request
	 *
	 * @return boolean TRUE on load all fields without error
	 */
	function load_from_Request()
	{
		global $Session;

		// Collection:
		$this->coll_ID = param( 'md_blog_ID', 'integer', 0 );
		param_check_not_empty( 'md_blog_ID', T_('Please select a collection!') );
		// Save last import collection in Session:
		$Session->set( 'last_import_coll_ID', $this->coll_ID );

		// Import File/Folder:
		$this->source = param( 'import_file', 'string', '' );
		$check_source_result = $this->check_source();
		if( $check_source_result !== true )
		{	// Don't import if errors have been detected:
			param_error( 'import_file', $check_source_result );
		}

		// Load options:
		foreach( $this->options_defs as $option_key => $option )
		{
			$this->set_option( $option_key, param( $option_key, $option['type'], ( $option['type'] == 'integer' ? 0 : $option['default'] ) ) );
		}

		return ! param_errors_detected();
	}


	/**
	 * Import markdown data from ZIP file or folder into b2evolution database
	 */
	function execute()
	{
		global $Blog, $DB, $tableprefix, $media_path, $current_User, $localtimenow;

		$folder_path = $this->get_data( 'path' );
		$source_folder_zip_name = basename( $this->source );

		// Set Collection by requested ID:
		$BlogCache = & get_BlogCache();
		$md_Blog = & $BlogCache->get_by_ID( $this->coll_ID );
		// Set current collection because it is used inside several functions like urltitle_validate():
		$Blog = $md_Blog;

		$DB->begin();

		if( $this->get_option( 'import_type' ) == 'replace' )
		{	// Remove data from selected collection:

			// Get existing categories
			$SQL = new SQL( 'Get existing categories of collection #'.$this->coll_ID );
			$SQL->SELECT( 'cat_ID' );
			$SQL->FROM( 'T_categories' );
			$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
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
					if( $this->get_option( 'delete_files' ) )
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
			$DB->query( 'DELETE FROM T_categories WHERE cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
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

			if( $this->get_option( 'delete_files' ) )
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

			// Always reuse existing categories on "update" mode:
			$reuse_cats = ( $this->get_option( 'import_type' ) == 'update' ||
				// Should we reuse existing categories on "append" mode?
				( $this->get_option( 'import_type' ) == 'append' && $this->get_option( 'reuse_cats' ) ) );
				// Don't try to use find existing categories on replace mode.

			if( $reuse_cats && $Chapter = & $this->get_Chapter( $relative_path ) )
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
				$Chapter = new Chapter( NULL, $this->coll_ID );

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

		$Plugins_admin = & get_Plugins_admin();

		$posts_count = 0;
		$post_results_num = array(
			'added_success'   => 0,
			'added_failed'    => 0,
			'updated_success' => 0,
			'updated_failed'  => 0,
			'no_changed'      => 0,
		);
		$imported_slugs = array();
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
			$this->item_file_is_updated = false;
			$this->item_file_content = file_get_contents( $file_path );
			$item_content = trim( $this->item_file_content );
			$item_content_hash = md5( $item_content );
			if( preg_match( '~^(---[\r\n]+(.+?)[\r\n]+---[\r\n]*)?(#+\s*(.+?)\s*#*\s*([\r\n]+|$))?(.*)$~s', $item_content, $content_match ) )
			{
				$item_yaml_data = trim( $content_match[2] );
				if( ! empty( $this->yaml_fields ) && ! empty( $item_yaml_data ) )
				{	// Parse YAML data:
					$item_yaml_data = spyc_load( $item_yaml_data );
				}
				else
				{	// Don't parse when no supported YAML fields or no provided YAML data:
					$item_yaml_data = NULL;
				}
				$item_title = empty( $content_match[4] )
					// Use yaml short title or item slug as title when title in content is not defined:
					? ( empty( $item_yaml_data['short-title'] ) ? $item_slug : $item_yaml_data['short-title'] )
					// Use title from content:
					: $content_match[4];
				$item_content = $content_match[6];
			}
			else
			{
				$item_yaml_data = NULL;
				$item_title = $item_slug;
			}

			// Limit title by max possible length:
			$item_title = utf8_substr( $item_title, 0, 255 );

			echo sprintf( T_('Importing post: %s'), '"<b>'.$item_title.'</b>" <code>'.$source_folder_zip_name.substr( $file_path, strlen( $folder_path ) ).'</code>: ' );
			evo_flush();

			if( in_array( $item_slug, $imported_slugs ) )
			{	// Skip md file/post with same name from different folder/category:
				echo '<ul class="list-default"><li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '.sprintf( '%s already found before, ignoring second instance.', '<code>'.$item_slug.'.md</code>' ).'</li></ul><br>';
				evo_flush();
				continue;
			}

			// Store imported posts slugs to avoid import md files with same name:
			$imported_slugs[] = $item_slug;

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
					$default_category_urlname = $md_Blog->get( 'urlname' ).'-main';
					if( ! ( $default_Chapter = & $ChapterCache->get_by_urlname( $default_category_urlname, false, false ) ) )
					{	// Create default category if it doesn't exist yet:
						$default_Chapter = new Chapter( NULL, $this->coll_ID );
						$default_Chapter->set( 'name', T_('Uncategorized') );
						$default_Chapter->set( 'urlname', urltitle_validate( $default_category_urlname, $default_category_urlname, 0, false, 'cat_urlname', 'cat_ID', 'T_categories' ) );
						$default_Chapter->dbinsert();
						// Add new created Chapter into cache to avoid wrong main category ID in ItemLight::get_main_Chapter():
						$ChapterCache->add( $default_Chapter );
					}
					$default_category_ID = $default_Chapter->ID;
				}
				$category_ID = $default_category_ID;
			}

			$item_slug = get_urltitle( $item_slug );
			if( $this->get_option( 'import_type' ) != 'update' ||
					! ( $Item = & $this->get_Item( $item_slug ) ) )
			{	// Create new Item for not update mode or if it is not found by slug in the requested Collection:
				$Item = new Item();
				$Item->set( 'creator_user_ID', $current_User->ID );
				$Item->set( 'datestart', date2mysql( $localtimenow ) );
				$Item->set( 'datecreated', date2mysql( $localtimenow ) );
				$Item->set( 'status', 'published' );
				$Item->set( 'ityp_ID', $md_Blog->get_setting( 'default_post_type' ) );
				$Item->set( 'locale', $md_Blog->get( 'locale' ) );
				$Item->set( 'urltitle', urltitle_validate( $item_slug, $item_slug, 0, false, 'post_urltitle', 'post_ID', 'T_items__item' ) );
			}

			// Get and update item content hash:
			$prev_last_import_hash = $Item->get_setting( 'last_import_hash' );
			$Item->set_setting( 'last_import_hash', $item_content_hash );
			// Decide content was changed when current hash is different than previous:
			$item_content_was_changed = ( $prev_last_import_hash != $item_content_hash );

			$prev_category_ID = $Item->get( 'main_cat_ID' );
			// Set new category for new Item or when post was moved to different category:
			$Item->set( 'main_cat_ID', $category_ID );
			// Reset YAML messages after import previous Item:
			$this->reset_yaml_messages();

			if( $this->get_option( 'convert_md_links' ) )
			{	// Convert Markdown links to b2evolution ShortLinks:
				// NOTE: Do this even when last import hash is different because below we may update content on import images:
				$this->link_messages = array();
				$this->current_item_locale = $Item->get( 'locale' );
				// Do convert:
				$item_content = preg_replace_callback( '#(^|[^\!])\[([^\[\]]*)\]\(((([a-z]*://)?([^\)]+[/\\\\])?([^\)]+?)(\.[a-z]{2,4})?)(\#[^\)]+)?)?\)#i', array( $this, 'callback_convert_links' ), $item_content );
				foreach( $this->link_messages as $link_message )
				{
					if( $link_message['type'] == 'content' )
					{	// Force to update content when at least one link was replaced with proper link to post with same language as current post:
						$item_content_was_changed = true;
						break;
					}
				}
			}

			// Set flag to don't filter content twice by renderer plugins:
			$item_is_filtered_by_plugins = false;

			if( $this->get_option( 'force_item_update' ) || $item_content_was_changed )
			{	// Set new fields only when import hash(title + content + YAML data) was really changed:
				$Item->set( 'lastedit_user_ID', $current_User->ID );
				$Item->set( 'datemodified', date2mysql( $localtimenow ) );

				// Filter title and content by renderer plugins:
				$item_is_filtered_by_plugins = true;
				$item_Blog = & $Item->get_Blog();
				$item_plugin_params = array(
						'object_type' => 'Item',
						'object'      => & $Item,
						'object_Blog' => & $item_Blog,
					);
				$Plugins_admin->filter_contents( $item_title /* by ref */, $item_content /* by ref */, $Item->get_renderers_validated(), $item_plugin_params /* by ref */ );
				$Item->set( 'title', $item_title );
				$Item->set( 'content', $item_content );

				foreach( $this->yaml_fields as $yaml_field )
				{	// Set YAML field:
					if( ! isset( $item_yaml_data[ $yaml_field ] ) )
					{	// Skip if the Item has no defined value for this YAML field:
						continue;
					}
					$yaml_method = 'set_yaml_'.str_replace( '-', '_', $yaml_field );
					if( method_exists( $this, $yaml_method ) )
					{	// Call method to set YAML field:
						$this->$yaml_method( $item_yaml_data[ $yaml_field ], $Item );
					}
				}

				// NOTE: Use auto generating of excerpt only after set of YAML fields,
				//       because there excerpt field may be defined as not auto generated.
				if( $Item->get( 'excerpt_autogenerated' ) && ! empty( $item_content ) )
				{	// Generate excerpt from content:
					$Item->set( 'excerpt', excerpt( $item_content ), true );
				}
			}

			// Flag to know Item is updated in STEP 1:
			$item_is_updated_step_1 = false;

			$item_result_messages = array();
			$item_result_class = '';
			$item_result_suffix = '';
			if( empty( $Item->ID ) )
			{	// Insert new Item:
				if( $Item->dbinsert() )
				{	// If post is inserted successfully:
					$item_is_updated_step_1 = true;
					$item_result_class = 'text-success';
					$item_result_messages[] = /* TRANS: Result of imported Item */ T_('new file');
					$item_result_messages[] = /* TRANS: Result of imported Item */ T_('New Post added to DB');
					$post_results_num['added_success']++;
				}
				else
				{	// Don't translate because it should not happens:
					$item_result_messages[] = 'Cannot be inserted';
					$item_result_class = 'text-danger';
					$post_results_num['added_failed']++;
				}
			}
			else
			{	// Update existing Item:
				if( ! $this->get_option( 'force_item_update' ) && ! $item_content_was_changed && $prev_category_ID == $category_ID )
				{	// Don't try to update item in DB because import hash(title + content) was not changed after last import:
					$post_results_num['no_changed']++;
					$item_result_messages[] = /* TRANS: Result of imported Item */ T_('No change');
				}
				elseif( 
					// This is UPDATE 1 of 2 (there is a 2nd UPDATE for [image:] tags. These tags cannot be created before the Item ID is known.):
					$Item->dbupdate( true, true, true, 
						$this->get_option( 'force_item_update' ) || $item_content_was_changed/* Force to create new revision only when file hash(title+content) was changed after last import or when update is forced */ ) )      
	// TODO: fp>yb: please give example of situation where we want to NOT create a new revision ? (I think we ALWAYS want to create a new revision)				
				{	// Item has been updated successfully:
					$item_is_updated_step_1 = true;
					$item_result_class = 'text-warning';
					if( $this->get_option( 'force_item_update' ) )
					{	// If item update was forced:
						$item_result_messages[] = /* TRANS: Result of imported Item */ T_('Forced update');
					}
					else
					{	// Normal update because content or category was changed:
						$item_result_messages[] = /* TRANS: Result of imported Item */ T_('Has changed');
					}
					if( $prev_category_ID != $category_ID )
					{	// If moved to different category:
						$item_result_messages[] =/* TRANS: Result of imported Item */  T_('Moved to different category');
					}
					if( $item_content_was_changed )
					{	// If content was changed:
						$item_result_messages[] = /* TRANS: Result of imported Item */ T_('New revision added to DB');
						if( $prev_last_import_hash === NULL )
						{	// Display additional warning when Item was edited manually:
							global $admin_url;
							$item_result_suffix = '. <br /><span class="label label-danger">'.T_('CONFLICT').'</span> <b>'
								.sprintf( T_('WARNING: this item has been manually edited. Check <a %s>changes history</a>'),
									'href="'.$admin_url.'?ctrl=items&amp;action=history&amp;p='.$Item->ID.'" target="_blank"' ).'</b>';
						}
					}
					$post_results_num['updated_success']++;
				}
				else
				{	// Failed update:
					// Don't translate because it should not happens:
					$item_result_messages[] = 'Cannot be updated';
					$item_result_class = 'text-danger';
					$post_results_num['updated_failed']++;
				}
			}

			// Display result messages of Item inserting or updating:
			echo empty( $item_result_class ) ? '' : '<span class="'.$item_result_class.'">';
			if( $Item->ID > 0 )
			{	// Set last message text as link to permanent URL of the inserted/updated Item:
				$last_msg_i = count( $item_result_messages ) - 1;
				$item_result_messages[ $last_msg_i ] = '<a href="'.$Item->get_permanent_url().'" target="_blank">'.$item_result_messages[ $last_msg_i ].'</a>';
			}
			echo implode( ' -> ', $item_result_messages );
			echo $item_result_suffix;
			echo empty( $item_result_class ) ? '' : '</span>';

			// Display messages of importing YAML fields:
			$this->display_yaml_messages();

			if( ! empty( $this->link_messages ) )
			{	// Display what links could not be converted:
				echo ( $this->has_yaml_messages() ? '' : ',' ).'<ul class="list-default" style="margin-bottom:0">';
				foreach( $this->link_messages as $link_message )
				{
					switch( $link_message['type'] )
					{
						case 'error_link':
						case 'error_image':
							echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '
									.sprintf( 'Markdown link %s could not be convered to b2evolution ShortLink.',
										'<code>'.$link_message['tag'].'</code>'
									).'</li>';
							if( $link_message['type'] == 'error_image' )
							{	// Special warning when URL to image is used in link markdown tag:
								echo '<li class="text-warning"><span class="label label-warning">'.T_('WARNING').'</span> '
										.'The above is a markdown link to an image file. Did you forget the <code>!</code> in order to make it an image inclusion, rather than a link?'
									.'</li>';
							}
							break;
						case 'check':
							echo '<li class="text-warning"><span class="label label-warning">'.T_('WARNING').'</span> '
								.sprintf( 'Link %s points to "%s" which is in %s instead of %s.',
									'<code>'.$link_message['tag'].'</code>',
									$link_message['link'],
									'<code>'.$link_message['locale'].'</code>',
									'<code>'.$Item->get( 'locale' ).'</code>'
								).'</li>';
							break;
						case 'recommend':
							echo '<li class="text-warning"><span class="label label-warning">'.T_('WARNING').'</span> '
								.sprintf( 'We recommend "%s" (%s) as destination.',
									$link_message['link'],
									'<code>'.$Item->get( 'locale' ).'</code>'
								).'</li>';
							break;
						case 'content':
							echo '<li class="text-warning"><span class="label label-warning">'.T_('WARNING').'</span> '
									.'We will update the content accordingly.'
								.'</li>';
							break;
					}
				}
				echo '</ul>';
			}

			$files_imported = false;
			if( ! empty( $Item->ID ) )
			{
				// Link files:
				if( preg_match_all( '#\!\[([^\]]*)\]\(([^\)"]+\.('.$this->get_image_extensions().'))\s*("[^"]*")?\)#i', $item_content, $image_matches ) )
				{
					$updated_item_content = $item_content;
					$all_links_count = 0;
					$new_links_count = 0;
					$LinkOwner = new LinkItem( $Item );
					$file_params = array(
							'file_root_type' => 'collection',
							'file_root_ID'   => $this->coll_ID,
							'folder_path'    => 'quick-uploads/'.$Item->get( 'urltitle' ),
						);
					echo ( ! $this->has_yaml_messages() && empty( $this->link_messages ) ? ',' : '' ).'<ul class="list-default" style="margin-bottom:0">';
					foreach( $image_matches[2] as $i => $image_relative_path )
					{
						$file_params['file_alt'] = trim( $image_matches[1][$i] );
						if( strtolower( $file_params['file_alt'] ) == 'img' ||
								strtolower( $file_params['file_alt'] ) == 'image' )
						{	// Don't use this default text for alt image text:
							$file_params['file_alt'] = '';
						}
						$file_params['file_title'] = trim( $image_matches[4][$i], ' "' );
						// Try to find existing and linked image File or create, copy and link image File:
						if( $link_data = $this->link_file( $LinkOwner, $folder_path, $category_path, rtrim( $image_relative_path ), $file_params ) )
						{	// Replace this img tag from content with b2evolution format:
							$updated_item_content = str_replace( $image_matches[0][$i], '[image:'.$link_data['ID'].']', $updated_item_content );
							if( $link_data['type'] == 'new' )
							{	// Count new linked files:
								$new_links_count++;
							}
							$all_links_count++;
						}
					}

					if( $new_links_count > 0 || ( $item_is_updated_step_1 && $all_links_count > 0 ) )
					{	// Update content for new markdown image links which were replaced with b2evo inline tags format:
						echo '<li class="text-warning">';
						if( $new_links_count > 0 )
						{	// Update content with new inline image tags:
							echo sprintf( T_('%d new image files were linked to the Item'), $new_links_count )
								.' -> './* TRANS: Result of imported Item */ T_('Saving to DB').'.';
						}
						else
						{	// Force to update content with inline image tags:
							echo T_('No image file changes BUT Item Update is required')
								.' -> './* TRANS: Result of imported Item */ T_('Saving <code>[image:]</code> tags to DB').'.';
						}
						echo '</li>';
						if( ! $item_is_filtered_by_plugins )
						{	// Filter title and content by renderer plugins:
							$item_Blog = & $Item->get_Blog();
							$item_plugin_params = array(
									'object_type' => 'Item',
									'object'      => & $Item,
									'object_Blog' => & $item_Blog,
								);
							$Plugins_admin->filter_contents( $item_title /* by ref */, $updated_item_content /* by ref */, $Item->get_renderers_validated(), $item_plugin_params /* by ref */ );
						}
						$Item->set( 'content', $updated_item_content );
						// This is UPDATE 2 of 2 . It is only for [image:] tags.
						$Item->dbupdate( true, true, true, 'no'/* Force to do NOT create new revision because we do this above when store new content */ );      
					}

					echo '</ul>';
					$files_imported = true;
				}
			}

			if( ! empty( $this->item_file_is_updated ) )
			{	// Update item's file with fixed content:
				echo '<ul class="list-default" style="margin-bottom:0">';
				if( ( $md_file_handle = @fopen( $file_path, 'w' ) ) &&
						fwrite( $md_file_handle, $this->item_file_content ) )
				{	// Inform about updated file content:
					echo '<li class="text-warning"><span class="label label-warning">'.T_('WARNING').'</span> '
						.sprintf( 'We modified the file %s accordingly.',
							'<code>'.$item_slug.'.md</code>'
						).'</li>';
					if( $md_file_handle )
					{	// Close file handle:
						fclose( $md_file_handle );
					}
				}
				else
				{	// No file rights to write into the file:
					echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '
						.sprintf( 'Impossible to update file %s with fixed content, please check file permissions.',
							'<code>'.$file_path.'</code>'
						).'</li>';
				}
				echo '</ul>';
			}

			if( ! $files_imported && ! $this->has_yaml_messages() && empty( $this->link_messages ) )
			{
				echo '.<br>';
			}
			echo '<br>';
			evo_flush();
		}

		foreach( $post_results_num as $post_result_type => $post_result_num )
		{
			if( $post_result_num > 0 )
			{
				switch( $post_result_type )
				{
					case 'added_success':
						$post_msg_text = T_('%d new posts added to DB');
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

		// Commit changes before event_after_import() in order ot avoid unexpected rollback from there:
		$DB->commit();

		// Execute additonal actions after import, e.g. by extended classes:
		$this->event_after_import();

		if( $this->get_data( 'type' ) == 'zip' && file_exists( $root_folder_path ) )
		{	// This folder was created only to extract files from ZIP package, Remove it now:
			rmdir_r( $root_folder_path );
		}

		echo '<h4 class="text-success">'.T_('Import completed.').'</h4>';
	}


	/**
	 * Create object File from source path
	 *
	 * @param object LinkOwner
	 * @param string Source folder absolute path
	 * @param string Source Category folder name
	 * @param string Requested file relative path
	 * @param array Params
	 * @return boolean|array FALSE or Array on success ( 'ID' - Link ID, 'type' - 'new'/'old' )
	 */
	function link_file( $LinkOwner, $source_folder_absolute_path, $source_category_folder, $requested_file_relative_path, $params )
	{
		$params = array_merge( array(
				'file_root_type' => 'collection',
				'file_root_ID'   => '',
				'file_title'     => '',
				'file_alt'       => '',
				'folder_path'    => '',
			), $params );

		$requested_file_relative_path = ltrim( str_replace( '\\', '/', $requested_file_relative_path ), '/' );

		$source_file_relative_path = $source_category_folder.'/'.$requested_file_relative_path;
		$file_source_path = $source_folder_absolute_path.'/'.$source_file_relative_path;

		if( strpos( get_canonical_path( $file_source_path ), $source_folder_absolute_path ) !== 0 )
		{	// Don't allow a traversal directory:
			echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '.sprintf( 'Skip file %s, because path is invalid.', '<code>'.$requested_file_relative_path.'</code>' ).'</li>';
			evo_flush();
			// Skip it:
			return false;
		}

		if( ! file_exists( $file_source_path ) )
		{	// File doesn't exist
			echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '.sprintf( T_('Unable to copy file %s, because it does not exist.'), '<code>'.$file_source_path.'</code>' ).'</li>';
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
				return array( 'ID' => $file_data['link_ID'], 'type' => 'old' );
			}
			else
			{	// Try to link the found File object to the Item:
				if( $link_ID = $File->link_to_Object( $LinkOwner, 0, 'inline' ) )
				{	// If file has been linked to the post
					echo '<li class="text-warning">'.sprintf( T_('File %s already exists in %s, it has been linked to this post.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
					evo_flush();
					return array( 'ID' => $link_ID, 'type' => 'new' );
				}
				else
				{	// If file could not be linked to the post:
					echo '<li class="text-warning">'.sprintf( 'Existing file of %s could not be linked to this post.', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
					evo_flush();
					return false;
				}
			}
		}

		// Get FileRoot by type and ID:
		$FileRootCache = & get_FileRootCache();
		$FileRoot = & $FileRootCache->get_by_type_and_ID( $params['file_root_type'], $params['file_root_ID'] );

		$replaced_File = NULL;
		$replaced_link_ID = NULL;

		if( $this->get_option( 'import_type' ) == 'update' )
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
						$replaced_link_type = 'old';
						// Don't find next files:
						break;
					}
					else
					{	// No change for same file:
						echo '<li>'.sprintf( T_('No file change, because %s is same as %s.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
						evo_flush();
						return array( 'ID' => $item_Link->ID, 'type' => 'old' );
					}
				}
			}
		}

		if( $this->get_option( 'import_type' ) != 'append' &&
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
					echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '.sprintf( 'Cannot to create file %s in DB.', '<code>'.$replaced_File->get_full_path().'</code>' ).'</li>';
					evo_flush();
					return false;
				}
			}

			// Try to replace old file with new:
			if( ! copy_r( $file_source_path, $replaced_File->get_full_path() ) )
			{	// No permission to replace file:
				echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '.sprintf( T_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$replaced_File->get_full_path().'</code>' ).'</li>';
				evo_flush();
				return false;
			}

			// If file has been updated successfully:
			// Clear evocache:
			$replaced_File->rm_cache();
			// Update file hash:
			$replaced_File->set_param( 'hash', 'string', md5_file( $replaced_File->get_full_path(), true ) );
			$replaced_File->dbupdate();

			if( $replaced_link_ID !== NULL )
			{	// Inform about replaced file:
				echo '<li class="text-warning">'.sprintf( T_('File %s has been replaced in %s successfully.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ).'</li>';
			}
			elseif( $replaced_link_ID = $replaced_File->link_to_Object( $LinkOwner, 0, 'inline' ) )
			{	// If file has been linked to the post
				$replaced_link_type = 'new';
				echo '<li class="text-warning">'.sprintf( T_('File %s already exists in %s, it has been updated and linked to this post successfully.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$replaced_File->get_rdfs_rel_path().'</code>' ).'</li>';
			}
			else
			{	// If file could not be linked to the post:
				echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '.sprintf( 'Existing file of %s could not be linked to this post.', '<code>'.$replaced_File->get_rdfs_rel_path().'</code>' ).'</li>';
				evo_flush();
				return false;
			}

			evo_flush();
			return array( 'ID' => $replaced_link_ID, 'type' => $replaced_link_type );
		}

		// Create new File:
		// - always for "append" mode,
		// - when File is not found above.

		// Get file name with a fixed name if file with such name already exists in the destination path:
		list( $File, $old_file_thumb ) = check_file_exists( $FileRoot, $params['folder_path'], $file_source_name );

		if( ! $File || ! copy_r( $file_source_path, $File->get_full_path() ) )
		{	// No permission to copy to the destination folder
			echo '<li class="text-danger"><span class="label label-danger">'.T_('ERROR').'</span> '.sprintf( T_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ).'</li>';
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

		return array( 'ID' => $link_ID, 'type' => 'new' );
	}


	/**
	 * Get category by provided folder path
	 *
	 * @param string Category folder path
	 * @param boolean Check by full path, FALSE - useful to find only by slug
	 * @return object|NULL Chapter object
	 */
	function & get_Chapter( $cat_folder_path, $check_full_path = true )
	{
		if( isset( $this->chapters_by_path[ $cat_folder_path ] ) )
		{	// Get Chapter from cache:
			return $this->chapters_by_path[ $cat_folder_path ];
		}

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
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
		$SQL->WHERE_and( 'cat_urlname REGEXP '.$DB->quote( '^('.$cat_urlname_base.')(-[0-9]+)?$' ) );
		$cat_IDs = $DB->get_col( $SQL );

		$r = NULL;
		$ChapterCache = & get_ChapterCache();
		foreach( $cat_IDs as $cat_ID )
		{
			if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false, false ) )
			{
				$full_match = true;
				if( $check_full_path )
				{	// Check full path:
					$cat_curr_url_path = explode( '/', substr( $Chapter->get_url_path(), 0 , -1 ) );
					foreach( $cat_full_url_path as $c => $cat_full_url_folder )
					{
						// Decide slug is same without number at the end:
						if( ! isset( $cat_curr_url_path[ $c ] ) ||
								! preg_match( '/^'.preg_quote( $cat_full_url_folder, '/' ).'(-\d+)?$/', $cat_curr_url_path[ $c ] ) )
						{
							$full_match = false;
							break;
						}
					}
				}
				if( $full_match )
				{	// We found category with same full url path:
					$r = $Chapter;
					break;
				}
			}
		}

		$this->chapters_by_path[ $cat_folder_path ] = $r;
		return $r;
	}


	/**
	 * Get Item by slug in given Collection
	 *
	 * @param string Item slug
	 * @return object|NULL Item object
	 */
	function & get_Item( $item_slug )
	{
		global $DB;

		// Try to find Item by slug with suffix like "-123" in the requested Collection:
		$item_slug_base = preg_replace( '/-\d+$/', '', $item_slug );
		$SQL = new SQL( 'Find Item by slug base "'.$item_slug_base.'" in the Collection #'.$this->coll_ID );
		$SQL->SELECT( 'post_ID' );
		$SQL->FROM( 'T_slug' );
		$SQL->FROM_add( 'INNER JOIN T_items__item ON post_ID = slug_itm_ID AND slug_type = "item"' );
		$SQL->FROM_add( 'INNER JOIN T_categories ON cat_ID = post_main_cat_ID' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
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
	 * Callback function to Convert Markdown links to b2evolution ShortLinks
	 *
	 * @param array Match data
	 * @return string Link in b2evolution ShortLinks format
	 */
	function callback_convert_links( $m )
	{
		$link_title = trim( $m[2] );
		$link_url = isset( $m[3] ) ? trim( $m[3] ) : '';

		if( $link_url === '' )
		{	// URL must be defined:
			$this->link_messages[] = array(
					'type' => 'error_link',
					'tag'  => $m[0],
				);
			return $m[0];
		}

		if( ! empty( $m[5] ) )
		{	// Use full URL because this is URL with protocol like http://
			$item_url = $m[3];
			// Anchor is already included in the $m[3]:
			$link_anchor = '';
		}
		elseif( isset( $m[8] ) && $m[8] === '.md' )
		{	// Extract item slug from relative URL of md file:
			$item_url = get_urltitle( $m[7] );
			$item_slug = $item_url;
			$link_anchor = isset( $m[9] ) ? trim( $m[9], '# ' ) : '';
		}
		elseif( strpos( $m[7], '#' ) === 0 && strlen( $m[7] ) > 1 )
		{	// This is anchor URL to current post:
			$item_url = '';
			$link_anchor = substr( $m[7], 1 );
		}
		else
		{	// We cannot convert this markdown link:
			$this->link_messages[] = array(
					'type' => ( isset( $m[8] ) && in_array( strtolower( substr( $m[8], 1 ) ), array( 'png', 'gif', 'jpg', 'jpeg', 'svg' ) ) ? 'error_image' : 'error_link' ),
					'tag'  => $m[0],
				);
			return $m[0];
		}

		if( $this->get_option( 'check_links' ) &&
		    isset( $item_slug ) &&
		    ( $ItemCache = & get_ItemCache() ) &&
		    ( $slug_Item = $ItemCache->get_by_urltitle( $item_slug, false, false ) ) )
		{	// Check internal link (slug) to see if it links to a page of the same language:
			if( $slug_Item->get( 'locale' ) != $this->current_item_locale )
			{	// Different locale:
				$this->link_messages[] = array(
						'type'   => 'check',
						'tag'    => $m[0],
						'locale' => $slug_Item->get( 'locale' ),
						'link'   => $slug_Item->get_title( array( 'link_type' => 'admin_view' ) ),
					);
				if( $this->get_option( 'diff_lang_suggest' ) )
				{	// Find and suggest equivalent from "linked languages/versions" table:
					if( $version_Item = & $slug_Item->get_version_Item( $this->current_item_locale, false ) )
					{	// We found a version Item with required locale:
						$version_item_link = $version_Item->get_title( array( 'link_type' => 'admin_view' ) );
						$this->link_messages[] = array(
							'type' => 'recommend',
							'link' => $version_item_link,
						);
						if( $this->get_option( 'same_lang_replace_link' ) )
						{	// Replace the link slug in the post:
							$item_url = $version_Item->get( 'urltitle' );
							$this->link_messages[] = array(
								'type' => 'content',
								'link' => $version_item_link,
								'tag'  => $m[0],
							);
							if( $this->get_option( 'same_lang_update_file' ) )
							{	// Update md file with new replaced links:
								$updated_link = str_replace( $m[7].'.md', $version_Item->get( 'urltitle' ).'.md', $m[0] );
								$this->set_item_file_content( str_replace( $m[0], $updated_link, $this->item_file_content ) );
							}
						}
					}
				}
			}
		}

		return $m[1] // Suffix like space or new line before link
			.( substr( $m[2], 0, 1 ) === ' ' ? ' ' : '' ) // space before link text inside []
			.'(('.$item_url
			.( empty( $link_anchor ) ? '' : '#'.$link_anchor )
			.( empty( $link_title ) ? '' : ' '.$link_title ).'))';
	}


	/**
	 * Get available image extensions
	 *
	 * @return string Image extensions separated by |
	 */
	function get_image_extensions()
	{
		if( ! isset( $this->image_extensions ) )
		{	// Load image extensions from DB into cache string:
			global $DB;
			$SQL = new SQL( 'Get available image extensions' );
			$SQL->SELECT( 'ftyp_extensions' );
			$SQL->FROM( 'T_filetypes' );
			$SQL->WHERE( 'ftyp_viewtype = "image"' );
			$this->image_extensions = str_replace( ' ', '|', implode( ' ', $DB->get_col( $SQL ) ) );
		}

		return $this->image_extensions;
	}


	/**
	 * Reset YAML messages
	 */
	function reset_yaml_messages()
	{
		$this->yaml_messages = array();
	}


	/**
	 * Add message to report about importing YAML field
	 *
	 * @param string Message
	 * @param string Type
	 */
	function add_yaml_message( $message, $type = 'error' )
	{
		$this->yaml_messages[] = array( $message, $type );
	}


	/**
	 * Display messages of importing YAML fields
	 */
	function display_yaml_messages()
	{
		if( ! empty( $this->yaml_messages ) )
		{	// Display errors of linking to extra categories:
			echo ',<ul class="list-default" style="margin-bottom:0">';
			foreach( $this->yaml_messages as $yaml_message )
			{
				switch( $yaml_message[1] )
				{
					case 'error':
						// Error message:
						$label = '<span class="label label-danger">'.T_('ERROR').'</span> ';
						$class = 'text-danger';
						break;
					case 'warning':
						// Warning message:
						$label = '<span class="label label-warning">'.T_('WARNING').'</span> ';
						$class = 'text-warning';
						break;
					default:
						// Normal message:
						$label = '';
						$class = '';
				}
				// Print message:
				echo '<li'.( empty( $class ) ? '' : ' class="'.$class.'"' ).'>'.$label.$yaml_message[0].'</li>';
			}
			echo '</ul>';
		}
	}


	/**
	 * Check for YAML messages were added during import
	 *
	 * @return boolean
	 */
	function has_yaml_messages()
	{
		return ! empty( $this->yaml_messages );
	}


	/**
	 * Set Item title from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_title( $value, & $Item )
	{
		$Item->set( 'titletag', utf8_substr( $value, 0, 255 ) );
	}


	/**
	 * Set Item meta description from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_description( $value, & $Item )
	{
		$Item->set_setting( 'metadesc', $value );
	}


	/**
	 * Set Item meta keywords from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_keywords( $value, & $Item )
	{
		$Item->set_setting( 'metakeywords', $value );
	}


	/**
	 * Set Item content excerpt from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_excerpt( $value, & $Item )
	{
		$Item->set( 'excerpt', $value, true );
		$Item->set( 'excerpt_autogenerated', 0 );
	}


	/**
	 * Set Item short title from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_short_title( $value, & $Item )
	{
		$Item->set( 'short_title', utf8_substr( $value, 0, 50 ) );
	}


	/**
	 * Set Item tags from YAML data
	 *
	 * @param string|array Value
	 * @param object Item (by reference)
	 */
	function set_yaml_tags( $value, & $Item )
	{
		if( ! $this->check_yaml_array( 'tags', $value, true, true ) )
		{	// Skip wrong data:
			// Don't print error messages here because all messages are initialized inside $this->check_yaml_array().
			return;
		}

		if( $value === '' ||  $value === array() )
		{	// Clear tags:
			$Item->set_tags_from_string( '' );
		}
		else
		{	// Set new tags:
			$Item->set_tags_from_string( is_array( $value )
				// Set tags from array:
				? implode( ',', $value )
				// Set tags from string separated by comma:
				: preg_replace( '#,\s+#', ',', $value ) );
		}
	}


	/**
	 * Set Item extra categories from YAML data
	 *
	 * @param array Value
	 * @param object Item (by reference)
	 */
	function set_yaml_extra_cats( $value, & $Item )
	{
		if( ! $this->check_yaml_array( 'extra-cats', $value ) )
		{	// Skip wrong data:
			// Don't print error messages here because all messages are initialized inside $this->check_yaml_array().
			return;
		}

		$extra_cat_IDs = array();
		foreach( $value as $extra_cat_slug )
		{
			if( $extra_Chapter = & $this->get_Chapter( $extra_cat_slug, strpos( $extra_cat_slug, '/' ) !== false ) )
			{	// Use only existing category:
				$extra_cat_IDs[] = $extra_Chapter->ID;
			}
			else
			{	// Display error on not existing category:
				$this->add_yaml_message( sprintf( T_('Skip extra category %s, because it doesn\'t exist.'), '<code>'.$extra_cat_slug.'</code>' ) );
			}
		}

		$Item->set( 'extra_cat_IDs', $extra_cat_IDs );
	}


	/**
	 * Check YAML data array
	 * 
	 * @param string YALM field name
	 * @param array|string YALM field value
	 * @param boolean TRUE to allow string for the YAML field
	 * @param boolean TRUE to allow empty value for the YAML field
	 * @return boolean TRUE - correct data, FALSE - wrong data
	 */
	function check_yaml_array( $field_name, $field_value, $allow_string_format = false, $allow_empty = false )
	{
		if( ! $allow_empty )
		{	// Check for not empty value:
			if( ( $allow_string_format && $field_value === '' ) ||
					( $field_value === array() ) )
			{	// Skip empty yaml field:
				$this->add_yaml_message( sprintf( T_('Skip yaml field %s, because it was specified without content.'), '<code>'.$field_name.'</code>' ), 'warning' );
				return false;
			}
		}

		if( $allow_string_format && is_string( $field_value ) )
		{	// Don't check array if the YAML field is allowed to be a string:
			return true;
		}

		if( ! is_array( $field_value ) )
		{	// Wrong not array data:
			$this->add_yaml_message( sprintf( T_('Skip yaml field %s, because it must be an array.'), '<code>'.$field_name.'</code>' ) );
			return false;
		}

		foreach( $field_value as $string )
		{
			if( is_array( $string ) )
			{	// Skip wrong indented data:
				$this->add_yaml_message( sprintf( T_('Skip yaml field %s, because it is wrongly indented.'), '<code>'.$field_name.'</code>' ) );
				return false;
			}
		}

		return true;
	}


	/**
	 * Additional actions after import is done
	 *
	 * Useful for extended classes
	 */
	function event_after_import()
	{
	}


	/**
	 * Set new content for item *.md file
	 */
	function set_item_file_content( $new_content )
	{
		if( $this->item_file_content != $new_content )
		{	// Update item content only when content is really changed:
			$this->item_file_content = $new_content;
			// Set flag to know the item content was updated:
			$this->item_file_is_updated = true;
		}
	}
}
?>