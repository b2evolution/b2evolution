<?php
/**
 * XML-RPC APIs
 *
 * This file implements the following XML-RPC remote procedures, to be called by remote clients:
 * - the B2 API for b2evo (this is used by w.bloggar for example...)
 * - the BLOGGER API for b2evo, see {@link http://www.blogger.com/developers/api/1_docs/}
 * - the PINGBACK functions
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package xmlsrv
 */

/**
 * Initialize everything:
 */
require_once(dirname(__FILE__).'/../conf/_config.php' );
require_once(dirname(__FILE__).'/../'.$core_subdir.'_main.inc.php' );

// We can't display standard error messages. We must return XMLRPC responses.
$DB->halt_on_error = false;

// All statuses are allowed for display/acting on (including drafts and deprecated posts):
$show_statuses = array( 'published', 'protected', 'private', 'draft', 'deprecated' );

$post_default_title = ''; // posts submitted via the xmlrpc interface get that title

$xmlrpc_logging = 1;		// Set to 1 if you want to enable logging

function logIO($io,$msg)
{
	global $xmlrpc_logging;
	if ($xmlrpc_logging)
	{
		$fp = fopen( dirname(__FILE__).'/xmlrpc.log',"a+");
		$date = date("Y-m-d H:i:s ");
		$iot = ($io == "I") ? " Input: " : " Output: ";
		fwrite($fp, "\n\n".$date.$iot.$msg);
		fclose($fp);
	}
	return true;
}


/**
 * Returns a string replaced by stars, for passwords.
 *
 * @param string the source string
 * @return string same length, but only stars
 */
function starify( $string )
{
	return str_repeat( '*', strlen( $string ) );
}





$b2newpost_doc='Adds a post, blogger-api like, +title +category +postdate';
$b2newpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.newPost
 *
 * b2 API
 *
 * {@internal b2newpost(-) }}
 */
function b2newpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $blog_ID, $DB, $UserCache;
	global $cafelogID;
	global $Settings, $Messages;

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	$content = $m->getParam(4);
	$content = $content->scalarval();

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

	$current_User = & $UserCache->get_by_login( $username );

	// Check if category exists
	if( get_the_category_by_ID( $category, false ) === false )
	{ // Cat does not exist:
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}

	$blog_ID = get_catblog($category);
	$blogparams = get_blogparams_by_ID( $blog_ID );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}

	if( $postdate != '' )
	{
		$now = $postdate;
	}
	else
	{
		$now = date('Y-m-d H:i:s', (time() + ($Settings->get('time_difference') * 3600)));
	}

	// CHECK and FORMAT content
	$post_title = format_to_post($post_title, 0, 0);
	$content = format_to_post($content, 0, 0);

	if( $errstring = $Messages->getString( 'Cannot post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}

	// INSERT NEW POST INTO DB:
	$edited_Item = & new Item();
	$post_ID = $edited_Item->insert( $current_User->ID, $post_title, $content, $now, $category, array(), 'published', $current_User->locale );
	if( !empty($DB->last_error) )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	// pingback( true, $content, $post_title, '', $post_ID, $blogparams, false);
	logIO("O","Pinging b2evolution.net...");
	pingb2evonet( $blogparams, $post_ID, $post_title, false );
	logIO("O","Pinging Weblogs...");
	pingWeblogs( $blogparams, false );
	logIO("O","Pinging Blo.gs...");
	pingBlogs( $blogparams, false );
	logIO("O","Pinging Technorati...");
	pingTechnorati( $blogparams, false );

	return new xmlrpcresp(new xmlrpcval($post_ID));

}




$b2getcategories_doc='given a blogID, gives a struct that list categories in that blog, using categoryID and categoryName. categoryName is there so the user would choose a category name from the client, rather than just a number. however, when using b2.newPost, only the category ID number should be sent.';
$b2getcategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.getCategories
 *
 * B2 API
 *
 * {@internal b2getcategories(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog to query
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function b2getcategories( $m )
{
	global $xmlrpcerruser, $DB;

	$blogid = $m->getParam(0);
	$blogid = $blogid->scalarval();

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();

	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$sql = 'SELECT *
					FROM T_categories ';
	if( $blogid > 1 ) $sql .= "WHERE cat_blog_ID = $blogid ";
	$sql .= "ORDER BY cat_name ASC";

	$rows = $DB->get_results( $sql );
	if( !empty($DB->last_error) )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	xmlrpc_debugmsg( 'Categories:'.count($rows) );

	$data = array();
	foreach( $rows as $row )
	{
		$data[] = new xmlrpcval( array( 'categoryID' => new xmlrpcval($row->cat_ID),
																		'categoryName' => new xmlrpcval( $row->cat_name )
			//	mb_convert_encoding( $row->cat_name, "utf-8", "iso-8859-1")  )
														),"struct");
	}

	return new xmlrpcresp( new xmlrpcval($data, "array") );

}




$b2_getPostURL_doc = 'Given a blog ID, username, password, and a post ID, returns the URL to that post.';
$b2_getPostURL_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.getPostURL
 *
 * B2 API
 *
 * {@internal b2_getPostURL(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog to query
 *					1 ?
 *					2 username (string): Login for a Blogger user who is member of the blog.
 *					3 password (string): Password for said username.
 *					4 post_ID (string): Post to query
 * @return xmlrpcresp XML-RPC Response
 */
function b2_getPostURL($m)
{
	global $xmlrpcerruser;
	global $siteurl;
	global $ItemCache, $UserCache;

	$blog_ID = $m->getParam(0);
	$blog_ID = $blog_ID->scalarval();

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

	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.' );
	}

	if( ( $Item = $ItemCache->get_by_ID( $post_ID ) ) === false )
	{ // Post does not exist
		return new xmlrpcresp(0, $xmlrpcerruser+7,
						'This post ID ('.$post_ID.') does not correspond to any post here.' );
	}

	return new xmlrpcresp( new xmlrpcval( $Item->gen_permalink() ) );
}




$bloggernewpost_doc = 'Adds a post, blogger-api like';
$bloggernewpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
/**
 * blogger.newPost makes a new post to a designated blog.
 *
 * BLOGGER API
 *
 * Optionally, will publish the blog after making the post. (In b2evo, this means the
 * new post will be in 'published' state).
 * On success, it returns the unique ID of the new post (usually a seven-digit number
 * at this time).
 * On error, it will return some error message.
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_newPost.html}
 *
 * {@internal bloggernewpost(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					2 username (string): Login for a Blogger user who has permission to post to the blog.
 *					3 password (string): Password for said username.
 *					4 content (string): Contents of the post.
 *					5 publish (boolean): If true, the blog will be published immediately after the
 *						post is made. (In b2evo,this means, the new post will be in 'published' state,
 *						otherwise it would be in draft state).
 * @return xmlrpcresp XML-RPC Response
 */
function bloggernewpost( $m )
{
	global $xmlrpcerruser; // import user errcode value
	global $blog_ID, $default_category, $DB;
	global $cafelogID;
	global $Settings, $Messages, $UserCache;

	logIO('I','Called function: blogger.newPost');

	$username = $m->getParam(2);
	$password = $m->getParam(3);
	$content  = $m->getParam(4);
	$publish  = $m->getParam(5);

	$username = $username->scalarval();
	$password = $password->scalarval();
	$content = $content->scalarval();
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO('I',"Publish: $publish -> Status: $status");

	if( !user_pass_ok($username,$password) )
	{
		logIO('O', "Wrong username/password combination <strong>$username / $password</strong>");
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$current_User = & $UserCache->get_by_login( $username );

	if( ! ($post_category = xmlrpc_getpostcategory($content) ) )
	{ // There was no category passed in the content:
		$post_category = $default_category;
	}

	// Check if category exists
	if( get_the_category_by_ID( $post_category, false ) === false )
	{ // Cat does not exist:
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}

	$blog_ID = get_catblog($post_category);
	$blogparams = get_blogparams_by_ID( $blog_ID );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}

	// Extract <title> from content
	$post_title = xmlrpc_getposttitle( $content );

	// cleanup content from extra tags like <category> and <title>:
	$content = xmlrpc_removepostdata( $content );

	$now = date('Y-m-d H:i:s', (time() + ($Settings->get('time_difference') * 3600)));

	// CHECK and FORMAT content
	$post_title = format_to_post($post_title,0,0);
	$content = format_to_post($content,0,0);

	if( $errstring = $Messages->getString( 'Cannot post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}

	// INSERT NEW POST INTO DB:
	$edited_Item = & new Item();
	$post_ID = $edited_Item->insert( $current_User->ID, $post_title, $content, $now, $post_category, array( $post_category ), $status, $current_User->locale, '', 0, $publish );

	if( !empty($DB->last_error) )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	logIO('O', "Posted ! ID: $post_ID");

	if( $publish )
	{ // If post is publicly published:
		// logIO("O","Doing pingbacks...");
		// pingback( true, $content, $post_title, '', $post_ID, $blogparams, false);
		logIO("O","Pinging b2evolution.net...");
		pingb2evonet( $blogparams, $post_ID, $post_title, false );
		logIO("O","Pinging Weblogs...");
		pingWeblogs( $blogparams, false );
		logIO("O","Pinging Blo.gs...");
		pingBlogs( $blogparams, false );
		logIO("O","Pinging Technorati...");
		pingTechnorati( $blogparams, false );
	}

	logIO("O","All done.");

	return new xmlrpcresp(new xmlrpcval($post_ID));

}




$bloggereditpost_doc='Edits a post, blogger-api like';
$bloggereditpost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
/**
 * blogger.editPost changes the contents of a given post.
 *
 * Optionally, will publish the blog the post belongs to after changing the post.
 * (In b2evo, this means the changed post will be moved to published state).
 * On success, it returns a boolean true value.
 * On error, it will return a fault with an error message.
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_editPost.html}
 *
 * {@internal bloggereditpost(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 postid (string): Unique identifier of the post to be changed.
 *					2 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					3 password (string): Password for said username.
 *					4 content (string): New content of the post.
 *					5 publish (boolean): If true, the blog will be published immediately after the
 *						post is made. (In b2evo,this means, the new post will be in 'published' state,
 *						otherwise it would be in draft state).
 * @return xmlrpcresp XML-RPC Response
 *
 * @todo check current status and permission on it
 */
function bloggereditpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $blog_ID, $ItemCache;
	global $cafelogID, $default_category, $DB;
	global $Messages, $UserCache;

	logIO('I','Called function: blogger.editPost');

	// return new xmlrpcresp(0, $xmlrpcerruser+50, 'bloggereditpost' );

	$post_ID = $m->getParam(1);
	$post_ID = $post_ID->scalarval();

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	if( !user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	if( ! ($edited_Item = $ItemCache->get_by_ID( $post_ID ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}

	$newcontent = $m->getParam(4);
	$newcontent = $newcontent->scalarval();
	xmlrpc_debugmsg( 'New content: '.$newcontent  );

	$publish = $m->getParam(5);
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO('I',"Publish: $publish -> Status: $status");

	if( ! ($postdata = get_postdata($post_ID)) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}

	logIO('O','Old post Title: '.$postdata['Title']);

	$current_User = & $UserCache->get_by_login( $username );

	if( ! ($post_category = xmlrpc_getpostcategory($newcontent) ) )
	{ // No category specified
		$post_category = $edited_Item->main_cat_ID;
	}
	elseif( get_the_category_by_ID( $post_category, false ) === false )
	{ // requested Cat does not exist:
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}
	// return new xmlrpcresp(0, $xmlrpcerruser+50, 'post_category='.$post_category );

	$blog_ID = get_catblog($post_category);
	$blogparams = get_blogparams_by_ID( $blog_ID );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.' );
	}

	$content = $newcontent;

	$post_title = xmlrpc_getposttitle($content);

	$content = xmlrpc_removepostdata($content);

	// CHECK and FORMAT content
	$post_title = format_to_post($post_title,0,0);
	$content = format_to_post($content,0,0);

	if( $errstring = $Messages->getString( 'Cannot update post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}

	// We need to check the previous flags...
	$post_flags = $postdata['Flags'];
	if( in_array( 'pingsdone', $post_flags ) )
	{ // pings have been done before
		$pingsdone = true;
	}
	elseif( !$publish )
	{ // still not publishing
		$pingsdone = false;
	}
	else
	{ // We'll be pinging now
		$pingsdone = true;
	}

	// UPDATE POST IN DB:
	$edited_Item->update( $post_title, $content, '', $post_category, array($post_category), $status, '#', '', 0, $pingsdone, '', '', 'open' );
	if( !empty($DB->last_error) )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	if( $publish )
	{ // If post is publicly published:

		// ping ?
		if( in_array( 'pingsdone', $post_flags ) )
		{ // pings have been done before
			logIO("O","pings have been done before...");
		}
		else
		{ // We'll ping now
			// We have less control here as in the backoffice, so we'll actually
			// only pingback once, at the same time we do the pings!
			// logIO("O","Doing pingbacks...");
			// pingback( true, $content, $post_title, '', $post_ID, $blogparams, false);
			logIO("O","Pinging b2evolution.net...");
			pingb2evonet( $blogparams, $post_ID, $post_title, false );
			logIO("O","Pinging Weblogs...");
			pingWeblogs( $blogparams, false );
			logIO("O","Pinging Blo.gs...");
			pingBlogs( $blogparams, false );
			logIO("O","Pinging Technorati...");
			pingTechnorati( $blogparams, false );
		}

	}

	return new xmlrpcresp(new xmlrpcval("1", "boolean"));
}




$bloggerdeletepost_doc='Deletes a post, blogger-api like';
$bloggerdeletepost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
/**
 * blogger.editPost deletes a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 *
 * {@internal bloggerdeletepost(-) }}
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
function bloggerdeletepost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $blog_ID, $DB, $UserCache;

	$post_ID = $m->getParam(1);
	$post_ID = $post_ID->scalarval();

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	if( ! user_pass_ok( $username, $password ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	if( ! ($edited_Item = $ItemCache->get_by_ID( $post_ID, false ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, 'No such post.');	// user error 7
	}

	$current_User = & $UserCache->get_by_login( $username );

	$blog_ID = $edited_Item->blog_ID;

	// Check permission:
	if( ! $current_User->check_perm( 'blog_del_post', 'any', false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.');
	}

	// DELETE POST FROM DB:
	$edited_Item->dbdelete();
	if( !empty($DB->last_error) )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	return new xmlrpcresp(new xmlrpcval(1));
}



$bloggergetusersblogs_doc='returns the user\'s blogs - this is a dummy function, just so that BlogBuddy and other blogs-retrieving apps work';
$bloggergetusersblogs_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getUsersBlogs returns information about all the blogs a given user is a member of.
 *
 * Data is returned as an array of <struct>'s containing the ID (blogid), name (blogName),
 * and URL (url) of each blog.
 *
 * Non official: Also return a boolean stating wether or not the user can edit th eblog templates
 * (isAdmin).
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_getUsersBlogs.html}
 *
 * {@internal bloggergetusersblogs(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 username (string): Login for the Blogger user who's blogs will be retrieved.
 *					2 password (string): Password for said username.
 *						(currently not required by b2evo)
 * @return xmlrpcresp XML-RPC Response, an array of <struct>'s containing for each blog:
 *					- ID (blogid),
 *					- name (blogName),
 *					- URL (url),
 *					- bool: can user edit template? (isAdmin).
 */
function bloggergetusersblogs($m)
{
	global $xmlrpcerruser, $UserCache;
	global $baseurl;

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();
	logIO("O","entered bloggergetusersblogs.");


	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	logIO("O","user approved.");


	$current_User = & $UserCache->get_by_login( $username );
	logIO("O","Got Current user.".$current_User);


	$resp_array = array();
	// Loop through all blogs:
	for( $curr_blog_ID=blog_list_start();
				$curr_blog_ID!=false;
				 $curr_blog_ID=blog_list_next() )

	{
		if( ! $current_User->check_perm( 'blog_ismember', 1, false, $curr_blog_ID ) )
		{ // Current user is not a member of this blog...
	logIO("O","Current user is not a member of this blog.->".$curr_blog_ID);


			continue;

		}
	logIO("O","Current user IS a member of this blog.".$curr_blog_ID);


		$resp_array[] = new xmlrpcval( array(
					"blogid" => new xmlrpcval( $curr_blog_ID ),
					"blogName" => new xmlrpcval( blog_list_iteminfo('shortname', false) ),
					"url" => new xmlrpcval( blog_list_iteminfo('blogurl', false) ),
					"isAdmin" => new xmlrpcval( $current_User->check_perm( 'templates', 'any' ) ,'boolean')
												), 'struct');
	}


	$resp = new xmlrpcval($resp_array, 'array');

	return new xmlrpcresp($resp);
}




$bloggergetuserinfo_doc='gives the info about a user';
$bloggergetuserinfo_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getUserInfo returns returns a struct containing user info.
 *
 * Data returned: userid, firstname, lastname, nickname, email, and url.
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_getUserInfo.html}
 *
 * {@internal bloggergetuserinfo(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 username (string): Login for the Blogger user who's blogs will be retrieved.
 *					2 password (string): Password for said username.
 *						(currently not required by b2evo)
 * @return xmlrpcresp XML-RPC Response, a <struct> containing:
 *					- userid,
 *					- firstname,
 *					- lastname,
 *					- nickname,
 *					- email,
 *					- url
 */
function bloggergetuserinfo($m)
{
	global $xmlrpcerruser, $UserCache;

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();

	$User =& $UserCache->get_by_login( $username );

	if( user_pass_ok( $username, $password) )
	{
		$struct = new xmlrpcval( array(
															'nickname' => new xmlrpcval( $User->get('nickname') ),
															'userid' => new xmlrpcval( $User->get('ID') ),
															'url' => new xmlrpcval( $User->get('url') ),
															'email' => new xmlrpcval( $User->get('email') ),
															'lastname' => new xmlrpcval( $User->get('lastname') ),
															'firstname' => new xmlrpcval( $User->get('firstname') )
															), 'struct' );
		$resp = $struct;
		return new xmlrpcresp($resp);
	}
	else
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
}




$bloggergetpost_doc = 'fetches a post, blogger-api like';
$bloggergetpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getPost retieves a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 *
 * {@internal bloggergetpost(-) }}
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
function bloggergetpost($m)
{
	global $xmlrpcerruser;

	$post_ID = $m->getParam(1);
	$post_ID = $post_ID->scalarval();

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	if( user_pass_ok($username,$password) )
	{
		$postdata = get_postdata($post_ID);

		if( $postdata['Date'] != '' )
		{
			$post_date = mysql2date("U", $postdata["Date"]);
			$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);

			$content	= "<title>".$postdata["Title"]."</title>";
			$content .= "<category>".$postdata["Category"]."</category>";
			$content .= $postdata["Content"];

			$struct = new xmlrpcval(array("userid" => new xmlrpcval($postdata["Author_ID"]),
											"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
											"content" => new xmlrpcval($content),
											"postid" => new xmlrpcval($postdata["ID"])
											),"struct");

			$resp = $struct;
			return new xmlrpcresp($resp);
		}
		else
		{
		return new xmlrpcresp(0, $xmlrpcerruser+7, // user error 7
					 "No such post #$post_ID");
		}
	}
	else
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
}




$bloggergetrecentposts_doc = 'fetches X most recent posts, blogger-api like';
$bloggergetrecentposts_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcInt));
/**
 * blogger.getRecentPosts retieves X most recent posts.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 *
 * {@internal bloggergetrecentposts(-) }}
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
function bloggergetrecentposts( $m )
{
	global $xmlrpcerruser, $DB, $show_statuses, $UserCache;
	global $blog;

	$blog_ID = $m->getParam(1);
	$blog_ID = $blog_ID->scalarval();

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	$numposts = $m->getParam(4);
	$numposts = $numposts->scalarval();

	if( ! user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.' ); // user error 2
	}

	$blog = $blog_ID;	// Some old functions still need this!

	// Getting current blog info:
	$blogparams = get_blogparams_by_ID( $blog_ID );

	// Get the posts to display:
	$MainList = & new ItemList( $blog_ID, $show_statuses, '', '', '', '', array(), '', 'DESC', '', $numposts );

	if( !empty($DB->last_error) )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	xmlrpc_debugmsg( 'Items:'.$MainList->result_num_rows );

	$data = array();
	while( $Item = $MainList->get_item() )
	{
		xmlrpc_debugmsg( 'Item:'.$Item->title.
											' - Issued: '.$Item->issue_date.
											' - Modified: '.$Item->mod_date );

		$post_date = mysql2date("U", $Item->issue_date);
		$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);

		$content	= '<title>'.$Item->title.'</title>';
		$content .= '<category>'.$Item->main_cat_ID.'</category>';
		$content .= $Item->content;

		$authorname = $Item->Author->get('preferedname');

		$data[] = new xmlrpcval(array(
									"authorName" => new xmlrpcval($authorname),
									"userid" => new xmlrpcval($Item->Author->ID),
									"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
									"content" => new xmlrpcval($content),
									"postid" => new xmlrpcval($Item->ID)
									),"struct");
	}

	$resp = new xmlrpcval($data, "array");

	return new xmlrpcresp($resp);

}





$bloggergettemplate_doc = 'returns the default template file\'s code';
$bloggergettemplate_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getTemplate returns text of the main or archive index template for a given blog.
 *
 * Currently, in b2evo, this will return the templates of the 'custom' skin.
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_getTemplate.html}
 *
 * {@internal bloggergettemplate(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 blogid (string): Unique identifier of the blog who's template is to be returned.
 *					2 username (string): Login for a Blogger who has admin permission on given blog.
 *					3 password (string): Password for said username.
 *					4 templateType (string): Determines which of the blog's templates will be returned.
 *						Currently, either "main" or "archiveIndex".
 * @return xmlrpcresp XML-RPC Response
 */
function bloggergettemplate($m)
{
	global $xmlrpcerruser, $UserCache;

	$blog_ID = $m->getParam(1);
	$blog_ID = $blog_ID->scalarval();

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	$templateType = $m->getParam(4);
	$templateType = $templateType->scalarval();

	if( !user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'templates' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.');
	}

	// Determine the edit folder:
	$edit_folder = get_path('skins').'custom/';

	if ($templateType == "main")
	{
		$file = $edit_folder.'_main.inc.php';
	}
	elseif ($templateType == "archiveIndex")
	{
		$file = $edit_folder.'_archives.php';
	}
	else return; // TODO: handle this cleanly

	$f = fopen($file,"r");
	$content = fread($f,filesize($file));
	fclose($file);

	$content = str_replace("\n","\r\n",$content); // so it is actually editable with a windows/mac client, instead of being returned as a looooooooooong line of code

	return new xmlrpcresp(new xmlrpcval("$content"));

}



$bloggersettemplate_doc = 'saves the default template file\'s code';
$bloggersettemplate_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.setTemplate changes the template for a given blog.
 *
 * Can change either main or archive index template.
 *
 * Currently, in b2evo, this will change the templates of the 'custom' skin.
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_getTemplate.html}
 *
 * {@internal bloggersettemplate(-) }}
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 blogid (string): Unique identifier of the blog who's template is to be returned.
 *					2 username (string): Login for a Blogger who has admin permission on given blog.
 *					3 password (string): Password for said username.
 *					4 template (string): The text for the new template (usually mostly HTML).
 *					5 templateType (string): Determines which of the blog's templates will be returned.
 *						Currently, either "main" or "archiveIndex".
 * @return xmlrpcresp XML-RPC Response
 */
function bloggersettemplate( $m )
{
	global $xmlrpcerruser, $blogfilename, $UserCache;

	$blog_ID = $m->getParam(1);
	$blog_ID = $blog_ID->scalarval();

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	$template = $m->getParam(4);
	$template = $template->scalarval();

	$templateType = $m->getParam(5);
	$templateType = $templateType->scalarval();

	if( !user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'templates' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.');
	}

	// Determine the edit folder:
	$edit_folder = get_path('skins').'custom/';

	if( $templateType == 'main' )
	{
		$file = $edit_folder.'_main.inc.php';
	}
	elseif ($templateType == "archiveIndex")
	{
		$file = $edit_folder.'_archives.php';
	}
	else return; // TODO: handle this cleanly

	$f = fopen($file,"w+");
	fwrite($f, $template);
	fclose($file);

	return new xmlrpcresp(new xmlrpcval("1", "boolean"));
}




/**
 * Pingback support function
 * strip_all_but_one_link(-)
 */
function strip_all_but_one_link($text, $mylink, $log)
{
	debug_fwrite($log, 'Searching '.$mylink.' in text block #####'.$text."####\n\n");

	$match_link = '#(<a.+?href.+?'.'>)(.+?)(</a>)#';
	preg_match_all($match_link, $text, $matches);
	$count = count($matches[0]);
	for ($i=0; $i<$count; $i++)
	{
		$thislink = $matches[0][$i];
		debug_fwrite($log, 'Analyzing link : '.$thislink."\n");

		if(strstr($thislink, $mylink))
		{
			debug_fwrite($log, "MATCH!\n");
		}
		else
		{ // this link doesn't contain what we're looking for
			$text = str_replace($matches[0][$i], $matches[2][$i], $text);
		}
	}
	return $text;
}

$pingback_ping_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString));

$pingback_ping_doc = 'gets a pingback and registers it as a comment prefixed by &lt;pingback /&gt;';



/**
 * pingback_ping(-)
 *
 * This is the pingback receiver!
 *
 * original code by Mort (http://mort.mine.nu:8080)
 * fplanque: every time you come here you can correct a couple of bugs...
 */
function pingback_ping( $m )
{
	global $DB, $notify_from, $xmlrpcerruser;
	global $baseurl;
	global $localtimenow, $Messages;
	global $UserCache, $BlogCache;

	$log = debug_fopen('./xmlrpc.log', 'w');

	$title = '';

	$pagelinkedfrom = $m->getParam(0);
	$pagelinkedfrom = $pagelinkedfrom->scalarval();

	$pagelinkedto = $m->getParam(1);
	$pagelinkedto = $pagelinkedto->scalarval();

	$pagelinkedfrom = str_replace('&amp;', '&', $pagelinkedfrom);
	$pagelinkedto = preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedto);

	debug_fwrite($log, 'BEGIN '.time().' - '.date('Y-m-d H:i:s')."\n\n");
	debug_fwrite($log, 'Page linked from: '.$pagelinkedfrom."\n");
	debug_fwrite($log, 'Page linked to: '.$pagelinkedto."\n");

	$messages = array(
		htmlentities("Pingback from ".$pagelinkedfrom." to ".$pagelinkedto." registered. Keep the web talking! :-)"),
		htmlentities("We can't find the URL to the post you are trying to link to in your entry. Please check how you wrote the post's permalink in your entry."),
		htmlentities("We can't find the post you are trying to link to. Please check the post's permalink.")
	);

	$resp_message = $messages[0];

	// Check if the page linked to is in our site
	// fplanque: TODO: coz we don't have a single siteurl any longer
	$pos1 = strpos( $pagelinkedto, preg_replace( '#^https?://(www\.)?#', '', $baseurl ));
	if( $pos1 !== false )
	{
		// let's find which post is linked to
		$urltest = parse_url($pagelinkedto);
		if( preg_match('#/p([0-9]+)#', $urltest['path'], $match) )
		{ // the path defines the post_ID (yyyy/mm/dd/pXXXX)
			$post_ID = $match[1];
			$way = 'from the path (1)';
		}
		elseif (preg_match('#p/[0-9]+#', $urltest['path'], $match) )
		{
			// the path defines the post_ID (archives/p/XXXX)
			$blah = explode('/', $match[0]);
			$post_ID = $blah[1];
			$way = 'from the path (2)';
		}
		elseif (preg_match('#p=[0-9]+#', $urltest['query'], $match)	 )
		{
			// the querystring defines the post_ID (?p=XXXX)
			$blah = explode('=', $match[0]);
			$post_ID = $blah[1];
			$way = 'from the querystring';
		}
		elseif (isset($urltest['fragment']))
		{
			// an #anchor is there, it's either...
			if (intval($urltest['fragment']))
			{ // ...an integer #XXXX (simpliest case)
				$post_ID = $urltest['fragment'];
				$way = 'from the fragment (numeric)';
			}
			elseif (is_string($urltest['fragment']))
			{ // ...or a string #title, a little more complicated
				$title = preg_replace('/[^a-zA-Z0-9]/', '.', $urltest['fragment']);
				$sql = "SELECT ID
								FROM T_posts
								WHERE post_title RLIKE '$title'";
				$blah = $DB->get_row( $sql, ARRAY_A );
				if( !empty($DB->last_error) )
				{ // DB error
					return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
				}
				$post_ID = $blah['ID'];
				$way = 'from the fragment (title)';
			}
		}
		else
		{
			$post_ID = -1;
		}

		debug_fwrite($log, "Found post ID $way: $post_ID\n");

		$postdata = get_postdata($post_ID);
		$blog = $postdata['Blog'];
		xmlrpc_debugmsg( 'Blog='.$blog );

		$tBlog =& $BlogCache->get_by_ID( $blog );
		if( !$tBlog->get('allowpingbacks') )
		{
			return new xmlrpcresp(new xmlrpcval('Sorry, this weblog does not allow you to pingback its posts.'));
		}


		// Check that post exists
		$sql = 'SELECT post_creator_user_ID
						FROM T_posts
						WHERE ID = '.$post_ID;
		$rows = $DB->get_results( $sql );
		if( !empty($DB->last_error) )
		{ // DB error
			return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
		}

		if(count($rows))
		{
			debug_fwrite($log, 'Post exists'."\n");

			// Let's check that the remote site didn't already pingback this entry
			$sql = "SELECT * FROM T_comments
							WHERE comment_post_ID = $post_ID
								AND comment_author_url = '".$DB->escape(preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedfrom))."'
								AND comment_type = 'pingback'";
			$rows = $DB->get_results( $sql );
			if( !empty($DB->last_error) )
			{ // DB error
				return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
			}

			xmlrpc_debugmsg( $sql.' Already found='.count($rows) );

			if( ! count($rows) )
			{
				// very stupid, but gives time to the 'from' server to publish !
				sleep(1);

				// Let's check the remote site
				$fp = @fopen($pagelinkedfrom, 'r');

				$puntero = 4096;
				$linea = "";
				while($fbuffer = fread($fp, $puntero))
				{ // fplanque: dis is da place where da bug was >:[
					$linea .= $fbuffer;		// dis is da fix!
				}
				fclose($fp);
				$linea = strip_tags($linea, '<a><title>');

				preg_match('|<title>([^<]*?)</title>|is', $linea, $matchtitle);

				// You never know what kind of crap you may have gotten on the web...
				$linea = convert_chars( $linea, 'html' );

				$pagelinkedto = convert_chars( $pagelinkedto, 'html' );
				$linea = strip_all_but_one_link($linea, $pagelinkedto, $log);
				// fplanque: removed $linea = preg_replace('#&([^amp\;])#is', '&amp;$1', $linea);

				debug_fwrite($log, 'SECOND SEARCH '.$pagelinkedto.' in text block #####'.$linea."####\n\n");
				$pos2 = strpos($linea, $pagelinkedto);
				$pos3 = strpos($linea, str_replace('http://www.', 'http://', $pagelinkedto));
				if (is_integer($pos2) || is_integer($pos3))
				{
					debug_fwrite($log, 'The page really links to us :)'."\n");
					$pos4 = (is_integer($pos2)) ? $pos2 : $pos3;
					$start = $pos4-100;
					$context = substr($linea, $start, 250);
					$context = str_replace("\n", ' ', $context);
					$context = str_replace('&amp;', '&', $context);

					global $admin_url, $comments_allowed_uri_scheme;

					$pagelinkedfrom = preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedfrom);
					$title = (!strlen($matchtitle[1])) ? $pagelinkedfrom : $matchtitle[1];
					$original_context = $context;
					$context = '[...] '.trim($context).' [...]';

					// CHECK and FORMAT content
					if( $error = validate_url( $pagelinkedfrom, $comments_allowed_uri_scheme ) )
					{
						$Messages->add( T_('Supplied URL is invalid: ').$error );
					}
					$context = format_to_post($context,1,1);

					if( ! ($message = $Messages->getString( 'Cannot insert pingback, please correct these errors:', '' )) )
					{ // No validation error:
						$original_pagelinkedfrom = $pagelinkedfrom;
						$original_title = $title;
						$title = strip_tags(trim($title));
						$now = date('Y-m-d H:i:s', $localtimenow );
						$sql = "INSERT INTO T_comments( comment_post_ID, comment_type, comment_author,
																								comment_author_url, comment_date, comment_content)
										VALUES( $post_ID, 'pingback', '".$DB->escape($title)."',
														'".$DB->escape($pagelinkedfrom)."', '$now',
														'".$DB->escape($context)."')";
						$DB->query( $sql );
						if( !empty($DB->last_error) )
						{ // DB error
							return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
						}

						/*
						 * New pingback notification:
						 */
						$AuthorUser = & $UserCache->get_by_ID( $postdata['Author_ID'] );
						if( $AuthorUser->get( 'notify' ) )
						{ // Author wants to be notified:
							locale_temp_switch( $AuthorUser->get( 'locale' ) );

							$recipient = $AuthorUser->get( 'email' );
							$subject = sprintf( T_('New pingback on your post #%d "%s"'), $post_ID, $postdata['Title'] );

							$comment_Blog =& $BlogCache->get_by_ID( $blog );

							$notify_message  = sprintf( T_('New pingback on your post #%d "%s"'), $post_ID, $postdata['Title'] )."\n";
							$notify_message .= url_add_param( $comment_Blog->get('blogurl'), "p=$post_ID&pb=1\n\n", '&' );
							$notify_message .= T_('Website'). ": $original_title\n";
							$notify_message .= T_('Url'). ": $original_pagelinkedfrom\n";
							$notify_message .= T_('Excerpt'). ": \n[...] $original_context [...]\n\n";
							$notify_message .= T_('Edit/Delete').': '.$admin_url.'b2browse.php?blog='.$blog.'&p='.$post_ID."&c=1\n\n";

							send_mail( $recipient, $subject, $notify_message, $notify_from );

							locale_restore_previous();
						}
					}
				}
				else
				{ // URL pattern not found - page doesn't link to us:
					debug_fwrite($log, 'The page doesn\'t link to us!'."\n");
					$resp_message = "Page linked to: $pagelinkedto\nPage linked from: $pagelinkedfrom\nTitle: $title\n\n".$messages[1];
				}
			}
			else
			{ // We already have a Pingback from this URL
				$resp_message = "Sorry, you already did a pingback to $pagelinkedto from $pagelinkedfrom.";
			}
		}
		else
		{ // Post_ID not found
			$resp_message = $messages[2];
			debug_fwrite($log, 'Post doesn\'t exist'."\n");
		}
	} // / in siteurl

	// xmlrpc_debugmsg( 'Okay'.$messages[0] );

	return new xmlrpcresp(new xmlrpcval($resp_message));
}


//---------- Tor Jan 2005 Metaweblog experimental API ----------------
//
//---------- NB! This implementation is still rather lax with some of the security






//	Tor Dec 2004

//  Last update 23012004

// image upload

//  image is supplied coded in the info struct as bits

//

// To do - do not overwrite existing pics with same name

//		-	security, password etc.

//

//

function mwnewMediaObj($m) {

		global $xmlrpcerruser,$tablecategories; // import user errcode value

		global $blog_ID;

		global $Settings, $img_url, $img_subdir, $baseurl,$fileupload_realpath;

		logIO("O","start of _newmediaobject...");



		// - getting parameters

					logIO("O", 'Now going to get blogid!'."\n");

		$blogid = $m->getParam(0);

		$blogid = $blogid->scalarval();

					logIO("O", 'Got blogid!->'. $blogid."\n");



		$username = $m->getParam(1);

		$username = $username->scalarval();



		$password = $m->getParam(2);

		$password = $password->scalarval();



	if( !user_pass_ok($username, $password) )

	{

		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1

					 'Wrong username/password combination '.$username.' / '.starify($password));

	}



	//	Get the main data - and decode it properly for the image - sorry, binary object

	$xcontent = $m->getParam(3);

	$contentstruct = xmlrpc_decode($xcontent);

					logIO("O", 'Got first contentstruct!'."\n");



	// This call seems to go wrong from Marsedit under certain circumstances - Tor 04012005

		$data = $contentstruct['bits']; // decoding was done transparantly by xmlrpclib

					logIO("O", 'Have decoded data data?'."\n");



		// - check filesize TODO





		// - check if filetype is allowed (check filename) TODO





		$filename = $contentstruct['name'];

					logIO("O", 'Found filename ->'. $filename ."\n");

		$type = $contentstruct['type'];

					logIO("O", 'Done type ->'. $type ."\n");

		$data = $contentstruct['bits'];

					logIO("O", 'Done bits ' ."\n");



		// prefix filename with current date (YYYY-MM-DD-)

		// this to avoid nameclashes

			$filename = strftime("%Y%m%d-", time()) . $filename;



		// create file

					logIO("O", 'Ready to create file ->'. "../" . $filename ."\n");

		// try to create dir if it does not exist
		$fileupload_realpath = $fileupload_realpath . "/" . $blogid;



		// try to create new private media directories if needed

		// TODO Tor 04012005 - if filename prepended with directory/directories

		// these should either be stripped out (?) or teh directories created automagically

		if (!@is_dir($fileupload_realpath) ) {

			$oldumask = umask(0000);

			if (!@mkdir($fileupload_realpath, 0777))

				return _ERROR_BADPERMISSIONS;

			umask($oldumask);

		}



		logIO("O", 'Fileupload_realpath ->'. $fileupload_realpath ."\n");



		$fh = @fopen($fileupload_realpath ."/". $filename, 'wb');



		logIO("O", 'Managed  to open file ->'. $filename ."\n");



		if (!$fh)

			return _ERROR_UPLOADFAILED;

		logIO("O", 'Managed  to open file ->'. $fileupload_realpath ."/". $filename."\n");



		$ok = @fwrite($fh, $data);

					logIO("O", 'Ready  to close file ->'. $filename ."\n");

		@fclose($fh);

		if (!$ok)

			return _ERROR_UPLOADFAILED;

		logIO("O", 'Ready to chmod file ->'. $filename ."\n");



		// chmod uploaded file

		$oldumask = umask(0000);

		@chmod($fileupload_realpath . "/" . $filename, 0644);

		umask($oldumask);

		logIO("O", 'Full returned filename ->'. $fileupload_realpath . '/' . $filename ."\n");



		// - return URL as XML

		$urlstruct = new xmlrpcval(array(

			"url" => new xmlrpcval($baseurl . "/media". "/" . $blogid  . "/" . $filename,'string')

		),'struct');



		return new xmlrpcresp($urlstruct);

	}







	// metaWeblog.newMediaObject

	$mwnewmediaobj_sig = array(array(

		//  return type

		$xmlrpcStruct,		// "url" element



		// params

		$xmlrpcString,		// blogid

		$xmlrpcString,		// username

		$xmlrpcString,		// password

		$xmlrpcStruct		// 'name', 'type' and 'bits'

	));

	$mwnewmediaobj_doc = 'Uploads a file to to the media library of the user';









$mwnewpost_doc='Adds a post, blogger-api like, +title +category +postdate';

//$mwnewpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$mwnewpost_sig =  array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));

/**

 * mw.newPost

 *

 * mw API

 * Tor 2004

 */

function mwnewpost($m)

{

	global $xmlrpcerruser; // import user errcode value
	global $blog_ID, $default_category, $DB;
	global $cafelogID;
	global $Settings, $Messages, $UserCache,$tablecategories;;
	logIO("O","start of mwnewpost...");



	$blogid = $m->getParam(0);

	$blogid = $blogid->scalarval();



	$username = $m->getParam(1);

	$username = $username->scalarval();

	logIO("O","finished getting username ...");



	$password = $m->getParam(2);

	$password = $password->scalarval();

	logIO("O","finished getting password ...");



	$xcontent = $m->getParam(3);

//	$xcontent = $xcontent->scalarval();

	logIO("O","finished getting xcontent ...");

	$publish  = $m->getParam(4);
	$status = $publish ? 'published' : 'draft';
	logIO('I',"Publish: $publish -> Status: $status");
	logIO('I',"Publish: $publish -> Status: $status");

	xmlrpc_debugmsg( 'Getting xcontent'  );

	$contentstruct = xmlrpc_decode($xcontent); //this does not work properly.... need better decoding

	logIO("O","finished getting contentstruct ...");



//	$content = format_to_post($contentstruct['description']);

	$title = $contentstruct['title'];

	$content = $contentstruct['description'];

	logIO("O","finished getting title ...".$title);

	$categories = $contentstruct['categories'];

	logIO("O","finished getting contentstruct category...".$categories[0]);

	xmlrpc_debugmsg( 'Category: '.$categories[0]  );

// Work on categories
	// Need to look up the ID of each category selected (or at least the first one)



//------------------

// This code is horribly inefficient, will rewrite soon - Tor dec 2004

///

//	$sql = "SELECT * FROM $tablecategories WHERE cat_blog_ID = $blogid AND cat_name = $categories[0] ";

	$sql = "SELECT * FROM $tablecategories WHERE cat_blog_ID = $blogid ";

		logIO("O","sql for finding ID ...".$sql);



	$rows = $DB->get_results( $sql );

	if( !empty($DB->last_error) )

	{ // DB error

		logIO("O","user error finding category info ...");

	}

// Tor - need to sort out default category if none supplied

	$category = $rows[0]->cat_ID;

	logIO("O","have temporary category ID...".$category);



	foreach( $rows as $row )

	{

	logIO("O","finished array element category ...".$row->cat_ID.'name->'.$row->cat_name);

	if ($row->cat_name == $categories[0]) {

		$category = $row->cat_ID;

	logIO("O","have category ID...".$category);

		}

	}

	$postdate = $contentstruct['dateCreated'];

	logIO("O","finished getting contentstruct dateCreated...".$postdate);

//	$postdate = $postdate->scalarval();



	if( ! user_pass_ok($username,$password) )

	{

	logIO("O","error during checking password ...");

		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1

					 'Wrong username/password combination '.$username.' / '.starify($password));

	}

	logIO("O","finished checking password ...");



// Tor 26092004 - I suspect that 0.9.0.11 does something funny with this function

// so some debugging code is probably in order

	$current_User = & $UserCache->get_by_login( $username );
	logIO( 'O', 'Got currentuser ...user_id -> '.$current_User->ID );





//	$blog_ID = get_catblog($post_category);

//	$blogparams = get_blogparams_by_ID( $blog_ID );

//	logIO("O","finished getting blogparams ...blog_id".$blog_ID. " and blogparams");



	// Check permission:

//	if( ! $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog_ID ) )

//	{

//		logIO("O","user error 9 ...");

//		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2

//	}

	logIO("O","finished checking permissions ...");



	if( $postdate != '' )

	{

		$now = $postdate;

	}

	else

	{

		$now = date('Y-m-d H:i:s', (time() + ($Settings->get('time_difference') * 3600)));

	}

	logIO("O","finished checking dates ...".$now);



	// CHECK and FORMAT content - error occur after this line

	//$post_title = format_to_post($post_title, 0, 0);

	$post_title = $title;

	logIO("O","finished converting post_title ...",$post_title);

	//$content = format_to_post($content, 0, 0);  // 25122004 tag - security !!!

	logIO("O","finished converting content ...".$content); // error occurs before this line



	if( $errstring = $Messages->getstring( 'Cannot post, please correct these errors:', '' ) )

	{

		logIO("O","user error 6 ...");

		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6

	}

	logIO("O","finished checking if errors exists, ready to insert into DB ...");





	// INSERT NEW POST INTO DB:

	// Tor - comment this out to stop inserts into database

//	$post_ID = bpost_create( $current_User->ID, $post_title, $content, $now, $category, array(), 'published', $current_User->locale );
	// INSERT NEW POST INTO DB:
	$edited_Item = & new Item();
	$post_ID = $edited_Item->insert( $current_User->ID, $post_title, $content, $now, $category, array( $category ), $status, $current_User->locale, '', 0, $publish );


	if( !empty($DB->last_error) )

	{ // DB error

		logIO("O","user error 9 ...");

		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9

	}

	// pingback( true, $content, $post_title, '', $post_ID, $blogparams, false); // bug here in 9.0.11

	logIO("O","Pinging b2evolution.net...");

//	New error is here somewhere - and of course with ecto these functions are not needed

// so we will need config option to opt out of all of these

//	pingb2evonet( $blogparams, $post_ID, $post_title, false );

//	logIO("O","Pinging Weblogs...");

//	pingWeblogs( $blogparams, false );

//	logIO("O","Pinging Blo.gs...");

//	pingBlogs( $blogparams, false );

//	logIO("O","Pinging Technorati...");

//	pingTechnorati( $blogparams, false );

	return new xmlrpcresp(new xmlrpcval($post_ID));



}







$mweditpost_doc='Adds a post, blogger-api like, +title +category +postdate';

$mweditpost_sig =  array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));

/**

 * mw.EditPost

 *

 * mw API

 *

 * {@internal mweditpost(-) }}

 */

function mweditpost($m)

{

	global $xmlrpcerruser; // import user errcode value
	global $blog_ID, $ItemCache;
	global $cafelogID, $default_category, $DB;
	global $Messages, $UserCache,$Settings;
	logIO("O","start of mwnewpost...");



	$blogid = $m->getParam(0);

	$blogid = $blogid->scalarval();
	logIO("O","finished getting blogid ...".$blogid);

	$post_ID = $blogid;

	logIO("O","finished getting post_ID ...".$post_ID);



	$username = $m->getParam(1);

	$username = $username->scalarval();

	logIO("O","finished getting username ...".$username);



	$password = $m->getParam(2);

	$password = $password->scalarval();

	logIO("O","finished getting password ...");


	if( !user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	if( ! ($edited_Item = $ItemCache->get_by_ID( $post_ID ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}
	$publish = $m->getParam(4);

	$status = $publish ? 'published' : 'draft';
	logIO('I',"Publish: $publish -> Status: $status");


	$newcontent = $m->getParam(3);

//	$newcontent = $newcontent->scalarval();

	logIO("O","finished getting newcontent ...".$newcontent);


	if( ! ($postdata = get_postdata($post_ID)) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}

	logIO('O','Old post Title: '.$postdata['Title']);

	$current_User = & $UserCache->get_by_login( $username );

//	if( ! ($post_category = xmlrpc_getpostcategory($newcontent) ) ) // bug in library Tor 23102005
	if( ! ($post_category = $contentstruct['categories'] ) )
	{ // No category specified
		$post_category = $edited_Item->main_cat_ID;
	}
	elseif( get_the_category_by_ID( $post_category, false ) === false )
	{ // requested Cat does not exist:
	logIO("O","Req category not found");

		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}
	// return new xmlrpcresp(0, $xmlrpcerruser+50, 'post_category='.$post_category );

	$blog_ID = get_catblog($post_category);
	logIO("O","finished getting next blog_ID ...".$blog_ID);

	$blogparams = get_blogparams_by_ID( $blog_ID );

	// Check permission:
	//if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog_ID ) )
//	{
//		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
//				'Permission denied.' );
//	}

	$content = $newcontent;

	$contentstruct = xmlrpc_decode($content);
		$post_title = $contentstruct['title'];

		$content = $contentstruct['description'];
	logIO("O","should now have post title ...".$post_title);
//	$content = xmlrpc_removepostdata($content);
	logIO("O","should now have pure content ...".$content);



	// CHECK and FORMAT content

	//$post_title = format_to_post($post_title, 0, 0);

	logIO("O","finished converting post_title ...".$post_title);

	//$content = format_to_post($content, 0, 0);  // 25122004 tag - security issue - need to sort !!!



	logIO("O","finished converting content ...".$content);



	if( $errstring = $Messages->getstring( 'Cannot post, please correct these errors:', '' ) )

	{

		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6

	}

	logIO("O","finished checking if errors exists, ready to insert into DB ...".'blogid: '.$blogid.' category->'.$post_category );

	// UPDATE POST IN DB:

	$edited_Item->update( $post_title, $content, '', $post_category, array($post_category), $status, '#', '', 0, $pingsdone, '', '', 'open' );


	if( !empty($DB->last_error) )

	{ // DB error

	       logIO("O","We got a database error ...");
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9

	}

	logIO("O","We got no database error ...");

	return new xmlrpcresp(new xmlrpcval($blogid));

}









$mwgetcats_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));

$mwgetcats_doc = 'Get a post, MetaWeblog API-style';

function mwgetcats( $m )

{

	global $xmlrpcerruser, $tablecategories, $DB;



	$blogid = $m->getParam(0);

	$blogid = $blogid->scalarval();



	$username = $m->getParam(1);

	$username = $username->scalarval();



	$password = $m->getParam(2);

	$password = $password->scalarval();



	if( ! user_pass_ok($username,$password) )

	{

		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1

					 'Wrong username/password combination '.$username.' / '.starify($password));

	}



	$sql = "SELECT *

					FROM $tablecategories ";

	if( $blogid > 1 ) $sql .= "WHERE cat_blog_ID = $blogid ";

	$sql .= "ORDER BY cat_name ASC";



	$rows = $DB->get_results( $sql );

	if( !empty($DB->last_error) )

	{ // DB error

		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9

	}



	xmlrpc_debugmsg( 'Categories:'.count($rows) );



	$data = array();

	foreach( $rows as $row )

	{

		$data[] = new xmlrpcval( array( 'categoryID' => new xmlrpcval($row->cat_ID),

										'description' => new xmlrpcval( $row->cat_name ),

										'categoryName' => new xmlrpcval( $row->cat_name ),

										'htmlUrl' => new xmlrpcval( ''),

										'rssUrl' => new xmlrpcval( '' )

			//	mb_convert_encoding( $row->cat_name, "utf-8", "iso-8859-1")  )

														),"struct");

	}



	return new xmlrpcresp( new xmlrpcval($data, "array") );



}









$metawebloggetrecentposts_doc = 'fetches X most recent posts, blogger-api like';

$metawebloggetrecentposts_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt));



function metawebloggetrecentposts( $m )

{

	global $xmlrpcerruser, $tableposts, $tablecategories, $DB, $show_statuses, $UserCache;

	global $blog;



	$blog_ID = $m->getParam(0);

	$blog_ID = $blog_ID->scalarval();



	$username = $m->getParam(1);

	$username = $username->scalarval();



	$password = $m->getParam(2);

	$password = $password->scalarval();



	$numposts = $m->getParam(3);

	$numposts = $numposts->scalarval();

	logIO("O","In metawebloggetrecentposts username is ...".$username);



	if( ! user_pass_ok($username, $password) )

	{

		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1

					 'Wrong username/password combination '.$username.' / '.starify($password));

	}

	logIO("O","In metawebloggetrecentposts, user and pass ok...");




	$current_User = & $UserCache->get_by_login( $username );


	logIO( 'O', 'In metawebloggetrecentposts, current user is ...'.$current_User->ID );



	// Check permission:

	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) )

	{

		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.' ); // user error 2

	}

	logIO("O","In metawebloggetrecentposts, permissions ok...");



	$blog = $blog_ID;	// Some old functions still need this!

	logIO("O","In metawebloggetrecentposts, getting current blog info...");



	// Getting current blog info:

	$blogparams = get_blogparams_by_ID( $blog_ID );

	logIO("O","In metawebloggetrecentposts,  current blog info...". $blogparams);



	// Get the posts to display:

	$MainList = & new ItemList( $blog_ID, $show_statuses, '', '', '', '', array(), '', 'DESC', '', $numposts );



	if( !empty($DB->last_error) )

	{ // DB error

		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9

	}



	xmlrpc_debugmsg( 'Items:'.$MainList->result_num_rows );



	$data = array();



	while( $Item = $MainList->get_item() )

	{

		xmlrpc_debugmsg( 'Item:'.$Item->title.

											' - Issued: '.$Item->issue_date.

											' - Modified: '.$Item->mod_date );



		$post_date = mysql2date("U", $Item->issue_date);

		$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);





//		$content	= '<title>'.$Item->title.'</title>';

//		$content .= '<category>'.$Item->main_cat_ID.'</category>';

//		$content .= $Item->content;

		$content = $Item->content;



		$authorname = $Item->Author->get('preferedname');





		// need a loop here to extract all categoy names

		// $extra_cat_IDs is the variable for the rest of the IDs

		$hope_cat_name = get_the_category_by_ID($Item->main_cat_ID);



		$test = $Item->extra_cat_IDs[0];



		xmlrpc_debugmsg( 'postcats:'.$hope_cat_name["cat_name"]);

		xmlrpc_debugmsg( 'test:'.$test);



		$data[] = new xmlrpcval(array(

									"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),

									"userid" => new xmlrpcval($Item->Author->ID),

									"postid" => new xmlrpcval($Item->ID),

//									"content" => new xmlrpcval($content),

				"categories" => new xmlrpcval(array(new xmlrpcval($hope_cat_name["cat_name"])),'array'),

//				"categories" => new xmlrpcval(array(new xmlrpcval($Item->main_cat_ID)),'array'),

				"title" => new xmlrpcval($Item->title),

				"description" => new xmlrpcval($content),

				"link" => new xmlrpcval($Item->url),

				"permalink" => new xmlrpcval($Item->urltitle),

				"mt_excerpt" => new xmlrpcval($content),

				"mt_allow_comments" => new xmlrpcval('1'),

				"mt_allow_pings" => new xmlrpcval('1'),

				"mt_text_more" => new xmlrpcval('')

									),"struct");

	}



	$resp = new xmlrpcval($data, "array");



	return new xmlrpcresp($resp);



}











$mwgetpost_doc = 'fetches a post, blogger-api like';

$mwgetpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

/**

 * metaweblog.getPost retieves a given post.

 *

 * This API call is not documented on

 * {@link http://www.blogger.com/developers/api/1_docs/}

 *

 * {@internal mwgetpost(-) }}

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

function mwgetpost($m)

{

	global $xmlrpcerruser,$tableposts;



	$post_ID = $m->getParam(0);

	$post_ID = $post_ID->scalarval();



	$username = $m->getParam(1);

	$username = $username->scalarval();



	$password = $m->getParam(2);

	$password = $password->scalarval();



	if( user_pass_ok($username,$password) )

	{

		$postdata = get_postdata($post_ID);



		if( $postdata['Date'] != '' )

		{

			$post_date = mysql2date("U", $postdata["Date"]);

			$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);



			$content = $postdata["Content"];



			$struct = new xmlrpcval(array("link" => new xmlrpcval(''),

											"title" => new xmlrpcval($postdata["Title"]),

											"description" => new xmlrpcval(""),

											"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),

											"userid" => new xmlrpcval(""),

											"postid" => new xmlrpcval($post_ID),

											"content" => new xmlrpcval($content),

											"permalink" => new xmlrpcval(""),

											"categories" => new xmlrpcval($postdata["Category"]),

											"mt_excerpt" => new xmlrpcval(""),

											"mt_allow_comments" => new xmlrpcval("",'int'),

											"mt_allow_pings" => new xmlrpcval("",'int'),

											"mt_text_more" => new xmlrpcval("")

											),"struct");



			$resp = $struct;

			return new xmlrpcresp($resp);

		}

		else

		{

		return new xmlrpcresp(0, $xmlrpcerruser+7, // user error 7

					 "No such post #$post_ID");

		}

	}

	else

	{

		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1

					 'Wrong username/password combination '.$username.' / '.starify($password));

	}

}












/**** SERVER FUNCTIONS ARRAY ****/

$s=new xmlrpc_server(
				array(




							 "metaWeblog.newMediaObject" =>

							 array("function" => "mwnewmediaobj",

										 "signature" => $mwnewmediaobj_sig,

										 "docstring" => $mwnewmediaobj_doc),



							 "metaWeblog.newPost" =>

							 array("function" => "mwnewpost",

										 "signature" => $mwnewpost_sig,

										 "docstring" => $mwnewpost_doc),



							 "metaWeblog.editPost" =>

							 array("function" => "mweditpost",

										 "signature" => $mweditpost_sig,

										 "docstring" => $mweditpost_doc),



							"metaWeblog.getPost" =>

							array("function" => "mwgetpost",

									 "signature" => $mwgetpost_sig,

									 "docstring" => $mwgetpost_doc),





							"metaWeblog.getCategories" =>

							array("function" => "mwgetcats",

										 "signature" => $mwgetcats_sig,

										 "docstring" => $mwgetcats_doc),





							 "metaWeblog.getRecentPosts" =>

							 array("function" => "metawebloggetrecentposts",

										 "signature" => $metawebloggetrecentposts_sig,

										 "docstring" => $metawebloggetrecentposts_doc),







							 "mt.getCategoryList" =>

							 array("function" => "b2getcategories",

										 "signature" => $b2getcategories_sig,

										 "docstring" => $b2getcategories_doc),



							"mt.getPostCategories" =>

							array("function" => "mt_getPostCategories",

	 								"signature" => $mt_getPostCategories_sig,

	 								"docstring" => $mt_getPostCategories_doc),




							 "b2.newPost" =>
							 array("function" => "b2newpost",
										 "signature" => $b2newpost_sig,
										 "docstring" => $b2newpost_doc),

							 "b2.getCategories" =>
							 array("function" => "b2getcategories",
										 "signature" => $b2getcategories_sig,
										 "docstring" => $b2getcategories_doc),

							 "b2.getPostURL" =>
							 array("function" => "pingback_getPostURL",
										 "signature" => $b2_getPostURL_sig,
										 "docstring" => $b2_getPostURL_doc),


							 "blogger.newPost" =>
							 array("function" => "bloggernewpost",
										 "signature" => $bloggernewpost_sig,
										 "docstring" => $bloggernewpost_doc),

							 "blogger.editPost" =>
							 array("function" => "bloggereditpost",
										 "signature" => $bloggereditpost_sig,
										 "docstring" => $bloggereditpost_doc),

							 "blogger.deletePost" =>
							 array("function" => "bloggerdeletepost",
										 "signature" => $bloggerdeletepost_sig,
										 "docstring" => $bloggerdeletepost_doc),

							 "blogger.getUsersBlogs" =>
							 array("function" => "bloggergetusersblogs",
										 "signature" => $bloggergetusersblogs_sig,
										 "docstring" => $bloggergetusersblogs_doc),

							 "blogger.getUserInfo" =>
							 array("function" => "bloggergetuserinfo",
										 "signature" => $bloggergetuserinfo_sig,
										 "docstring" => $bloggergetuserinfo_doc),

							 "blogger.getPost" =>
							 array("function" => "bloggergetpost",
										 "signature" => $bloggergetpost_sig,
										 "docstring" => $bloggergetpost_doc),

							 "blogger.getRecentPosts" =>
							 array("function" => "bloggergetrecentposts",
										 "signature" => $bloggergetrecentposts_sig,
										 "docstring" => $bloggergetrecentposts_doc),

							 "blogger.getTemplate" =>
							 array("function" => "bloggergettemplate",
										 "signature" => $bloggergettemplate_sig,
										 "docstring" => $bloggergettemplate_doc),

							 "blogger.setTemplate" =>
							 array("function" => "bloggersettemplate",
										 "signature" => $bloggersettemplate_sig,
										 "docstring" => $bloggersettemplate_doc),


							 "pingback.ping" =>
							 array("function" => "pingback_ping",
										 "signature" => $pingback_ping_sig,
										 "docstring" => $pingback_ping_doc),

						)
				);

?>
