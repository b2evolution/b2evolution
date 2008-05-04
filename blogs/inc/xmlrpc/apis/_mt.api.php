<?php
/**
 * XML-RPC : Movable Type API (partial)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author tor
 *
 * @see http://manual.b2evolution.net/MovableType_API
 *
 * @package xmlsrv
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );



$mt_setPostCategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcArray));
$mt_setPostCategories_doc = "Sets the categories for a post.";
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
	global $xmlrpcerruser,$Settings;
	global $DB, $Messages;

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

 	$xcontent = $m->getParam(3); // This is now an array of structs
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO("Decoded xcontent");

	$categories = array();
	$category = NULL;
	foreach( $contentstruct as $catstruct )
	{
		logIO( 'Category ID: '.$catstruct['categoryId'] ) ;
		if( !empty($catstruct['isPrimary']) )
		{
			logIO("got primary category and there should only be one...".$tempcat);
			$category = $catstruct['categoryId'];
		}
		$categories[] = $catstruct['categoryId'];
	}

	if( empty( $categories ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+4, // user error 4
					 'No categories specified.');
	}

	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$edited_Item->status, 'edit', false, $categories ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


	if( empty( $category ) )
	{	// Use first one as default:
		$category = $categories[0];
	}

	logIO( 'Main Cat: '.$category.' - Other: '.implode(',',$categories) );

	// UPDATE POST CATEGORIES IN DB:
	$edited_Item->set( 'main_cat_ID', $category );
	$edited_Item->set( 'extra_cat_IDs', $categories );

	if( $edited_Item->dbupdate() === false )
	{
		logIO( 'Update failed.' );
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
					 'Update failed.');
	}

	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval(1));
}



$mt_getPostCategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$mt_getPostCategories_doc = "Returns a list of all categories to which the post is assigned.";
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
	global $xmlrpcerruser;
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

	// CHECK PERMISSION: (we need at least one post/edit status)
	if( ! $current_User->check_perm( 'blog_post_statuses', 1, false, $edited_Item->blog_ID ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


	$categories = postcats_get_byID( $edited_Item->ID ); // Secondary categories
	$iSize = count($categories); // The number of objects ie categories
	logIO("mt_getgategorylist  no of categories...".$iSize);// works
	$struct = array();
	for( $i=0; $i<$iSize; $i++)
	{
		logIO("mt_getPostCategories categories  ...".$categories[$i]);
		// In database cat_ID and cat_name from tablecategories
		$sql = "SELECT * FROM T_categories WHERE  cat_ID = $categories[$i] ";
		logIO("mt_getgategorylist  sql...".$sql);
		$rows = $DB->get_results( $sql );
		foreach( $rows as $row )
		{
			$Categoryname =  $row->cat_name;
			logIO("mt_getPostCategories Categoryname  ...".$Categoryname);
		}

		// Is this the primary cat?
		$isPrimary = ($categories[$i] == $edited_Item->main_cat_ID) ? 1 : 0;

		$struct[$i] = new xmlrpcval(array("categoryId" => new xmlrpcval($categories[$i]),    // Look up name from ID separately
										"categoryName" => new xmlrpcval($Categoryname),
										"isPrimary" => new xmlrpcval($isPrimary)
										),"struct");
	}

 	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval($struct, "array") );
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
	logIO("mt_getCategoryList start");
	return _b2_or_mt_get_categories('mt', $m);
}


/*
 *  mt.supportedMethods
 *  mt.supportedTextFilters
 *  mt.publishPost
 * *mt.getCategoryList
 * *mt.getPostCategories
 * *mt.setPostCategories
 *  mt.getRecentPostTitles
 *  mt.getTrackbackPings
 */

$xmlrpc_procs["mt.getCategoryList"] = array(
				"function" => "mt_getCategoryList",
				"signature" => $mt_getCategoryList_sig,
				"docstring" => $mt_getCategoryList_doc );

$xmlrpc_procs["mt.setPostCategories"] = array(
				"function" => "mt_setPostCategories",
				"signature" => $mt_setPostCategories_sig,
				"docstring" => $mt_setPostCategories_doc );

$xmlrpc_procs["mt.getPostCategories"] = array(
				"function" => "mt_getPostCategories",
				"signature" => $mt_getPostCategories_sig,
				"docstring" => $mt_getPostCategories_doc );


/*
	Missing:

	- mt.supportedTextFilters
	- mt.supportedMethods
  - mt.publishPost
  - mt.getTrackbackPings
  - mt.getRecentPostTitles

	http://www.sixapart.com/developers/xmlrpc/movable_type_api/
*/


/*
 * $Log$
 * Revision 1.2  2008/05/04 23:01:05  blueyed
 * fix fatal phpdoc errors
 *
 * Revision 1.1  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 * Revision 1.5  2008/01/13 19:43:26  fplanque
 * minor
 *
 * Revision 1.4  2008/01/13 03:12:06  fplanque
 * XML-RPC API debugging
 *
 * Revision 1.3  2008/01/12 22:51:11  fplanque
 * RSD support
 *
 * Revision 1.2  2008/01/12 08:06:15  fplanque
 * more xmlrpc tests
 *
 */
?>
