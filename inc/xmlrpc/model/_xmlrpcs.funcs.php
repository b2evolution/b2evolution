<?php
/**
 * @see http://phpxmlrpc.sourceforge.net/
 * @see http://xmlrpc.usefulinc.com/doc/
 * @copyright Edd Dumbill <edd@usefulinc.com> (C) 1999-2002
 *
 * @package evocore
 * @subpackage xmlrpc
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( CANUSEXMLRPC !== TRUE )
{
	return;
}

/**
 * Include XML-RPC for PHP SERVER library
 */
load_funcs('_ext/xmlrpc/_xmlrpcs.inc.php');


// --------------------------------------- SUPPORT FUNCTIONS ----------------------------------------


/**
 * Used for logging, only if {@link $debug_xmlrpc_logging} is true
 *
 * @return boolean Have we logged?
 */
function logIO( $msg, $newline = false )
{
	global $debug_xmlrpc_logging, $basepath, $xmlsrv_subdir;

	if( ! $debug_xmlrpc_logging )
	{
		return false;
	}

	if( $newline )
	{
		$msg = "\n\n".date('Y-m-d H:i:s')."\n".$msg;
	}

	$ok = save_to_file( $msg."\n", $basepath.$xmlsrv_subdir.'xmlrpc.log', 'a+' );

	return (bool) $ok;
}


/**
 * Returns a string replaced by stars, for passwords.
 *
 * @param string the source string
 * @return string same length, but only stars
 */
function starify( $string )
{
	return str_repeat( '*', strlen( $string ) );
}


/**
 * metaWeblog.newMediaObject image upload
 * wp.uploadFile
 *
 * Supplied image is encoded into the struct as bits
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metaweblognewmediaobject
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.uploadFile
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 * 							- name : filename
 * 							- type : mimetype
 * 							- bits : base64 encoded file
 * @return xmlrpcresp XML-RPC Response
 */
function _wp_mw_newmediaobject($m)
{
	global $Settings, $Plugins, $force_upload_forbiddenext;

	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION:
	if( ! $current_User->check_perm( 'files', 'add', false, $Blog->ID ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	if( ! $Settings->get('upload_enabled') )
	{
		return xmlrpcs_resperror( 2, 'Object upload not allowed' );
	}

	$xcontent = $m->getParam(3);
	// Get the main data - and decode it properly for the image - sorry, binary object
	logIO( 'Decoding content...' );
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	$data = $contentstruct['bits'];

	$file_mimetype = isset($contentstruct['type']) ? $contentstruct['type'] : '(none)';
	logIO( 'Received MIME type: '.$file_mimetype );

	$overwrite = false;
	if( isset($contentstruct['overwrite']) )
	{
		$overwrite = (bool) $contentstruct['overwrite'];
	}
	logIO( 'Overwrite if exists: '.($overwrite ? 'yes' : 'no') );

	load_funcs('files/model/_file.funcs.php');

	$filesize = evo_bytes( $data );
	if( ( $maxfilesize = $Settings->get('upload_maxkb') * 1024 ) && $filesize > $maxfilesize )
	{
		return xmlrpcs_resperror( 4, sprintf( T_('The file is too large: %s but the maximum allowed is %s.'),
					bytesreadable($filesize, false), bytesreadable($maxfilesize, false) ) );
	}
	logIO( 'File size is OK: '.bytesreadable($filesize, false) );

	$FileRootCache = & get_FileRootCache();
	$fm_FileRoot = & $FileRootCache->get_by_type_and_ID( 'collection', $Blog->ID, true );
	if( ! $fm_FileRoot )
	{	// fileRoot not found:
		return xmlrpcs_resperror( 14, 'File root not found' );
	}

	$rf_filepath = $contentstruct['name'];
	logIO( 'Received filepath: '.$rf_filepath );

	// Split into path + name:
	$filepath_parts = explode( '/', $rf_filepath );
	$filename = array_pop( $filepath_parts );
	logIO( 'Original file name: '.$filename );

	// Validate and sanitize filename
	if( $error_filename = process_filename( $filename, true ) )
	{
		return xmlrpcs_resperror( 5, $error_filename );
	}
	logIO( 'Sanitized file name: '.$filename );

	// Check valid path parts:
	$rds_subpath = '';
	foreach( $filepath_parts as $filepath_part )
	{
		if( empty($filepath_part) || $filepath_part == '.' )
		{	// self ref not useful
			continue;
		}

		if( $error = validate_dirname($filepath_part) )
		{	// invalid relative path:
			logIO( $error );
			syslog_insert( sprintf( 'Invalid name is detected for folder %s', '<b>'.$filepath_part.'</b>' ), 'warning', 'file' );
			return xmlrpcs_resperror( 6, $error );
		}

		$rds_subpath .= $filepath_part.'/';
	}
	logIO( 'Subpath: '.$rds_subpath );

	// Create temporary file and insert contents into it.
	$tmpfile_name = tempnam(sys_get_temp_dir(), 'fmupload');
	if( $tmpfile_name )
	{
		if( save_to_file( $data, $tmpfile_name, 'wb' ) )
		{
			$image_info = @getimagesize($tmpfile_name);
		}
		else
		{
			return xmlrpcs_resperror( 13, 'Error while writing to temp file.' );
		}
	}

	if( !empty($image_info) )
	{	// This is an image file, let's check mimetype and correct extension
		if( $image_info['mime'] != $file_mimetype )
		{	// Invalid file type
			$FiletypeCache = & get_FiletypeCache();
			// Get correct file type based on mime type
			$correct_Filetype = $FiletypeCache->get_by_mimetype( $image_info['mime'], false, false );

			$file_mimetype = $image_info['mime'];

			// Check if file type is known by us, and if it is allowed for upload.
			// If we don't know this file type or if it isn't allowed we don't change the extension! The current extension is allowed for sure.
			if( $correct_Filetype && $correct_Filetype->is_allowed() )
			{	// A FileType with the given mime type exists in database and it is an allowed file type for current User
				// The "correct" extension is a plausible one, proceed...
				$correct_extension = array_shift($correct_Filetype->get_extensions());
				$path_info = pathinfo($filename);
				$current_extension = $path_info['extension'];

				// change file extension to the correct extension, but only if the correct extension is not restricted, this is an extra security check!
				if( strtolower($current_extension) != strtolower($correct_extension) && ( !in_array( $correct_extension, $force_upload_forbiddenext ) ) )
				{	// change the file extension to the correct extension
					$old_filename = $filename;
					$filename = $path_info['filename'].'.'.$correct_extension;
				}
			}
		}
	}

	// Get File object for requested target location:
	$FileCache = & get_FileCache();
	$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash($rds_subpath).$filename, true );

	if( $newFile->exists() )
	{
		if( $overwrite && $newFile->unlink() )
		{	// OK, file deleted
			// Delete thumb caches from old location:
			logIO( 'Old file deleted' );
			$newFile->rm_cache();
		}
		else
		{
			return xmlrpcs_resperror( 8, sprintf( T_('The file &laquo;%s&raquo; already exists.'), $filename ) );
		}
	}

	// Trigger plugin event
	if( $Plugins->trigger_event_first_false( 'AfterFileUpload', array(
				'File' => & $newFile,
				'name' => & $filename,
				'type' => & $file_mimetype,
				'tmp_name' => & $tmpfile_name,
				'size' => & $filesize,
			) ) )
	{
		// Plugin returned 'false'.
		// Abort upload for this file:
		@unlink($tmpfile_name);
		return xmlrpcs_resperror( 16, 'File upload aborted by a plugin.' );
	}

	if( ! mkdir_r( $newFile->get_dir() ) )
	{	// Dir didn't already exist and could not be created
		return xmlrpcs_resperror( 9, 'Error creating sub directories: '.$newFile->get_rdfs_rel_path() );
	}

	if( ! @rename( $tmpfile_name, $newFile->get_full_path() ) )
	{
		return xmlrpcs_resperror( 13, 'Error while writing to file.' );
	}

	// chmod the file
	$newFile->chmod();

	// Initializes file properties (type, size, perms...)
	$newFile->load_properties();

	// Load meta data AND MAKE SURE IT IS CREATED IN DB:
	$newFile->meta == 'unknown';
	$newFile->load_meta( true );

	// Resize and rotate
	logIO( 'Running file post-processing (resize and rotate)...' );
	prepare_uploaded_files( array($newFile) );
	logIO( 'Done' );

	$url = $newFile->get_url();
	logIO( 'URL of new file: '.$url );

	$struct = new xmlrpcval(array(
			'file' => new xmlrpcval($filename, 'string'),
			'url' => new xmlrpcval($url, 'string'),
			'type' => new xmlrpcval($file_mimetype, 'string'),
		), 'struct');

	logIO( 'OK.' );
	return new xmlrpcresp($struct);
}


/**
 * Used in these methods
 * 		- metaweblog.getPost
 * 		- metaWeblog.getRecentPosts
 * 		- wp.getPages
 * 		- wp.getPage
 *
 * Note: a valid Item object must be supplied
 *
 */
function _wp_mw_get_item_struct( & $Item )
{
	global $DB;

	logIO('Item title: '.$Item->title);

	if( is_null($Item->extra_cat_IDs) )
	{	// Load extra cats
		$Item->extra_cat_IDs = postcats_get_byID($Item->ID);
	}

	$cat_ids = $Item->extra_cat_IDs;
	array_unshift($cat_ids, $Item->main_cat_ID); // Move to top
	$cat_ids = array_unique( $cat_ids );

	$SQL = 'SELECT cat_name FROM T_categories WHERE cat_ID IN ('.$DB->quote($cat_ids).')';
	$cat_names = array();
	if( $categories = $DB->get_col($SQL) )
	{
		foreach( $categories as $cat )
		{
			$cat_names[] = new xmlrpcval($cat);
		}
		logIO( 'Categories: '.implode(', ', $categories) );
	}

	$tag_names_string = '';
	if( $tags = $Item->get_tags() )
	{
		$tag_names_string = implode(', ', $tags);
		logIO( 'Tags: '.$tag_names_string );
	}

	$SQL = 'SELECT * FROM T_items__item_settings WHERE iset_item_ID = '.$DB->quote($Item->ID);
	$item_settings = array();
	if( $settings = $DB->get_results($SQL) )
	{
		foreach( $settings as $setting )
		{
			$item_settings[] = new xmlrpcval(array(
					'id'    => new xmlrpcval( 0 ),
					'key'   => new xmlrpcval( $setting->iset_name ),
					'value' => new xmlrpcval( $setting->iset_value ),
				),'struct');
		}
	}

	// Split item content on before and after [teaserbreak]
	// No tag balancing, no pages, no rendering, just raw text
	$content_parts = explode( '[teaserbreak]', $Item->content, 2 );
	if( !isset($content_parts[1]) ) $content_parts[1] = '';

	$parent_title = '';
	if( isset($Item->parent_ID) )
	{
		$ItemCache = & get_ItemCache();
		if( $parent_Item = & $ItemCache->get_by_ID( $Item->parent_ID, false, false ) );
		{
			$parent_title = $parent_Item->title;
		}
	}

	$item_status = wp_or_b2evo_item_status($Item->status, 'wp');
	$url = $Item->get_permanent_url();

	$data = array(
			'page_id'					=> new xmlrpcval( $Item->ID, 'int' ),
			'postid'					=> new xmlrpcval( $Item->ID, 'int' ),	// mw
			'userid'					=> new xmlrpcval( $Item->creator_user_ID, 'int' ),
			'page_status'				=> new xmlrpcval( $item_status ),
			'post_status'				=> new xmlrpcval( $item_status ),	// mw
			'description'				=> new xmlrpcval( $content_parts[0] ),
			'text_more'					=> new xmlrpcval( $content_parts[1] ),
			'title'						=> new xmlrpcval( $Item->title ),
			'link'						=> new xmlrpcval( $url ),
			'permalink'					=> new xmlrpcval( $url ),	// mw
			'permaLink'					=> new xmlrpcval( $url ),
			'categories'				=> new xmlrpcval( $cat_names, 'array' ),
			'excerpt'					=> new xmlrpcval( $Item->excerpt ),
			'mt_excerpt'				=> new xmlrpcval( $Item->excerpt ),
			'mt_allow_comments'			=> new xmlrpcval( ($Item->can_comment(NULL) ? 1 : 0), 'int' ),
			'mt_text_more'				=> new xmlrpcval( $content_parts[1] ),
			'mt_keywords'				=> new xmlrpcval( $tag_names_string ),
			'wp_slug'					=> new xmlrpcval( $Item->urltitle ),
			'wp_author'					=> new xmlrpcval( $Item->get('t_author') ),
			'wp_page_parent_id'			=> new xmlrpcval( (isset($Item->parent_ID) ? $Item->parent_ID : 0), 'int' ),
			'wp_page_parent_title'		=> new xmlrpcval( $parent_title ),
			'wp_page_order'				=> new xmlrpcval( $Item->order ), // We don't use 'int' here because b2evolution "order" is stored as double while WP uses integer values
			'wp_author_id'				=> new xmlrpcval( $Item->creator_user_ID, 'string' ),
			'wp_author_display_name'	=> new xmlrpcval( $Item->get('t_author') ),
			'wp_post_format'			=> new xmlrpcval( $Item->ityp_ID ),
			'date_created_gmt'			=> new xmlrpcval( datetime_to_iso8601($Item->issue_date, true), 'dateTime.iso8601' ),
			'dateCreated'				=> new xmlrpcval( datetime_to_iso8601($Item->issue_date),'dateTime.iso8601' ),
			'custom_fields'				=> new xmlrpcval( $item_settings, 'array' ),
			'wp_page_template'			=> new xmlrpcval('default'), // n/a
			'mt_allow_pings'			=> new xmlrpcval( 0, 'int' ), // n/a
			'wp_password'				=> new xmlrpcval(''), // n/a
		);

	return $data;
}


function _wp_or_blogger_getusersblogs( $type, $m )
{
	global $xmlsrv_url;

	if ( $type == 'wp')
	{
		$username_index = 0;
		$password_index = 1;
	}
	else
	{
		$username_index = 1;
		$password_index = 2;
	}
	// CHECK LOGIN:
	if( ! $current_User = & xmlrpcs_login( $m, $username_index, $password_index ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// LOAD BLOGS the user is a member of:
	$BlogCache = & get_BlogCache();
	$blog_array = $BlogCache->load_user_blogs( 'blog_ismember', 'view', $current_User->ID );

	$resp_array = array();
	foreach( $blog_array as $l_blog_ID )
	{	// Loop through all blogs that match the requested permission:

		/**
		 * @var Blog
		 */
		$l_Blog = & $BlogCache->get_by_ID( $l_blog_ID );

		logIO('Current user IS a member of this blog: '.$l_blog_ID);
		$item = array(
					'blogid' => new xmlrpcval( $l_blog_ID ),
					'blogName' => new xmlrpcval( $l_Blog->get('shortname') ),
					'url' => new xmlrpcval( $l_Blog->gen_blogurl() ),
					'isAdmin' => new xmlrpcval( $current_User->check_perm( 'blog_admin', 'edit', false, $l_Blog->ID ), 'boolean') );
		if ( $type == 'wp')
		{
			$item['xmlrpc'] = new xmlrpcval ( $xmlsrv_url.'xmlrpc.php' );
		}

		$resp_array[] = new xmlrpcval( $item, 'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $resp_array, 'array' ) );
}


/**
 * Decode the dateCreated
 *
 * @param struct
 * @return string MYSQL date
 */
function _mw_decode_date( $contentstruct )
{
	global $Settings;

	$postdate = NULL;

	if( !empty($contentstruct['date_created_gmt']) )
	{
		$postdate = iso8601_to_datetime($contentstruct['date_created_gmt']);

		// Add time difference to GMT date
		$postdate = date( 'Y-m-d H:i:s', (mysql2timestamp($postdate, true) + $Settings->get('time_difference')) );

		logIO( 'Using contentstruct date_created_gmt: '.$postdate );
	}

	if( empty($postdate) && !empty($contentstruct['dateCreated']) )
	{
		$postdate = $contentstruct['dateCreated'];
		if( strpos($postdate, 'T') > 0 )
		{	// Date is in ISO 8601 format
			$postdate = iso8601_to_datetime($postdate);
		}

		logIO( 'Using contentstruct dateCreated: '.$postdate );
	}

	return $postdate;
}


/**
 * Get IDs for requested categories
 *
 * @param array struct
 * @param integer blog ID
 * @param boolean Return empty array (instead of error), if no cats given in struct?
 * @return array|xmlrpcresp A list of category IDs or xmlrpcresp in case of error.
 */
function _mw_get_cat_IDs( $contentstruct, $Blog, $empty_struct_is_ok = false )
{
	global $DB;

	$categories = array();
	if( isset($contentstruct['categories']) )
	{
		foreach( $contentstruct['categories'] as $l_catname )
		{
			$categories[] = trim(strip_tags($l_catname));
		}
	}
	logIO( 'Categories: '.implode( ', ', $categories ) );

	if( $empty_struct_is_ok && empty($categories) )
	{
		return $categories;
	}

	$cat_IDs = array();
	if( ! empty($categories) )
	{
		// for cross-blog-entries, the cat_blog_ID WHERE clause should be removed (but cats are given by name!)
		$SQL = 'SELECT cat_ID
				FROM T_categories
				WHERE cat_blog_ID = '.$DB->quote($Blog->ID).'
				AND cat_name IN (';

		foreach( $categories as $l_cat )
		{
			$SQL .= '"'.$DB->escape($l_cat).'", ';
		}
		if( ! empty($categories) )
		{
			$SQL = substr($SQL, 0, -2); // remove ', '
		}
		$SQL .= ')';

		logIO('Loading categories by name');
		if( ! $cat_IDs = $DB->get_col($SQL) )
		{	// DB error
			logIO('Couldn\'t find requested categories');
		}
	}

	if( empty($cat_IDs) )
	{
		// No category given/valid - use default for this blog:
		logIO('Using default category');

		if( ! $default_cat = $Blog->get_default_cat_ID() )
		{
			logIO( 'There are no categories in this blog yet');
			return xmlrpcs_resperror( 5, 'There are no categories in this blog yet' ); // user error 5
		}
		$cat_IDs = array($default_cat);
	}
	return $cat_IDs;
}


function add_to_categories_data( $Chapter )
{
	global $categories_data, $categoryIdName;

	$categories_data[] = new xmlrpcval( array(
			$categoryIdName => new xmlrpcval( $Chapter->ID ),
			'categoryName' => new xmlrpcval( $Chapter->name )
		), 'struct' );
}


/**
 * Helper for {@link b2_getcategories()} and {@link mt_getPostCategories()}, because they differ
 * only in the "categoryId" case ("categoryId" (b2) vs "categoryID" (MT))
 *
 * @param string Type, either "b2" or "mt"
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog to query
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function _b2_or_mt_get_categories( $type, $m )
{
	global $DB;

	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	global $categories_data, $categoryIdName;
	$categories_data = array();
	$categoryIdName = ( $type == 'b2' ? 'categoryID' : 'categoryId' );
	$aggregate_coll_IDs = $Blog->get_aggregate_coll_IDs();
	$callbacks = array( 'line' => 'add_to_categories_data' );

	$ChapterCache = & get_ChapterCache();
	$ChapterCache->recurse( $callbacks, $aggregate_coll_IDs, NULL, 0, 0, array( 'sorted' => true ) );

	logIO( 'Categories: '.count($ChapterCache->cache) );
	logIO( 'OK.' );

	$result = new xmlrpcresp( new xmlrpcval($categories_data, 'array') );
	unset( $categories_data );

	return $result;
}


/**
 * metaWeblog.getCategories
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metawebloggetcategories
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 * @param array of params to narrow category selection
 */
function _wp_mw_getcategories( $m, $params = array() )
{
	global $DB;

	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Not found the selected blog, return (last) error:
		return xmlrpcs_resperror();
	}

	$SQL = new SQL();
	$SQL->SELECT( 'cat_ID, cat_name, cat_order' );
	$SQL->FROM( 'T_categories' );
	$SQL->WHERE( $Blog->get_sql_where_aggregate_coll_IDs('cat_blog_ID') );

	if( !empty($params['search']) )
	{	// Category name starts with 'search'
		$SQL->WHERE_and( 'cat_name LIKE "'.$DB->like_escape( $params['search'] ).'%"' );
	}
	// TODO: asimo>fp How should we order categories from multiple blogs?
	$SQL->ORDER_BY( 'cat_name' );

	$rows = $DB->get_results( $SQL->get() );
	if( $DB->error )
	{	// DB error
		return xmlrpcs_resperror( 99, 'DB error: '.$DB->last_error ); // user error 9
	}
	$total_rows = count($rows);

	logIO( 'Categories: '.$total_rows );

	$ChapterCache = & get_ChapterCache();
	$data = array();
	for( $i=0; $i<$total_rows; $i++ )
	{
		if( !empty($params['limit']) && $i >= $params['limit'] )
		{	// We found enough, exit the loop
			break;
		}

		$Chapter = & $ChapterCache->get_by_ID( $rows[$i]->cat_ID, false, false );
		if( ! $Chapter )
		{
			continue;
		}

		if( isset($params['search']) )
		{	// wp.suggestCategories
			$data[] = new xmlrpcval( array(
					'category_id' => new xmlrpcval( intval($Chapter->ID) ),
					'category_name' => new xmlrpcval( $Chapter->name ),
				),'struct');
		}
		else
		{
			$data[] = new xmlrpcval( array(
					'categoryId' => new xmlrpcval( intval($Chapter->ID) ),				// not in RFC (http://www.xmlrpc.com/metaWeblogApi)
					'parentId' => new xmlrpcval( intval($Chapter->parent_ID) ),			// not in RFC
					'description' => new xmlrpcval( $Chapter->name ),
					'categoryDescription' => new xmlrpcval( $Chapter->description ),	// not in RFC
					'categoryName' => new xmlrpcval( $Chapter->name ),					// not in RFC
					'htmlUrl' => new xmlrpcval( $Chapter->get_permanent_url() ),
					'rssUrl' => new xmlrpcval( url_add_param($Chapter->get_permanent_url(), 'tempskin=_rss2') )
				),'struct');
		}
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval($data, 'array') );
}


/**
 * Get current_User for an XML-RPC request - Includes login (password) check.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of login param in XML-RPC Message
 * @param integer idx of pass param in XML-RPC Message
 * @return User or NULL
 */
function & xmlrpcs_login( $m, $login_param, $pass_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$username = $m->getParam( $login_param );
	$username = $username->scalarval();

	$password = $m->getParam( $pass_param );
	$password = $password->scalarval();

	/**
	 * @var UserCache
	 */
	$UserCache = & get_UserCache();
	$current_User = & $UserCache->get_by_login( $username );

	if( empty( $current_User ) || ! $current_User->check_password( $password, false ) )
	{	// User not found or password doesn't match
		$xmlrpcs_errcode = $xmlrpcerruser+1;
		$xmlrpcs_errmsg = 'Wrong username/password combination: '.$username.' / '.starify($password);
		$r = NULL;
		return $r;
	}

	// This may be needed globally for status permissions in ItemList2, etc..
	$GLOBALS['current_User'] = & $current_User;

	// Check here ability to use APIs
	$group = $current_User->get_Group();
	if( ! $group->check_perm('perm_api', 'always') )
	{	// Permission denied
		$xmlrpcs_errcode = $xmlrpcerruser+1;
		$xmlrpcs_errmsg = 'User has no permission to use this API: '.$username.' / '.starify($password);
		$r = NULL;
		return $r;
	}

	logIO( 'Login OK - User: '.$current_User->ID.' - '.$current_User->login );


	return $current_User;
}


/**
 * Get current Blog for an XML-RPC request.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of blog ID param
 * @return Blog or NULL
 */
function & xmlrpcs_get_Blog( $m, $id_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$blog = $m->getParam( $id_param );
	$blog = $blog->scalarval();
	// waltercruz> qtm: http://qtm.blogistan.co.uk/ inserts some spacing before/after blogID.
	$blog = (int) trim($blog);
	/**
	 * @var BlogCache
	 */
	$BlogCache = & get_BlogCache();
	/**
	 * @var Blog
	 */
	$Blog = & $BlogCache->get_by_ID( $blog, false, false );

	if( empty( $Blog ) )
	{	// Blog not found
		$xmlrpcs_errcode = $xmlrpcerruser+2;
		$xmlrpcs_errmsg = 'Requested blog/Collection ('.$blog.') does not exist.';
		$r = NULL;
		return $r;
	}

	logIO( 'Requested Blog: '.$Blog->ID.' - '.$Blog->name );

	return $Blog;
}


/**
 * Get current Item for an XML-RPC request.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of item ID param
 * @return Item or NULL
 */
function & xmlrpcs_get_Item( $m, $id_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$postid = $m->getParam( $id_param );
	$postid = $postid->scalarval();

	/**
	 * @var ItemCache
	 */
	$ItemCache = & get_ItemCache();
	/**
	 * @var Item
	 */
	$edited_Item = & $ItemCache->get_by_ID( $postid, false, false );

	if( empty( $edited_Item ) )
	{	// Item not found
		$xmlrpcs_errcode = $xmlrpcerruser+6;
		$xmlrpcs_errmsg = 'Requested post/Item ('.$postid.') does not exist.';
		$r = NULL;
		return $r;
	}

	logIO( 'Requested Item: '.$edited_Item->ID.' - '.$edited_Item->title );

	return $edited_Item;
}


/**
 * Get current Chapter for an XML-RPC request.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of chapter ID param
 * @return Chapter or NULL
 */
function & xmlrpcs_get_Chapter( $m, $id_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$id = $m->getParam( $id_param );
	$id = $id->scalarval();

	/**
	 * @var ChapterCache
	 */
	$ChapterCache = & get_ChapterCache();
	/**
	 * @var Chapter
	 */
	$edited_Chapter = & $ChapterCache->get_by_ID( $id, false, false );

	if( empty( $edited_Chapter ) )
	{	// Chapter not found
		$xmlrpcs_errcode = $xmlrpcerruser+9;
		$xmlrpcs_errmsg = 'Requested chapter ('.$id.') does not exist.';
		$r = NULL;
		return $r;
	}

	logIO( 'Requested Chapter: '.$edited_Chapter->ID.' - '.strmaxlen($edited_Chapter->name, 30) );

	return $edited_Chapter;
}


/**
 * Get current Comment for an XML-RPC request.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of comment ID param
 * @return Comment or NULL
 */
function & xmlrpcs_get_Comment( $m, $id_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$id = $m->getParam( $id_param );
	$id = $id->scalarval();

	/**
	 * @var CommentCache
	 */
	$CommentCache = & get_CommentCache();
	/**
	 * @var Comment
	 */
	$edited_Comment = & $CommentCache->get_by_ID( $id, false, false );

	if( empty( $edited_Comment ) )
	{	// Comment not found
		$xmlrpcs_errcode = $xmlrpcerruser+9;
		$xmlrpcs_errmsg = 'Requested comment ('.$id.') does not exist.';
		$r = NULL;
		return $r;
	}

	logIO( 'Requested Comment: '.$edited_Comment->ID.' - '.strmaxlen($edited_Comment->content, 30) );

	return $edited_Comment;
}


/**
 * If no errcode or errmsg given, will use the last one that has been set previously.
 *
 * @param integer
 * @param string
 * @return xmlrpcresp
 */
function xmlrpcs_resperror( $errcode = NULL, $errmsg = NULL )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	if( !empty($errcode) )
	{ // Transform into user error code
		$xmlrpcs_errcode = $xmlrpcerruser + $errcode;
	}

	if( !empty($errmsg) )
	{	// Custom message
		$xmlrpcs_errmsg = $errmsg;
	}
	elseif( empty( $xmlrpcs_errmsg ) )
	{	// Use a standard messsage
		switch( $errcode )
		{
			case 3:
				$xmlrpcs_errmsg = 'Permission denied.';
				break;

			case 11:
				$xmlrpcs_errmsg = 'Requested category not found in requested blog.';
				break;

			case 12:
				$xmlrpcs_errmsg = 'No default category found for requested blog.';
				break;

			case 21:
				$xmlrpcs_errmsg = 'Invalid post title.';
				break;

			case 22:
				$xmlrpcs_errmsg = 'Invalid post contents.';
				break;

			case 99:
				$xmlrpcs_errmsg = 'Database error.';
				break;

			default:
				$xmlrpcs_errmsg = 'Unknown error.';
		}
	}

	logIO( 'ERROR: '.$xmlrpcs_errcode.' - '.$xmlrpcs_errmsg );

  return new xmlrpcresp( 0, $xmlrpcs_errcode, $xmlrpcs_errmsg );
}


/**
 * Create a new Comment and return an XML-RPC response
 *
 * @param array of params
 *			- Item (object)
 *			- User (object) Can be NULL for anonymous comments
 *			- password (string)
 *			- username (string)
 *			- comment_parent (int)
 *			- content (string)
 *			- author (string)
 *			- author_url (string)
 *			- author_email (string)
 * @return xmlrpcmsg
 */
function xmlrpcs_new_comment( $params = array(), & $commented_Item )
{
	global $DB, $Plugins, $Messages, $Hit, $localtimenow, $require_name_email, $minimum_comment_interval;

	$params = array_merge( array(
			'password'			=> '',
			'username'			=> '',
			'content'			=> '',
			'comment_parent'	=> 0,
			'author'			=> '',
			'author_url'		=> '',
			'author_email'		=> '',
		), $params);

	$comment = $params['content'] = trim($params['content']);

	if( ! $commented_Item->can_comment(NULL) )
	{
		return xmlrpcs_resperror( 5, T_('You cannot leave comments on this post!') );
	}

	$commented_Item->load_Blog(); // Make sure Blog is loaded (will be needed whether logged in or not)

	if( empty($params['username']) && empty($params['password']) )
	{	// Anonymous comment
		// NO permission to edit!
		$perm_comment_edit = false;
		$User = NULL;

		$author = trim($params['author']);
		$email = utf8_strtolower( trim($params['author_email']) );

		if( $commented_Item->Blog->get_setting('allow_anon_url') )
		{
			$url = trim($params['author_url']);
		}
		else
		{
			$url = NULL;
		}

		// we need some id info from the anonymous user:
		if( $require_name_email )
		{ // We want Name and EMail with comments
			if( empty($author) )
			{
				return xmlrpcs_resperror( 5, T_('Please fill in your name.') );
			}
			if( empty($email) )
			{
				return xmlrpcs_resperror( 5, T_('Please fill in your email.') );
			}
		}

		if( !empty($author) && antispam_check( $author ) )
		{
			return xmlrpcs_resperror( 5, T_('Supplied name is invalid.') );
		}

		if( !empty($email)
			&& ( !is_email($email)|| antispam_check( $email ) ) )
		{
			return xmlrpcs_resperror( 5, T_('Supplied email address is invalid.') );
		}


		if( !stristr($url, '://') && !stristr($url, '@') )
		{ // add 'http://' if no protocol defined for URL; but not if the user seems to be entering an email address alone
			$url = 'http://'.$url;
		}

		if( strlen($url) <= 8 )
		{	// ex: https:// is 8 chars
			$url = '';
		}

		// Note: as part of the validation we require the url to be absolute; otherwise we cannot detect bozos typing in
		// a title for their comment or whatever...
		if( $error = validate_url( $url, 'commenting' ) )
		{
			return xmlrpcs_resperror( 5, T_('Supplied website address is invalid: ').$error );
		}
	}
	else
	{
		$User = & $params['User'];

		$perm_comment_edit = $User->check_perm( 'blog_comment!published', 'edit', false, $commented_Item->Blog->ID );

		$author = $User->ID;
		$url = $User->url;
		$email = $User->email;
	}

	// Following call says "WARNING: this does *NOT* (necessarilly) make the HTML code safe.":
	$comment = check_html_sanity( $comment, $perm_comment_edit ? 'posting' : 'commenting', $User );
	if( $comment === false )
	{	// ERROR! Restore original comment for further editing:
		$comment = $params['content'];
	}

	if( empty($comment) )
	{ // comment should not be empty!
		return xmlrpcs_resperror( 5, T_('Please do not send empty comments.') );
	}

	$now = date2mysql($localtimenow);

	/*
	 * Flood-protection
	 * NOTE: devs can override the flood protection delay in /conf/_overrides_TEST.php
	 * TODO: Put time check into query?
	 * TODO: move that as far !!UP!! as possible! We want to waste minimum resources on Floods
	 * TODO: have several thresholds. For example:
	 * 1 comment max every 30 sec + 5 comments max every 10 minutes + 15 comments max every 24 hours
	 * TODO: factorize with trackback
	 */
	$query = 'SELECT MAX(comment_date)
				FROM T_comments
				WHERE comment_author_IP = '.$DB->quote( $Hit->IP ).'
				OR comment_author_email = '.$DB->quote( $email );
	$ok = 1;
	if( $then = $DB->get_var( $query ) )
	{
		$time_lastcomment = mysql2date("U",$then);
		$time_newcomment = mysql2date("U",$now);
		if( ($time_newcomment - $time_lastcomment) < $minimum_comment_interval )
			$ok = 0;
	}
	if( !$ok )
	{
		return xmlrpcs_resperror( 5, sprintf( T_('You can only post a new comment every %d seconds.'), $minimum_comment_interval ) );
	}
	/* end flood-protection */

	/**
	 * Create comment object. Gets validated, before recording it into DB:
	 */
	$Comment = new Comment();
	$Comment->set( 'type', 'comment' );
	$Comment->set_Item( $commented_Item );
	if( $User )
	{ // User is logged in, we'll use his ID
		$Comment->set_author_User( $User );
	}
	else
	{	// User is not logged in:
		$Comment->set( 'author', $author );
		$Comment->set( 'author_email', $email );
		$Comment->set( 'author_url', $url );
	}
	if( !empty($params['comment_parent']) )
	{
		$Comment->set( 'in_reply_to_cmt_ID', intval($params['comment_parent']) );
	}
	$Comment->set( 'author_IP', $Hit->IP );
	$Comment->set( 'date', $now );
	$Comment->set( 'content', $comment );

	if( $perm_comment_edit )
	{	// User has perm to moderate comments, publish automatically:
		$Comment->set( 'status', 'published' );
	}
	else
	{ // Assign default status for new comments:
		$Comment->set( 'status', $commented_Item->Blog->get_setting('new_feedback_status') );
	}

	$action = 'submit_comment_post_'.$commented_Item->ID;

	// Trigger event: a Plugin could add a $category="error" message here..
	$Plugins->trigger_event('BeforeCommentFormInsert', array(
			'Comment' => & $Comment,
			'original_comment' => $params['content'],
			'is_preview' => false,
			'action' => & $action
		) );

	if( $Messages->has_errors() )
	{
		return xmlrpcs_resperror( 5, $Messages->get_string( 'Cannot create comment, please correct these errors:'."\n", '', "  //  \n", 'xmlrpc' ) );
	}

	$Comment->dbinsert();

	if( $Comment->ID )
	{	// comment has not been deleted
		// Trigger event: a Plugin should cleanup any temporary data here..
		$Plugins->trigger_event( 'AfterCommentFormInsert', array( 'Comment' => & $Comment, 'original_comment' => $params['content'] ) );

		/*
		 * --------------------------
		 * New comment notifications:
		 * --------------------------
		 */
		// TODO: dh> this should only send published feedback probably and should also use "outbound_notifications_mode"
		// fp> yes for general users, but comment moderators need to receive notifications for new unpublished comments
		// asimo> this handle moderators and general users as well and use "outbound_notifications_mode" in case of general users
		// Moderators will get emails about every new comment
		// Subscribed user will only get emails about new published comments
		$executed_by_userid = empty( $User ) ? NULL : $User->ID;
		$Comment->handle_notifications( true, $executed_by_userid );
	}
	else
	{
		return xmlrpcs_resperror( 99, 'Error while inserting comment: '.$DB->last_error );
	}

	return new xmlrpcresp( new xmlrpcval($Comment->ID, 'int') );
}


/**
 * Edit a Comment and return an XML-RPC response
 *
 * @param array of params
 *			- status (string)
 *			- date_created_gmt (string)
 *			- content (string)
 *			- author (string)
 *			- author_url (string)
 *			- author_email (string)
 * @return xmlrpcmsg
 */
function xmlrpcs_edit_comment( $params = array(), & $edited_Comment )
{
	global $DB, $current_User, $Messages;

	$params = array_merge( array(
			'status'		=> '',
			'date'			=> '',
			'content'		=> '',
			'author'		=> '',
			'author_url'	=> '',
			'author_email'	=> '',
		), $params);

	$comment = trim($params['content']);

	$edited_Comment_Item = $edited_Comment->get_Item();
	$edited_Comment_Item->load_Blog();
	$perm_comment_edit = $current_User->check_perm( 'blog_comment!published', 'edit', false, $edited_Comment_Item->Blog->ID );

	// CHECK HTML SANITY:
	// Following call says "WARNING: this does *NOT* (necessarilly) make the HTML code safe.":
	$comment = check_html_sanity( $comment, $perm_comment_edit ? 'posting' : 'commenting', $current_User );
	if( $comment === false )
	{	// ERROR! Restore original comment for further editing:
		$comment = trim($params['content']);
	}

	if( empty($comment) )
	{ // comment should not be empty!
		return xmlrpcs_resperror( 5, T_('Please do not send empty comments.') );
	}

	// UPDATE COMMENT IN DB:
	$edited_Comment->set( 'content', $comment );
	$edited_Comment->set( 'status', $params['status'] );
	if( !empty($date) )
	{
		$edited_Comment->set( 'date', $date );
	}
	if( ! $edited_Comment->get_author_User() )
	{ // If this is not a member comment
		$edited_Comment->set( 'author', $params['author'] );
		$edited_Comment->set( 'author_url', $params['author_url'] );
		$edited_Comment->set( 'author_email', $params['author_email'] );
	}
	$edited_Comment->dbupdate();

	if( $DB->error )
	{ // DB error
		return xmlrpcs_resperror( 99, 'Error while updating comment: '.$DB->last_error );
	}

	// Execute or schedule notifications & pings:
	logIO( 'Handling notifications...' );
	$edited_Comment->handle_notifications( false, $current_User->ID );

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( true, 'boolean' ) );
}


/**
 * Create a new Item and return an XML-RPC response
 *
 * @param array Item properties
 * @param object Blog where we are going to create a new Item
 * @return xmlrpcmsg
 */
function xmlrpcs_new_item( $params, & $Blog = NULL )
{
	global $current_User, $Settings, $Messages, $DB, $posttypes_perms;

	$params = array_merge( array(
			'title'				=> '',
			'content'			=> '',
			'date'				=> '',
			'main_cat_ID'		=> 0,
			'extra_cat_IDs'		=> array(),
			'cat_IDs'			=> array(),	// we may use this to set main and extra cats
			'status'			=> 'published',
			'tags'				=> '',
			'excerpt'			=> '',
			'item_typ_ID'		=> 1,
			'comment_status'	=> 'open',
			'urltitle'			=> '',
			'featured'			=> 0,
			'custom_fields'		=> array(),
			'order'				=> '',
			'parent_ID'			=> '',
		), $params );

	if( empty($Blog) && !empty($params['main_cat_ID']) )
	{	// Get the blog by main category ID

		// Check if category exists and can be used
		$ChapterCache = & get_ChapterCache();
		$main_Chapter = & $ChapterCache->get_by_ID( $params['main_cat_ID'], false, false );
		if( empty($main_Chapter) )
		{	// Cat does not exist:
			return xmlrpcs_resperror( 11 );	// User error 11
		}

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $main_Chapter->blog_ID, false, false );

		logIO( 'Requested Blog: '.$Blog->ID.' - '.$Blog->name );
	}

	if( empty($Blog) )
	{	// Blog does not exist:
		return xmlrpcs_resperror();
	}

	if( empty($params['main_cat_ID']) )
	{
		if( is_array($params['cat_IDs']) && count($params['cat_IDs']) > 0 )
		{	// Let's use first cat for MAIN and others for EXTRA
			$params['main_cat_ID'] = array_shift($params['cat_IDs']);
			$params['extra_cat_IDs'] = $params['cat_IDs'];
		}
		else
		{
			if( ! $main_cat = $Blog->get_default_cat_ID() )
			{	// No default category found for requested blog
				return xmlrpcs_resperror( 12 ); // User error 12
			}
			$params['main_cat_ID'] = $main_cat;
		}
	}
	logIO( 'Main cat ID: '.$params['main_cat_ID'] );
	logIO( 'Extra cat IDs: '.implode( ', ', $params['extra_cat_IDs'] ) );

	if( empty($params['main_cat_ID']) )
	{	// Main category does not exist:
		return xmlrpcs_resperror( 11 );	// User error 11
	}

	// Check if category exists and can be used
	if( ! xmlrpcs_check_cats( $params['main_cat_ID'], $Blog, $params['extra_cat_IDs'] ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}

	/*
	 * CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	 * NOTE: extra_cat_IDs array now includes main_cat_ID too, so we are actually checking ALL categories below
	 */
	if( ! $current_User->check_perm( 'cats_post!'.$params['status'], 'edit', false, $params['extra_cat_IDs'] ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}

	if( !empty($params['item_typ_ID']) )
	{
		if( ! preg_match('~^[0-9]+$~', $params['item_typ_ID']) )
		{	// Only accept numeric values, switch to default value
			$params['item_typ_ID'] = 1;
		}

		foreach( $posttypes_perms as $l_permname => $l_posttypes )
		{	// "Reverse" the $posttypes_perms array:
			foreach( $l_posttypes as $ll_posttype )
			{
				$posttype2perm[$ll_posttype] = $l_permname;
			}
		}

		if( isset( $posttype2perm[ $params['item_typ_ID'] ] ) )
		{	// Check permission for this post type
			if( ! $current_User->check_perm( 'cats_'.$posttype2perm[ $params['item_typ_ID'] ], 'edit', false, $params['extra_cat_IDs'] ) )
			{	// Permission denied
				return xmlrpcs_resperror( 3 );	// User error 3
			}
		}
	}
	logIO( 'Post type: '.$params['item_typ_ID'] );

	logIO( 'Permission granted.' );

	// CHECK HTML SANITY:
	if( ($params['title'] = check_html_sanity( $params['title'], 'xmlrpc_posting' )) === false )
	{
		return xmlrpcs_resperror( 21, $Messages->get_string( 'Invalid post title, please correct these errors:', '' ) );
	}
	if( ($params['content'] = check_html_sanity( $params['content'], 'xmlrpc_posting' )) === false  )
	{
		return xmlrpcs_resperror( 22, $Messages->get_string( 'Invalid post contents, please correct these errors:'."\n", '', "  //  \n", 'xmlrpc' ) );
	}

	if( empty($params['date']) )
	{
		$params['date'] = date( 'Y-m-d H:i:s', (time() + $Settings->get('time_difference')) );
	}

	// INSERT NEW POST INTO DB:
	load_class( 'items/model/_item.class.php', 'Item' );
	$edited_Item = new Item();
	$edited_Item->set( 'title', $params['title'] );
	$edited_Item->set( 'content', $params['content'] );
	$edited_Item->set( 'issue_date', $params['date'] );
	$edited_Item->set( 'main_cat_ID', $params['main_cat_ID'] );
	$edited_Item->set( 'extra_cat_IDs', $params['extra_cat_IDs'] );
	$edited_Item->set( 'status', $params['status'] );
	$edited_Item->set( 'ityp_ID', $params['item_typ_ID'] );
	$edited_Item->set( 'featured', $params['featured'] );
	$edited_Item->set_tags_from_string( $params['tags'] );
	$edited_Item->set( 'locale', $current_User->locale );
	$edited_Item->set_creator_User( $current_User );

	if( $params['excerpt'] != '' ) $edited_Item->set( 'excerpt', $params['excerpt'] );
	if( $params['urltitle'] != '' ) $edited_Item->set( 'urltitle', $params['urltitle'] );
	if( $params['parent_ID'] != '' ) $edited_Item->set( 'parent_ID', $params['parent_ID'] );
	if( !empty($params['order']) ) $edited_Item->set( 'order', $params['order'] ); // Do not set if order is 0

	if( $edited_Item->allow_comment_statuses() )
	{ // Comment status
		$edited_Item->set( 'comment_status', $params['comment_status'] );
	}

	$edited_Item->dbinsert();
	if( empty( $edited_Item->ID ) )
	{
		return xmlrpcs_resperror( 99, 'Error while inserting item: '.$DB->last_error );
	}
	logIO( 'Posted with ID: '.$edited_Item->ID );

	if( !empty($params['custom_fields']) && is_array($params['custom_fields']) && count($params['custom_fields']) > 0 )
	{	// TODO sam2kb> Add custom fields
		foreach( $params['custom_fields'] as $field )
		{	// id, key, value
			logIO( 'Custom field: '.var_export($field, true) );
		}
	}

	// Execute or schedule notifications & pings:
	logIO( 'Handling notifications...' );
	$edited_Item->handle_post_processing( true );

 	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval($edited_Item->ID));
}


/**
 * Edit an Item and return an XML-RPC response
 *
 * @param Item
 * @param array Item properties
 * @param object Blog where we are going to create a new Item
 * @return xmlrpcmsg
 */
function xmlrpcs_edit_item( & $edited_Item, $params )
{
	global $current_User, $Messages, $DB, $posttypes_perms;

	$params = array_merge( array(
			'title'				=> NULL,
			'content'			=> NULL,
			'date'				=> '',
			'main_cat_ID'		=> NULL,
			'extra_cat_IDs'		=> NULL,
			'cat_IDs'			=> array(),	// we may use this to set main and extra cats
			'status'			=> '',
			'tags'				=> NULL,
			'excerpt'			=> NULL,
			'item_typ_ID'		=> NULL,
			'comment_status'	=> '',
			'urltitle'			=> NULL,
			'featured'			=> NULL,
			'custom_fields'		=> NULL,
			'order'				=> NULL,
			'parent_ID'			=> NULL,
			'author_ID'			=> NULL,
			'locale'			=> '',
		), $params );

	$Blog = & $edited_Item->get_Blog();
	logIO( 'Requested Blog: '.$Blog->ID.' - '.$Blog->name );

	if( empty($Blog) )
	{	// Blog does not exist:
		return xmlrpcs_resperror();
	}

	if( is_array($params['cat_IDs']) && count($params['cat_IDs']) > 0 )
	{	// Let's use first cat for MAIN and others for EXTRA
		$params['main_cat_ID'] = array_shift($params['cat_IDs']);
		$params['extra_cat_IDs'] = $params['cat_IDs'];
	}

	if( !is_null($params['main_cat_ID']) && is_array($params['extra_cat_IDs']) )
	{	// Check new categories
		logIO( 'Main cat ID: '.$params['main_cat_ID'] );
		logIO( 'Extra cat IDs: '.implode( ', ', $params['extra_cat_IDs'] ) );

		// Check if category exists and can be used
		if( ! xmlrpcs_check_cats( $params['main_cat_ID'], $Blog, $params['extra_cat_IDs'] ) )
		{	// Permission denied
			return xmlrpcs_resperror( 3 );	// User error 3
		}

		/*
		 * CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
		 * NOTE: extra_cat_IDs array now includes main_cat_ID too, so we are actually checking ALL categories below
		 */
		if( ! $current_User->check_perm( 'cats_post!'.$params['status'], 'edit', false, $params['extra_cat_IDs'] ) )
		{
		}
	}

	if( !is_null($params['item_typ_ID']) )
	{
		if( ! preg_match('~^[0-9]+$~', $params['item_typ_ID']) )
		{	// Only accept numeric values, switch to default value
			$params['item_typ_ID'] = NULL;
		}

		foreach( $posttypes_perms as $l_permname => $l_posttypes )
		{	// "Reverse" the $posttypes_perms array:
			foreach( $l_posttypes as $ll_posttype )
			{
				$posttype2perm[$ll_posttype] = $l_permname;
			}
		}

		if( isset( $posttype2perm[ $params['item_typ_ID'] ] ) )
		{	// Check permission for this post type
			if( ! $current_User->check_perm( 'cats_'.$posttype2perm[ $params['item_typ_ID'] ], 'edit', false, $params['extra_cat_IDs'] ) )
			{	// Permission denied
				return xmlrpcs_resperror( 3 );	// User error 3
			}
		}
	}
	logIO( 'Post type: '.$params['item_typ_ID'] );

	logIO( 'Permission granted.' );

	// CHECK HTML SANITY:
	if( ($params['title'] = check_html_sanity( $params['title'], 'xmlrpc_posting' )) === false )
	{
		return xmlrpcs_resperror( 21, $Messages->get_string( 'Invalid post title, please correct these errors:', '' ) );
	}
	if( ($params['content'] = check_html_sanity( $params['content'], 'xmlrpc_posting' )) === false  )
	{
		return xmlrpcs_resperror( 22, $Messages->get_string( 'Invalid post contents, please correct these errors:'."\n", '', "  //  \n", 'xmlrpc' ) );
	}

	if( !is_null($params['title']) )
	{
		$edited_Item->set( 'title', $params['title'] );
	}
	if( !is_null($params['content']) )
	{
		$edited_Item->set( 'content', $params['content'] );
	}
	if( !is_null($params['urltitle']) )
	{
		$edited_Item->set( 'urltitle', $params['urltitle'] );
	}
	if( !is_null($params['main_cat_ID']) && !is_null($params['extra_cat_IDs']) )
	{
		$edited_Item->set('main_cat_ID', $params['main_cat_ID']);
		$edited_Item->set('extra_cat_IDs', $params['extra_cat_IDs']);
	}
	if( !is_null($params['item_typ_ID']) )
	{
		$edited_Item->set('ityp_ID', $params['item_typ_ID']);
	}
	if( !is_null($params['featured']) )
	{
		$edited_Item->set('featured', $params['featured']);
	}
	if( !is_null($params['order']) )
	{
		if( ! (empty($params['order']) && ! $edited_Item->order) )
		{	// Do not allow 0 order if there was no order set before
			$edited_Item->set('order', $params['order']);
		}
	}
	if( !is_null($params['parent_ID']) )
	{
		$edited_Item->set('parent_ID', $params['parent_ID']);
	}
	if( !is_null($params['author_ID']) && $params['author_ID'] != $this->creator_user_ID )
	{	// We have already checked perms to edit items created by other users
		$edited_Item->set( 'lastedit_user_ID', $params['parent_ID']);
	}
	if( !is_null($params['tags']) )
	{
		$edited_Item->set_tags_from_string( $params['tags'] );
	}
	if( !is_null($params['excerpt']) )
	{
		$edited_Item->set( 'excerpt', $params['excerpt'] );
	}
	if( !empty($params['comment_status']) && $edited_Item->allow_comment_statuses() )
	{	// Comment status
		$edited_Item->set( 'comment_status', $params['comment_status'] );
	}
	if( !empty($params['status']) )
	{
		$edited_Item->set( 'status', $params['status'] );
	}
	if( !empty($params['date']) )
	{
		$edited_Item->set( 'issue_date', $params['date'] );
	}
	if( !empty($params['locale']) )
	{
		$edited_Item->set('locale', $params['locale']);
	}

	logIO( var_export($edited_Item->dbchanges, true) );

	// UPDATE POST IN DB:
	$edited_Item->dbupdate();

	if( $DB->error )
	{
		return xmlrpcs_resperror( 99, 'Error while updating item: '.$DB->last_error );
	}

	if( !is_null($params['custom_fields']) )
	{	// TODO sam2kb> Add custom fields
		if( is_array($params['custom_fields']) && count($params['custom_fields']) > 0 )
		{
			logIO( 'Modifying custom fields...' );
			foreach( $params['custom_fields'] as $field )
			{	// id, key, value
				logIO( 'Custom field: '.var_export($field, true) );
			}
		}
		else
		{
			logIO( 'Deleting custom fields...' );
		}
	}

	// Execute or schedule notifications & pings:
	logIO( 'Handling notifications...' );
	$edited_Item->handle_post_processing( false );

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( 1, 'boolean' ) );
}


/**
 * Check that an User can view a specific Item.
 *
 * @param object The Item (by reference).
 * @param object The User (by reference).
 * @return boolean True if permission granted, false otherwise.
 */
function xmlrpcs_can_view_item( & $Item, & $current_User )
{
	$can_view_post = false;
	switch( $Item->status )
	{
		case 'published':
		case 'redirected':
			$can_view_post = true;
			break;
		case 'protected':
		case 'draft':
		case 'deprecated':
			$can_view_post = $current_User->check_perm( 'blog_ismember', 'view', false, $Item->get_blog_ID() );
			break;
		case 'private':
			$can_view_post = ( $Item->creator_user_ID == $current_User->ID );
			break;
	}

	logIO( 'xmlrpcs_can_view_item(): Post status: '.$Item->status );
	logIO( 'xmlrpcs_can_view_item( Item(#'.$Item->ID.'), User(#'.$current_User->ID.') ): Permission '.( $can_view_post ? 'granted' : 'DENIED' ) );
	return $can_view_post;
}


/**
 * Check whether the main category and the extra categories are valid
 * in a Blog's context and try to fix errors.
 *
 * @author Tilman BLUMENBACH / Tblue
 *
 * @param integer The main category to check (by reference).
 * @param object The Blog to which the category is supposed to belong to (by reference).
 * @param array Extra categories for the post (by reference).
 *
 * @return boolean False on error (use xmlrpcs_resperror() to return it), true on success.
 */
function xmlrpcs_check_cats( & $maincat, & $Blog, & $extracats )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	// Trim $maincat and $extracats (qtm sends whitespace before the cat IDs):
	$maincat   = trim( $maincat );
	$extracats = array_map( 'trim', $extracats );

	$ChapterCache = & get_ChapterCache();

	// ---- CHECK MAIN CATEGORY ----
	if( $ChapterCache->get_by_ID( $maincat, false ) === false )
	{	// Category does not exist!
		// Remove old category from extra cats:
		if( ( $key = array_search( $maincat, $extracats ) ) !== false )
		{
			unset( $extracats[$key] );
		}

		// Set new category (blog default):
		$maincat = $Blog->get_default_cat_ID();
		logIO( 'Invalid main cat ID - new ID: '.$maincat );
	}
	else if( get_allow_cross_posting() < 2 && get_catblog( $maincat ) != $Blog->ID )
	{	// We cannot use a maincat of another blog than the current one:
		$xmlrpcs_errcode = $xmlrpcerruser + 11;
		$xmlrpcs_errmsg = 'Current crossposting setting does not allow moving posts to a different blog.';
		return false;
	}

	// ---- CHECK EXTRA CATEGORIES ----
	foreach( $extracats as $ecat )
	{
		if( $ecat == $maincat )
		{	// We already checked the maincat above (or reset it):
			continue;
		}

		logIO( 'Checking extra cat: '.$ecat );
		if( $ChapterCache->get_by_ID( $ecat, false ) === false )
		{	// Extra cat does not exist:
			$xmlrpcs_errcode = $xmlrpcerruser + 11;
			$xmlrpcs_errmsg  = 'Extra category '.(int)$ecat.' not found in requested blog.';
			return false;
		}
	}

	if( ! in_array( $maincat, $extracats ) )
	{
		logIO( '$maincat was not found in $extracats array - adding.' );
		$extracats[] = $maincat;
	}

	return true;
}


/**
 * Get array of latest items
 *
 * @param array of params
 *			- limit (int) the number of items to return
 *			- post_ID (int) return specified item or NULL to return all available
 * @return xmlrpcmsg
 */
function xmlrpc_get_items( $params, & $Blog )
{
	global $current_User;

	$params = array_merge( array(
			'limit' => 0,
			'item_ID' => 0,
			'types' => '', // all post types
		), $params);

	// Protected and private get checked by statuses_where_clause().
	$statuses = array( 'published', 'redirected', 'protected', 'private' );
	if( $current_User->check_perm( 'blog_ismember', 'view', false, $Blog->ID ) )
	{	// These statuses require member status:
		$statuses = array_merge( $statuses, array( 'draft', 'deprecated' ) );
	}
	logIO( 'Statuses: '.implode(', ', $statuses) );

	if( !empty($params['item_ID']) )
	{
		logIO('Getting item #'.$params['item_ID']);

		$filters = array(
				'visibility_array'	=> $statuses,
				'types'				=> NULL,
				'post_ID'			=> $params['item_ID'],
			);
	}
	else
	{
		logIO( sprintf('Trying to get latest items (%s)', ($params['limit'] ? $params['limit'] : 'all')) );

		$filters = array(
				'visibility_array'	=> $statuses,
				'types'				=> $params['types'],
				'order'				=> 'DESC',
				'unit'				=> 'posts',
			);
	}

	// Get the pages to display:
	load_class( 'items/model/_itemlist.class.php', 'ItemList2' );
	$ItemList = new ItemList2( $Blog, NULL, NULL, $params['limit'] );

	$ItemList->set_filters( $filters, false );

	// Run the query:
	$ItemList->query();

	logIO( 'Items found: '.$ItemList->result_num_rows );

	$data = array();
	while( $Item = & $ItemList->get_item() )
	{
		$data[] = _wp_mw_get_item_struct( $Item );
	}

	return $data;
}


/**
 * Get array of latest comments
 *
 * @param array of params
 *			- Blog (object)
 *			- User (object)
 *			- limit (int) the number of comments to return
 *			- comment_ID (int) return specified comment or NULL to return all available
 *			- item_ID (int) return comments for specified item only
 * @return xmlrpcmsg
 */
function xmlrpc_get_comments( $params, & $Blog )
{
	global $DB, $current_User;

	$params = array_merge( array(
			'limit'			=> 0,
			'comment_ID'	=> 0,
			'item_ID'		=> 0,
			'statuses'		=> '',
			'types'			=> array('comment', 'trackback', 'pingback'),
		), $params );

	$params['comment_ID'] = abs( intval($params['comment_ID']) );
	$params['item_ID'] = abs( intval($params['item_ID']) );

	if( empty($params['statuses']) )
	{	// Return all except 'trash'
		$params['statuses'] = array('published', 'deprecated', 'draft');
	}

	if( !empty($params['comment_ID']) )
	{
		logIO('Getting comment #'.$params['comment_ID']);

		$filters = array(
				'comment_ID'=> $params['comment_ID'],
				'types'		=> $params['types'],
				'statuses'	=> $params['statuses'],
			);
	}
	elseif( !empty($params['item_ID']) )
	{
		logIO('Getting comments to item #'.$params['item_ID']);

		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $params['item_ID'], false, false );

		if( empty( $Item ) )
		{	// Item not found
			return xmlrpcs_resperror( 5, 'Requested post/Item ('.$params['item_ID'].') does not exist.' );
		}

		if( ! $Item->can_see_comments() )
		{	// Cannot see comments
			return xmlrpcs_resperror( 5, 'You are not allowed to view comments for this post/Item ('.$params['item_ID'].').' );
		}

		$filters = array(
				'post_ID'	=> $Item->ID,
				'types'		=> $params['types'],
				'statuses'	=> $params['statuses'],
				'comments'	=> $params['limit'],
				'order'		=> 'DESC',
			);
	}
	else
	{
		logIO( sprintf('Trying to get latest comments (%s)', ($params['limit'] ? $params['limit'] : 'all')) );

		$filters = array(
				'types'		=> $params['types'],
				'statuses'	=> $params['statuses'],
				'comments'	=> $params['limit'],
				'order'		=> 'DESC',
			);
	}

	//logIO( "Filters:\n".var_export($filters, true) );

	$CommentList = new CommentList2( $Blog );

	// Filter list:
	$CommentList->set_filters( $filters, false );

	// Get ready for display (runs the query):
	$CommentList->display_init();

	logIO( 'Comments found: '.$CommentList->result_num_rows );

	$data = array();
	if( $CommentList->result_num_rows )
	{
		while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments:
			$Comment->get_Item();

			$data[] = array(
					'dateCreated'		=> new xmlrpcval( datetime_to_iso8601($Comment->date, true), 'dateTime.iso8601' ), // Force GMT date
					'date_created_gmt'	=> new xmlrpcval( datetime_to_iso8601($Comment->date, true), 'dateTime.iso8601' ),
					'user_id'			=> new xmlrpcval( intval($Comment->author_user_ID) ),
					'comment_id'		=> new xmlrpcval($Comment->ID),
					'parent'			=> new xmlrpcval( intval($Comment->in_reply_to_cmt_ID) ),
					'status'			=> new xmlrpcval( wp_or_b2evo_comment_status($Comment->status, 'wp') ),
					'content'			=> new xmlrpcval($Comment->content),
					'link'				=> new xmlrpcval($Comment->get_permanent_url()),
					'post_id'			=> new xmlrpcval($Comment->Item->ID),
					'post_title'		=> new xmlrpcval($Comment->Item->title),
					'author'			=> new xmlrpcval($Comment->get_author_name()),
					'author_url'		=> new xmlrpcval($Comment->get_author_url()),
					'author_email'		=> new xmlrpcval($Comment->get_author_email()),
					'author_ip'			=> new xmlrpcval($Comment->author_IP),
					'type'				=> new xmlrpcval( (($Comment->type == 'comment') ? '' : $Comment->type) ) // empty string for 'comment'
				);
		}
	}

	return $data;
}


/**
 * Deletes given Item
 *
 * @return xmlrpcresp XML-RPC Response (bool)
 */
function xmlrpcs_delete_item( & $edited_Item )
{
	global $current_User, $DB;

	// CHECK PERMISSION:
	if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'delete', false, $edited_Item ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	// DELETE POST FROM DB:
	$edited_Item->dbdelete();
	if( $DB->error )
	{
		return xmlrpcs_resperror( 99, 'DB error: '.$DB->last_error ); // user error 9
	}

	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval(1, 'boolean'));
}


function wp_or_b2evo_comment_status( $raw_status, $convert_to = 'b2evo' )
{
	$status = '';

	if( $convert_to == 'b2evo' )
	{
		switch( $raw_status )
		{	// Map WP statuses to b2evo

			// Keep native b2evo statuses
			case 'published':
			case 'deprecated':
			case 'draft':
			case 'trash':
				$status = $raw_status;
				break;

			case 'hold':
				$status = 'draft';
				break;

			case 'spam':
				$status = 'deprecated';
				break;

			case 'approve':
				$status = 'published';
				break;

			default:
				$status = NULL;
		}
	}
	elseif( $convert_to == 'wp' )
	{
		switch( $raw_status )
		{	// Map b2evo statuses to WP
			case 'deprecated':
				$status = 'spam';
				break;

			case 'draft':
				$status = 'hold';
				break;

			case 'trash':
				$status = 'trash';
				break;

			default:
				$status = 'approve';
				break;
		}
	}

	return $status;
}


function wp_or_b2evo_item_status( $raw_status, $convert_to = 'b2evo' )
{
	$status = '';

	if( $convert_to == 'b2evo' )
	{
		switch( $raw_status )
		{	// Map WP statuses to b2evo
			// Note: we drop 'inherit' status because b2evo doesn't support it

			// Keep native b2evo statuses
			case 'published':
			case 'deprecated':
			case 'protected':
			case 'private':
			case 'draft':
			case 'redirected':
				$status = $raw_status;
				break;

			case 'auto-draft':
			case 'pending':
				$status = 'draft';
				break;

			case 'publish':
			case 'future':
				$status = 'published';
				break;

			case 'trash':
				$status = 'deprecated';
				break;
		}
	}
	elseif( $convert_to == 'wp' )
	{
		switch( $raw_status )
		{	// Map b2evo statuses to WP
			case 'private':
			case 'draft':
				$status = $raw_status;
				break;

			case 'deprecated':
				$status = 'trash';
				break;

			case 'protected':
				$status = 'private';
				break;

			case 'published':
				$status = 'publish';
				break;

			case 'redirected':
				$status = 'published';
				break;

			default:
				$status = 'approve';
				break;
		}
	}

	return $status;
}

?>