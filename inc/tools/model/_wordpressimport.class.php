<?php
/**
 * This file implements the WordPress Import class.
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


load_class( 'tools/model/_abstractimport.class.php', 'AbstractImport' );

/**
 * WordPress Import Class
 *
 * @package evocore
 */
class WordpressImport extends AbstractImport
{
	var $import_code = 'wordpress';
	var $coll_ID;

	var $info_data;

	// Number of errors to stop import:
	var $errors_limit = false;

	/**
	 * Initialize data for WordPress import
	 */
	function __construct()
	{
		global $Plugins;

		// Call plugin event for additional initialization:
		$Plugins->trigger_event( 'ImporterConstruct', array(
				'type'     => $this->import_code,
				'Importer' => $this,
			) );
	}


	/**
	 * Log a message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string Type: 'success', 'error', 'warning'
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log( $message, $type = NULL, $type_html_tag = 'p', $display_label = true )
	{
		parent::log( $message, $type, $type_html_tag, $display_label );

		if( $this->errors_limit && $this->log_errors_num >= $this->errors_limit )
		{	// Stop import because of errors limit:
			global $DB;
			$errors_limit = $this->errors_limit;

			// Reset this var in order to avoid infinite recursion for the error logging below:
			$this->errors_limit = false;

			// Inform user about this stopping:
			$this->log_error( 'Stop import because <code>'.$errors_limit.'</code> errors have been reached!' );

			// Rollback all DB changes:
			$DB->rollback();

			// Exit here:
			exit;
		}
	}


	/**
	 * Load import data from request
	 *
	 * @return boolean TRUE on load all fields without error
	 */
	function load_from_Request()
	{
		// Collection:
		$this->coll_ID = param( 'wp_blog_ID', 'integer', 0 );
		param_check_not_empty( 'wp_blog_ID', 'Please select a collection!' );

		// The import type ( replace | append )
		$import_type = param( 'import_type', 'string', 'replace', true );
		// Should we delete files on 'replace' mode?
		param( 'delete_files', 'integer', 0, true );
		// Should we try to match <img> tags with imported attachments based on filename in post content after import?
		param( 'import_img', 'integer', 0, true );
		// Stop import after X errors:
		$stop_error_enabled = param( 'stop_error_enabled', 'integer', 0, true );
		$stop_error_num = param( 'stop_error_num', 'integer', 0, true );
		if( $stop_error_enabled )
		{	// Wrong stop errors number:
			if( $stop_error_num < 1 )
			{
				param_error( 'stop_error_num', 'Stop errors number must be greater than 0.' );
			}
			else
			{	// Set limit by errors:
				$this->errors_limit = $stop_error_num;
			}
		}
		// Convert wp links like "?page_id=" to b2evo shortlinks:
		param( 'convert_links', 'integer', 0, true );

		// XML File:
		$xml_file = param( 'import_file', 'string', '', true );
		if( empty( $xml_file ) )
		{	// File is not selected
			param_error( 'import_file', 'Please select file to import.' );
		}
		else if( ! preg_match( '/\.(xml|txt|zip)$/i', $xml_file ) )
		{	// Extension is incorrect
			param_error( 'import_file', sprintf( '&laquo;%s&raquo; has an unrecognized extension.', $xml_file ) );
		}

		if( get_param( 'action' ) == 'import' && 
		    $import_type == 'replace' &&
		    param( 'import_type_replace_confirm', 'string' ) !== 'DELETE' )
		{	// If deleting/replacing is not confirmed:
			param_error( 'import_type_replace_confirm', sprintf( T_('Type %s to confirm'), '<code>DELETE</code>' ).'!' );
		}

		return ! param_errors_detected();
	}


	/**
	 * Display info for the wordpress importer
	 *
	 * @param boolean TRUE to allow to use already extracted ZIP archive
	 * @return array Data of the parsed XML file, @see wpxml_get_import_data()
	 */
	function display_info( $allow_use_extracted_folder = false )
	{
		$this->log( '<p style="margin-bottom:0">' );

		$wp_file = get_param( 'import_file' );

		if( preg_match( '/\.zip$/i', $wp_file ) )
		{	// Inform about unzipping before start this in wpxml_get_import_data():
			$zip_folder_path = substr( $wp_file, 0, -4 );
			if( ! $allow_use_extracted_folder ||
					! file_exists( $zip_folder_path ) ||
					! is_dir( $zip_folder_path ) )
			{
				$this->log( '<b>'.TB_('Unzipping ZIP').':</b> <code>'.$wp_file.'</code>...<br />' );
			}
		}

		// Get data to import from wordpress XML file:
		$this->info_data = wpxml_get_import_data( $wp_file, $allow_use_extracted_folder );

		if( $this->info_data['errors'] === false )
		{
			if( preg_match( '/\.zip$/i', $wp_file ) )
			{	// ZIP archive:
				$this->log( '<b>'.TB_('Source ZIP').':</b> <code>'.$wp_file.'</code><br />' );
				// XML file from ZIP archive:
				$this->log( '<b>'.TB_('Source XML').':</b> '
					.( empty( $this->info_data['XML_file_path'] )
						? T_('Not found')
						: '<code>'.$this->info_data['XML_file_path'].'</code>' ).'<br />' );
			}
			else
			{	// XML file:
				$this->log( '<b>'.TB_('Source XML').':</b> <code>'.$wp_file.'</code><br />' );
			}

			$this->log( '<b>'.TB_('Source attachments folder').':</b> '
				.( empty( $this->info_data['attached_files_path'] )
					? T_('Not found')
					: '<code>'.$this->info_data['attached_files_path'].'</code>' ).'<br />' );

			$BlogCache = & get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( get_param( 'wp_blog_ID' ) );
			$this->info_data['Blog'] = & $Blog;
			$this->log( '<b>'.TB_('Destination collection').':</b> '.$Blog->dget( 'shortname' ).' &ndash; '.$Blog->dget( 'name' ).'<br />' );

			$this->log( '<b>'.TB_('Import mode').':</b> ' );
			switch( get_param( 'import_type' ) )
			{
				case 'append':
					$this->log( TB_('Append to existing contents') );
					break;
				case 'replace':
					$this->log( TB_('Replace existing contents').' <span class="note">'.TB_('WARNING: this option will permanently remove existing posts, comments, categories and tags from the selected collection.').'</span>' );
					if( get_param( 'action' ) == 'confirm' )
					{	// Display an input to confirm replace import mode:
						echo '<br /><div class="alert alert-danger" style="display:inline-block;width:auto;margin:0">'
							.TB_('WARNING').': '
							.TB_('you will LOSE any data that is not part of the files you import.').' '
							.sprintf( TB_('Type %s to confirm'), '<code>DELETE</code>' ).': '
							.'<input name="import_type_replace_confirm" type="text" class="form-control" size="8" style="display:inline-block;width:auto;margin:-8px 0" /></div>';
					}
					if( get_param( 'delete_files' ) )
					{
						$this->log( '<br /> &nbsp; &nbsp; [√] '.TB_(' Also delete media files that will no longer be referenced in the destination collection after replacing its contents') );
					}
					break;
			}
			$this->log( '<br />' );

			// Display selected options:
			$selected_options = array();
			if( get_param( 'import_img' ) )
			{
				$selected_options[] = sprintf( TB_('Try to replace %s tags with imported attachments based on filename'), '<code>&lt;img src="...&gt;</code>' );
			}
			if( get_param( 'stop_error_enabled' ) )
			{
				$selected_options[] = sprintf( TB_('Stop import after %s errors'), '<code>'.get_param( 'stop_error_num' ).'</code>' );
			}
			if( get_param( 'convert_links' ) )
			{
				$selected_options[] = sprintf( TB_('Convert wp links like %s to b2evo shortlinks'), '<code>?page_id=</code>' );
			}
			$selected_options_count = count( $selected_options );
			if( $selected_options_count )
			{
				$this->log( '<b>'.TB_('Options').':</b> ' );
				if( $selected_options_count == 1 )
				{
					$this->log( $selected_options[0] );
				}
				else
				{
					$this->log( '<ul class="list-default">' );
					foreach( $selected_options as $option )
					{
						$this->log( '<li>'.$option.'</li>' );
					}
					$this->log( '</ul>' );
				}
			}
		}
		else
		{	// Display errors if import cannot be done:
			$this->log( $this->info_data['errors'].'<br />' );
			$this->log_error( T_('Import failed.'), 'p', false );
		}

		$this->log( '</p>' );
	}


	/**
	 * Import WordPress data from XML/ZIP file into b2evolution database
	 */
	function execute()
	{
		global $DB, $tableprefix, $media_path, $Plugins;

		// Load classes:
		load_class( 'regional/model/_country.class.php', 'Country' );
		load_class( 'regional/model/_region.class.php', 'Region' );
		load_class( 'regional/model/_subregion.class.php', 'Subregion' );
		load_class( 'regional/model/_city.class.php', 'City' );

		$XML_file_path = $this->info_data['XML_file_path'];
		$attached_files_path = $this->info_data['attached_files_path'];
		$ZIP_folder_path = $this->info_data['ZIP_folder_path'];

		$wp_Blog = & $this->get_Blog();

		// The import type ( replace | append )
		$import_type = get_param( 'import_type' );
		// Should we delete files on 'replace' mode?
		$delete_files = get_param( 'delete_files' );
		// Should we try to match <img> tags with imported attachments based on filename in post content after import?
		$import_img = get_param( 'import_img' );
		// Item Types relations:
		$selected_item_type_names = param( 'item_type_names', 'array:integer' );
		$selected_item_type_usages = param( 'item_type_usages', 'array:integer' );
		$selected_item_type_none = param( 'item_type_none', 'integer' );
		// Convert wp links like "?page_id=" to b2evo shortlinks:
		$convert_links = param( 'convert_links', 'integer', 0, true );

		// Store here all imported files:
		$imported_file_names = array(); // Key - file name, Value - File ID or FALSE when same file name is used in different folders
		$imported_file_paths = array(); // Key - relative file path, Value - File ID

		// Parse WordPress XML file into array
		$this->log( 'Loading & parsing the XML file...'.'<br />' );
		$xml_data = wpxml_parser( $XML_file_path );
		$this->log( '<ul class="list-default">' );
			$this->log( '<li>'.'Memory used by XML parsing (difference between free RAM before loading XML and after)'.': <b>'.bytesreadable( $xml_data['memory']['parsing'] ).'</b></li>' );
			$this->log( '<li>'.'Memory used by temporary arrays (difference between free RAM after loading XML and after copying all the various data into temporary arrays)'.': <b>'.bytesreadable( $xml_data['memory']['arrays'] ).'</b></li>' );
		$this->log( '</ul>' );

		$DB->begin();

		if( $import_type == 'replace' )
		{ // Remove data from selected blog

			// Get existing categories
			$SQL = new SQL( 'Get existing categories of collection #'.$this->coll_ID );
			$SQL->SELECT( 'cat_ID' );
			$SQL->FROM( 'T_categories' );
			$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
			$old_categories = $DB->get_col( $SQL );
			if( !empty( $old_categories ) )
			{	// Get existing posts:
				$SQL = new SQL( 'WP Import: Get existing posts for deleting' );
				$SQL->SELECT( 'post_ID' );
				$SQL->FROM( 'T_items__item' );
				$SQL->WHERE( 'post_main_cat_ID IN ( '.implode( ', ', $old_categories ).' )' );
				$old_posts = $DB->get_col( $SQL );
			}

			$this->log( 'Removing the comments... ' );
			$deleted_comments_num = 0;
			if( ! empty( $old_posts ) )
			{
				$SQL = new SQL( 'WP Import: Get existing comments for deleting' );
				$SQL->SELECT( 'comment_ID' );
				$SQL->FROM( 'T_comments' );
				$SQL->WHERE( 'comment_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$old_comments = $DB->get_col( $SQL );
				$deleted_comments_num = $DB->query( 'DELETE FROM T_comments WHERE comment_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
				if( !empty( $old_comments ) )
				{
					$DB->query( 'DELETE FROM T_comments__votes WHERE cmvt_cmt_ID IN ( '.implode( ', ', $old_comments ).' )' );
					$DB->query( 'DELETE FROM T_links WHERE link_cmt_ID IN ( '.implode( ', ', $old_comments ).' )' );
				}
			}
			$this->log( '<b>'.( $deleted_comments_num
					? sprintf( '%d comments were deleted', $deleted_comments_num )
					: 'No comments were deleted'
				).'</b><br />' );

			$this->log( 'Removing the posts... ' );
			$deleted_posts_num = 0;
			if( !empty( $old_categories ) )
			{
				$deleted_posts_num = $DB->query( 'DELETE FROM T_items__item WHERE post_main_cat_ID IN ( '.implode( ', ', $old_categories ).' )' );
				if( !empty( $old_posts ) )
				{ // Remove the post's data from related tables
					if( $delete_files )
					{ // Get the file IDs that should be deleted from hard drive
						$SQL = new SQL( 'WP Import: Get the file IDs that should be deleted from hard drive' );
						$SQL->SELECT( 'DISTINCT link_file_ID' );
						$SQL->FROM( 'T_links' );
						$SQL->WHERE( 'link_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
						$deleted_file_IDs = $DB->get_col( $SQL );
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

					// Call plugin event after Items were deleted:
					$Plugins->trigger_event( 'ImporterAfterItemsDelete', array(
							'type'             => $this->import_code,
							'Importer'         => $this,
							'deleted_item_IDs' => $old_posts,
						) );
				}
			}
			$this->log( '<b>'.( $deleted_posts_num
					? sprintf( '%d posts were deleted', $deleted_posts_num )
					: 'No posts were deleted'
				).'</b><br />' );

			$this->log( 'Removing the categories... ' );
			$deleted_cats_num = $DB->query( 'DELETE FROM T_categories WHERE cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
			$this->log( '<b>'.( $deleted_cats_num
					? sprintf( '%d cats were deleted', $deleted_cats_num )
					: 'No cats were deleted'
				).'</b><br />' );

			$this->log( 'Removing the tags that are no longer used... ' );
			$deleted_tags_num = 0;
			if( !empty( $old_posts ) )
			{ // Remove the tags

				// Get tags from selected collection:
				$SQL = new SQL( 'WP Import: Get tags from selected collection for deleting' );
				$SQL->SELECT( 'itag_tag_ID' );
				$SQL->FROM( 'T_items__itemtag' );
				$SQL->WHERE( 'itag_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$old_tags_this_blog = array_unique( $DB->get_col( $SQL ) );

				if( ! empty( $old_tags_this_blog ) )
				{
					// Get tags from other collections:
					$SQL = new SQL( 'WP Import: Get tags from other collections for deleting' );
					$SQL->SELECT( 'itag_tag_ID' );
					$SQL->FROM( 'T_items__itemtag' );
					$SQL->WHERE( 'itag_itm_ID NOT IN ( '.implode( ', ', $old_posts ).' )' );
					$old_tags_other_blogs = array_unique( $DB->get_col( $SQL ) );
					$old_tags_other_blogs_sql = !empty( $old_tags_other_blogs ) ? ' AND tag_ID NOT IN ( '.implode( ', ', $old_tags_other_blogs ).' )': '';

					// Remove the tags that are no longer used
					$deleted_tags_num = $DB->query( 'DELETE FROM T_items__tag
						WHERE tag_ID IN ( '.implode( ', ', $old_tags_this_blog ).' )'.
						$old_tags_other_blogs_sql );
				}

				// Remove the links of tags with posts
				$DB->query( 'DELETE FROM T_items__itemtag WHERE itag_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
			}
			$this->log( '<b>'.( $deleted_tags_num
					? sprintf( '%d tags were deleted', $deleted_tags_num )
					: 'No tags were deleted'
				).'</b><br />' );

			if( $delete_files )
			{ // Delete the files
				$this->log( 'Removing the files... ' );

				$deleted_files_num = 0;
				if( ! empty( $deleted_file_IDs ) )
				{
					// Commit the DB changes before files deleting
					$DB->commit();

					// Get the deleted file IDs that are linked to other objects
					$SQL = new SQL( 'WP Import: Get the file IDs that are linked to other objects' );
					$SQL->SELECT( 'DISTINCT link_file_ID' );
					$SQL->FROM( 'T_links' );
					$SQL->WHERE( 'link_file_ID IN ( '.implode( ', ', $deleted_file_IDs ).' )' );
					$linked_file_IDs = $DB->get_col( $SQL );
					// We can delete only the files that are NOT linked to other objects
					$deleted_file_IDs = array_diff( $deleted_file_IDs, $linked_file_IDs );

					$FileCache = & get_FileCache();
					foreach( $deleted_file_IDs as $deleted_file_ID )
					{
						if( ! ( $deleted_File = & $FileCache->get_by_ID( $deleted_file_ID, false, false ) ) )
						{	// Incorrect file ID:
							$this->log_error( sprintf( 'No file #%s found in DB. It cannot be deleted.', $deleted_file_ID ) );
							continue;
						}
						$deleted_file_path = $deleted_File->get_full_path();
						if( $deleted_File->unlink() )
						{	// Successful deleting:
							$this->log_success( sprintf( 'File #%d %s has been deleted.', $deleted_file_ID, '<code>'.$deleted_file_path.'</code>' ) );
							$deleted_files_num++;
						}
						else
						{ // No permission to delete file
							$this->log_error( sprintf( 'Could not delete the file %s.', '<code>'.$deleted_File->get_full_path().'</code>' ) );
						}
						// Clear cache to save memory
						$FileCache->clear();
					}

					// Start new transaction for the data inserting
					$DB->begin();
				}

				$this->log( '<b>'.( $deleted_files_num
						? sprintf( '%d files were deleted', $deleted_files_num )
						: 'No files were deleted'
					).'</b><br />' );
			}

			$this->log( '<br />' );
		}


		/* Import authors */
		$authors = array();
		$authors_IDs = array();
		$authors_links = array();
		if( isset( $xml_data['authors'] ) && count( $xml_data['authors'] ) > 0 )
		{
			global $Settings, $UserSettings;

			$this->log( '<p><b>'.'Importing users...'.' </b>' );

			// Get existing users:
			$SQL = new SQL( 'WP Import: Get existing users before importing' );
			$SQL->SELECT( 'user_login, user_ID' );
			$SQL->FROM( 'T_users' );
			$existing_users = $DB->get_assoc( $SQL );

			$new_users_num = 0;
			$skipped_users_num = 0;
			$failed_users_num = 0;
			foreach( $xml_data['authors'] as $author )
			{
				// Replace unauthorized chars of username:
				$author_login = preg_replace( '/([^a-z0-9_\-\.])/i', '_', $author['author_login'] );
				$author_login = utf8_substr( utf8_strtolower( $author_login ), 0, 20 );

				$this->log( '<p>'.sprintf( 'Importing user: %s', '#'.$author['author_id'].' - "'.$author_login.'"' ).'... ' );

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
					$User->set( 'salt', $author['author_salt'] );
					$User->set( 'pass_driver', $author['author_pass_driver'] );
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
					if( ! $User->dbinsert() )
					{	// Error on insert new user:
						$failed_users_num++;
						$this->log_error( sprintf( 'User %s could not be inserted in DB.', '<code>'.$author_login.'</code>' ), 'span', false );
						continue;
					}
					$user_ID = $User->ID;
					if( !empty( $user_ID ) && !empty( $author['author_created_fromIPv4'] ) )
					{
						$UserSettings->set( 'created_fromIPv4', ip2int( $author['author_created_fromIPv4'] ), $user_ID );
					}

					if( ! empty( $author['links'] ) )
					{	// Store user attachments in array to link them below after importing files:
						$authors_links[ $user_ID ] = array();
						foreach( $author['links'] as $link )
						{
							if( isset( $author['author_avatar_file_ID'] ) &&
									$link['link_file_ID'] == $author['author_avatar_file_ID'] )
							{	// Mark this link as main avatar in order to update this with new inserted file ID below:
								$link['is_main_avatar'] = true;
							}
							$authors_links[ $user_ID ][ $link['link_file_ID'] ] = $link;
						}
					}

					$new_users_num++;
					$this->log_success( 'OK', 'span' );
				}
				else
				{	// Get ID of existing user
					$user_ID = $existing_users[ $author_login ];
					$this->log_warning( sprintf( 'Skip because user already exists with same login and ID #%d.', intval( $user_ID ) ), 'span', false );
					$skipped_users_num++;
				}
				// Save user ID of current author
				$authors[ $author_login ] = (string) $user_ID;
				$authors_IDs[ $author['author_id'] ] = (string) $user_ID;

				$this->log( '</p>' );
			}

			$UserSettings->dbupdate();

			$this->log_success( sprintf( '%d new users', $new_users_num ), 'b' );
			if( $skipped_users_num )
			{
				$this->log( '<br />' );
				$this->log_warning( sprintf( '%d skipped users', $skipped_users_num ), 'b', false );
			}
			if( $failed_users_num )
			{
				$this->log( '<br />' );
				$this->log_error( sprintf( '%d users could not be imported', $failed_users_num ), 'b', false );
			}
			$this->log( '</p>' );
		}

		/* Import files, Copy them all to media folder */
		$files = array();
		if( isset( $xml_data['files'] ) && count( $xml_data['files'] ) > 0 )
		{
			$this->log( '<p><b>'.'Importing the files...'.' </b>' );

			if( ! $attached_files_path || ! file_exists( $attached_files_path ) )
			{	// Display an error if files are attached but folder doesn't exist:
				$this->log_error( sprintf( 'No attachments folder %s found. It must exists to import the attached files properly.', ( $attached_files_path ? '<code>'.$attached_files_path.'</code>' : '' ) ) );
			}
			else
			{	// Try to import files from the selected subfolder:
				$files_count = 0;

				$UserCache = & get_UserCache();

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
								$wp_user_ID = $file['file_root_ID'];
								$file['file_root_ID'] = $authors_IDs[ $file['file_root_ID'] ];
								break;
							}
							else
							{
								unset( $wp_user_ID );
							}
							// Otherwise we should upload this file into blog's folder:

						default: // 'collection', 'absolute', 'skins'
							// The files from other blogs and from other places must be moved in the folder of the current blog
							$file['file_root_type'] = 'collection';
							$file['file_root_ID'] = $this->coll_ID;
							break;
					}

					// Source of the importing file:
					$file_source_path = $attached_files_path.$file['zip_path'].$file['file_path'];

					// Try to import file from source path:
					if( $File = & $this->create_File( $file_source_path, $file ) )
					{	// Store the created File in array because it will be linked to the Items below:
						$files[ $file['file_ID'] ] = $File;

						if( $import_img )
						{	// Collect file name in array to link with post below:
							$file_name = basename( $file['file_path'] );
							if( isset( $imported_file_names[ $file_name ] ) )
							{	// Don't use this file if more than one use same name, e.g. from different folders:
								$imported_file_names[ $file_name ] = false;
								$this->log_error( sprintf( 'there are 2+ attachements with conflicting name %s.',
										'<code>'.$file_name.'</code>' ) );
							}
							else
							{	// This is a first detected file with current name:
								$imported_file_names[ $file_name ] = $File->ID;
							}
							// Store relative file path:
							$imported_file_paths[ $file['zip_path'].$file['file_path'] ] = $File->ID;
						}

						if( $file['file_root_type'] == 'user' &&
								isset( $authors_links[ $file['file_root_ID'] ], $authors_links[ $file['file_root_ID'] ][ $file['file_ID'] ] ) &&
								( $User = & $UserCache->get_by_ID( $file['file_root_ID'], false, false ) ) )
						{	// Link file to User:
							$link = $authors_links[ $file['file_root_ID'] ][ $file['file_ID'] ];
							$LinkOwner = new LinkUser( $User );
							if( ! empty( $link['is_main_avatar'] ) )
							{	// Update if current file is main avatar for the User:
								$User->set( 'avatar_file_ID', $File->ID );
								$User->dbupdate();
							}
							if( $File->link_to_Object( $LinkOwner, $link['link_order'], $link['link_position'] ) )
							{	// If file has been linked to the post:
								$this->log_success( sprintf( 'File %s has been linked to User %s.', '<code>'.$File->_adfp_full_path.'</code>', $User->get_identity_link() ) );
							}
							else
							{	// If file could not be linked to the post:
								$this->log_warning( sprintf( 'File %s could not be linked to User %s.', '<code>'.$File->_adfp_full_path.'</code>', $User->get_identity_link() ) );
							}
						}

						$files_count++;
					}
				}

				$this->log( '<b>'.sprintf( '%d records', $files_count ).'</b></p>' );
			}
		}

		/* Import categories */
		$category_default = 0;
		load_class( 'chapters/model/_chapter.class.php', 'Chapter' );

		// Get existing categories
		$SQL = new SQL( 'WP Import: Get existing categories before importing' );
		$SQL->SELECT( 'cat_urlname, cat_ID' );
		$SQL->FROM( 'T_categories' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
		$categories = $DB->get_assoc( $SQL );

		if( isset( $xml_data['categories'] ) && count( $xml_data['categories'] ) > 0 )
		{
			$this->log( '<p><b>'.'Importing the categories...'.' </b>' );

			load_funcs( 'locales/_charset.funcs.php' );

			$categories_count = 0;
			foreach( $xml_data['categories'] as $cat )
			{
				$this->log( '<p>'.sprintf( 'Importing category: %s', '"'.$cat['cat_name'].'"' ).'... ' );

				if( ! empty( $categories[ (string) $cat['category_nicename'] ] ) )
				{
					$this->log_warning( sprintf( 'Skip creating, use existing category #%d with same slug %s.', intval( $categories[ (string) $cat['category_nicename'] ] ), '<code>'.$cat['category_nicename'].'</code>' ), 'span', false );
				}
				else
				{
					$Chapter = new Chapter( NULL, $this->coll_ID );
					$Chapter->set( 'name', $cat['cat_name'] );
					$Chapter->set( 'urlname', $cat['category_nicename'] );
					$Chapter->set( 'description', $cat['cat_description'] );
					$Chapter->set( 'order', ( $cat['cat_order'] === '' ? NULL : $cat['cat_order'] ), true );
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
					$this->log_success( 'OK', 'span' );
				}
			}

			$this->log( '</p>' );

			$this->log( '<b>'.sprintf( '%d records', $categories_count ).'</b></p>' );
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
			$new_Chapter = new Chapter( NULL, $this->coll_ID );
			$new_Chapter->set( 'name', 'Uncategorized' );
			$new_Chapter->set( 'urlname', $wp_Blog->get( 'urlname' ).'-main' );
			$new_Chapter->dbinsert();
			$category_default = $new_Chapter->ID;
		}

		/* Import tags */
		$tags = array();
		if( isset( $xml_data['tags'] ) && count( $xml_data['tags'] ) > 0 )
		{
			$this->log( '<p><b>'.'Importing the tags...'.' </b>' );

			// Get existing tags:
			$SQL = new SQL( 'WP Import: Get existing tags before importing' );
			$SQL->SELECT( 'tag_name, tag_ID' );
			$SQL->FROM( 'T_items__tag' );
			$tags = $DB->get_assoc( $SQL );

			$tags_count = 0;
			foreach( $xml_data['tags'] as $tag )
			{
				$tag_name = substr( html_entity_decode( $tag['tag_name'] ), 0, 50 );
				$this->log( '<p>'.sprintf( 'Importing tag: %s', '"'.$tag_name.'"' ).'... ' );
				if( ! empty( $tags[ $tag_name ] ) )
				{
					$this->log_warning( sprintf( 'Skip because tag #%d already exists with same name %s.', intval( $tags[ $tag_name ] ), '<code>'.$tag_name.'</code>' ), 'span', false );
				}
				else
				{	// Insert new tag into DB if tag doesn't exist with current name
					$DB->query( 'INSERT INTO '.$tableprefix.'items__tag ( tag_name )
						VALUES ( '.$DB->quote( $tag_name ).' )' );
					$tag_ID = $DB->insert_id;
					// Save new tag
					$tags[ $tag_name ] = (string) $tag_ID;
					$tags_count++;
					$this->log_success( 'OK', 'span' );
				}
			}
			$this->log( '</p>' );
			$this->log( '<b>'.sprintf( '%d records', $tags_count ).'</b></p>' );
		}


		/* Import posts */
		$posts = array();
		$comments = array();
		if( isset( $xml_data['posts'] ) && count( $xml_data['posts'] ) > 0 )
		{
			load_class( 'items/model/_item.class.php', 'Item' );

			$ChapterCache = & get_ChapterCache();
			// We MUST clear Chapters Cache here in order to avoid inserting new Items with wrong/previous/deleted Chapters from DB:
			$ChapterCache->clear();

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

			$this->log( '<p><b>'.'Importing the files from attachment posts...'.' </b>' );

			$attached_post_files = array();
			$attachment_IDs = array();
			$attachments_count = 0;
			foreach( $xml_data['posts'] as $post )
			{	// Import ONLY attachment posts here, all other posts are imported below:
				if( $post['post_type'] != 'attachment' )
				{	// Skip not attachment post:
					continue;
				}

				$this->log( '<p>'.sprintf( 'Importing attachment: %s', '#'.$post['post_id'].' - "'.$post['post_title'].'"' ) );

				if( ! empty( $post['post_parent'] ) )
				{	// Store what post the File is linked to:
					if( ! isset( $attached_post_files[ $post['post_parent'] ] ) )
					{
						$attached_post_files[ $post['post_parent'] ] = array();
					}
					$attached_post_files[ $post['post_parent'] ][] = $post['post_id'];
				}

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
								'file_root_ID'   => $this->coll_ID,
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
						if( $File = & $this->create_File( $file_source_path, $file_params ) )
						{	// Store the created File in array because it will be linked to the Items below:
							$attachment_IDs[ $post['post_id'] ] = $File->ID;
							$files[ $File->ID ] = $File;

							if( $import_img )
							{	// Collect file name in array to link with post below:
								$file_name = basename( $file_source_path );
								if( isset( $imported_file_names[ $file_name ] ) )
								{	// Don't use this file if more than one use same name, e.g. from different folders:
									$imported_file_names[ $file_name ] = false;
									$this->log_error( sprintf( 'there are 2+ attachements with conflicting name %s.',
											'<code>'.$file_name.'</code>' ) );
								}
								else
								{	// This is a first detected file with current name:
									$imported_file_names[ $file_name ] = $File->ID;
								}
								// Store relative file path:
								$imported_file_paths[ $attch_file_name ] = $File->ID;
							}

							$attachments_count++;
							// Break here because such post can contains only one file:
							break;
						}

						$attch_imported_files[] = $attch_file_name;
					}
				}

				$this->log( '</p>' );
				$attachments_count++;
			}

			$this->log( '<b>'.sprintf( '%d records', $attachments_count ).'</b></p>' );

			$this->log( '<p><b>'.'Importing the posts...'.' </b>' );

			$posts_count = 0;
			foreach( $xml_data['posts'] as $post )
			{
				if( $post['post_type'] == 'revision' )
				{	// Ignore post with type "revision":
					$this->log_warning( sprintf( 'Ignore post "%s" because of post type is %s',
							'#'.$post['post_id'].' - '.$post['post_title'],
							'<code>'.$post['post_type'].'</code>' ) );
					continue;
				}
				elseif( $post['post_type'] == 'attachment' )
				{	// Skip attachment post because it shoul be imported above:
					continue;
				}
				elseif( $post['post_type'] == 'page' && ! isset( $categories['standalone-pages'] ) )
				{	// Try to create special category "Standalone Pages" for pages only it doesn't exist:
					$page_Chapter = new Chapter( NULL, $this->coll_ID );
					$page_Chapter->set( 'name', 'Standalone Pages' );
					$page_Chapter->set( 'urlname', 'standalone-pages' );
					$page_Chapter->dbinsert();
					$categories['standalone-pages'] = $page_Chapter->ID;
					// Add new created chapter to cache to avoid error when this cache loaded all elements before:
					$ChapterCache->add( $page_Chapter );
				}

				$this->log( '<p>'.sprintf( 'Importing post: %s', '#'.$post['post_id'].' - "'.$post['post_title'].'"... ' ) );

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
								$tag_name = substr( html_entity_decode( $term['slug'] ), 0, 50 );
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

				// Set Item Type ID:
				if( ! empty( $post['itemtype'] ) && isset( $selected_item_type_names[ $post['itemtype'] ] ) )
				{	// Try to use Item Type by name:
					$post_type_ID = $selected_item_type_names[ $post['itemtype'] ];
					if( $post_type_ID == 0 )
					{	// Skip Item because this was selected on the confirm form:
						$this->log_warning( sprintf( 'Skip Item because Item Type %s is not selected for import.',
								'<code>&lt;evo:itemtype&gt;</code> = <code>'.$post['itemtype'].'</code>' ) );
						continue;
					}
				}
				elseif( ! empty( $post['post_type'] ) && isset( $selected_item_type_usages[ $post['post_type'] ] ) )
				{	// Try to use Item Type by usage:
					$post_type_ID = $selected_item_type_usages[ $post['post_type'] ];
					if( $post_type_ID == 0 )
					{	// Skip Item because this was selected on the confirm form:
						$this->log_warning( sprintf( 'Skip Item because Item Type %s is not selected for import.',
								'<code>&lt;wp:post_type&gt;</code> = <code>'.$post['post_type'].'</code>' ) );
						continue;
					}
				}
				else
				{	// Try to use Item Type without provided value sin XML:
					$post_type_ID = $selected_item_type_none;
					if( $post_type_ID == 0 )
					{	// Skip Item because this was selected on the confirm form:
						$this->log_warning( 'Skip Item because you didn\'t select to import without provided item type.' );
						continue;
					}
				}

				$ItemTypeCache = & get_ItemTypeCache();
				if( ! ( $ItemType = & $ItemTypeCache->get_by_ID( $post_type_ID, false, false ) ) )
				{	// Skip not found Item Type:
					$this->log_error( sprintf( 'Skip Item because Item Type %s is not found.',
						'<code>'.( isset( $post['itemtype'] ) ? $post['itemtype'] : $post['post_type'] ).'</code>' ) );
					continue;
				}

				$Item = new Item();

				if( ! empty( $post['custom_fields'] ) )
				{	// Import custom fields:
					$item_type_custom_fields = $ItemType->get_custom_fields();
					foreach( $post['custom_fields'] as $custom_field_name => $custom_field )
					{
						if( ! isset( $item_type_custom_fields[ $custom_field_name ] ) )
						{	// Skip unknown custom field:
							$this->log_error( sprintf( 'Skip custom field %s because Item Type %s has no it.',
								'<code>'.$custom_field_name.'</code>',
								'#'.$ItemType->ID.' "'.$ItemType->get( 'name' ).'"' ) );
							continue;
						}
						if( $item_type_custom_fields[ $custom_field_name ]['type'] != $custom_field['type'] )
						{	// Skip wrong custom field type:
							$this->log_error( sprintf( 'Cannot import custom field %s because it has type %s and we expect type %s',
								'<code>'.$custom_field_name.'</code>',
								'<code>'.$custom_field['type'].'</code>',
								'<code>'.$item_type_custom_fields[ $custom_field_name ]['type'].'</code>' ) );
							continue;
						}
						$Item->set_custom_field( $custom_field_name, $custom_field['value'] );
					}
				}

				// Get regional IDs by their names
				$item_regions = wp_get_regional_data( $post['post_country'], $post['post_region'], $post['post_subregion'], $post['post_city'] );

				$post_content = $post['post_content'];

				// Use title by default to generate new slug:
				$post_urltitle = $post['post_title'];
				if( ! empty( $post['post_urltitle'] ) )
				{	// Use top priority urltitle if it is provided:
					$post_urltitle = $post['post_urltitle'];
				}
				elseif( ! empty( $post['post_link'] ) && preg_match( '#/([^/]+)/?$#', $post['post_link'], $post_link_match ) )
				{	// Try to extract canonical slug from post URL:
					$post_urltitle = $post_link_match[1];
				}

				$Item->set( 'main_cat_ID', $post_main_cat_ID );
				$Item->set( 'creator_user_ID', $author_ID );
				$Item->set( 'lastedit_user_ID', $last_edit_user_ID );
				$Item->set( 'title', $post['post_title'] );
				$Item->set( 'content', $post_content );
				$Item->set( 'datestart', $post['post_date'] );
				$Item->set( 'datecreated', !empty( $post['post_datecreated'] ) ? $post['post_datecreated'] : $post['post_date'] );
				$Item->set( 'datemodified', !empty( $post['post_datemodified'] ) ? $post['post_datemodified'] : $post['post_date'] );
				$Item->set( 'urltitle', $post_urltitle );
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
				$item_content_was_changed = false;
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
								$this->log_success( sprintf( 'File %s has been linked to this post as %s from %s.',
									'<code>'.$File->_adfp_full_path.'</code>',
									'<code>'.$link['link_position'].'</code>',
									'<code>&lt;evo:link&gt;</code>' ) );
								$file_is_linked = true;
								// Update link order to the latest for two other ways([caption] and <img />) below:
								$link_order = $link['link_order'] + 1;
							}
						}
						if( ! $file_is_linked )
						{	// If file could not be linked to the post:
							$this->log_warning( sprintf( 'Link %s could not be attached to this post because file %s is not found.', '#'.$link['link_ID'], '#'.$link['link_file_ID'] ) );
						}
					}
				}

				$linked_post_files = array();
				if( isset( $post['postmeta'] ) )
				{	// Extract additional data:
					foreach( $post['postmeta'] as $postmeta )
					{
						if( $postmeta['key'] == '_thumbnail_id' )
						{	// Try to link the File as cover:
								$linked_post_files[] = $postmeta['value'];
								$file_is_linked = false;
								if( isset( $attachment_IDs[ $postmeta['value'] ] ) && isset( $files[ $attachment_IDs[ $postmeta['value'] ] ] ) )
								{
									$File = $files[ $attachment_IDs[ $postmeta['value'] ] ];
									if( $File->link_to_Object( $LinkOwner, $link_order, 'cover' ) )
									{	// If file has been linked to the post:
										$this->log_success( sprintf( 'File %s has been linked to this post as %s from %s.',
											'<code>'.$File->_adfp_full_path.'</code>',
											'<code>cover</code>',
											'<code>&lt;wp:meta_key&gt;_thumbnail_id&lt;/wp:meta_key&gt;</code>' ) );
										$file_is_linked = true;
										$link_order++;
									}
								}
								if( ! $file_is_linked )
								{	// If file could not be linked to the post:
									$this->log_warning( sprintf( 'Cover file %s could not be attached to this post because it is not found in the source attachments by %s.',
										'#'.$postmeta['value'],
										'<code>&lt;wp:meta_key&gt;_thumbnail_id&lt;/wp:meta_key&gt;</code> = <code>'.$postmeta['value'].'</code>' ) );
								}
						}
						elseif( strpos( $postmeta['key'], 'wpcf-' ) === 0 )
						{	// Custom field:
							$custom_field_name = substr( $postmeta['key'], 5 );

							if( $custom_field_name === '' )
							{	// Empty custom field name:
								$this->log_error( sprintf( 'Skip wp custom field without name; value %s',
									isset( $postmeta['value'] ) ? '= <code>'.$postmeta['value'].'</code>' : 'is not defined' ) );
								continue;
							}

							if( ! isset( $postmeta['value'] ) || $postmeta['value'] === NULL )
							{	// No provided value:
								$this->log_error( sprintf( 'Skip wp custom field %s without value!',
									'<code>'.$custom_field_name.'</code>' ) );
								continue;
							}

							$custom_field_value = $postmeta['value'];

							$item_type_custom_fields = $ItemType->get_custom_fields();

							if( ! isset( $item_type_custom_fields[ $custom_field_name ] ) )
							{	// Skip unknown custom field:
								$this->log_warning( sprintf( 'Custom field %s has been ignored because there is no %s custom field in Item Type %s.',
									'<code>wpcf-'.$custom_field_name.'</code>',
									'<code>'.$custom_field_name.'</code>',
									'#'.$ItemType->ID.' "'.$ItemType->get( 'name' ).'"' ) );
								continue;
							}

							if( ( in_array( $item_type_custom_fields[ $custom_field_name ]['type'], array( 'double', 'computed' ) ) && ! empty( $custom_field_value ) && ! preg_match( '/^(\+|-)?[0-9]+(\.[0-9]+)?$/', $custom_field_value ) ) ||
							    ( $item_type_custom_fields[ $custom_field_name ]['type'] == 'url' && validate_url( $custom_field_value, 'http-https' ) !== false ) ||
							    ( $item_type_custom_fields[ $custom_field_name ]['type'] == 'image' && ! empty( $custom_field_value ) && ! is_number( $custom_field_value ) ) )
							{	// Skip wrong custom field type format:
								$this->log_error( sprintf( 'Custom field %s type mismatch: value %s cannot be imported into %s',
									'<code>wpcf-'.$custom_field_name.'</code>',
									'<code>'.$custom_field_value.'</code>',
									'<code>'.$custom_field_name.'</code>' ) );
								continue;
							}

							// Set custom field value and log this:
							$Item->set_custom_field( $custom_field_name, $custom_field_value );
							$this->log( '<p>'.sprintf( 'Custom field %s imported into %s with value %s.',
								'<code>wpcf-'.$custom_field_name.'</code>',
								'<code>'.$custom_field_name.'</code>',
								'<code>'.$custom_field_value.'</code>' ).'</p>' );

							// Set flag to update Item:
							$item_content_was_changed = true;
						}
					}
				}

				// Try to extract files from content tag [caption ...]:
				if( preg_match_outcode( '#\[caption[^\]]+id="attachment_(\d+)"[^\]]+\].+?\[/caption\]#i', $updated_post_content, $caption_matches ) )
				{	// If [caption ...] tag is detected
					foreach( $caption_matches[1] as $caption_post_ID )
					{
						$linked_post_files[] = $caption_post_ID;
						$file_is_linked = false;
						if( isset( $attachment_IDs[ $caption_post_ID ] ) && isset( $files[ $attachment_IDs[ $caption_post_ID ] ] ) )
						{
							$File = $files[ $attachment_IDs[ $caption_post_ID ] ];
							if( $link_ID = $File->link_to_Object( $LinkOwner, $link_order, 'inline' ) )
							{	// If file has been linked to the post
								$this->log_success( sprintf( 'File %s has been linked to this post from %s.',
									'<code>'.$File->_adfp_full_path.'</code>',
									'<code>[caption id="attachment_'.$caption_post_ID.'"]</code>' ) );
								// Replace this caption tag from content with b2evolution format:
								$updated_post_content = replace_content_outcode( '#\[caption[^\]]+id="attachment_'.$caption_post_ID.'"[^\]]+\].+?\[/caption\]#i', ( $File->is_image() ? '[image:'.$link_ID.']' : '[file:'.$link_ID.']' ), $updated_post_content );
								$file_is_linked = true;
								$link_order++;
							}
						}
						if( ! $file_is_linked )
						{	// If file could not be linked to the post:
							$this->log_warning( sprintf( 'Caption file %s could not be attached to this post because it is not found in the source attachments folder by %s.',
								'#'.$caption_post_ID,
								'<code>[caption id="attachment_'.$caption_post_ID.'"]</code>' ) );
						}
					}
				}

				// Try to extract files from html tag <img />:
				if( $import_img && count( $imported_file_names ) )
				{	// Only if it is requested and at least one attachment has been detected above:
					if( preg_match_outcode( '#<img[^>]+src="([^"]+)"[^>]*>#i', $updated_post_content, $img_matches ) )
					{	// If <img /> tag is detected
						foreach( $img_matches[1] as $img_url )
						{
							$matched_file_ID = NULL;
							$matched_file_place = NULL;
							$file_is_linked = false;
							$img_file_name = basename( $img_url );
							$img_file_rel_path = NULL;

							if( ! empty( $this->info_data['attached_files_folder'] ) &&
							    strpos( $img_url, $this->info_data['attached_files_folder'] ) !== false )
							{	// Get relative path because image URL contains it:
								$img_file_rel_path = preg_replace( '#^.+?'.preg_quote( $this->info_data['attached_files_folder'] ).'#', '', $img_url );
							}

							if( $img_file_rel_path !== NULL &&
							    ! empty( $imported_file_paths[ $img_file_rel_path ] ) )
							{	// We find file by relative path:
								$matched_file_ID = $imported_file_paths[ $img_file_rel_path ];
								$matched_file_place = 'path';
							}
							elseif( isset( $imported_file_names[ $img_file_name ] ) )
							{	// We find file by name:
								$matched_file_ID = $imported_file_names[ $img_file_name ];
								$matched_file_place = 'file';
							}

							if( $matched_file_ID === false )
							{	// Skip a duplicated file by name:
								$this->log_error( sprintf( 'Cannot replace img src="%s" because the file name %s is a duplicate and it was not found in %s.',
										'<code>'.$img_url.'</code>',
										'<code>'.$img_file_name.'</code>',
										( empty( $this->info_data['attached_files_folder'] ) ? ' attachments folder because it is not detected for the imported XML file' : '<code>'.$this->info_data['attached_files_folder'].'</code>' ) ) );
								continue;
							}

							if( isset( $files[ $matched_file_ID ] ) )
							{	// Try to link File to the Item:
								$File = $files[ $matched_file_ID ];
								if( $linked_post_ID = array_search( $File->ID, $attachment_IDs ) )
								{
									$linked_post_files[] = $linked_post_ID;
								}
								if( $link_ID = $File->link_to_Object( $LinkOwner, $link_order, 'inline' ) )
								{	// If file has been linked to the post
									if( $matched_file_place == 'file' )
									{	// Inform the file was matched only by name:
										$additional_file_log = ' '.$this->get_log( sprintf( 'We could not match file name %s but we could match %s.',
											( empty( $img_file_rel_path )
												? ' by relative path '.( empty( $this->info_data['attached_files_folder'] ) ? '' : '<code>'.$this->info_data['attached_files_folder'].'</code>' ).' because it is not found in image URL'
												: '<code>'.$img_file_rel_path.'</code>' ),
											'<code>'.$img_file_name.'</code>' ), 'warning', 'span' );
									}
									else
									{
										$additional_file_log = '';
									}
									$this->log_success( sprintf( 'File %s has been linked to this post as %s from img src="%s"'.( $matched_file_place == 'path' ? ' and matched with <code>'.$img_file_rel_path.'</code>' : '' ).'.',
										'<code>'.$File->_adfp_full_path.'</code>',
										'<code>inline</code>',
										'<code>'.$img_url.'</code>' ).$additional_file_log );
									// Replace this img tag from content with b2evolution format:
									$updated_post_content = replace_content_outcode( '#<img[^>]+src="[^"]+'.preg_quote( $img_file_name ).'"[^>]*>#i', '[image:'.$link_ID.']', $updated_post_content );
									$file_is_linked = true;
									$link_order++;
								}
								else
								{	// If file could not be linked:
									$this->log_error( sprintf( 'File %s could not be linked to this post as %s from img src="%s".',
										'<code>'.$File->_adfp_full_path.'</code>',
										'<code>inline</code>',
										'<code>'.$img_url.'</code>' ) );
								}
							}

							if( ! $file_is_linked )
							{	// If file could not be linked to the post:
								$this->log_warning( sprintf( 'File of img src=%s could not be attached to this post because the name %s does not match any %s or %s.',
									'<code>'.$img_url.'</code>',
									'<code>'.$img_file_name.'</code>',
									'<code>&lt;evo:file&gt;</code>',
									'<code>&lt;item&gt;&lt;wp:post_type&gt;attachment&lt;/wp:post_type&gt;...</code>' ) );
							}
						}
					}
				}

				if( isset( $attached_post_files[ $post['post_id'] ] ) )
				{	// Link all found attached files for the Item which were not linked yer above as cover or inline image tags:
					foreach( $attached_post_files[ $post['post_id'] ] as $attachment_post_ID )
					{
						if( in_array( $attachment_post_ID, $linked_post_files ) )
						{	// Skip already linked File:
							continue;
						}
						$file_is_linked = false;
						if( isset( $attachment_IDs[ $attachment_post_ID ] ) && isset( $files[ $attachment_IDs[ $attachment_post_ID ] ] ) )
						{
							$File = $files[ $attachment_IDs[ $attachment_post_ID ] ];
							if( $File->link_to_Object( $LinkOwner, $link_order, 'aftermore' ) )
							{	// If file has been linked to the post:
								$this->log_success( sprintf( 'File %s has been linked to this post as %s by %s.',
									'<code>'.$File->_adfp_full_path.'</code>',
									'<code>aftermore</code>',
									'<code>&lt;wp:post_id&gt;'.$post['post_id'].'&lt;/wp:post_id&gt;</code>' ) );
								$file_is_linked = true;
								$link_order++;
							}
						}
						if( ! $file_is_linked )
						{	// If file could not be linked to the post:
							$this->log_warning( sprintf( 'File %s could not be attached to this post because it is not found in the source attachments by %s.',
								'#'.$attachment_post_ID,
								'<code>&lt;wp:post_parent&gt;'.$post['post_id'].'&lt;/wp:post_parent&gt;</code>' ) );
						}
					}
				}

				if( $updated_post_content != $post_content )
				{	// Set new content:
					$Item->set( 'content', $updated_post_content );
					$item_content_was_changed = true;
				}

				if( $item_content_was_changed )
				{	// Update Item:
					$Item->dbupdate();
				}

				if( !empty( $post['comments'] ) )
				{ // Set comments
					$comments[ $Item->ID ] = $post['comments'];
				}

				// Call plugin event after Item was imported:
				$Plugins->trigger_event( 'ImporterAfterItemImport', array(
						'type'     => $this->import_code,
						'Importer' => $this,
						'Item'     => $Item,
						'data'     => $post,
					) );

				$this->log_success( 'OK -> '.$Item->get_title(), 'span' );
				$this->log( '</p>' );
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

			$this->log( '<b>'.sprintf( '%d records', $posts_count ).'</b></p>' );

			if( $convert_links && ! empty( $posts ) )
			{	// Convert wp links like "?page_id=" to b2evo shortlinks:
				$converted_links_num = 0;
				$updated_posts_num = 0;
				$this->log( '<p><b>'.'Converting wp links to b2evo shortlinks...'.' </b>' );

				$ItemCache = & get_ItemCache();
				foreach( $posts as $wp_post_ID => $evo_item_ID )
				{
					if( ! ( $Item = & $ItemCache->get_by_ID( $evo_item_ID, false, false ) ) )
					{	// Skip unknown Item:
						$this->log_error( 'Cannot find Item #'.$evo_item_ID.' in DB for converting wp links!' );
						continue;
					}

					$log_prefix = 'Converted links for the Item #'.$Item->ID.'('.$Item->get_title().'): ';

					$item_content = $Item->get( 'content' );
					if( preg_match_outcode( '#<a.+?href="\?page_id=(\d+)".*?>(.+?)</a>#i', $item_content, $link_matches ) )
					{	// If a link like <a href="?page_id=123"> is detected in item content:
						$converted_links = array();
						foreach( $link_matches[1] as $l => $link_wp_post_ID )
						{
							if( ! isset( $posts[ $link_wp_post_ID ] ) )
							{	// Skip unknown wordpress post:
								$this->log_warning( $log_prefix.'No wp post #'.$link_wp_post_ID.' for link <code>'.format_to_output( $link_matches[0][ $l ], 'htmlspecialchars' ).'</code> in content of the Item' );
								continue;
							}

							if( ! ( $link_Item = & $ItemCache->get_by_ID( $posts[ $link_wp_post_ID ], false, false ) ) )
							{	// Skip not found Item in DB:
								$this->log_warning( $log_prefix.'Cannot find evo Item #'.$posts[ $link_wp_post_ID ].' in DB for wp post #'.$link_wp_post_ID.'!' );
								continue;
							}

							$evo_short_link = '[['.$link_Item->get( 'urltitle' ).' '.$link_matches[2][ $l ].']]';

							// Replace a link tag with evo short link:
							$item_content = replace_content_outcode( $link_matches[0][ $l ], $evo_short_link, $item_content, 'replace_content', 'str' );

							// For log:
							$converted_links[] = '<code>'.format_to_output( $link_matches[0][ $l ], 'htmlspecialchars' ).'</code> => <code>'.$evo_short_link.'</code>';
						}
						$item_converted_links_num = count( $converted_links );
						if( $item_converted_links_num > 0 )
						{	// If at least one links is converted:
							$Item->set( 'content', $item_content );
							if( $Item->dbupdate() )
							{	// Success updating:
								$this->log_success( $log_prefix.implode( ', ', $converted_links ) );
								$updated_posts_num++;
								$converted_links_num += $item_converted_links_num;
							}
							else
							{	// Failed updating:
								$this->log_error( $log_prefix.'Cannot update content for converted links: '.implode( ', ', $converted_links ) );
							}
						}
					}
				}

				$this->log( '<b>'.sprintf( '%d converted links in %d posts', $converted_links_num, $updated_posts_num ).'</b></p>' );
			}
		}


		/* Import comments */
		if( !empty( $comments ) )
		{
			$this->log( '<p><b>'.'Importing the comments...'.' </b>' );

			$comments_count = 0;
			$comments_IDs = array();
			foreach( $comments as $post_ID => $comments )
			{
				$post_comments_count = 0;
				$this->log( '<p>'.sprintf( 'Importing comments of the post #%d', intval( $post_ID ) ).'... ' );
				if( empty( $comments ) )
				{	// Skip if no comments
					$this->log_warning( 'Skip because the post has no comments.', 'span', false );
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
					$Comment->set( 'author_url', $comment['comment_author_url'] );
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
					$Comment->set( 'author_url_nofollow', $comment['comment_author_url_nofollow'] );
					$Comment->set( 'author_url_ugc', $comment['comment_author_url_ugc'] );
					$Comment->set( 'author_url_sponsored', $comment['comment_author_url_sponsored'] );
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
					$post_comments_count++;

					$this->log( '.' );
				}

				$this->log_success( ' '.sprintf( '%d comments', $post_comments_count ).'.', 'span' );

				$this->log( '</p>' );
			}

			$this->log( '<b>'.sprintf( '%d records', $comments_count ).'</b></p>' );
		}

		$this->log_success( 'Import complete.' );

		$DB->commit();
	}


	/**
	 * Create object File from source path
	 *
	 * @param string Source file path
	 * @param array Params
	 * @return object|boolean File or FALSE
	 */
	function & create_File( $file_source_path, $params )
	{
		global $DB;

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
			$this->log_warning( sprintf( 'Unable to copy file %s, because it does not exist.', '<code>'.$file_source_path.'</code>' ) );
			// Skip it:
			return $File;
		}

		// Try to find already existing File by hash in DB:
		$FileCache = & get_FileCache();
		$SQL = new SQL( 'Find file by hash' );
		$SQL->SELECT( 'file_ID' );
		$SQL->FROM( 'T_files' );
		$SQL->WHERE( 'file_hash = '.$DB->quote( md5_file( $file_source_path, true ) ) );
		$SQL->ORDER_BY( 'file_ID' );
		$SQL->LIMIT( '1' );
		$existing_file_ID = $DB->get_var( $SQL );
		if( ! empty( $existing_file_ID ) &&
				( $File = & $FileCache->get_by_ID( $existing_file_ID, false, false ) ) &&
				$File->exists() )
		{	// Use already exsiting File:
			$this->log_warning( sprintf( 'Don\'t copy/import the file %s, use already existing File #%d in %s instead.',
				'<code>'.$file_source_path.'</code>', $File->ID, '<code>'.$File->get_full_path().'</code>' ) );
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
				$this->log_warning( sprintf( 'Unable to copy folder %s to %s. Please, check the permissions assigned to this folder.', '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ) );
			}
			else
			{	// File
				$this->log_warning( sprintf( 'Unable to copy file %s to %s. Please, check the permissions assigned to this file.', '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ) );
			}
			// Skip it:
			return $File;
		}

		// Set additional params for new creating File object:
		$File->set( 'title', $params['file_title'] );
		$File->set( 'alt', $params['file_alt'] );
		$File->set( 'desc', $params['file_desc'] );
		$File->dbsave();

		$this->log_success( sprintf( 'File %s has been imported to %s successfully.', '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ) );

		return $File;
	}
}
?>