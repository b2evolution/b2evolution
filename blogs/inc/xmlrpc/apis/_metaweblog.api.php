<?php
/**
 * XML-RPC : MetaWeblog API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author tor
 *
 * @see http://manual.b2evolution.net/MetaWeblog_API
 * @see http://www.xmlrpc.com/metaWeblogApi
 *
 * @package xmlsrv
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Decode the dateCreated
 *
 * @param struct
 * @return string MYSQL date
 */
function _mw_decode_postdate( $contentstruct, $now_if_empty = true )
{
	global $Settings;

	$postdate = NULL;

	if( ! empty($contentstruct['dateCreated']) )
	{
		$postdate = $contentstruct['dateCreated'];
		logIO( 'Using contentstruct dateCreated: '.$postdate );
	}
	elseif( $now_if_empty );
	{
		$postdate = date('Y-m-d H:i:s', (time() + $Settings->get('time_difference')));
		logIO( 'No contentstruct dateCreated, using now: '.$postdate );
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
function _mw_get_cat_IDs( $contentstruct, $blog_ID, $empty_struct_ok = false )
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

	logIO( 'finished getting categories...'.implode( ', ', $categories ) );

	if( $empty_struct_ok && empty($categories) )
	{
		return $categories;
	}

	logIO( 'Categories: '.implode( ', ', $categories ) );

	// for cross-blog-entries, the cat_blog_ID WHERE clause should be removed (but cats are given by name!)
	if( ! empty($categories) )
	{
		$sql = "
			SELECT cat_ID FROM T_categories
			 WHERE cat_blog_ID = $blog_ID
				 AND cat_name IN ( ";
		foreach( $categories as $l_cat )
		{
			$sql .= $DB->quote($l_cat).', ';
		}
		if( ! empty($categories) )
		{
			$sql = substr($sql, 0, -2); // remove ', '
		}
		$sql .= ' )';
		logIO('sql for finding IDs ...'.$sql);

		$cat_IDs = $DB->get_col( $sql );
		if( $DB->error )
		{	// DB error
			logIO('user error finding categories info ...');
		}
	}
	else
	{
		$cat_IDs = array();
	}

	if( ! empty($cat_IDs) )
	{ // categories requested to be set:

		// Check if category exists
		// Tblue> Why is this needed?
		$ChapterCache = & get_Cache('ChapterCache');
		if( $ChapterCache->get_by_ID( $cat_IDs[0], false ) === false )
		{ // Main cat does not exist:
			logIO('usererror 5 ...');
			return xmlrpcs_resperror( 5, 'Requested category does not exist.' ); // user error 5
		}
		logIO('finished checking if main category exists ...'.$cat_IDs[0]);
	}
	else
	{ // No category given/valid - use the first for the blog:
		logIO('No category for post given ...');

		$first_cat = $DB->get_var( '
			SELECT cat_ID
			  FROM T_categories
			 WHERE cat_blog_ID = '.$blog_ID.'
			 LIMIT 1' );
		if( empty($first_cat) )
		{
			logIO( 'No categories for this blog...');
			return xmlrpcs_resperror( 5, 'No categories for this blog.' ); // user error 5
		}
		else
		{
			$cat_IDs = array($first_cat);
		}
	}

	return $cat_IDs;
}



$mwnewMediaObject_doc = 'Uploads a file to the media library of the blog';
$mwnewMediaObject_sig = array(array( $xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcStruct ));
/**
 * metaWeblog.newMediaObject  image upload
 *
 * image is supplied coded in the info struct as bits
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metaweblognewmediaobject
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
function mw_newmediaobject($m)
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
	$rf_filepath = preg_replace( '¤[^a-z0-9\-_./]+¤i', '-', $rf_filepath );
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




$mwnewpost_doc='Adds a post, blogger-api like, +title +category +postdate';
$mwnewpost_sig =  array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));
/**
 * metaWeblog.newPost
 *
 * NB! (Tor Feb 2005) status in metaweblog API speak dictates whether static html files are generated or not, so fairly misleading
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 */
function mw_newpost($m)
{
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

	// getParam(4) should now be a flag for publish or draft
	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO("Publish: $xstatus -> Status: $status");

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO( 'Decoded xcontent' );

	// Categories:
	$cat_IDs = _mw_get_cat_IDs( $contentstruct, $Blog->ID );
	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;	// This can be a preformatted error message
	}
	$main_cat = $cat_IDs[0];

	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$status, 'edit', false, $cat_IDs ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	$main_cat = xmlrpcs_get_maincat( $main_cat, $Blog, $cat_IDs );
	if( ! is_int( $main_cat ) )
	{	// Error:
		return $main_cat;
	}

	$post_date = _mw_decode_postdate( $contentstruct, true );
	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];

	// non-standard MT extensions
	$tags = isset( $contentstruct['mt_keywords'] ) ? $contentstruct['mt_keywords'] : '';

	$allow_comments = 'open';

	if ( $Blog->allowcomments == 'post_by_post' )
	{
		if ( isset($contentstruct['mt_allow_comments'] ) )
		{
			if ( ! $contentstruct['mt_allow_comments'] )
			{
				$allow_comments = 'closed';
			}
		}

	}

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags, $allow_comments );
}




$mweditpost_doc='Edits a post, blogger-api like, +title +category +postdate';
$mweditpost_sig =  array(array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));
/**
 * metaWeblog.editPost (metaWeblog.editPost)
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#basicEntrypoints
 *
 * @todo Tor - TODO
 *		- Sort out sql select with blog ID
 *		- screws up posts with multiple categories
 *		  partly due to the fact that Movable Type calls to this API are different to Metaweblog API calls when handling categories.
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post to edit
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 */
function mw_editpost( $m )
{
	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 0 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// We need to be able to edit this post:
	if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	{
		return xmlrpcs_resperror( 3 ); // Permission denied
	}

	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO("Publish: $xstatus -> Status: $status");

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO('Decoded xcontent');

	// Categories:
	$cat_IDs = _mw_get_cat_IDs( $contentstruct, $edited_Item->get_blog_ID(), true /* empty is ok */ );
	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;
	}

	if( empty( $cat_IDs ) )
	{
		$cat_IDs = postcats_get_byID( $edited_Item->ID );
	}
	$main_cat = $cat_IDs[0];

	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$status, 'edit', false, $cat_IDs ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	$Blog = & $edited_Item->get_Blog();
	$main_cat = xmlrpcs_get_maincat( $main_cat, $Blog, $cat_IDs );
	if( ! is_int( $main_cat ) )
	{	// Error:
		return $main_cat;
	}

	$post_date = _mw_decode_postdate( $contentstruct, false );
	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];
	$tags = isset( $contentstruct['mt_keywords'] ) ? $contentstruct['mt_keywords'] : NULL /* don't change tags */; // non-standard MT extension

	// COMPLETE VALIDATION & UPDATE:
	return xmlrpcs_edit_item( $edited_Item, $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags );


	/*
	// Time to perform trackbacks NB NOT WORKING YET
	//
	// NB Requires a change to the _trackback library
	//
	// function trackbacks( $post_trackbacks, $content, $post_title, $post_ID )

	// first extract these from posting as post_trackbacks array, then rest is easy
	// 	<member>
	//		<name>mt_tb_ping_urls</name>
	//	<value><array><data>
	//		<value><string>http://archive.scripting.com/2005/04/17</string></value>
	//	</data></array></value>
	//	</member>
	// First check that trackbacks are allowed - mt_allow_pings
	$trackback_ok = 0;
	$trackbacks = array();
	$trackback_ok = $contentstruct['mt_allow_pings'];
	logIO("Trackback OK  ...".$trackback_ok);
	if ($trackback_ok == 1)
	{
		$trackbacks = $contentstruct['mt_tb_ping_urls'];
		logIO("Trackback url 0  ...".$trackbacks[0]);
		$no_of_trackbacks = count($trackbacks);
		logIO("Number of Trackbacks  ...".$no_of_trackbacks);
		if ($no_of_trackbacks > 0)
		{
			logIO("Calling Trackbacks  ...");
			load_funcs('comments/_trackback.funcs.php');
 			$result = trackbacks( $trackbacks, $content, $post_title, $post_ID );
			logIO("Returned from  Trackbacks  ...");
 		}

	}
	*/
}




$mwgetcats_sig =  array(array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mwgetcats_doc = 'Get categories of a post, MetaWeblog API-style';
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
function mw_getcategories( $m )
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




$metawebloggetrecentposts_doc = 'fetches X most recent posts, blogger-api like';
$metawebloggetrecentposts_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * metaWeblog.getRecentPosts
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metawebloggetrecentposts
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 */
function mw_getrecentposts( $m )
{
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

	$numposts = $m->getParam(3);
	$numposts = $numposts->scalarval();
	logIO('In mw_getrecentposts, current numposts is ...'. $numposts);

	// Get the posts to display:
	load_class( 'items/model/_itemlist.class.php' );
	$MainList = & new ItemList2( $Blog, NULL, NULL, $numposts );

	// Protected and private get checked by statuses_where_clause().
	$statuses = array( 'published', 'redirected', 'protected', 'private' );
	if( $current_User->check_perm( 'blog_ismember', 'view', false, $Blog->ID ) )
	{	// These statuses require member status:
		$statuses = array_merge( $statuses, array( 'draft', 'deprecated' ) );
	}
	logIO( 'Statuses: '.implode( ', ', $statuses ) );

	$MainList->set_filters( array(
			'visibility_array' => $statuses,
			'order' => 'DESC',
			'unit' => 'posts',
		) );
	// Run the query:
	$MainList->query();

	logIO( 'Items:'.$MainList->result_num_rows );

	$data = array();
	/**
	 * @var Item
	 */
	while( $Item = & $MainList->get_item() )
	{
		logIO( 'Item:'.$Item->title.
					' - Issued: '.$Item->issue_date.
					' - Modified: '.$Item->mod_date );
		$post_date = mysql2date('U', $Item->issue_date);
		$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);
		$content = $Item->content;
		// Load Item's creator User:
		$Item->get_creator_User();
		$authorname = $Item->creator_User->get('preferredname');
		// need a loop here to extract all categoy names
		// $extra_cat_IDs is the variable for the rest of the IDs
		$hope_Chapter = & $Item->get_main_Chapter();
		logIO( 'postcats: '.$hope_Chapter->name );
		$data[] = new xmlrpcval(array(
				'dateCreated' => new xmlrpcval($post_date,'dateTime.iso8601'),
				'userid' => new xmlrpcval($Item->creator_user_ID),
				'postid' => new xmlrpcval($Item->ID),
				'categories' => new xmlrpcval(array(new xmlrpcval($hope_Chapter->name)),'array'),
				'title' => new xmlrpcval($Item->title),
				'description' => new xmlrpcval($content),
				'link' => new xmlrpcval($Item->url),
				'publish' => new xmlrpcval(($Item->status == 'published'),'boolean'),
				'mt_keywords' => new xmlrpcval( implode( ',', $Item->get_tags() ), 'string' ),
				/*
				"permalink" => new xmlrpcval($Item->urltitle),
				"mt_excerpt" => new xmlrpcval($content),
				"mt_allow_comments" => new xmlrpcval('1'),
				"mt_allow_pings" => new xmlrpcval('1'),
				"mt_text_more" => new xmlrpcval('')
				*/
			),'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}



$mwgetpost_doc = 'Fetches a post, blogger-api like';
$mwgetpost_sig = array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * metaweblog.getPost retieves a given post.
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#basicEntrypoints
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function mw_getpost($m)
{
	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 0 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION:
	if( ! xmlrpcs_can_view_item( $edited_Item, $current_User ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


	$post_date = mysql2date( 'U', $edited_Item->issue_date );
	$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);

	$struct = new xmlrpcval(array(
			'link'              => new xmlrpcval( $edited_Item->get_permanent_url()),
			'title'             => new xmlrpcval( $edited_Item->title),
			'description'       => new xmlrpcval( $edited_Item->content),
			'dateCreated'       => new xmlrpcval( $post_date,'dateTime.iso8601'),
			'userid'            => new xmlrpcval( $edited_Item->creator_user_ID),
			'postid'            => new xmlrpcval( $edited_Item->ID),
			'content'           => new xmlrpcval( $edited_Item->content),
			'permalink'         => new xmlrpcval( $edited_Item->get_permanent_url()),
			'categories'        => new xmlrpcval( $edited_Item->main_cat_ID),	// TODO: CATEGORY NAMES!
			'mt_keywords'       => new xmlrpcval( implode( ',', $edited_Item->get_tags() ), 'string' ),
			/*
			'mt_excerpt'        => new xmlrpcval( $edited_Item->excerpt),
			'mt_allow_comments' => new xmlrpcval( $edited_Item->comment_status,'int'), // TODO: convert, looking for doc!!?
			'mt_allow_pings'    => new xmlrpcval( $edited_Item->notifications_status,'int'), // TODO: convert
			'mt_text_more'      => new xmlrpcval( "")	// Doc?
			*/
		),'struct');

	logIO( 'OK.' );
	return new xmlrpcresp($struct);
}




$xmlrpc_procs['metaWeblog.newMediaObject'] = array(
				'function' => 'mw_newmediaobject',
				'signature' => $mwnewMediaObject_sig,
				'docstring' => $mwnewMediaObject_doc);

$xmlrpc_procs['metaWeblog.newPost'] = array(
				'function' => 'mw_newpost',
				'signature' => $mwnewpost_sig,
				'docstring' => $mwnewpost_doc );

$xmlrpc_procs['metaWeblog.editPost'] = array(
				'function' => 'mw_editpost',
				'signature' => $mweditpost_sig,
				'docstring' => $mweditpost_doc );

$xmlrpc_procs['metaWeblog.getPost'] = array(
				'function' => 'mw_getpost',
				'signature' => $mwgetpost_sig,
				'docstring' => $mwgetpost_doc );

$xmlrpc_procs['metaWeblog.getCategories'] = array(
				'function' => 'mw_getcategories',
				'signature' => $mwgetcats_sig,
				'docstring' => $mwgetcats_doc );

$xmlrpc_procs['metaWeblog.getRecentPosts'] = array(
				'function' => 'mw_getrecentposts',
				'signature' => $metawebloggetrecentposts_sig,
				'docstring' => $metawebloggetrecentposts_doc );


/*
 * $Log$
 * Revision 1.15  2009/08/30 15:50:52  waltercruz
 * Adding support for mt_allow_comments
 *
 * Revision 1.14  2009/08/29 13:53:27  tblue246
 * Minor/fixed PHP warning
 *
 * Revision 1.13  2009/08/29 12:23:56  tblue246
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
 * Revision 1.12  2009/08/27 17:46:12  tblue246
 * Metaweblog API: Use mt_keywords for item tags (set/get)
 *
 * Revision 1.11  2009/08/27 16:01:34  tblue246
 * Replaced unnecessary double quotes with single quotes
 *
 * Revision 1.10  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.9  2009/03/03 21:21:10  blueyed
 * Deprecate get_the_category_by_ID and replace its usage with ChapterCache
 * in core.
 *
 * Revision 1.8  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.7  2009/01/28 21:23:23  fplanque
 * Manual ordering of categories
 *
 * Revision 1.6  2009/01/26 00:11:21  fplanque
 * fixing bugs resulting from someone tumble DRYing the code :(
 *
 * Revision 1.5  2009/01/23 00:05:25  blueyed
 * Add Blog::get_sql_where_aggregate_coll_IDs, which adds support for '*' in list of aggregated blogs.
 *
 * Revision 1.4  2008/05/04 23:01:05  blueyed
 * fix fatal phpdoc errors
 *
 * Revision 1.3  2008/04/12 19:58:03  fplanque
 * bugfix
 *
 * Revision 1.2  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.1  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 * Revision 1.7  2008/01/13 19:43:07  fplanque
 * fixed file upload though metaweblog
 *
 * Revision 1.6  2008/01/13 04:07:12  fplanque
 * XML-RPC API debugging
 *
 * Revision 1.5  2008/01/13 03:12:06  fplanque
 * XML-RPC API debugging
 *
 * Revision 1.4  2008/01/12 22:51:11  fplanque
 * RSD support
 *
 * Revision 1.3  2008/01/12 08:12:03  fplanque
 * more xmlrpc tests
 *
 * Revision 1.2  2008/01/12 08:06:15  fplanque
 * more xmlrpc tests
 *
 */
?>
