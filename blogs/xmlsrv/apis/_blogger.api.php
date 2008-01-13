<?php
/**
 * XML-RPC : Blogger API
 *
 * @see http://manual.b2evolution.net/Blogger_API
 * @see http://www.blogger.com/developers/api/1_docs/
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


$bloggernewpost_doc = 'Adds a post, blogger-api like';
$bloggernewpost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
/**
 * blogger.newPost makes a new post to a designated blog.
 *
 * Optionally, will publish the blog after making the post. (In b2evo, this means the
 * new post will be in 'published' state).
 * On success, it returns the unique ID of the new post (usually a seven-digit number
 * at this time).
 * On error, it will return some error message.
 *
 * @see http://www.blogger.com/developers/api/1_docs/xmlrpc_newPost.html
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
function blogger_newpost( $m )
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings, $Messages;

	logIO('Called function: blogger.newPost');

	$blog = $m->getParam(1);
	$blog = $blog->scalarval();

	$username = $m->getParam(2);
	$username = $username->scalarval();

	$password = $m->getParam(3);
	$password = $password->scalarval();

	$content  = $m->getParam(4);
	$content = $content->scalarval();

	$publish  = $m->getParam(5);
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO("Publish: $publish -> Status: $status");

	if( !user_pass_ok($username,$password) )
	{
		logIO( "Wrong username/password combination <strong>$username / $password</strong>");
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	$post_categories = xmlrpc_getpostcategories($content);

	if( empty( $post_categories ) )
	{ // There were no categories passed in the content:

		// Get the Blog we want to post in:
  	$BlogCache = & get_Cache( 'BlogCache' );
		$Blog = & $BlogCache->get_by_ID( $blog );

		if( ! $main_cat = $Blog->get_default_cat_ID() )
		{
			return new xmlrpcresp(0, $xmlrpcerruser+5, 'No default category found for this blog.'); // user error 5
		}
	}
	else
	{
		// echo '<!-- Cats: '.implode(',',$post_categories).' -->';
		$main_cat = array_shift($post_categories);
	}

	logIO( 'Main cat: '.$main_cat);

	// Check if category exists
	if( get_the_category_by_ID( $main_cat, false ) === false )
	{ // Cat does not exist:
		// fp> TODO use $Blog->get_default_cat_ID();
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}

	$blog = get_catblog($main_cat);

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}

	// Extract <title> from content
	$post_title = xmlrpc_getposttitle( $content );

	// cleanup content from extra tags like <category> and <title>:
	$content = xmlrpc_removepostdata( $content );

	$now = date('Y-m-d H:i:s', (time() + $Settings->get('time_difference')));

	// CHECK and FORMAT content
	$post_title = format_to_post($post_title,0,0);
	$content = format_to_post($content,0,0);

	if( $errstring = $Messages->get_string( 'Cannot post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}

	// INSERT NEW POST INTO DB:
	$edited_Item = & new Item();
	$edited_Item->set('title', $post_title);
	$edited_Item->set('content', $content);
	$edited_Item->set('datestart', $now);
	$edited_Item->set('main_cat_ID', $main_cat);
	$edited_Item->set('extra_cat_IDs', $post_categories);
	$edited_Item->set('status', $status);
	$edited_Item->set('locale', $current_User->locale );
	$edited_Item->set_creator_User($current_User);
	$edited_Item->dbinsert();

	if( ! $edited_Item->ID )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'Error while inserting item: '.$DB->last_error ); // user error 9
	}

	logIO( "Posted ! ID: $edited_Item->ID");

	logIO( 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	logIO("All done.");

	return new xmlrpcresp(new xmlrpcval($edited_Item->ID));
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
 * @see http://www.blogger.com/developers/api/1_docs/xmlrpc_editPost.html
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
function blogger_editpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Messages;

	logIO('Called function: blogger.editPost');

	// return new xmlrpcresp(0, $xmlrpcerruser+50, 'blogger_editpost' );

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

	$ItemCache = & get_Cache( 'ItemCache' );
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID, false, false ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}

	$content = $m->getParam(4);
	$content = $content->scalarval();
	$content = str_replace("\n",'',$content); // Tor - kludge to fix bug in xmlrpc libraries
	// WARNING: the following debug MAY produce a non valid response (XML comment containing emebedded <!-- more -->)
	// xmlrpc_debugmsg( 'New content: '.$content  );

	$publish = $m->getParam(5);
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO("Publish: $publish -> Status: $status");

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	$post_categories = xmlrpc_getpostcategories($content);
	if( $post_categories )
	{
		$main_cat = array_shift($post_categories);

		if( get_the_category_by_ID( $main_cat, false ) === false )
		{ // requested Cat does not exist:
			return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested main category does not exist.'); // user error 5
		}
	}
	else
	{
		$main_cat = $edited_Item->main_cat_ID;
	}

	$blog = get_catblog($main_cat);

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.' );
	}

	$post_title = xmlrpc_getposttitle($content);
	$content = xmlrpc_removepostdata($content);

	// CHECK and FORMAT content
	$post_title = format_to_post($post_title,0,0);
	$content = format_to_post($content,0,0);

	if( $errstring = $Messages->get_string( 'Cannot update post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}

	// UPDATE POST IN DB:
	$edited_Item->set( 'title', $post_title );
	$edited_Item->set( 'content', $content );
	if( $post_categories )
	{ // update cats, if given:
		$edited_Item->set( 'main_cat_ID', $main_cat );
		$edited_Item->set( 'extra_cat_IDs', array($post_categories) );
	}
	$edited_Item->set( 'status', $status );
	$edited_Item->dbupdate();

	if( $DB->error )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	logIO( 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	return new xmlrpcresp( new xmlrpcval( 1, 'boolean' ) );
}




$bloggerdeletepost_doc = 'Deletes a post, blogger-api like';
$bloggerdeletepost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.deletePost deletes a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
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
function blogger_deletepost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;

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

	$ItemCache = & get_Cache( 'ItemCache' );
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID, false ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, 'No such post.');	// user error 7
	}

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_del_post', 'any', false, $edited_Item->blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.');
	}

	// DELETE POST FROM DB:
	$edited_Item->dbdelete();
	if( $DB->error )
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
function blogger_getusersblogs($m)
{
	global $xmlrpcerruser;
	global $baseurl;

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();
	logIO("entered blogger_getusersblogs.");


	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	logIO("user approved.");


	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );
	logIO("Got Current user (ID ".$current_User->ID.')');


	$resp_array = array();

	$BlogCache = & get_Cache( 'BlogCache' );

	$blog_array = $BlogCache->load_user_blogs( 'blog_ismember', 'view', $current_User->ID, 'ID' );

	foreach( $blog_array as $l_blog_ID )
	{	// Loop through all blogs that match the requested permission:

		/**
		 * @var Blog
		 */
		$l_Blog = & $BlogCache->get_by_ID( $l_blog_ID );

		logIO("Current user IS a member of this blog.".$l_blog_ID);

		$resp_array[] = new xmlrpcval( array(
					"blogid" => new xmlrpcval( $l_blog_ID ),
					"blogName" => new xmlrpcval( $l_Blog->get('shortname') ),
					"url" => new xmlrpcval( $l_Blog->gen_blogurl() ),
					"isAdmin" => new xmlrpcval( $current_User->check_perm( 'templates', 'any' ), 'boolean')
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
function blogger_getuserinfo($m)
{
	global $xmlrpcerruser;

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();

	$UserCache = & get_Cache( 'UserCache' );
	$User = & $UserCache->get_by_login( $username );

	if( user_pass_ok( $username, $password) )
	{
		$struct = new xmlrpcval( array(
															'nickname' => new xmlrpcval( $User->get('nickname') ),
															'userid' => new xmlrpcval( $User->ID ),
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
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 postid (string): Unique identifier of the post to be deleted.
 *					2 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					3 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function blogger_getpost($m)
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
function blogger_getrecentposts( $m )
{
	global $xmlrpcerruser, $DB;

	$blog = $m->getParam(1);
	$blog = $blog->scalarval();

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

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.' ); // user error 2
	}


	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = & $BlogCache->get_by_ID( $blog );

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
	while( $Item = & $MainList->get_item() )
	{
		xmlrpc_debugmsg( 'Item:'.$Item->title.
											' - Issued: '.$Item->issue_date.
											' - Modified: '.$Item->mod_date );

		$post_date = mysql2date("U", $Item->issue_date);
		$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);

		$content	= '<title>'.$Item->title.'</title>';
		$content .= '<category>'.$Item->main_cat_ID.'</category>';
		$content .= $Item->content;

		// Load Item's creator User:
		$Item->get_creator_User();
		$authorname = $Item->creator_User->get('preferredname');

		$data[] = new xmlrpcval(array(
									"authorName" => new xmlrpcval($authorname),
									"userid" => new xmlrpcval($Item->creator_user_ID),
									"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
									"content" => new xmlrpcval($content),
									"postid" => new xmlrpcval($Item->ID)
									),"struct");
	}

	$resp = new xmlrpcval($data, "array");

	return new xmlrpcresp($resp);

}


$xmlrpc_procs["blogger.newPost"] = array(
				"function" => "blogger_newpost",
				"signature" => $bloggernewpost_sig,
				"docstring" => $bloggernewpost_doc );

$xmlrpc_procs["blogger.editPost"] = array(
				"function" => "blogger_editpost",
				"signature" => $bloggereditpost_sig,
				"docstring" => $bloggereditpost_doc );

$xmlrpc_procs["blogger.deletePost"] = array(
				"function" => "blogger_deletepost",
				"signature" => $bloggerdeletepost_sig,
				"docstring" => $bloggerdeletepost_doc );

$xmlrpc_procs["blogger.getUsersBlogs"] = array(
				"function" => "blogger_getusersblogs",
				"signature" => $bloggergetusersblogs_sig,
				"docstring" => $bloggergetusersblogs_doc );

$xmlrpc_procs["blogger.getUserInfo"] = array(
				"function" => "blogger_getuserinfo",
				"signature" => $bloggergetuserinfo_sig,
				"docstring" => $bloggergetuserinfo_doc );

$xmlrpc_procs["blogger.getPost"] = array(
				"function" => "blogger_getpost",
				"signature" => $bloggergetpost_sig,
				"docstring" => $bloggergetpost_doc );

$xmlrpc_procs["blogger.getRecentPosts"] = array(
				"function" => "blogger_getrecentposts",
				"signature" => $bloggergetrecentposts_sig,
				"docstring" => $bloggergetrecentposts_doc );

/*
 * $Log$
 * Revision 1.5  2008/01/13 03:12:06  fplanque
 * XML-RPC API debugging
 *
 * Revision 1.4  2008/01/12 22:51:11  fplanque
 * RSD support
 *
 * Revision 1.3  2008/01/12 08:06:15  fplanque
 * more xmlrpc tests
 *
 */
?>