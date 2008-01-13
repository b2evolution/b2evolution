<?php
/**
 * XML-RPC : MetaWeblog API
 *
 * @see http://manual.b2evolution.net/MetaWeblog_API
 * @see http://www.xmlrpc.com/metaWeblogApi
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author tor
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
 *
 * @param array struct
 * @param integer blog ID
 * @param boolean Return empty array (instead of error), if no cats given in struct?
 * @return array|xmlrpcresp A list of category IDs or xmlrpcresp in case of error.
 */
function _mw_get_cat_IDs($contentstruct, $blog_ID, $empty_struct_ok = false)
{
	global $DB, $xmlrpcerruser;

	if( isset($contentstruct['categories']) )
	{
		$categories = $contentstruct['categories'];
	}
	else
	{
		$categories = array();
	}
	logIO("finished getting contentstruct categories...".implode( ', ', $categories ) );

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
		logIO("sql for finding IDs ...".$sql);

		$cat_IDs = $DB->get_col( $sql );
		if( $DB->error )
		{	// DB error
			logIO("user error finding categories info ...");
		}
	}
	else
	{
		$cat_IDs = array();
	}

	if( ! empty($cat_IDs) )
	{ // categories requested to be set:

		// Check if category exists
		if( get_the_category_by_ID( $cat_IDs[0], false ) === false )
		{ // Main cat does not exist:
			logIO("usererror 5 ...");
			return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
		}
		logIO("finished checking if main category exists ...".$cat_IDs[0]);
	}
	else
	{ // No category given/valid - use the first for the blog:
		logIO("No category for post given ...");

		$first_cat = $DB->get_var( '
			SELECT cat_ID
			  FROM T_categories
			 WHERE cat_blog_ID = '.$blog_ID.'
			 ORDER BY cat_name
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
$mwnewMediaObject_sig = array(array(
	$xmlrpcStruct,		// RETURN "url" element
	$xmlrpcString,		// PARAMS blogid
	$xmlrpcString,		// username
	$xmlrpcString,		// password
	$xmlrpcStruct		  // 'name', 'type' and 'bits'
));
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
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					2 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					3 password (string): Password for said username.
 *					4 numposts (integer): number of posts to retrieve.
 * @return xmlrpcresp XML-RPC Response
 */
function mw_newmediaobject($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $Settings, $baseurl,$fileupload_allowedtypes;

	logIO("start of _newmediaobject...");
	$blog = $m->getParam(0);
	$blog = $blog->scalarval();
	$username = $m->getParam(1);
	$username = $username->scalarval();
	$password = $m->getParam(2);
	$password = $password->scalarval();
	if( !user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
				 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	if( ! $Settings->get('upload_enabled') )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
				 'Object upload not allowed ');
	}
	$BlogCache = & get_Cache('BlogCache');
	$Blog = & $BlogCache->get_by_ID($blog);

	// Get the main data - and decode it properly for the image - sorry, binary object
	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO( 'Got first contentstruct!'."\n");

	// This call seems to go wrong from Marsedit under certain circumstances - Tor 04012005
	$data = $contentstruct['bits']; // decoding was done transparantly by xmlrpclibs xmlrpc_decode
	logIO( 'Have decoded data data?'."\n");

	// TODO: check filesize
	$filename = $contentstruct['name'];
	logIO( 'Found filename ->'. $filename ."\n");
	$type = $contentstruct['type'];
	logIO( 'Done type ->'. $type ."\n");
	$data = $contentstruct['bits'];
	logIO( 'Done bits ' ."\n");

	// Split into path + name:
	$filepath = dirname($filename);
	$filename = basename($filename);

	$filepath_parts = explode('/', $filepath);
	if( in_array('..', $filepath_parts) )
	{ // invalid relative path:
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Invalid relative part in file path: '.$filepath);
	}

	// Check valid filename/extension:
	load_funcs('files/model/_file.funcs.php');
	if( $error_filename = validate_filename($filename) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Invalid objecttype for upload ('.$filename.'): '.$error_filename);
	}

	$fileupload_path = $Blog->get_media_dir();
	if( ! $fileupload_path )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Error accessing Blog media directory.');
	}

	// Handle subdirs, if any:
	if( strlen($filepath) && $filepath != '.' )
	{
		$fileupload_path .= $filepath;
		if( ! mkdir_r(dirname($fileupload_path)) )
		{
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
				'Error creating sub directories: '.rel_path_to_base($fileupload_path));
		}
	}

	logIO( 'fileupload_path ->'. $fileupload_path ."\n");
	$fh = @fopen($fileupload_path.$filename, 'wb');
	logIO( 'Managed to open file ->'. $filename ."\n");
	if (!$fh)
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Error opening file for writing.');
	}

	logIO( 'Managed to open file for writing ->'. $fileupload_path.$filename."\n");
	$ok = @fwrite($fh, $data);
	@fclose($fh);

	if (!$ok)
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Error while writing to file.');
	}

	// chmod uploaded file:
	$oldumask = umask(0000);
	$chmod = $Settings->get('fm_default_chmod_file');
	@chmod($fileupload_path.$filename, octdec( $chmod ));
	umask($oldumask);

	$url = $Blog->get_media_url().$filepath.$filename;
	logIO( 'Full returned filename ->'. $fileupload_path . '/' . $filename ."\n");
	logIO( 'Full returned url ->'. $url ."\n");

	// - return URL as XML
	$urlstruct = new xmlrpcval(array(
			'url' => new xmlrpcval($url, 'string')
		), 'struct');
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
 */
function mw_newpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings;

	logIO("start of mw_newpost...");

	$blog_ID = $m->getParam(0);
	$blog_ID = $blog_ID->scalarval();

	$username = $m->getParam(1);
	$username = $username->scalarval();
	logIO("finished getting username ...");

	$password = $m->getParam(2);
	$password = $password->scalarval();
	logIO("finished getting password ...".starify($password));

	$xcontent = $m->getParam(3);
	logIO("finished getting xcontent ...");
	xmlrpc_debugmsg( 'Getting xcontent'  );

	// getParam(4) should now be a flag for publish or draft
	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO("Publish: $xstatus -> Status: $status");

	if( ! user_pass_ok($username,$password) )
	{
	logIO("error during checking password ...");
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO( 'Decoded xcontent' );

	$post_title = $contentstruct['title'];
	//	$content = format_to_post($contentstruct['description']);
	$content = $contentstruct['description'];

	// Categories:
	$cat_IDs = _mw_get_cat_IDs($contentstruct, $blog_ID);

	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;	// This can be a preformatted error message
	}

	// Check permission:
	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );
	logIO("currentuser ...". $current_User->ID);
	if( ! $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog_ID ) )
	{
		logIO("user error 9 ...");
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}
	logIO("finished checking permissions ...");

	$post_date = _mw_decode_postdate( $contentstruct, true );

	// CHECK and FORMAT content - error occur after this line
	//$post_title = format_to_post($post_title, 0, 0);
	//logIO("finished converting post_title ...",$post_title);

	//$content = format_to_post($content, 0, 0);  // 25122004 tag - security !!!
	//logIO("finished converting content ...".$content); // error occurs before this line

	//	if( $errstring = $Messages->get_string( 'Cannot post, please correct these errors:', '' ) )
	//	{
	//		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	//	}
	//logIO("finished checking if errors exists, ready to insert into DB ...");

	// INSERT NEW POST INTO DB:
	// Tor - comment this out to stop inserts into database
	$edited_Item = & new Item();

	$post_ID = $edited_Item->insert( $current_User->ID, $post_title, $content, $post_date, $cat_IDs[0], $cat_IDs, $status, $current_User->locale );

	if( $DB->error )
	{	// DB error
		logIO("user error 9 ...");
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	logIO( 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	return new xmlrpcresp(new xmlrpcval($post_ID));
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
 */
function mw_editpost( $m )
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings;
	global $Messages;
	global $xmlrpc_htmlchecking;

	$post_ID = $m->getParam(0);
	$post_ID = $post_ID->scalarval();

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();

	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO("Decoded xcontent");

	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO("Publish: $xstatus -> Status: $status");

	// Get Item:
	$ItemCache = & get_Cache( 'ItemCache' );
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}

	// Check permission:
	$UserCache = & get_Cache( 'UserCache' );
	$User = & $UserCache->get_by_login( $username );
	logIO('User ID ...'.$User->ID);
	if( ! $User->check_perm( 'blog_post_statuses', $status, false, $edited_Item->blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}
	logIO("finished checking permissions ...");


	$post_date = _mw_decode_postdate( $contentstruct, false );

	// Categories:
	$cat_IDs = _mw_get_cat_IDs( $contentstruct, $edited_Item->blog_ID, true /* empty is ok */ );
	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;
	}

	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];
	logIO("finished getting title ...".$post_title);


	if( ! empty($xmlrpc_htmlchecking) )
	{ // CHECK and FORMAT content
		$post_title = format_to_post($post_title, 0, 0);
	}
	logIO("finished converting post_title ...->".$post_title);
	if( ! empty($xmlrpc_htmlchecking) )
	{
		$content = format_to_post($content, 0, 0);  // 25122004 tag - security issue - need to sort !!!
	}
	logIO("finished converting content ...".$content);
	if( $errstring = $Messages->get_string( 'Cannot post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}
	logIO("finished checking if errors exists, ready to insert into DB ...");
	xmlrpc_debugmsg( 'post_ID: '.$post_ID  );

	// UPDATE POST IN DB:
	$edited_Item->set( 'title', $post_title );
	$edited_Item->set( 'content', $content );
	$edited_Item->set( 'status', $status );
	if(  empty($post_date) )
	{
		$edited_Item->set( 'issue_date', $post_date );
	}
	if( !empty($cat_IDs) )
	{ // Update cats:
		$edited_Item->set('main_cat_ID', $cat_IDs[0]);

		if( count($cat_IDs) > 1 )
		{ // Extra-Cats:
			$edited_Item->set('extra_cat_IDs', $cat_IDs);
		}
	}
	$edited_Item->dbupdate();
	if( $DB->error )
	{	// DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

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

	return new xmlrpcresp( new xmlrpcval( 1, 'boolean' ) );
}




$mwgetcats_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mwgetcats_doc = 'Get categories of a post, MetaWeblog API-style';
/**
 * metaWeblog.getCategories
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metawebloggetcategories
 */
function mw_getcategories( $m )
{
	global $xmlrpcerruser, $DB;

	$blog = $m->getParam(0);
	$blog = $blog->scalarval();

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();

	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	$sql = "SELECT cat_ID, cat_name
					FROM T_categories ";

	$BlogCache = & get_Cache('BlogCache');
	$current_Blog = $BlogCache->get_by_ID( $blog );
	$aggregate_coll_IDs = $current_Blog->get_setting('aggregate_coll_IDs');
	if( empty( $aggregate_coll_IDs ) )
	{	// We only want posts from the current blog:
		$sql .= 'WHERE cat_blog_ID ='.$current_Blog->ID;
	}
	else
	{	// We are aggregating posts from several blogs:
		$sql .= 'WHERE cat_blog_ID IN ('.$aggregate_coll_IDs.')';
	}

	$sql .= " ORDER BY cat_name ASC";
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
			),"struct");
	}
	return new xmlrpcresp( new xmlrpcval($data, "array") );
}




$metawebloggetrecentposts_doc = 'fetches X most recent posts, blogger-api like';
$metawebloggetrecentposts_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * metaWeblog.getRecentPosts
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metawebloggetrecentposts
 */
function mw_getrecentposts( $m )
{
	global $xmlrpcerruser, $DB;

	$blog_ID = $m->getParam(0);
	$blog_ID = $blog_ID->scalarval();
	logIO("In mw_getrecentposts, current blog_id is ...". $blog_ID);

	$username = $m->getParam(1);
	$username = $username->scalarval();
	logIO("In mw_getrecentposts, current username is ...". $username);

	$password = $m->getParam(2);
	$password = $password->scalarval();

	$numposts = $m->getParam(3);
	$numposts = $numposts->scalarval();
	logIO("In mw_getrecentposts, current numposts is ...". $numposts);

	if( ! user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	logIO("In mw_getrecentposts, user and pass ok...");

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );
	logIO( 'In mw_getrecentposts, current user is ...'.$current_User->ID );

	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) )
	{	// No perm to list posts:
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.' ); // user error 2
	}
	logIO("In mw_getrecentposts, permissions ok...");

	logIO("In mw_getrecentposts, current blog is ...". $blog_ID);
	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = & $BlogCache->get_by_ID( $blog_ID );

	// Get the posts to display:
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
		$post_date = mysql2date("U", $Item->issue_date);
		$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);
		$content = $Item->content;
		$content = str_replace("\n",'',$content); // Tor - kludge to fix bug in xmlrpc libraries
		// Load Item's creator User:
		$Item->get_creator_User();
		$authorname = $Item->creator_User->get('preferredname');
		// need a loop here to extract all categoy names
		// $extra_cat_IDs is the variable for the rest of the IDs
		$hope_cat_name = get_the_category_by_ID($Item->main_cat_ID);
		$test = $Item->extra_cat_IDs[0];
		xmlrpc_debugmsg( 'postcats:'.$hope_cat_name["cat_name"]);
		xmlrpc_debugmsg( 'test:'.$test);
		$data[] = new xmlrpcval(array(
				"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
				"userid" => new xmlrpcval($Item->creator_user_ID),
				"postid" => new xmlrpcval($Item->ID),
				"categories" => new xmlrpcval(array(new xmlrpcval($hope_cat_name["cat_name"])),'array'),
				"title" => new xmlrpcval($Item->title),
				"description" => new xmlrpcval($content),
				"link" => new xmlrpcval($Item->url),
				'publish' => new xmlrpcval(($Item->status == 'published'),'boolean'),
				/*
				"permalink" => new xmlrpcval($Item->urltitle),
				"mt_excerpt" => new xmlrpcval($content),
				"mt_allow_comments" => new xmlrpcval('1'),
				"mt_allow_pings" => new xmlrpcval('1'),
				"mt_text_more" => new xmlrpcval('')
				*/
			),"struct");
	}
	$resp = new xmlrpcval($data, "array");
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
 *					0 postid (string): Unique identifier of the post to be deleted.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function mw_getpost($m)
{
	global $xmlrpcerruser;

	$post_ID = $m->getParam(0);
	$post_ID = $post_ID->scalarval();

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();

	if( ! user_pass_ok( $username, $password ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$ItemCache = & get_Cache( 'ItemCache' );
  /**
	 * @var Item
	 */
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID, false ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, 'No such post.');	// user error 7
	}

	$post_date = mysql2date( "U", $edited_Item->issue_date );
	$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);

	$struct = new xmlrpcval(array(
			'link'              => new xmlrpcval($edited_Item->get_permanent_url()),
			'title'             => new xmlrpcval($edited_Item->title),
			'description'       => new xmlrpcval($edited_Item->content),
			'dateCreated'       => new xmlrpcval($post_date,"dateTime.iso8601"),
			'userid'            => new xmlrpcval($edited_Item->creator_user_ID),
			'postid'            => new xmlrpcval($edited_Item->ID),
			'content'           => new xmlrpcval($edited_Item->content),
			'permalink'         => new xmlrpcval($edited_Item->get_permanent_url()),
			'categories'        => new xmlrpcval($edited_Item->main_cat_ID),	// TODO: CATEGORY NAMES!
			/*
			'mt_excerpt'        => new xmlrpcval($edited_Item->excerpt),
			'mt_allow_comments' => new xmlrpcval($edited_Item->comment_status,'int'), // TODO: convert, looking for doc!!?
			'mt_allow_pings'    => new xmlrpcval($edited_Item->notifications_status,'int'), // TODO: convert
			'mt_text_more'      => new xmlrpcval("")	// Doc?
			*/
		),"struct");
	$resp = $struct;

	return new xmlrpcresp($resp);
}




$xmlrpc_procs["metaWeblog.newMediaObject"] = array(
				"function" => "mw_newmediaobject",
				"signature" => $mwnewMediaObject_sig,
				"docstring" => $mwnewMediaObject_doc);

$xmlrpc_procs["metaWeblog.newPost"] = array(
				"function" => "mw_newpost",
				"signature" => $mwnewpost_sig,
				"docstring" => $mwnewpost_doc );

$xmlrpc_procs["metaWeblog.editPost"] = array(
				"function" => "mw_editpost",
				"signature" => $mweditpost_sig,
				"docstring" => $mweditpost_doc );

$xmlrpc_procs["metaWeblog.getPost"] = array(
				"function" => "mw_getpost",
				"signature" => $mwgetpost_sig,
				"docstring" => $mwgetpost_doc );

$xmlrpc_procs["metaWeblog.getCategories"] = array(
				"function" => "mw_getcategories",
				"signature" => $mwgetcats_sig,
				"docstring" => $mwgetcats_doc );

$xmlrpc_procs["metaWeblog.getRecentPosts"] = array(
				"function" => "mw_getrecentposts",
				"signature" => $metawebloggetrecentposts_sig,
				"docstring" => $metawebloggetrecentposts_doc );


/*
 * $Log$
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