<?php
/**
 * XML-RPC : B2 API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @see http://b2evolution.net/man/b2-api
 *
 * @package xmlsrv
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

 	$content = $m->getParam(4);
 	$title = $m->getParam(6);
 	$main_cat = $m->getParam(7);
 	$date = $m->getParam(8);

	$params = array(
			'title'			=> $title->scalarval(),
			'content'		=> $content->scalarval(),
			'main_cat_ID'	=> $main_cat->scalarval(),
			'date'			=> $date->scalarval(),
			'status'		=> $status,
		);

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $params );
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

?>