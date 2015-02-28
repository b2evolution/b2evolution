<?php
/**
 * XML-RPC : Movable Type API (partial)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author tor
 * @author tblue246 (Tilman BLUMENBACH)
 * @author waltercruz
 *
 * @see http://b2evolution.net/man/movabletype-api
 *
 * @package xmlsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );



$mt_supportedMethods_sig = array( array( $xmlrpcArray ) );
$mt_supportedMethods_doc = 'Returns methods supported by the server.';
function mt_supportedMethods()
{
	return new xmlrpcresp( new xmlrpcval( array(
					new xmlrpcval( 'mt.supportedMethods', 'string' ),
					new xmlrpcval( 'mt.setPostCategories', 'string' ),
					new xmlrpcval( 'mt.getPostCategories', 'string' ),
					new xmlrpcval( 'mt.getCategoryList', 'string' ),
					new xmlrpcval( 'mt.publishPost', 'string' ),
				), 'array' ) );
}



$mt_setPostCategories_sig = array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcArray));
$mt_setPostCategories_doc = 'Sets the categories for a post.';
/**
 * mt.setPostCategories : set cats for a post
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post to edit
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 */
function mt_setPostCategories($m)
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

	if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );
	}

	$xcontent = $m->getParam(3); // This is now an array of structs
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO('Decoded xcontent');

	$categories = array();
	$category = NULL;
	foreach( $contentstruct as $catstruct )
	{
		logIO( 'Category ID: '.$catstruct['categoryId'] ) ;
		if( !empty($catstruct['isPrimary']) )
		{
			$category = $catstruct['categoryId'];
			logIO('got primary category and there should only be one... '.$category);
		}
		$categories[] = $catstruct['categoryId'];
	}

	if( empty( $categories ) )
	{
		return xmlrpcs_resperror( 4, 'No categories specified.' );
	}
	else if( empty( $category ) )
	{	// Use first one as default:
		$category = $categories[0];
	}

	// Check if category exists and can be used:
	$Blog = & $edited_Item->get_Blog();
	if( ! xmlrpcs_check_cats( $category, $Blog, $categories ) )
	{	// Error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$edited_Item->status, 'edit', false, $categories ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	logIO( 'Main Cat: '.$category.' - Other: '.implode(',',$categories) );

	// UPDATE POST CATEGORIES IN DB:
	$edited_Item->set( 'main_cat_ID', $category );
	$edited_Item->set( 'extra_cat_IDs', $categories );

	if( $edited_Item->dbupdate() === false )
	{
		logIO( 'Update failed.' );
		return xmlrpcs_resperror( 99, 'Update failed.' );
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( 1, 'boolean' ) );
}



$mt_getPostCategories_sig = array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$mt_getPostCategories_doc = 'Returns a list of all categories to which the post is assigned.';
/**
 * mt.getPostCategories : Get the categories for a given post.
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post to query
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 */
function mt_getPostCategories($m)
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

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 0 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION (user needs to be able to view the post):
	if( ! xmlrpcs_can_view_item( $edited_Item, $current_User ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}

	// Tblue> TODO: We could save one DB query by using our own custom query
	//              instead of postcats_get_byID().
	$categories = postcats_get_byID( $edited_Item->ID ); // Secondary categories
	$iSize = count($categories); // The number of objects ie categories
	logIO('mt_getPostCategories  no. of categories... '.$iSize);// works
	$struct = array();
	for( $i=0; $i<$iSize; $i++)
	{
		logIO('mt_getPostCategories categories  ...'.$categories[$i]);
		// In database cat_ID and cat_name from tablecategories
		$sql = 'SELECT * FROM T_categories WHERE  cat_ID = '.$categories[$i];
		logIO('mt_getPostCategories  sql...'.$sql);
		$rows = $DB->get_results( $sql );
		foreach( $rows as $row )
		{
			$Categoryname =  $row->cat_name;
			logIO('mt_getPostCategories Categoryname  ...'.$Categoryname);
		}

		// Is this the primary cat?
		$isPrimary = ($categories[$i] == $edited_Item->main_cat_ID) ? 1 : 0;

		$struct[$i] = new xmlrpcval(array('categoryId' => new xmlrpcval($categories[$i]),    // Look up name from ID separately
										'categoryName' => new xmlrpcval($Categoryname),
										'isPrimary' => new xmlrpcval($isPrimary, 'boolean'),
										),'struct');
	}

 	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval($struct, 'array') );
}



$mt_getCategoryList_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mt_getCategoryList_doc = 'Get category list';
/**
 * mt.getCategoryList
 *
 * @see http://www.sixapart.com/developers/xmlrpc/movable_type_api/mtgetcategorylist.html
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog to query
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 */
function mt_getCategoryList($m)
{
	logIO('mt_getCategoryList start');
	return _b2_or_mt_get_categories('mt', $m);
}

$mt_publishPost_sig =  array(array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mt_publishPost_doc = 'Published a post';
/**
 * mt.publishPost
 *
 * @see http://www.sixapart.com/developers/xmlrpc/movable_type_api/mtpublishpost.html
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post to publish
 *					1 username (string): Login for a user who is member of the blog.
 *					2 password (string): Password for said username.
 */
function mt_publishPost($m)
{
	global $localtimenow, $DB;

	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}
	logIO( 'mt_publishPost: Login OK' );

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 0 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	if( ! $current_User->check_perm( 'item_post!published', 'edit', false, $edited_Item )
		/*|| ! $current_User->check_perm( 'edit_timestamp' )*/ )
	{
		return xmlrpcs_resperror( 3 ); // Permission denied
	}
	logIO('mt_publishPost: Permission granted');

	logIO( 'mt_publishPost: Old post status: '.$edited_Item->status );
	$edited_Item->set( 'status', 'published' );
	//$edited_Item->set( 'datestart', date('Y-m-d H:i:s', $localtimenow) );

	if( $edited_Item->dbupdate() === false )
	{	// Could not update item...
		return xmlrpcs_resperror( 99, 'Database error: '.$DB->last_error ); // DB error
	}
	logIO('mt_publishPost: Item published.');

	// Execute or schedule notifications & pings:
	logIO( 'mt_publishPost: Handling notifications...' );
	$edited_Item->handle_post_processing( false, false );

	logIO( 'mt_publishPost: OK.' );

	return new xmlrpcresp( new xmlrpcval( 1, 'boolean' ) );
}


$xmlrpc_procs['mt.supportedMethods'] = array(
				'function' => 'mt_supportedMethods',
				'signature' => $mt_supportedMethods_sig,
				'docstring' => $mt_supportedMethods_doc );

$xmlrpc_procs['mt.getCategoryList'] = array(
				'function' => 'mt_getCategoryList',
				'signature' => $mt_getCategoryList_sig,
				'docstring' => $mt_getCategoryList_doc );

$xmlrpc_procs['mt.setPostCategories'] = array(
				'function' => 'mt_setPostCategories',
				'signature' => $mt_setPostCategories_sig,
				'docstring' => $mt_setPostCategories_doc );

$xmlrpc_procs['mt.getPostCategories'] = array(
				'function' => 'mt_getPostCategories',
				'signature' => $mt_getPostCategories_sig,
				'docstring' => $mt_getPostCategories_doc );

$xmlrpc_procs['mt.publishPost'] = array(
				'function' => 'mt_publishPost',
				'signature' => $mt_publishPost_sig,
				'docstring' => $mt_publishPost_doc );


/*
	Missing:

	- mt.supportedTextFilters
	- mt.getTrackbackPings
	- mt.getRecentPostTitles

	http://www.sixapart.com/developers/xmlrpc/movable_type_api/
*/

?>