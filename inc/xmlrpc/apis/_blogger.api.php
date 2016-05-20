<?php
/**
 * XML-RPC : Blogger API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @see http://b2evolution.net/man/blogger-api
 * @see http://www.blogger.com/developers/api/1_docs/
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/
 *
 * @package xmlsrv
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
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggernewpost.html
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
	global $Settings;
	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 2, 3 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 1 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	$content = $m->getParam(4);
	$content = $content->scalarval();

	$publish  = $m->getParam(5);
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO("Publish: $publish -> Status: $status");

	$title = xmlrpc_getposttitle( $content );
	$cat_IDs = xmlrpc_getpostcategories( $content );

	// Cleanup content from extra tags like <category> and <title>:
	$content = xmlrpc_removepostdata( $content );

	$params = array(
			'title'		=> $title,
			'content'	=> $content,
			'cat_IDs'	=> $cat_IDs,
			'status'	=> $status,
		);

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $params, $Blog );
}


$bloggereditpost_doc='Edits a post, blogger-api like';
$bloggereditpost_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
/**
 * blogger.editPost changes the contents of a given post.
 *
 * Optionally, will publish the blog the post belongs to after changing the post.
 * (In b2evo, this means the changed post will be moved to published state).
 * On success, it returns a boolean true value.
 * On error, it will return a fault with an error message.
 *
 * @see http://www.blogger.com/developers/api/1_docs/xmlrpc_editPost.html
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggereditpost.html
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
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 1 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// We need to be able to edit this post:
	if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	{
		return xmlrpcs_resperror( 3 ); // Permission denied
	}

	$content = $m->getParam(4);
	$content = $content->scalarval();

	$publish = $m->getParam(5);
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO("Publish: $publish -> Status: $status");

	$title = xmlrpc_getposttitle( $content );
	$cat_IDs = xmlrpc_getpostcategories( $content );

	// Cleanup content from extra tags like <category> and <title>:
	$content = xmlrpc_removepostdata( $content );

	$params = array(
			'title'		=> $title,
			'content'	=> $content,
			'cat_IDs'	=> $cat_IDs,
			'status'	=> $status,
		);

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_edit_item( $edited_Item, $params );
}




$bloggerdeletepost_doc = 'Deletes a post, blogger-api like';
$bloggerdeletepost_sig = array(
		array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean),
		array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString),
	);
/**
 * blogger.deletePost deletes a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggerdeletepost.html
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



$bloggergetusersblogs_doc='returns the user\'s blogs - this is a dummy function, just so that BlogBuddy and other blogs-retrieving apps work';
$bloggergetusersblogs_sig=array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getUsersBlogs returns information about all the blogs a given user is a member of.
 *
 * Data is returned as an array of <struct>s containing the ID (blogid), name (blogName),
 * and URL (url) of each blog.
 *
 * Non official: Also return a boolean stating wether or not the user can edit th eblog templates
 * (isAdmin).
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_getUsersBlogs.html}
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggergetusersblogs.html
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
function blogger_getusersblogs($m)
{
	logIO('blogger_getusersblogs start');
	return _wp_or_blogger_getusersblogs( 'blogger', $m );
}




$bloggergetuserinfo_doc='gives the info about a user';
$bloggergetuserinfo_sig=array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getUserInfo returns returns a struct containing user info.
 *
 * Data returned: userid, firstname, lastname, nickname, email, and url.
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_getUserInfo.html}
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggergetuserinfo.html
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
	// CHECK LOGIN:
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// INFO about logged in user
	$struct = new xmlrpcval( array(
			'nickname'  => new xmlrpcval( $current_User->get('nickname') ),
			'userid'    => new xmlrpcval( $current_User->ID ),
			'url'       => new xmlrpcval( $current_User->get('url') ),
			'email'     => new xmlrpcval( $current_User->get('email') ),
			'lastname'  => new xmlrpcval( $current_User->get('lastname') ),
			'firstname' => new xmlrpcval( $current_User->get('firstname') )
		), 'struct' );

	logIO( 'OK.' );
	return new xmlrpcresp( $struct );
}




$bloggergetpost_doc = 'fetches a post, blogger-api like';
$bloggergetpost_sig = array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getPost retieves a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggergetpost.html
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
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 1 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION:
	if( ! xmlrpcs_can_view_item( $edited_Item, $current_User ) )
	{
		return xmlrpcs_resperror( 3 );
	}
	logIO( 'Permission granted.' );

	$post_date = mysql2date( 'U', $edited_Item->issue_date );
	$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);

	$content	= '<title>'.$edited_Item->title.'</title>';
	$content .= '<category>'.$edited_Item->main_cat_ID.'</category>';
	$content .= $edited_Item->content;

	$struct = new xmlrpcval( array(
									'userid'      => new xmlrpcval( $edited_Item->creator_user_ID ),
									'dateCreated' => new xmlrpcval( $post_date, 'dateTime.iso8601' ),
									'content'     => new xmlrpcval( $content ),
									'postid'      => new xmlrpcval( $edited_Item->ID )
								), 'struct' );

	logIO( 'OK.' );
	return new xmlrpcresp($struct);
}




$bloggergetrecentposts_doc = 'fetches X most recent posts, blogger-api like';
$bloggergetrecentposts_sig = array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcInt));
/**
 * blogger.getRecentPosts retieves X most recent posts.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 * @see http://www.sixapart.com/developers/xmlrpc/blogger_api/bloggergetrecentposts.html
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

	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 2, 3 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 1 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	$numposts = $m->getParam(4);
	$numposts = $numposts->scalarval();


	// Get the posts to display:
	load_class( 'items/model/_itemlist.class.php', 'ItemList' );
	$MainList = new ItemList2( $Blog, NULL, NULL, $numposts );

	// Protected and private get checked by statuses_where_clause().
	$statuses = array( 'published', 'redirected', 'protected', 'private' );
	if( $current_User->check_perm( 'blog_ismember', 'view', false, $Blog->ID ) )
	{	// These statuses require member status:
		$statuses = array_merge( $statuses, array( 'draft', 'deprecated' ) );
	}
	logIO( 'Statuses: '.implode( ', ', $statuses ) );

	$MainList->set_filters( array(
			'visibility_array' => $statuses,
			'order' => 'DESC',
			'unit' => 'posts',
		) );

	// Run the query:
	$MainList->query();


	logIO( 'Items:'.$MainList->result_num_rows );

	$data = array();
	while( $Item = & $MainList->get_item() )
	{
		logIO( 'Item:'.$Item->title.
					' - Issued: '.$Item->issue_date.
					' - Modified: '.$Item->datemodified );

		$post_date = mysql2date('U', $Item->issue_date);
		$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);

		$content	= '<title>'.$Item->title.'</title>';
		$content .= '<category>'.$Item->main_cat_ID.'</category>';
		$content .= $Item->content;

		// Load Item's creator User:
		$Item->get_creator_User();
		$authorname = $Item->creator_User->get('preferredname');

		$data[] = new xmlrpcval(array(
									'authorName' => new xmlrpcval($authorname),
									'userid' => new xmlrpcval($Item->creator_user_ID),
									'dateCreated' => new xmlrpcval($post_date,'dateTime.iso8601'),
									'content' => new xmlrpcval($content),
									'postid' => new xmlrpcval($Item->ID)
									),'struct');
	}

	$resp = new xmlrpcval($data, 'array');

	logIO( 'OK.' );
	return new xmlrpcresp($resp);
}


$xmlrpc_procs['blogger.newPost'] = array(
				'function' => 'blogger_newpost',
				'signature' => $bloggernewpost_sig,
				'docstring' => $bloggernewpost_doc );

$xmlrpc_procs['blogger.editPost'] = array(
				'function' => 'blogger_editpost',
				'signature' => $bloggereditpost_sig,
				'docstring' => $bloggereditpost_doc );

$xmlrpc_procs['blogger.deletePost'] = array(
				'function' => 'blogger_deletepost',
				'signature' => $bloggerdeletepost_sig,
				'docstring' => $bloggerdeletepost_doc );

$xmlrpc_procs['blogger.getUsersBlogs'] = array(
				'function' => 'blogger_getusersblogs',
				'signature' => $bloggergetusersblogs_sig,
				'docstring' => $bloggergetusersblogs_doc );

$xmlrpc_procs['blogger.getUserInfo'] = array(
				'function' => 'blogger_getuserinfo',
				'signature' => $bloggergetuserinfo_sig,
				'docstring' => $bloggergetuserinfo_doc );

$xmlrpc_procs['blogger.getPost'] = array(
				'function' => 'blogger_getpost',
				'signature' => $bloggergetpost_sig,
				'docstring' => $bloggergetpost_doc );

$xmlrpc_procs['blogger.getRecentPosts'] = array(
				'function' => 'blogger_getrecentposts',
				'signature' => $bloggergetrecentposts_sig,
				'docstring' => $bloggergetrecentposts_doc );

?>