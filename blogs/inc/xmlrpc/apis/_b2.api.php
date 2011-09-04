<?php
/**
 * XML-RPC : B2 API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @see http://manual.b2evolution.net/B2_API
 *
 * @package xmlsrv
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$b2newpost_doc='Adds a post, blogger-api like, +title +category +postdate';
$b2newpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.newPost. Adds a post, blogger-api like, +title +category +postdate.
 *
 * b2 API
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 ?
 *					1 ?
 *					2 username (string): Login for a Blogger user who is member of the blog.
 *					3 password (string): Password for said username.
 *					4 content (string): The content of the post.
 *					5 publish (boolean): If set to true, the post will be published immediately.
 *					6 title (string): The title of the post.
 *					7 category (string): The internal name of the category you want to post the post into.
 *					8 date (string): This is the date that will be shown in the post, give "" for current date.
 * @return xmlrpcresp XML-RPC Response
 */
function b2_newpost($m)
{
	global $localtimenow;

	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 2, 3 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	$publish  = $m->getParam(5);
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';

	$main_cat = $m->getParam(7);
	$main_cat = $main_cat->scalarval();

	// Check if category exists and can be used
	$ChapterCache = & get_ChapterCache();
	if( $ChapterCache->get_by_ID( $main_cat, false ) === false )
	{ // Cat does not exist:
		return xmlrpcs_resperror( 11 );	// User error 11
	}
	$cat_IDs = array( $main_cat );

	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$status, 'edit', false, $cat_IDs ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	
	logIO( 'Permission granted.' );

	$postdate = $m->getParam(8);
	$postdate = $postdate->scalarval();
	if( $postdate != '' )
	{
		$post_date = $postdate;
	}
	else
	{
		$post_date = date('Y-m-d H:i:s', $localtimenow);
	}

	$post_title = $m->getParam(6);
	$post_title = $post_title->scalarval();

 	$content = $m->getParam(4);
	$content = $content->scalarval();

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $post_title, $content, $post_date, $main_cat, $cat_IDs, $status );
}




$b2getcategories_doc='given a blogID, gives an array of structs that list categories in that blog, using categoryID and categoryName. categoryName is there so the user would choose a category name from the client, rather than just a number. however, when using b2.newPost, only the category ID number should be sent.';
$b2getcategories_sig = array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.getCategories
 *
 * Gets also used for mt.getCategoryList. Is this correct?
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog to query
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function b2_getcategories( $m )
{
	return _b2_or_mt_get_categories('b2', $m);
}




$b2_getPostURL_doc = 'Given a blog ID, username, password, and a post ID, returns the URL to that post.';
$b2_getPostURL_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.getPostURL
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 ? NO LONGER USED (was: blogid (string): Unique identifier of the blog to query)
 *					1 ? (string)
 *					2 username (string): Login for a Blogger user who is member of the blog.
 *					3 password (string): Password for said username.193
 *
 *					4 post_ID (string): Post to query
 * @return xmlrpcresp XML-RPC Response
 */
function b2_getposturl($m)
{
	// CHECK LOGIN:
  /**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 2, 3 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET POST:
  /**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 4 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION: (user needs to be able to view the item)
	if( ! xmlrpcs_can_view_item( $edited_Item, $User ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	
	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $edited_Item->get_permanent_url() ) );
}


$xmlrpc_procs['b2.newPost'] = array(
				'function' => 'b2_newpost',
				'signature' => $b2newpost_sig,
				'docstring' => $b2newpost_doc );

$xmlrpc_procs['b2.getCategories'] = array(
				'function' => 'b2_getcategories',
				'signature' => $b2getcategories_sig,
				'docstring' => $b2getcategories_doc );

$xmlrpc_procs['b2.getPostURL'] = array(
				'function' => 'b2_getposturl',
				'signature' => $b2_getPostURL_sig,
				'docstring' => $b2_getPostURL_doc );

/*
 * $Log$
 * Revision 1.14  2011/09/04 22:13:23  fplanque
 * copyright 2011
 *
 * Revision 1.13  2010/02/28 13:42:07  efy-yury
 * move APIs permissions check in xmlrpcs_login func
 *
 * Revision 1.12  2010/02/26 16:18:52  efy-yury
 * add: permission "Can use APIs"
 *
 * Revision 1.11  2010/02/08 17:55:17  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.10  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.9  2009/09/18 19:09:04  tblue246
 * XML-RPC: Check extracats in addition to maincat before calling check_perm(). Fixes debug_die()ing and sends an XML-RPC error instead.
 *
 * Revision 1.8  2009/08/29 12:23:56  tblue246
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
 * Revision 1.7  2009/08/27 16:01:34  tblue246
 * Replaced unnecessary double quotes with single quotes
 *
 * Revision 1.6  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.5  2009/03/03 21:21:10  blueyed
 * Deprecate get_the_category_by_ID and replace its usage with ChapterCache
 * in core.
 *
 * Revision 1.4  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.3  2008/05/04 23:01:05  blueyed
 * fix fatal phpdoc errors
 *
 * Revision 1.2  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.1  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 * Revision 1.4  2008/01/12 22:51:11  fplanque
 * RSD support
 *
 * Revision 1.3  2008/01/12 08:06:15  fplanque
 * more xmlrpc tests
 *
 */
?>
