<?php
/**
 * XML-RPC : Blogger API
 *
 * this is used by w.bloggar for example
 * @see http://manual.b2evolution.net/B2_API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings, $Messages;

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


	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$status, 'edit', false, array($main_cat) ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	// Check if category exists
	if( get_the_category_by_ID( $main_cat, false ) === false )
	{ // Cat does not exist:
		return xmlrpcs_resperror( 11 );	// User error 11
	}
	$cat_IDs = array( $main_cat );

	$postdate = $m->getParam(8);
	$postdate = $postdate->scalarval();
	if( $postdate != '' )
	{
		$post_date = $postdate;
	}
	else
	{
		$post_date = date('Y-m-d H:i:s', (time() + $Settings->get('time_difference')));
	}

	$post_title = $m->getParam(6);
	$post_title = $post_title->scalarval();

 	$content = $m->getParam(4);
	$content = $content->scalarval();

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $post_title, $content, $post_date, $main_cat, $cat_IDs, $status );
}




$b2getcategories_doc='given a blogID, gives a struct that list categories in that blog, using categoryID and categoryName. categoryName is there so the user would choose a category name from the client, rather than just a number. however, when using b2.newPost, only the category ID number should be sent.';
$b2getcategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
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
	global $xmlrpcerruser;
	global $siteurl;

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

	// CHECK PERMISSION: (we need at least one post/edit status)
	if( ! $current_User->check_perm( 'blog_post_statuses', 1, false, $edited_Item->blog_ID ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $edited_Item->get_permanent_url() ) );
}


$xmlrpc_procs["b2.newPost"] = array(
				"function" => "b2_newpost",
				"signature" => $b2newpost_sig,
				"docstring" => $b2newpost_doc );

$xmlrpc_procs["b2.getCategories"] = array(
				"function" => "b2_getcategories",
				"signature" => $b2getcategories_sig,
				"docstring" => $b2getcategories_doc );

$xmlrpc_procs["b2.getPostURL"] = array(
				"function" => "b2_getposturl",
				"signature" => $b2_getPostURL_sig,
				"docstring" => $b2_getPostURL_doc );

/*
 * $Log$
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