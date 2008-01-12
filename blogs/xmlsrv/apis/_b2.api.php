<?php
/**
 * XML-RPC : Blogger API
 *
 * this is used by w.bloggar for example
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

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	$content = $m->getParam(4);
	$content = $content->scalarval();

	$publish  = $m->getParam(5);
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';

	$post_title = $m->getParam(6);
	$post_title = $post_title->scalarval();

	$category = $m->getParam(7);
	$category = $category->scalarval();

	$postdate = $m->getParam(8);
	$postdate = $postdate->scalarval();

	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$UserCache = & get_Cache( 'UserCache' );
  /**
	 * @var User
	 */
	$current_User = & $UserCache->get_by_login( $username );

	// Check if category exists
	if( get_the_category_by_ID( $category, false ) === false )
	{ // Cat does not exist:
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}

	$blog = get_catblog($category);

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}

	if( $postdate != '' )
	{
		$now = $postdate;
	}
	else
	{
		$now = date('Y-m-d H:i:s', (time() + $Settings->get('time_difference')));
	}

	// CHECK and FORMAT content
	$post_title = format_to_post($post_title, 0, 0);
	$content = format_to_post($content, 0, 0);

	if( $errstring = $Messages->get_string( 'Cannot post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}

	// INSERT NEW POST INTO DB:
	$edited_Item = & new Item();
	$edited_Item->set('title', $post_title);
	$edited_Item->set('content', $content);
	$edited_Item->set('datestart', $now);
	$edited_Item->set('main_cat_ID', $category);
	$edited_Item->set('extra_cat_IDs', array($category) );
	$edited_Item->set('status', $status);
	$edited_Item->set('locale', $current_User->locale );
	$edited_Item->set_creator_User($current_User);
	$edited_Item->dbinsert();

	if( ! $edited_Item->ID )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'Error while inserting item: '.$DB->last_error ); // user error 9
	}

	logIO( 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	return new xmlrpcresp(new xmlrpcval($edited_Item->ID));
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
 *					3 password (string): Password for said username.
 *					4 post_ID (string): Post to query
 * @return xmlrpcresp XML-RPC Response
 */
function b2_getposturl($m)
{
	global $xmlrpcerruser;
	global $siteurl;

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	$post_ID = $m->getParam(4);
	$post_ID = intval($post_ID->scalarval());

	if( ! user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$ItemCache = & get_Cache( 'ItemCache' );
  /**
	 * @var Item
	 */
	if( ( $Item = & $ItemCache->get_by_ID( $post_ID ) ) === false )
	{ // Post does not exist
		return new xmlrpcresp(0, $xmlrpcerruser+7,
						'This post ID ('.$post_ID.') does not correspond to any post here.' );
	}

	$Blog = & $Item->get_Blog();

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $Blog->ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.' );
	}


	return new xmlrpcresp( new xmlrpcval( $Item->get_permanent_url() ) );
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
 * Revision 1.3  2008/01/12 08:06:15  fplanque
 * more xmlrpc tests
 *
 */
?>