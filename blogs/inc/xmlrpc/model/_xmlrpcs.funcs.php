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

	$fp = fopen( $basepath.$xmlsrv_subdir.'xmlrpc.log', 'a+' );
	if( $newline )
	{
		$date = date('Y-m-d H:i:s ');
		fwrite( $fp, "\n\n".$date );
	}
	fwrite($fp, $msg."\n");
	fclose($fp);

	return true;
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
 * blogger.deletePost deletes a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggerdeletepost.html
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 postid (string): Unique identifier of the post to be deleted.
 *					2 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					3 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function _mw_blogger_deletepost($m)
{
	global $DB;

	// CHECK LOGIN:
	if( ! $current_User = & xmlrpcs_login( $m, 2, 3 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 1 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION:
	if( ! $current_User->check_perm( 'blog_del_post', 'edit', false, $edited_Item->get_blog_ID() ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	// DELETE POST FROM DB:
	$edited_Item->dbdelete();
	if( $DB->error )
	{ // DB error
		return xmlrpcs_resperror( 99, 'DB error: '.$DB->last_error ); // user error 9
	}

	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval(1, 'boolean'));
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
 */
function _wp_mw_getcategories( $m )
{
	global $DB, $Settings;

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

	$sql = "SELECT cat_ID, cat_name
					FROM T_categories ";
	$sql .= 'WHERE '.$Blog->get_sql_where_aggregate_coll_IDs('cat_blog_ID');
	if( $Settings->get('chapter_ordering') == 'manual' )
	{	// Manual order
		$sql .= ' ORDER BY cat_order';
	}
	else
	{	// Alphabetic order
		$sql .= ' ORDER BY cat_name';
	}

	$rows = $DB->get_results( $sql );
	if( $DB->error )
	{	// DB error
		return xmlrpcs_resperror( 99, 'DB error: '.$DB->last_error ); // user error 9
	}
	logIO( 'Categories: '.count($rows) );

	$ChapterCache = & get_Cache('ChapterCache');
	$data = array();
	foreach( $rows as $row )
	{
		$Chapter = & $ChapterCache->get_by_ID($row->cat_ID);
		if( ! $Chapter )
		{
			continue;
		}
		$data[] = new xmlrpcval( array(
				'categoryId' => new xmlrpcval( $row->cat_ID ), // not in RFC (http://www.xmlrpc.com/metaWeblogApi)
				'description' => new xmlrpcval( $row->cat_name ),
				'categoryName' => new xmlrpcval( $row->cat_name ), // not in RFC (http://www.xmlrpc.com/metaWeblogApi)
				'htmlUrl' => new xmlrpcval( $Chapter->get_permanent_url() ),
				'rssUrl' => new xmlrpcval( url_add_param($Chapter->get_permanent_url(), 'tempskin=_rss2') )
			//	mb_convert_encoding( $row->cat_name, "utf-8", "iso-8859-1")  )
			),'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval($data, 'struct') );
}


/**
 * metaWeblog.newMediaObject  image upload
 * wp.uploadFile
 *
 * image is supplied coded in the info struct as bits
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metaweblognewmediaobject
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.uploadFile
 *
 * @todo do not overwrite existing pics with same name
 * @todo extensive permissions
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
	global $Settings;

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
	logIO( 'Received MIME type: '.( isset( $contentstruct['type'] ) ? $contentstruct['type'] : '(none)' ) );

	load_funcs('files/model/_file.funcs.php');

	$filesize = strlen( $data );
	if( ( $maxfilesize = $Settings->get( 'upload_maxkb' ) * 1024 ) && $filesize > $maxfilesize )
	{
		return xmlrpcs_resperror( 4, 'File too big ('.bytesreadable( $filesize, false )
									.'); max. allowed size is '.bytesreadable( $maxfilesize, false ) );
	}

	$rf_filepath = $contentstruct['name'];
	logIO( 'Received filepath: '.$rf_filepath );
	// Avoid problems:
	$rf_filepath = strtolower($rf_filepath);
	$rf_filepath = preg_replace( '€[^a-z0-9\-_./]+€i', '-', $rf_filepath );
	logIO( 'Sanitized filepath: '.$rf_filepath );

	// Split into path + name:
	$filepath_parts = explode( '/', $rf_filepath );
	$filename = array_pop( $filepath_parts );

	// Check valid filename/extension: (includes check for locked filenames)
	logIO( 'File name: '.$filename );
	if( $error_filename = validate_filename( $filename, false ) )
	{
		return xmlrpcs_resperror( 5, $error_filename );
	}

	// Check valid path parts:
	$rds_subpath = '';
	foreach( $filepath_parts as $filepath_part )
	{
		if( empty($filepath_part) || $filepath_part == '.' )
		{	// self ref not useful
			continue;
		}

		if( $error = validate_dirname($filepath_part) )
		{ // invalid relative path:
			logIO( $error );
			return xmlrpcs_resperror( 6, $error );
		}

		$rds_subpath .= $filepath_part.'/';
	}
	logIO( 'Subpath: '.$rds_subpath );

	$fileupload_path = $Blog->get_media_dir();
	if( ! $fileupload_path )
	{
		return xmlrpcs_resperror( 7, 'Error accessing Blog media directory.' );
	}

	$afs_filedir = $fileupload_path.$rds_subpath;
	$afs_filepath = $afs_filedir.$filename;
	if( file_exists( $afs_filepath ) )
	{
		return xmlrpcs_resperror( 8, 'File exists.' );
	}

	// Create subdirs, if necessary:
	if( !empty($rds_subpath) )
	{
		if( ! mkdir_r( $afs_filedir ) )
		{	// Dir didn't already exist and could not be created
			return xmlrpcs_resperror( 9, 'Error creating sub directories: '.rel_path_to_base($afs_filedir));
		}
	}

	logIO( 'Saving to: '.$afs_filepath );
	$fh = @fopen( $afs_filepath, 'wb' );
	if( !$fh )
	{
		return xmlrpcs_resperror( 10, 'Error opening file for writing.' );
	}

	$ok = @fwrite($fh, $data);
	@fclose($fh);

	if ( $ok === false )
	{
		return xmlrpcs_resperror( 13, 'Error while writing to file.' );
	}

	// chmod uploaded file:
	$chmod = $Settings->get('fm_default_chmod_file');
	logIO( 'chmod to: '.$chmod );
	@chmod( $afs_filepath, octdec( $chmod ) );

	$url = $Blog->get_media_url().$rds_subpath.$filename;
	logIO( 'URL of new file: '.$url );

	// - return URL as XML
	$urlstruct = new xmlrpcval(array(
			'url' => new xmlrpcval($url, 'string')
		), 'struct');

	logIO( 'OK.' );
	return new xmlrpcresp($urlstruct);
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

	// LOAD BLOGS tehuser is a member of:
	$BlogCache = & get_Cache( 'BlogCache' );
	$blog_array = $BlogCache->load_user_blogs( 'blog_ismember', 'view', $current_User->ID, 'ID' );

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
					'isAdmin' => new xmlrpcval( $current_User->check_perm( 'templates', 'any' ), 'boolean') );
		if ( $type == 'wp')
		{
			$item['xmlrpc'] = new xmlrpcval ( $xmlsrv_url );
		}

		$resp_array[] = new xmlrpcval( $item, 'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $resp_array, 'array' ) );
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
	global $DB, $Settings;

	// CHECK LOGIN:
	// Tblue> Note on perms: I think an user doesn't need any special perms
	//                       to get a list of blog categories; the only
	//                       requirement for the user is to have an account
	//                       in the system.
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

	$sql = 'SELECT *
					  FROM T_categories ';

	$BlogCache = & get_Cache('BlogCache');
	$current_Blog = $BlogCache->get_by_ID( $Blog->ID );
	$sql .= 'WHERE '.$Blog->get_sql_where_aggregate_coll_IDs('cat_blog_ID');
	if( $Settings->get('chapter_ordering') == 'manual' )
	{	// Manual order
		$sql .= ' ORDER BY cat_order';
	}
	else
	{	// Alphabetic order
		$sql .= ' ORDER BY cat_name';
	}

	$rows = $DB->get_results( $sql );
	if( $DB->error )
	{ // DB error
		return xmlrpcs_resperror( 99, 'DB error: '.$DB->last_error ); // user error 99
	}

	xmlrpc_debugmsg( 'Categories: '.$DB->num_rows );

	$categoryIdName = ( $type == 'b2' ? 'categoryID' : 'categoryId' );
	$data = array();
	foreach( $rows as $row )
	{
		$data[] = new xmlrpcval( array(
				$categoryIdName => new xmlrpcval( $row->cat_ID ),
				'categoryName' => new xmlrpcval( $row->cat_name )
			//	mb_convert_encoding( $row->cat_name, "utf-8", "iso-8859-1")  )
			), 'struct' );
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
	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	if( empty( $current_User ) || ! $current_User->check_password( $password, false ) )
	{	// User not found or password doesn't match
		$xmlrpcs_errcode = $xmlrpcerruser+1;
		$xmlrpcs_errmsg = 'Wrong username/password combination: '.$username.' / '.starify($password);
		$r = NULL;
		return $r;
	}

	logIO( 'Login OK - User: '.$current_User->ID.' - '.$current_User->login );

  // This may be needed globally for status permissions in ItemList2, etc..
	$GLOBALS['current_User'] = & $current_User;

	return $current_User;
}


/**
 * Get current Blog for an XML-RPC request.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of blog param
 * @return Blog or NULL
 */
function & xmlrpcs_get_Blog( $m, $blog_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$blog = $m->getParam( $blog_param );
	$blog = $blog->scalarval();
	// waltercruz> qtm: http://qtm.blogistan.co.uk/ inserts some spacing before/after blogID.
	$blog = (int) trim($blog);
	/**
	 * @var BlogCache
	 */
	$BlogCache = & get_Cache( 'BlogCache' );
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
 * @param integer idx of postid param
 * @return Blog or NULL
 */
function & xmlrpcs_get_Item( $m, $postid_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$postid = $m->getParam( $postid_param );
	$postid = $postid->scalarval();

  /**
	 * @var ItemCache
	 */
	$ItemCache = & get_Cache( 'ItemCache' );
  /**
	 * @var Item
	 */
	$edited_Item = & $ItemCache->get_by_ID( $postid, false, false );

	if( empty( $edited_Item ) )
	{	// Blog not found
		$xmlrpcs_errcode = $xmlrpcerruser+6;
		$xmlrpcs_errmsg = 'Requested post/Item ('.$postid.') does not exist.';
		$r = NULL;
		return $r;
	}

	logIO( 'Requested Item: '.$edited_Item->ID.' - '.$edited_Item->title );

	return $edited_Item;
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
	else
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
 * Create a new Item and return an XML-RPC response
 *
 * @param string HTML
 * @param string HTML
 * @param string date
 * @param integer main category
 * @param array of integers : extra categories
 * @param string status
 * @param string Tags
 * @param string Comment status. See {@link Item::$comment_status}.
 * @param string Excerpt
 * @return xmlrpcmsg
 */
function xmlrpcs_new_item( $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags = '', $allow_comments = 'open', $excerpt = '' )
{
  /**
	 * @var User
	 */
	global $current_User;

	global $Messages;
	global $DB;

	// CHECK HTML SANITY:
	if( ($post_title = check_html_sanity( $post_title, 'xmlrpc_posting' )) === false )
	{
		return xmlrpcs_resperror( 21, $Messages->get_string( 'Invalid post title, please correct these errors:', '' ) );
	}
	if( ($content = check_html_sanity( $content, 'xmlrpc_posting' )) === false  )
	{
		return xmlrpcs_resperror( 22, $Messages->get_string( 'Invalid post contents, please correct these errors:'."\n", '', NULL, "  //  \n", 'xmlrpc' ) );
	}

	// INSERT NEW POST INTO DB:
	load_class( 'items/model/_item.class.php' );
	$edited_Item = & new Item();
	$edited_Item->set( 'title', $post_title );
	$edited_Item->set( 'content', $content );
	$edited_Item->set( 'datestart', $post_date );
	$edited_Item->set( 'main_cat_ID', $main_cat );
	$edited_Item->set( 'extra_cat_IDs', $cat_IDs );
	$edited_Item->set( 'status', $status );
	$edited_Item->set_tags_from_string( $tags );
	$edited_Item->set( 'locale', $current_User->locale );
	$edited_Item->set_creator_User( $current_User );

	// Comment status:
	$edited_Item->load_Blog();
	if( $edited_Item->Blog->allowcomments == 'post_by_post' )
	{
		$edited_Item->set( 'comment_status', $allow_comments );
	}

	//Excerpt:
	if ( ! empty( $excerpt ) )
	{
		$edited_Item->set( 'excerpt', $excerpt );
	}

	$edited_Item->dbinsert();
	if( empty($edited_Item->ID) )
	{ // DB error
		return xmlrpcs_resperror( 99, 'Error while inserting item: '.$DB->last_error );
	}
	logIO( 'Posted with ID: '.$edited_Item->ID );

	// Execute or schedule notifications & pings:
	logIO( 'Handling notifications...' );
	$edited_Item->handle_post_processing();

 	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval($edited_Item->ID));
}


/**
 * Edit an Item and return an XML-RPC response
 *
 * @param Item
 * @param string HTML
 * @param string HTML
 * @param string date
 * @param integer main category
 * @param array of integers : extra categories
 * @param string status
 * @param NULL|string Tags (NULL to leave tags unchanged)
 * @return xmlrpcmsg
 */
function xmlrpcs_edit_item( & $edited_Item, $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags = NULL )
{
  /**
	 * @var User
	 */
	global $current_User;

	global $Messages;
	global $DB;

	// CHECK HTML SANITY:
	if( ($post_title = check_html_sanity( $post_title, 'xmlrpc_posting' )) === false )
	{
		return xmlrpcs_resperror( 21, $Messages->get_string( 'Invalid post title, please correct these errors:', '' ) );
	}
	if( ($content = check_html_sanity( $content, 'xmlrpc_posting' )) === false  )
	{
		return xmlrpcs_resperror( 22, $Messages->get_string( 'Invalid post contents, please correct these errors:'."\n", '', NULL, "  //  \n", 'xmlrpc' ) );
	}

	// UPDATE POST IN DB:
	$edited_Item->set( 'title', $post_title );
	$edited_Item->set( 'content', $content );
	$edited_Item->set( 'status', $status );
	if( !empty($post_date) )
	{
		$edited_Item->set( 'issue_date', $post_date );
	}
	if( !empty($main_cat) )
	{ // Update cats:
		$edited_Item->set('main_cat_ID', $main_cat);
	}
	if( !empty($cat_IDs) )
	{ // Extra-Cats:
		$edited_Item->set('extra_cat_IDs', $cat_IDs);
	}
	if( $tags !== NULL )
	{
		$edited_Item->set_tags_from_string( $tags );
	}
	$edited_Item->dbupdate();
	if( $DB->error )
	{ // DB error
		return xmlrpcs_resperror( 99, 'Error while updating item: '.$DB->last_error );
	}

	// Execute or schedule notifications & pings:
	logIO( 'Handling notifications...' );
	$edited_Item->handle_post_processing();

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
 * Get a main category that exists and is allowed by the current crossposting
 * settings.
 *
 * If the category doesn't exist, the blog's default category is returned.
 * If the value of $allow_cross_posting doesn't allow changing of the post's
 * main category across blogs and the category doesn't belong to the supplied
 * blog, an XML-RPC error is returned.
 * If no errors occurred, this function returns its $maincat argument.
 * 
 * @param integer The main category to check.
 * @param object The Blog to which the category is supposed to belong to (by reference).
 * @param array Extra categories for the post (by reference).
 *
 * @return object|integer An usable category or a XML-RPC error (object).
 */
function xmlrpcs_get_maincat( $maincat, & $Blog, & $extracats )
{
	global $allow_cross_posting;

	$ChapterCache = & get_Cache( 'ChapterCache' );
	if( $ChapterCache->get_by_ID( $maincat, false ) === false )
	{	// Category does not exist, use default:
		$new_maincat = $Blog->get_default_cat_ID();

		// Remove old category from extra cats:
		if( ( $key = array_search( $maincat, $extracats ) ) !== false )
		{
			unset( $extracats[$key] );
		}
		// Add new category to extracats:
		if( ! in_array( $new_maincat, $extracats ) )
		{
			$extracats[] = $new_maincat;
		}

		return (int)$new_maincat;
	}
	else if( $allow_cross_posting < 3 && get_catblog( $maincat ) != $Blog->ID )
	{	// We cannot use a maincat of another blog than the current one:
		return xmlrpcs_resperror( 11 );
	}

	// Main category is OK:
	return (int)$maincat;
}


/*
 * $Log$
 * Revision 1.16  2009/09/01 16:44:58  waltercruz
 * Generic functions to avoid alias and allow enable/disabling of specific APIs on future
 *
 * Revision 1.15  2009/08/31 16:32:26  tblue246
 * Check whether XML-RPC is enabled in xmlsrv/xmlrpc.php
 *
 * Revision 1.13  2009/08/30 17:15:41  waltercruz
 * Adding support to mt_excerpt
 *
 * Revision 1.12  2009/08/30 17:06:25  tblue246
 * Let xmlrpcs_new_item() handle Blog::allowcomments.
 *
 * Revision 1.11  2009/08/30 16:50:19  tblue246
 * Minor
 *
 * Revision 1.10  2009/08/30 15:50:52  waltercruz
 * Adding support for mt_allow_comments
 *
 * Revision 1.9  2009/08/29 12:23:56  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.8  2009/08/28 19:15:06  waltercruz
 * Fixing post creation in qtm
 *
 * Revision 1.7  2009/08/27 17:46:13  tblue246
 * Metaweblog API: Use mt_keywords for item tags (set/get)
 *
 * Revision 1.6  2009/01/28 21:23:23  fplanque
 * Manual ordering of categories
 *
 * Revision 1.5  2009/01/26 00:11:22  fplanque
 * fixing bugs resulting from someone tumble DRYing the code :(
 *
 * Revision 1.4  2009/01/23 00:05:25  blueyed
 * Add Blog::get_sql_where_aggregate_coll_IDs, which adds support for '*' in list of aggregated blogs.
 *
 * Revision 1.3  2008/01/19 10:57:11  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.2  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.1  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 */
?>
