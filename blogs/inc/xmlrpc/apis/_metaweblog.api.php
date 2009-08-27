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
	global $DB, $xmlrpcerruser;

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

	xmlrpc_debugmsg( 'Categories: '.implode( ', ', $categories ) );

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
		$ChapterCache = & get_Cache('ChapterCache');
		if( $ChapterCache->get_by_ID( $cat_IDs[0], false ) === false )
		{ // Main cat does not exist:
			logIO('usererror 5 ...');
			return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
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
			return new xmlrpcresp(0, $xmlrpcerruser+5, 'No categories for this blog.'); // user error 5
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
	global $xmlrpcerruser; // import user errcode value
	global $Settings, $baseurl,$fileupload_allowedtypes;

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
	// For lack of more subtle perm: require any edit perm on blog + global file add perm.
	if( ! $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID )
		|| ! $current_User->check_perm( 'files', 'add', false ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


	if( ! $Settings->get('upload_enabled') )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				 'Object upload not allowed ');
	}


	$xcontent = $m->getParam(3);


	// Get the main data - and decode it properly for the image - sorry, binary object
	$contentstruct = xmlrpc_decode_recurse($xcontent);

	$data = $contentstruct['bits'];

	$type = $contentstruct['type'];
	logIO( 'Received MIME type: '.$type );

	$rf_filepath = $contentstruct['name'];
	logIO( 'Received filepath: '.$rf_filepath );
	// Avoid problems:
	$rf_filepath = strtolower($rf_filepath);
	$rf_filepath = preg_replace( '¤[^a-z0-9\-_./]¤', '-', $rf_filepath);
	logIO( 'Sanitized filepath: '.$rf_filepath );

 	load_funcs('files/model/_file.funcs.php');

	// Split into path + name:
	$filepath_parts = explode( '/', $rf_filepath );
	$filename = array_pop( $filepath_parts );

	// Check valid filename/extension: (includes check for locked filenames)
	logIO( 'File name: '.$filename );
	if( $error_filename = validate_filename( $filename, false ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+4, // user error 4
			'Invalid objecttype for upload ('.$filename.'): '.$error_filename);
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
			return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
				$error );
		}

		$rds_subpath .= $filepath_part.'/';
	}
	logIO( 'Subpath: '.$rds_subpath );

	$fileupload_path = $Blog->get_media_dir();
	if( ! $fileupload_path )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+5, // user error 5
			'Error accessing Blog media directory.');
	}

	// Create subdirs, if necessary:
	if( !empty($rds_subpath) )
	{
		$fileupload_path = $fileupload_path.$rds_subpath;
		if( ! mkdir_r( $fileupload_path ) )
		{	// Dir didn't already exist and could not be created
			return new xmlrpcresp(0, $xmlrpcerruser+6, // user error 6
				'Error creating sub directories: '.rel_path_to_base($fileupload_path));
		}
	}

	$afs_filepath = $fileupload_path.$filename;
	logIO( 'Saving to: '.$afs_filepath );
	$fh = @fopen( $afs_filepath, 'wb' );
	if( !$fh )
	{
		logIO( 'Error opening file' );
		return new xmlrpcresp(0, $xmlrpcerruser+7, // user error 7
			'Error opening file for writing.');
	}

	$ok = @fwrite($fh, $data);
	@fclose($fh);

	if (!$ok)
	{
		logIO( 'Error writing to file' );
		return new xmlrpcresp(0, $xmlrpcerruser+8, // user error 8
			'Error while writing to file.');
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
 * mw API
 * Tor 2004
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
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings, $Messages;

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

	$post_date = _mw_decode_postdate( $contentstruct, true );
	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $post_title, $content, $post_date, $main_cat, $cat_IDs, $status );
}




$mweditpost_doc='Edits a post, blogger-api like, +title +category +postdate';
$mweditpost_sig =  array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));
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
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 */
function mw_editpost( $m )
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings;
	global $Messages;

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

	if( empty($cat_IDs) )
	{	// TODO: make this finer
		// CHECK PERMISSION: (we need perm on current status)
		if( ! $current_User->check_perm( 'blog_post!'.$status, 'edit', false, $edited_Item->get_blog_ID() ) )
		{	// Permission denied
			return xmlrpcs_resperror( 3 );	// User error 3
		}
		$main_cat = NULL;
	}
	else
	{
		// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
		if( ! $current_User->check_perm( 'cats_post!'.$status, 'edit', false, $cat_IDs ) )
		{	// Permission denied
			return xmlrpcs_resperror( 3 );	// User error 3
		}
		$main_cat = $cat_IDs[0];
	}
	logIO( 'Permission granted.' );



	$post_date = _mw_decode_postdate( $contentstruct, false );
	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];


	// COMPLETE VALIDATION & UPDATE:
	return xmlrpcs_edit_item( $edited_Item, $post_title, $content, $post_date, $main_cat, $cat_IDs, $status );


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




$mwgetcats_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));
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
	global $xmlrpcerruser, $DB, $Settings;

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

	// CHECK PERMISSION: (we need at least one post/edit status)
	if( ! $current_User->check_perm( 'blog_post_statuses', 1, false, $Blog->ID ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


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
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}
	xmlrpc_debugmsg( 'Categories:'.count($rows) );

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
	return new xmlrpcresp( new xmlrpcval($data, "array") );
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
	global $xmlrpcerruser, $DB;

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

	// CHECK PERMISSION: (we need at least one post/edit status)
	if( ! $current_User->check_perm( 'blog_post_statuses', 1, false, $Blog->ID ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


	$numposts = $m->getParam(3);
	$numposts = $numposts->scalarval();
	logIO('In mw_getrecentposts, current numposts is ...'. $numposts);

	// Get the posts to display:
	load_class( 'items/model/_itemlist.class.php' );
	$MainList = & new ItemList2( $Blog, NULL, NULL, $numposts );

	$MainList->set_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated', 'redirected' ),
			'order' => 'DESC',
			'unit' => 'posts',
		) );

	// Run the query:
	$MainList->query();

	xmlrpc_debugmsg( 'Items:'.$MainList->result_num_rows );

	$data = array();
  /**
	 * @var Item
	 */
	while( $Item = & $MainList->get_item() )
	{
		xmlrpc_debugmsg( 'Item:'.$Item->title.
											' - Issued: '.$Item->issue_date.
											' - Modified: '.$Item->mod_date );
		$post_date = mysql2date('U', $Item->issue_date);
		$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);
		$content = $Item->content;
		$content = str_replace("\n",'',$content); // Tor - kludge to fix bug in xmlrpc libraries
		// Load Item's creator User:
		$Item->get_creator_User();
		$authorname = $Item->creator_User->get('preferredname');
		// need a loop here to extract all categoy names
		// $extra_cat_IDs is the variable for the rest of the IDs
		$hope_Chapter = & $Item->get_main_Chapter();
		$test = $Item->extra_cat_IDs[0];
		xmlrpc_debugmsg( 'postcats:'.$hope_Chapter->name );
		xmlrpc_debugmsg( 'test:'.$test);
		$data[] = new xmlrpcval(array(
				'dateCreated' => new xmlrpcval($post_date,'dateTime.iso8601'),
				'userid' => new xmlrpcval($Item->creator_user_ID),
				'postid' => new xmlrpcval($Item->ID),
				'categories' => new xmlrpcval(array(new xmlrpcval($hope_Chapter->name)),'array'),
				'title' => new xmlrpcval($Item->title),
				'description' => new xmlrpcval($content),
				'link' => new xmlrpcval($Item->url),
				'publish' => new xmlrpcval(($Item->status == 'published'),'boolean'),
				/*
				"permalink" => new xmlrpcval($Item->urltitle),
				"mt_excerpt" => new xmlrpcval($content),
				"mt_allow_comments" => new xmlrpcval('1'),
				"mt_allow_pings" => new xmlrpcval('1'),
				"mt_text_more" => new xmlrpcval('')
				*/
			),'struct');
	}
	$resp = new xmlrpcval($data, 'array');

	logIO( 'OK.' );
	return new xmlrpcresp($resp);
}



$mwgetpost_doc = 'Fetches a post, blogger-api like';
$mwgetpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
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
	global $xmlrpcerruser;

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

	// CHECK PERMISSION: (we need at least one post/edit status)
	if( ! $current_User->check_perm( 'blog_post_statuses', 1, false, $edited_Item->get_blog_ID() ) )
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
			/*
			'mt_excerpt'        => new xmlrpcval( $edited_Item->excerpt),
			'mt_allow_comments' => new xmlrpcval( $edited_Item->comment_status,'int'), // TODO: convert, looking for doc!!?
			'mt_allow_pings'    => new xmlrpcval( $edited_Item->notifications_status,'int'), // TODO: convert
			'mt_text_more'      => new xmlrpcval( "")	// Doc?
			*/
		),'struct');
	$resp = $struct;

	logIO( 'OK.' );
	return new xmlrpcresp($resp);
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
