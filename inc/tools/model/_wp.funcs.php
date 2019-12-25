<?php
/**
 * This file implements the functions to work with WordPress importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get data to start import from wordpress XML/ZIP file or from Item Type XML file
 *
 * @param string Path of XML/ZIP file
 * @param boolean TRUE to allow to use already extracted ZIP archive
 * @return array Data array:
 *                 'error' - FALSE on success OR error message,
 *                 'XML_file_path' - Path to XML file,
 *                 'attached_files_path' - Path to attachments folder,
 *                 'ZIP_folder_path' - Path of the extracted ZIP files.
 */
function wpxml_get_import_data( & $XML_file_path, $allow_use_extracted_folder = false )
{
	// Start to collect all printed errors from buffer:
	ob_start();

	$XML_file_name = basename( $XML_file_path );
	$ZIP_folder_path = NULL;
	$zip_file_name = NULL;

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

		$zip_file_name = $XML_file_name;

		$ZIP_folder_name = substr( $XML_file_name, 0, -4 );
		$ZIP_folder_path = $media_path.'import/'.$ZIP_folder_name;

		$zip_folder_exists = ( file_exists( $ZIP_folder_path ) && is_dir( $ZIP_folder_path ) );

		if( ! $allow_use_extracted_folder && $zip_folder_exists )
		{	// Don't try to extract into already existing folder:
			echo '<p class="text-danger">'.sprintf( 'The destination folder %s already exists. If you want to unzip %s again, delete %s first.',
					'<code>'.$ZIP_folder_path.'/</code>',
					'<code>'.$XML_file_name.'</code>',
					'<code>'.$ZIP_folder_path.'/</code>'
				).'</p>';
		}
		elseif( ( $allow_use_extracted_folder && $zip_folder_exists ) ||
		        unpack_archive( $XML_file_path, $ZIP_folder_path, true, $XML_file_name ) )
		{	// If we can use already extracted ZIP archive or it is unpacked successfully now:

			// Move all files and sub-folders of single top level folder to top/root level of the folder:
			move_single_dir_to_top_level( $ZIP_folder_path );

			// Reset path and set only if XML file is found in ZIP archive:
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
		echo '<p class="text-danger">'.sprintf( '%s has an unrecognized extension.', '<code>'.$xml_file['name'].'</code>' ).'</p>';
	}

	if( $XML_file_path )
	{	// Get a path with attached files for the XML file:
		$attached_files_path = get_import_attachments_folder( $XML_file_path, $use_first_folder_for_attachments );
		$attached_files_folder = substr( $attached_files_path, strlen( dirname( $XML_file_path ) ) );
	}
	else
	{	// Wrong source file:
		$attached_files_path = false;
		$attached_files_folder = false;
	}

	if( isset( $xml_exists_in_zip ) && $xml_exists_in_zip === false && file_exists( $ZIP_folder_path ) )
	{	// No XML is detected in ZIP package:
		echo '<p class="text-danger">'.'Correct XML file is not detected in your ZIP package.'.'</p>';
		// Delete temporary folder that contains the files from extracted ZIP package:
		rmdir_r( $ZIP_folder_path );
	}

	// Get all printed errors:
	$errors = ob_get_clean();

	global $wpxml_file_path_renamed, $wpxml_file_path_orig;
	if( isset( $wpxml_file_path_renamed ) )
	{	// Display these messages only after $errors = ob_get_clean(); because they are not errors of XML parsing:
		if( $wpxml_file_path_renamed === true )
		{	// Success renaming:
			echo '<p class="text-warning"><span class="label label-warning">WARNING</span> '.sprintf( 'We renamed %s to %s. The PHP XML parser doesn\'t support spaces in filenames.',
				'<code>'.$wpxml_file_path_orig.'</code>',
				'<code>'.$XML_file_path.'</code>' ).'</p>';
		}
	}

	return array(
			'errors'               => empty( $errors ) ? false : $errors,
			'XML_file_path'        => $XML_file_path,
			'attached_files_path'  => $attached_files_path,
			'attached_files_folder'=> $attached_files_folder,
			'ZIP_folder_path'      => $ZIP_folder_path,
		);
}


/**
 * Get XML content from file
 *
 * @param string File path
 * @return object|FALSE SimpleXMLElement
 */
function wpxml_get_xml_from_file( & $file_path )
{
	global $wpxml_file_path_renamed, $wpxml_file_path_orig;

	// Register filter to avoid wrong chars in XML content:
	stream_filter_register( 'xmlutf8', 'ValidUTF8XMLFilter' );

	if( strpos( $file_path, ' ' ) !== false )
	{	// Try to rename file:
		$wpxml_file_path_orig = $file_path;
		$new_file_path = str_replace( ' ', '-', $file_path );

		if( ! file_exists( $new_file_path ) )
		{	// If file was not renamed yet:
			if( $wpxml_file_path_renamed = @rename( $file_path, $new_file_path ) )
			{
				$file_path = $new_file_path;
			}
			else
			{	// Failed renaming:
				echo '<p class="text-warning"><span class="label label-danger">ERROR</span> '.sprintf( 'Cannot rename %s to %s. The PHP XML parser doesn’t support spaces in filenames.',
					'<code>'.$file_path.'</code>',
					'<code>'.$new_file_path.'</code>' ).'</p>';
			}
		}
		else
		{
			$file_path = $new_file_path;
		}
	}

	if( ! isset( $wpxml_file_path_renamed ) || $wpxml_file_path_renamed === true )
	{	// Load XML content from file with xmlutf8 filter:
		$xml = simplexml_load_file( 'php://filter/read=xmlutf8/resource='.$file_path );
	}
	else
	{
		$xml = false;
	}

	return $xml;
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
	$memory = array();

	// Start to get amount of memory for parsing:
	$memory_usage = memory_get_usage();

	// Load XML content from file with xmlutf8 filter:
	$xml = wpxml_get_xml_from_file( $file );

	// Store here what memory was used for XML parsing:
	$memory['parsing'] = memory_get_usage() - $memory_usage;

	// Get WXR version
	$wxr_version = $xml->xpath( '/rss/channel/wp:wxr_version' );
	$wxr_version = isset( $wxr_version[0] ) ? (string) trim( $wxr_version[0] ) : '';

	$base_url = $xml->xpath( '/rss/channel/wp:base_site_url' );
	$base_url = isset( $base_url[0] ) ? (string) trim( $base_url[0] ) : '';

	// Check language
	global $evo_charset, $xml_import_convert_to_latin;
	$language = $xml->xpath( '/rss/channel/language' );
	$language = isset( $language[0] ) ? (string) trim( $language[0] ) : '';
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
		$namespaces['evo'] = 'http://b2evolution.net/export/2.0/';
	}
	if( !isset( $namespaces['excerpt'] ) )
	{
		$namespaces['excerpt'] = 'http://wordpress.org/export/1.1/excerpt/';
	}

	// Start to get amount of memory for temporary arrays:
	$memory_usage = memory_get_usage();

	// Get authors:
	$authors_data = $xml->xpath( '/rss/channel/wp:author' );
	if( is_array( $authors_data ) )
	{
		foreach( $authors_data as $author_arr )
		{
			$a = $author_arr->children( $namespaces['wp'] );
			$ae = $author_arr->children( $namespaces['evo'] );
			$login = (string) $a->author_login;
			$author = array(
				'author_id'                   => (int) $a->author_id,
				'author_login'                => $login,
				'author_email'                => (string) $a->author_email,
				'author_display_name'         => wpxml_convert_value( $a->author_display_name ),
				'author_first_name'           => wpxml_convert_value( $a->author_first_name ),
				'author_last_name'            => wpxml_convert_value( $a->author_last_name ),
				'author_pass'                 => (string) $ae->author_pass,
				'author_salt'                 => isset( $ae->author_salt ) ? (string) $ae->author_salt : '',
				'author_pass_driver'          => isset( $ae->author_pass_driver ) ? (string) $ae->author_pass_driver : 'evo$md5',
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
				'author_avatar_file_ID'       => (int) $ae->author_avatar_file_ID,
			);

			foreach( $ae->link as $link )
			{	// Get the links:
				$author['links'][] = array(
					'link_ID'               => (int) $link->link_ID,
					'link_datecreated'      => (string) $link->link_datecreated,
					'link_datemodified'     => (string) $link->link_datemodified,
					'link_creator_user_ID'  => (int) $link->link_creator_user_ID,
					'link_lastedit_user_ID' => (int) $link->link_lastedit_user_ID,
					'link_itm_ID'           => (int) $link->link_itm_ID,
					'link_cmt_ID'           => (int) $link->link_cmt_ID,
					'link_usr_ID'           => (int) $link->link_usr_ID,
					'link_file_ID'          => (int) $link->link_file_ID,
					'link_position'         => (string) $link->link_position,
					'link_order'            => (int) $link->link_order,
				);
			}

			$authors[ $login ] = $author;
		}
	}

	// Get files:
	$files_data = $xml->xpath( $namespaces['evo'] == 'http://b2evolution.net/export/2.0/'
		? '/rss/channel/evo:file' // ver 2.0
		: '/rss/channel/file' ); // ver 1.0
	if( is_array( $files_data ) )
	{
		foreach( $files_data as $file_arr )
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
	}

	// Get categories:
	$categories_data = $xml->xpath( '/rss/channel/wp:category' );
	if( is_array( $categories_data ) )
	{
		foreach( $categories_data as $term_arr )
		{
			$t = $term_arr->children( $namespaces['wp'] );
			$categories[] = array(
				'term_id'              => (int) $t->term_id,
				'category_nicename'    => wpxml_convert_value( $t->category_nicename ),
				'category_parent'      => (string) $t->category_parent,
				'cat_name'             => wpxml_convert_value( $t->cat_name ),
				'cat_description'      => wpxml_convert_value( $t->cat_description ),
				'cat_order'            => wpxml_convert_value( $t->cat_order ),
			);
		}
	}

	// Get tags:
	$tags_data = $xml->xpath( '/rss/channel/wp:tag' );
	if( is_array( $tags_data ) )
	{
		foreach( $tags_data as $term_arr )
		{
			$t = $term_arr->children( $namespaces['wp'] );
			$tags[] = array(
				'term_id'         => (int) $t->term_id,
				'tag_slug'        => (string) $t->tag_slug,
				'tag_name'        => wpxml_convert_value( $t->tag_name ),
				'tag_description' => wpxml_convert_value( $t->tag_description )
			);
		}
	}

	// Get terms:
	$terms_data = $xml->xpath( '/rss/channel/wp:term' );
	if( is_array( $terms_data ) )
	{
		foreach( $terms_data as $term_arr )
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
	}

	// Get posts
	foreach( $xml->channel->item as $item )
	{
		$post = array(
			'post_title' => wpxml_convert_value( $item->title ),
			'post_link'  => ( isset( $item->link ) ? wpxml_convert_value( $item->link ) : '' ),
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
		$post['itemtype']       = (string) $evo->itemtype;
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
		$post['switchable']         = (int) $evo->switchable;
		$post['switchable_params']  = (string) $evo->switchable_params;

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

		if( isset( $evo->custom_field ) )
		{	// Parse values of custom fields of the Item:
			foreach( $evo->custom_field as $custom_field )
			{
				$custom_field_attrs = $custom_field->attributes();
				$post['custom_fields'][ wpxml_convert_value( $custom_field_attrs->name ) ] = array(
						'type'  => wpxml_convert_value( $custom_field_attrs->type ),
						'value' => wpxml_convert_value( $custom_field ),
					);
			}
		}

		foreach( $wp->postmeta as $meta )
		{
			$post['postmeta'][] = array(
				'key'   => (string) $meta->meta_key,
				'value' => isset( $meta->meta_value) ? wpxml_convert_value( $meta->meta_value ) : NULL,
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
				'comment_author_url_nofollow'  => isset( $evo_comment->comment_author_url_nofollow ) ? (int) $evo_comment->comment_author_url_nofollow : (int) $evo_comment->comment_nofollow,
				'comment_author_url_ugc'       => isset( $evo_comment->comment_author_url_ugc ) ? (int) $evo_comment->comment_author_url_ugc : 1,
				'comment_author_url_sponsored' => isset( $evo_comment->comment_author_url_sponsored ) ? (int) $evo_comment->comment_author_url_sponsored : 0,
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
				'link_position'         => (string) $link->link_position,
				'link_order'            => (int) $link->link_order,
			);
		}

		$posts[] = $post;
	}

	// Store here what memory was used for temporary arrays:
	$memory['arrays'] = memory_get_usage() - $memory_usage;

	return array(
		'authors'    => $authors,
		'files'      => $files,
		'posts'      => $posts,
		'categories' => $categories,
		'tags'       => $tags,
		'terms'      => $terms,
		'base_url'   => $base_url,
		'version'    => $wxr_version,
		'memory'     => $memory,
	);
}


/**
 * Check WordPress XML file for correct format
 *
 * @param string File path
 * @param boolean TRUE to halt process of error, FALSE to print out error
 * @return boolean TRUE on success, FALSE or HALT on errors
 */
function wpxml_check_xml_file( & $file, $halt = false )
{
	global $wpxml_file_path_renamed;

	// Enable XML error handling:
	$internal_errors = libxml_use_internal_errors( true );

	// Clear error of previous XML file (e.g. when ZIP archive has several XML files):
	libxml_clear_errors();

	// Load XML content from file with xmlutf8 filter:
	$xml = wpxml_get_xml_from_file( $file );

	if( isset( $wpxml_file_path_renamed ) && $wpxml_file_path_renamed === false )
	{	// Don't display errors messages here, because they arready were displayed inside wpxml_get_xml_from_file():
		return false;
	}

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
				$errors[] = sprintf( 'Line %s', '<code>'.$error->line.'</code>' ).' - '.'"'.format_to_output( $error->message, 'htmlspecialchars' ).'"';
			}
			echo '<p class="text-danger">'.sprintf( 'There was an error when reading XML file %s.', '<code>'.$file.'</code>' ).'<br />'
				.sprintf( 'Error: %s', implode( ',<br />', $errors ) ).'</p>';
			return false;
		}
	}

	$r = false;
	if( $wxr_version = $xml->xpath( '/rss/channel/wp:wxr_version' ) )
	{	// Check WXR version for correct format:
		$wxr_version = (string) trim( $wxr_version[0] );
		$r = preg_match( '/^\d+\.\d+$/', $wxr_version );
	}
	elseif( $app_version = $xml->xpath( '/rss/channel/evo:app_version' ) )
	{	// Check application version for correct format:
		$app_version = (string) trim( $app_version[0] );
		$r = preg_match( '/^[\d\.]+(-[a-z]+)?$/i', $app_version );
	}
	

	if( ! $r )
	{	// If file format is wrong:
		if( $halt )
		{	// Halt on error:
			debug_die( 'This does not appear to be a XML file, missing/invalid WXR version number.' );
		}
		else
		{	// Display error:
			echo '<p class="text-danger">'.'This does not appear to be a XML file, missing/invalid WXR version number.'.'</p>';
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
		$SQL = new SQL( 'WordPress import: Check for unique url name in DB' );
		$SQL->SELECT( $field );
		$SQL->FROM( $table );
		$SQL->WHERE( $field.' = '.$DB->quote( $url_name_correct ) );
		$category = $DB->get_var( $SQL );
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


/**
 * Display a selector for Item Types
 *
 * @param string XML file path
 * @param string|NULL Temporary folder of unpacked ZIP archive
 */
function wpxml_item_types_selector( $XML_file_path, $ZIP_folder_path = NULL )
{
	// Parse WordPress XML file into array
	echo 'Loading & parsing the XML file...'.'<br />';
	evo_flush();
	$xml_data = wpxml_parser( $XML_file_path );
	echo '<ul class="list-default">';
		echo '<li>'.'Memory used by XML parsing (difference between free RAM before loading XML and after)'.': <b>'.bytesreadable( $xml_data['memory']['parsing'] ).'</b></li>';
		echo '<li>'.'Memory used by temporary arrays (difference between free RAM after loading XML and after copying all the various data into temporary arrays)'.': <b>'.bytesreadable( $xml_data['memory']['arrays'] ).'</b></li>';
	echo '</ul>';
	evo_flush();

	$item_type_names = array();
	$item_type_usages = array();
	$no_item_types = 0;
	if( ! empty( $xml_data['posts'] ) )
	{	// Count items number per item type:
		foreach( $xml_data['posts'] as $post )
		{
			if( $post['post_type'] == 'attachment' || $post['post_type'] == 'revision' )
			{	// Skip reserved post type:
				continue;
			}

			if( ! empty( $post['itemtype'] ) )
			{	// Use evo field Item Type name as first priority:
				if( ! isset( $item_type_names[ $post['itemtype'] ] ) )
				{
					$item_type_names[ $post['itemtype'] ] = 1;
				}
				else
				{
					$item_type_names[ $post['itemtype'] ]++;
				}
			}
			elseif( ! empty( $post['post_type'] ) )
			{	// Use wp field Item Type usage as second priority:
				if( ! isset( $item_type_usages[ $post['post_type'] ] ) )
				{
					$item_type_usages[ $post['post_type'] ] = 1;
				}
				else
				{
					$item_type_usages[ $post['post_type'] ]++;
				}
			}
			else
			{	// If Item Type is not defined at all:
				$no_item_types++;
			}
		}
	}

	if( empty( $item_type_names ) && empty( $item_type_usages ) && $no_item_types == 0 )
	{	// No posts:
		echo '<p>No posts found in XML file, you can try to import other data like catefories and etc.</p>';
	}
	else
	{	// Display Item Types selectors:
		$ItemTypeCache = & get_ItemTypeCache();
		$ItemTypeCache->clear();
		$SQL = $ItemTypeCache->get_SQL_object();
		$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID' );
		$SQL->WHERE( 'itc_coll_ID = '.get_param( 'wp_blog_ID' ) );
		$ItemTypeCache->load_by_sql( $SQL );

		echo '<b>'.TB_('Select item types:').'</b>';
		echo '<ul class="list-default controls">';
		// Selector for Item Types by name:
		wpxml_display_item_type_selector( $item_type_names, 'name' );
		// Selector for Item Types by usage:
		wpxml_display_item_type_selector( $item_type_usages, 'usage' );
		if( $no_item_types > 0 )
		{	// Selector for without provided Item Types:
			wpxml_display_item_type_selector( array( $no_item_types ), 'none' );
		}
		echo '</ul>';
	}
}


/**
 * Display item type selector
 *
 * @param array
 */
function wpxml_display_item_type_selector( $item_types, $item_type_field )
{
	$ItemTypeCache = & get_ItemTypeCache();

	foreach( $item_types as $item_type => $items_num )
	{
		echo '<li>';
		switch( $item_type_field )
		{
			case 'name':
				printf( '%d items with %s -> import as', $items_num, '<code>&lt;evo:itemtype&gt;</code> = <code>'.$item_type.'</code>' );
				$form_field_name = 'item_type_names['.$item_type.']';
				break;
			case 'usage':
				printf( '%d items with %s -> import as', $items_num, '<code>&lt;wp:post_type&gt;</code> = <code>'.$item_type.'</code>' );
				$form_field_name = 'item_type_usages['.$item_type.']';
				break;
			case 'none':
				printf( '%d items without provided item type -> import as', $items_num );
				$form_field_name = 'item_type_none';
				break;
		}
		echo ' <select name="'.$form_field_name.'" class="form-control" style="margin:2px">'
					.'<option value="0">'.format_to_output( TB_('Do not import') ).'</option>';
		$is_auto_selected = false;
		$is_first_selected = false;
		foreach( $ItemTypeCache->cache as $ItemType )
		{
			if( $item_type_field != 'none' &&
			    ! $is_first_selected &&
			    $ItemType->get( $item_type_field ) == $item_type )
			{
				$is_auto_selected = true;
				$is_first_selected = true;
			}
			else
			{
				$is_auto_selected = false;
			}
			echo '<option value="'.$ItemType->ID.'"'.( $is_auto_selected ? ' selected="selected"' : '' ).'>'.format_to_output( $ItemType->get( 'name' ) ).'</option>';
		}
		echo '</select>'
			.'</li>';
	}
}
?>