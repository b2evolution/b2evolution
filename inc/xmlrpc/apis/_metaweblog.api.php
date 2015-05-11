<?php
/**
 * XML-RPC : MetaWeblog API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author tor
 *
 * @see http://b2evolution.net/man/metaweblog-api
 * @see http://www.xmlrpc.com/metaWeblogApi
 *
 * @package xmlsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$mwnewMediaObject_doc = 'Uploads a file to the media library of the blog';
$mwnewMediaObject_sig = array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString, $xmlrpcStruct),
		array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString, $xmlrpcStruct)
	);
/**
 * metaWeblog.newMediaObject  image upload
 *
 * image is supplied coded in the info struct as bits
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metaweblognewmediaobject
 *
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
	return _wp_mw_newmediaobject( $m );
}


$mwnewpost_doc='Adds a post, blogger-api like, +title +category +postdate';
$mwnewpost_sig = array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct),
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean),
		array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean)
	);
/**
 * metaWeblog.newPost
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 * 					4 publish (bool)
 * @param string post type 'post' or 'page'
 */
function mw_newpost($m, $item_type = 'post' )
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

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO( 'Decoded xcontent' );

	$status = 'published';
	if( isset($m->params[4]) )
	{	// getParam(4) is a flag for publish or draft
		$xstatus = $m->getParam(4);
		$xstatus->scalarval();
		$status = $xstatus ? 'published' : 'draft'; // might be overrided later
	}

	$cat_IDs = _mw_get_cat_IDs( $contentstruct, $Blog );
	$date = _mw_decode_date( $contentstruct );

	if( !empty($contentstruct['post_type']) && $contentstruct['post_type'] != $item_type )
	{	// Overwrite from struct
		$item_type = $contentstruct['post_type'];
	}

	$tags = isset($contentstruct['mt_keywords']) ? $contentstruct['mt_keywords'] : '';
	$content = isset($contentstruct['description']) ? $contentstruct['description'] : '';
	$excerpt = isset($contentstruct['mt_excerpt']) ? $contentstruct['mt_excerpt'] : '';
	$urltitle = isset($contentstruct['wp_slug']) ? $contentstruct['wp_slug'] : '';
	$featured = isset($contentstruct['sticky']) ? $contentstruct['sticky'] : 0;
	$order = isset($contentstruct['wp_page_order']) ? $contentstruct['wp_page_order'] : 0;
	$custom_fields = isset($contentstruct['custom_fields']) ? $contentstruct['custom_fields'] : '';
	$parent_ID = isset($contentstruct['wp_page_parent_id']) ? $contentstruct['wp_page_parent_id'] : '';

	$item_typ_ID = isset($contentstruct['wp_post_format']) ? $contentstruct['wp_post_format'] : 1;
	if( $item_type == 'page' )
	{	// Force post type 'page'
		$item_typ_ID = 1000;
	}

	if( !empty( $contentstruct[$item_type.'_status'] ) )
	{	// Use WP status
		$status = wp_or_b2evo_item_status( $contentstruct[$item_type.'_status'], 'b2evo' );
	}

	if( !empty($content_struct['mt_text_more']) )
	{	// Add content extension
		$content .= '[teaserbreak]'.$content_struct['mt_text_more'];
	}
	//logIO( "Item content:\n".$content );

	if( !empty($content_struct['enclosure']) && is_array($content_struct['enclosure']) )
	{	// Add content extension
		$enclosure = $content_struct['enclosure'];
		if( isset($enclosure['url']) && isset($enclosure['length']) && isset($enclosure['type']) )
		{
			logIO( "Item enclosure\n".var_export($enclosure, true) );
			// TODO: sam2kb> Handle enclosures
		}
	}

	$comment_status = 'open';
	if( isset($contentstruct['mt_allow_comments']) )
	{
		if( ! $contentstruct['mt_allow_comments'] || in_array( $contentstruct['mt_allow_comments'], array(2,'closed') ) )
		{	// Comments disabled
			$comment_status = 'disabled';
		}
	}

	$params = array(
			'title'				=> $contentstruct['title'],
			'content'			=> $content,
			'cat_IDs'			=> $cat_IDs,
			'status'			=> $status,
			'date'				=> $date,
			'tags'				=> $tags,
			'excerpt'			=> $excerpt,
			'item_typ_ID'		=> $item_typ_ID,
			'comment_status'	=> $comment_status,
			'urltitle'			=> $urltitle,
			'featured'			=> $featured,
			'order'				=> $order,
			'parent_ID'			=> $parent_ID,
			'custom_fields'		=> $custom_fields,
		);

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $params, $Blog );
}


$mweditpost_doc='Edits a post, blogger-api like, +title +category +postdate';
$mweditpost_sig = array(
		array($xmlrpcBoolean,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct),
		array($xmlrpcBoolean,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean),
		array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean),
	);
/**
 * metaWeblog.editPost
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#basicEntrypoints
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post to edit
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 *					4 publish (bool)
 * @param string post type 'post' or 'page'
 */
function mw_editpost( $m, $item_type = 'post' )
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

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO('Decoded xcontent');

	if( isset($m->params[4]) )
	{	// getParam(4) is a flag for publish or draft
		$xstatus = $m->getParam(4);
		$xstatus = $xstatus->scalarval();
		$status = $xstatus ? 'published' : 'draft'; // might be overrided later
	}

	$cat_IDs = _mw_get_cat_IDs( $contentstruct, $edited_Item->get_Blog(), true /* empty is ok */ );
	$date = _mw_decode_date( $contentstruct );

	if( !empty($contentstruct['post_type']) && $contentstruct['post_type'] != $item_type )
	{	// Overwrite from struct
		$item_type = $contentstruct['post_type'];
	}

	// Don't overwrite if not set
	$tags = isset($contentstruct['mt_keywords']) ? $contentstruct['mt_keywords'] : NULL;
	$content = isset($contentstruct['description']) ? $contentstruct['description'] : NULL;
	$excerpt = isset($contentstruct['mt_excerpt']) ? $contentstruct['mt_excerpt'] : NULL;
	$urltitle = isset($contentstruct['wp_slug']) ? $contentstruct['wp_slug'] : NULL;
	$parent_ID = isset($contentstruct['wp_page_parent_id']) ? $contentstruct['wp_page_parent_id'] : NULL;
	$author_ID = isset($contentstruct['wp_author_id']) ? $contentstruct['wp_author_id'] : NULL;
	$featured = isset($contentstruct['sticky']) ? $contentstruct['sticky'] : NULL;
	$order = isset($contentstruct['wp_page_order']) ? $contentstruct['wp_page_order'] : NULL;
	$custom_fields = isset($contentstruct['custom_fields']) ? $contentstruct['custom_fields'] : NULL;
	$item_typ_ID = isset($contentstruct['wp_post_format']) ? $contentstruct['wp_post_format'] : NULL;

	if( isset($contentstruct[$item_type.'_status']) )
	{
		$status = wp_or_b2evo_item_status( $contentstruct[$item_type.'_status'], 'b2evo' );
	}

	if( isset($content_struct['mt_text_more']) )
	{	// Add content extension
		$content .= '[teaserbreak]'.$content_struct['mt_text_more'];
	}
	//logIO( "Item content:\n".$content );

	if( !empty($content_struct['enclosure']) && is_array($content_struct['enclosure']) )
	{	// Add content extension
		$enclosure = $content_struct['enclosure'];
		if( isset($enclosure['url']) && isset($enclosure['length']) && isset($enclosure['type']) )
		{
			logIO( "Item enclosure\n".var_export($enclosure, true) );
			// TODO: sam2kb> Handle enclosures
		}
	}

	$comment_status = ''; // Don't overwrite if not set
	if( isset($contentstruct['mt_allow_comments']) )
	{
		if( ! $contentstruct['mt_allow_comments'] || in_array( $contentstruct['mt_allow_comments'], array(2,'closed') ) )
		{	// Comments disabled
			$comment_status = 'disabled';
		}
		else
		{
			$comment_status = 'open';
		}
	}

	$params = array(
			'title'				=> $contentstruct['title'],
			'content'			=> $content,
			'cat_IDs'			=> $cat_IDs,
			'status'			=> $status,
			'date'				=> $date,
			'tags'				=> $tags,
			'excerpt'			=> $excerpt,
			'item_typ_ID'		=> $item_typ_ID,
			'comment_status'	=> $comment_status,
			'urltitle'			=> $urltitle,
			'parent_ID'			=> $parent_ID,
			'author_ID'			=> $author_ID,
			'featured'			=> $featured,
			'order'				=> $order,
			'custom_fields'		=> $custom_fields,
		);

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_edit_item( $edited_Item, $params );


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


$mwgetcats_sig =  array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString),
		array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString)
	);
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
	return _wp_mw_getcategories ( $m ) ;
}


$metawebloggetrecentposts_doc = 'fetches X most recent posts, blogger-api like';
$metawebloggetrecentposts_sig = array(
		array($xmlrpcArray,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt),
		array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt),
	);
/**
 * metaWeblog.getRecentPosts
 *
 * @see http://xmlrpc.scripting.com/metaWeblogApi.html#metawebloggetrecentposts
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 numposts (integer): number of posts to retrieve.
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

	$limit = $m->getParam(3);
	$limit = abs($limit->scalarval());

	$items = xmlrpc_get_items( array(
			'limit' => $limit,
		), $Blog );

	if( empty($items) )
	{
		return new xmlrpcresp( new xmlrpcval( array(), 'array' ) );
	}

	$data = array();
	foreach( $items as $item )
	{
		$data[] = new xmlrpcval( $item, 'struct' );
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}


$mwgetusersblogs_doc = 'returns the user\'s blogs - this is a dummy function, just so that BlogBuddy and other blogs-retrieving apps work';
$mwgetusersblogs_sig = array(
		array($xmlrpcArray,$xmlrpcInt,$xmlrpcString,$xmlrpcString),
		array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString)
	);
/**
 * metaweblog.getUsersBlogs returns information about all the blogs a given user is a member of.
 *
 * Data is returned as an array of <struct>s containing the ID (blogid), name (blogName),
 * and URL (url) of each blog.
 *
 * Non official: Also return a boolean stating wether or not the user can edit th eblog templates
 * (isAdmin).
 *
 * @see http://www.xmlrpc.com/stories/storyReader$2460
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 username (string): Login for the Blogger user who's blogs will be retrieved.
 *					2 password (string): Password for said username.
 *						(currently not required by b2evo)
 * @return xmlrpcresp XML-RPC Response, an array of <struct>s containing for each blog:
 *					- ID (blogid),
 *					- name (blogName),
 *					- URL (url),
 *					- bool: can user edit template? (isAdmin).
 */
function mw_getusersblogs($m)
{
	return _wp_or_blogger_getusersblogs( 'blogger', $m );
}


$mwgetpost_doc = 'Fetches a post, blogger-api like';
$mwgetpost_sig = array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString),
		array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString),
	);
/**
 * metaWeblog.getPost retieves a given post.
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

	$item = _wp_mw_get_item_struct($edited_Item);

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $item, 'struct' ) );
}


$mwdeletepost_doc = 'Deletes a post, blogger-api like';
$mwdeletepost_sig = array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
/**
 * metaWeblog.deletePost deletes a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 * @see http://www.xmlrpc.com/stories/storyReader$2460
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
function mw_deletepost($m)
{
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

	return xmlrpcs_delete_item( $edited_Item );
}


$xmlrpc_procs['metaWeblog.newMediaObject'] = array( // OK
				'function' => 'mw_newmediaobject',
				'signature' => $mwnewMediaObject_sig,
				'docstring' => $mwnewMediaObject_doc);

$xmlrpc_procs['metaWeblog.newPost'] = array( // Incomplete (minor): handle 'enclosure', 'custom_fields' in WP
				'function' => 'mw_newpost',
				'signature' => $mwnewpost_sig,
				'docstring' => $mwnewpost_doc );

$xmlrpc_procs['metaWeblog.editPost'] = array( // Incomplete (minor): handle 'enclosure', 'custom_fields' in WP
				'function' => 'mw_editpost',
				'signature' => $mweditpost_sig,
				'docstring' => $mweditpost_doc );

$xmlrpc_procs['metaWeblog.getPost'] = array( // OK
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

// Blogger aliases, as in http://www.xmlrpc.com/stories/storyReader$2460

$xmlrpc_procs['metaWeblog.deletePost'] = array(
				'function' => 'mw_deletepost',
				'signature' => $mwdeletepost_sig,
				'docstring' => $mwdeletepost_doc );

$xmlrpc_procs['metaWeblog.getUsersBlogs'] = array(
				'function' => 'mw_getusersblogs',
				'signature' => $mwgetusersblogs_sig,
				'docstring' => $mwgetusersblogs_doc );

?>