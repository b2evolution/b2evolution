<?php
/**
 * XML-RPC : Wordpress API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author waltercruz
 *
 * @see http://codex.wordpress.org/XML-RPC_wp
 *
 * @package xmlsrv
 * @version $Id: _wordpress.api.php 7482 2014-10-21 11:50:57Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$wordpressgetusersblogs_doc = 'returns information about all the blogs a given user is a member of.';
$wordpressgetusersblogs_sig = array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString));
/**
 * wp.getUsersBlogs
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getUsersBlogs
 *
 * Data is returned as an array of <struct>s containing the ID (blogid), name (blogName),
 * and URL (url) of each blog.
 *
 * Non official: Also return a boolean stating wether or not the user can edit the blog options
 * (isAdmin). Also return a value for xmlrpc url.
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 username (string): Login for the Blogger user who's blogs will be retrieved.
 *					1 password (string): Password for said username.
 *						(currently not required by b2evo)
 * @return xmlrpcresp XML-RPC Response, an array of <struct>s containing for each blog:
 *					- isAdmin (boolean)
 *					- url (string)
 *					- blogid (string)
 *					- blogName (string)
 *					- xmlrpc (string)
 */
function wp_getusersblogs($m)
{
	return _wp_or_blogger_getusersblogs( 'wp', $m );
}


$wordpressgetauthors_doc = 'Retrieve list of all authors.';
$wordpressgetauthors_sig = array(array($xmlrpcArray,$xmlrpcInt,$xmlrpcString,$xmlrpcString));
/**
 * wp.getAuthors
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getAuthors
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.

 */
function wp_getauthors($m)
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

	if( ! $current_User->check_perm('users', 'view') )
	{
		return xmlrpcs_resperror( 5, T_('You have no permission to view other users!') );
	}

	load_class( 'users/model/_userlist.class.php', 'UserList' );
	$UserList = new UserList( '', NULL, 'u_', array( 'join_group' => false, 'join_session' => false, 'join_country' => false, 'join_city' => false ) );

	// Run the query:
	$UserList->query();

	logIO( 'Found users: '.$UserList->result_num_rows );

	$data = array();
	while( $User = & $UserList->get_next() )
	{
		$data[] = new xmlrpcval(array(
				'user_id' => new xmlrpcval( $User->ID, 'int' ),
				'user_login' => new xmlrpcval( $User->login ),
				'display_name' => new xmlrpcval( $User->get_preferred_name() ),
			),'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}


$wordpressgettags_doc = 'Get list of all tags.';
$wordpressgettags_sig =  array(array($xmlrpcArray,$xmlrpcInt,$xmlrpcString,$xmlrpcString));
/**
 * wp.getTags
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getTags
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 */
function wp_gettags( $m )
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

	$data = array();
	$tags = get_tags( $Blog->ID );

	if( !empty($tags) )
	{
		logIO( 'Got '.count($tags).' tags' );

		load_funcs( '_core/_url.funcs.php' );

		$BlogCache = & get_BlogCache();

		foreach( $tags as $tag )
		{
			if( ($l_Blog = & $BlogCache->get_by_id( $tag->cat_blog_ID, false )) === false ) continue;

			$tag_url = $l_Blog->gen_tag_url($tag->tag_name);

			$data[] = new xmlrpcval(array(
					'tag_id' => new xmlrpcval( $tag->tag_ID, 'int' ),
					'name' => new xmlrpcval( $tag->tag_name ),
					'count' => new xmlrpcval( $tag->tag_count, 'int'),
					'slug' => new xmlrpcval(''), // not used in b2evolution
					'html_url' => new xmlrpcval( $tag_url ),
					'rss_url' => new xmlrpcval( url_add_param($tag_url, 'tempskin=_rss2', '&') ),
				),'struct');
		}
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}


$wordpressgetpagelist_doc = 'Get an array of all the pages on a blog. Just the minimum details, lighter than wp.getPages.';
$wordpressgetpagelist_sig =  array(array($xmlrpcArray,$xmlrpcInt,$xmlrpcString,$xmlrpcString));
/**
 * wp.getPageList
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getPageList
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 */
function wp_getpagelist( $m )
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

	// Get the pages to display:
	load_class( 'items/model/_itemlistlight.class.php', 'ItemListLight' );
	$MainList = new ItemListLight( $Blog, NULL, NULL, 0 );

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
			'types' => '1000',
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

		$data[] = new xmlrpcval(array(
				'page_id' => new xmlrpcval($Item->ID, 'int'),
				'page_title' => new xmlrpcval($Item->title),
				'page_parent_id' => new xmlrpcval( (isset($Item->parent_ID) ? $Item->parent_ID : 0), 'int'),
				'dateCreated' => new xmlrpcval( datetime_to_iso8601($Item->issue_date), 'dateTime.iso8601' ),
				'date_created_gmt' => new xmlrpcval( datetime_to_iso8601($Item->issue_date, true), 'dateTime.iso8601' ),
			),'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}


$wordpressgetpages_doc = 'Get an array of all the pages on a blog.';
$wordpressgetpages_sig =  array(array($xmlrpcArray,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * wp.getPages
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getPages
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 max_pages (int) optional, default=10.
 */
function wp_getpages( $m )
{
	logIO( 'wp_getpages start' );

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
			'types' => '1000',
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


$wordpressgetpage_doc = 'Get the page identified by the page id.';
$wordpressgetpage_sig =  array(array($xmlrpcArray,$xmlrpcInt,$xmlrpcInt,$xmlrpcString,$xmlrpcString));
/**
 * wp.getPage
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getPage
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 page_id (int): Requested page ID.
 *					2 username (string): User login.
 *					3 password (string): Password for said username.
 */
function wp_getpage( $m )
{
	logIO( 'wp_getpage start' );

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
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	$item_ID = $m->getParam(1);
	$item_ID = abs($item_ID->scalarval());

	$items = xmlrpc_get_items( array(
			'item_ID' => $item_ID,
		), $Blog );

	if( empty($items) )
	{
		return xmlrpcs_resperror( 6, 'Requested post/Item ('.$item_ID.') does not exist.' );
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $items[0], 'struct' ) );
}

$wordpressgetpagestatuslist_doc = 'Retrieve all of the WordPress supported page statuses.';
$wordpressgetpoststatuslist_doc = 'Retrieve post statuses.';
$wordpressgetpagestatuslist_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString));
/**
 * wp.getPageStatusList
 * wp.getPostStatusList
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getPageStatusList
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 */
function wp_getpagestatuslist( $m )
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

	$status_list = array();

	if( $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID ) )
	{
		$status_list[ wp_or_b2evo_item_status('published', 'wp') ] = new xmlrpcval(T_('Published')) ;
	}

	if( $current_User->check_perm( 'blog_post!protected', 'edit', false, $Blog->ID ) )
	{	// Not supported by WP, maps to 'private'
		$status_list[ wp_or_b2evo_item_status('protected', 'wp') ] = new xmlrpcval(T_('Protected')) ;
	}

	if( $current_User->check_perm( 'blog_post!private', 'edit', false, $Blog->ID ) )
	{
		$status_list[ wp_or_b2evo_item_status('private', 'wp') ] = new xmlrpcval(T_('Private')) ;
	}

	if( $current_User->check_perm( 'blog_post!draft', 'edit', false, $Blog->ID ) )
	{
		$status_list[ wp_or_b2evo_item_status('draft', 'wp') ] = new xmlrpcval(T_('Draft')) ;
	}

	if( $current_User->check_perm( 'blog_post!deprecated', 'edit', false, $Blog->ID ) )
	{
		$status_list[ wp_or_b2evo_item_status('deprecated', 'wp') ] = new xmlrpcval(T_('Deprecated')) ;
	}

	if( $current_User->check_perm( 'blog_post!redirected', 'edit', false, $Blog->ID ) )
	{	// Not supported by WP, maps to 'published'
		$status_list[ wp_or_b2evo_item_status('redirected', 'wp') ] = new xmlrpcval(T_('Redirected')) ;
	}
	return new xmlrpcresp(  new xmlrpcval($status_list,'struct') );
}


$wordpressgetpostformats_doc = 'Retrieve post formats.';
$wordpressgetpostformats_sig =  array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString), // WP for iOS
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct), // WP specs
	);
/**
 * wp.getPostFormats
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getPostFormats
 *
 * Note: by default (no filter) we return supported formats only.
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 filter (struct):
 * 						- show-supported
 */
function wp_getpostformats( $m )
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

	if( isset($m->params[3]) )
	{
		$xcontent = $m->getParam(3);
		$contentstruct = xmlrpc_decode_recurse($xcontent);
	}

	global $posttypes_reserved_IDs, $posttypes_perms;

	// Compile an array of post type IDs to exclude:
	$exclude_posttype_IDs = $posttypes_reserved_IDs;

	foreach( $posttypes_perms as $l_permname => $l_posttype_IDs )
	{
		if( ! $current_User->check_perm( 'blog_'.$l_permname, 'edit', false, $Blog->ID ) )
		{	// No permission to use this post type(s):
			$exclude_posttype_IDs = array_merge( $exclude_posttype_IDs, $l_posttype_IDs );
		}
	}

	$saved_global = $posttypes_reserved_IDs; // save
	$posttypes_reserved_IDs = $exclude_posttype_IDs;

	$ItemTypeCache = & get_ItemTypeCache();

	$supported = $ItemTypeCache->get_option_array();
	ksort($supported);

	$posttypes_reserved_IDs = $saved_global; // restore

	$all = $ItemTypeCache->get_option_array();
	ksort($all);

	logIO( "All item types:\n".var_export($all, true) );
	logIO( "Supported item types:\n".var_export($supported, true) );

	$all_types = $supported_types = array();
	foreach( $all as $k=>$v )
	{
		$all_types[ strval($k) ] = new xmlrpcval($v);
	}

	foreach( $supported as $k=>$v )
	{
		$supported_types[ strval($k) ] = new xmlrpcval($v);
	}

	if( !empty($contentstruct) && is_array($contentstruct) )
	{	// Make sure there's a filter 'show-supported' that evaluates to TRUE
		if( isset($contentstruct['show-supported']) && $contentstruct['show-supported'] )
		{	// Display both 'all' and 'supported' post types
			$types = array(
					'all'		=> new xmlrpcval($all_types, 'struct'),
					'supported'	=> php_xmlrpc_encode( array_keys($supported_types) ),
				);

			logIO('OK.');
			return new xmlrpcresp( new xmlrpcval($types, 'struct') );
		}
	}

	logIO('OK.');
	return new xmlrpcresp( new xmlrpcval($supported_types, 'struct') );
}


$wordpressnewpage_doc = 'Create a new page';
$wordpressnewpage_sig = array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct),
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean),
	);
/**
 * wp.newPage
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.newPage
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 *					4 publish (bool)
 */
function wp_newpage( $m )
{	// Call metaWeblog.newPost
	return mw_newpost( $m, 'page' );
}


$wordpresseditpage_doc = 'Make changes to a blog page';
$wordpresseditpage_sig = array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct),
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean),
	);
/**
 * wp.editPage
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.editPage
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 postid (string): Unique identifier of the post to edit
 *					2 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					3 password (string): Password for said username.
 *					4 struct (struct)
 *					5 publish (bool)
 */
function wp_editpage( $m )
{
	// Arrange args in the way mw_editpost() understands.
	array_shift($m->params);

	// Call metaWeblog.editPost
	return mw_editpost( $m, 'page' );
}


$wordpressdeletepage_doc = 'Removes a page from the blog';
$wordpressdeletepage_sig = array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * wp.deletePage
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.deletePage
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 * 					3 postid (string): Unique identifier of the post to edit
 */
function wp_deletepage( $m )
{
	// CHECK LOGIN:
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 3 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	return xmlrpcs_delete_item( $edited_Item );
}


$wordpressUploadFile_doc = 'Uploads a file to the media library of the blog';
$wordpressUploadFile_sig = array(array( $xmlrpcStruct, $xmlrpcInt, $xmlrpcString, $xmlrpcString, $xmlrpcStruct ));
/**
 * wp.uploadFile
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.uploadFile
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 * 							- name : filename
 * 							- type : mimetype
 * 							- bits : base64 encoded file
 *							- overwrite : boolean
 * @return xmlrpcresp XML-RPC Response
 */
function wp_uploadfile($m)
{
	return _wp_mw_newmediaobject( $m );
}


$wordpressgetcats_sig =  array(array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$wordpressgetcats_doc = 'Get categories of a post, MetaWeblog API-style';
/**
 * wp.getCategories
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getCategories
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 */
function wp_getcategories( $m )
{
	return _wp_mw_getcategories( $m );
}


$wordpresssuggestcats_sig =  array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcString),
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt),
	);
$wordpresssuggestcats_doc = 'Get an array of categories that start with a given string.';
/**
 * wp.suggestCategories
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.suggestCategories
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 search (string): search string
 *					4 max_results (int)
 */
function wp_suggestcategories( $m )
{
	// Note: we display all cats if search string is empty
	$params['search'] = '';

	if( isset($m->params[3]) )
	{
		$search = $m->getParam(3);
		$params['search'] = trim($search->scalarval());
	}

	if( isset($m->params[4]) )
	{
		$limit = $m->getParam(4);
		$params['limit'] = abs($limit->scalarval());
	}

	return _wp_mw_getcategories( $m, $params );
}


$wordpressnewcategory_sig =  array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct),
		array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct),
	);
$wordpressnewcategory_doc = 'Create a new category.';
/**
 * wp.newCategory
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.newCategory
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 params (struct):
 *						- name (string)
 *						- slug (string)
 *						- parent_id (int)
 *						- description (string)
 */
function wp_newcategory( $m )
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

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	if( ! $current_User->check_perm( 'blog_cats', '', false, $Blog->ID ) )
	{
		return xmlrpcs_resperror( 5, 'You are not allowed to add or edit categories in this blog.' );
	}

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);

	$slug = strtolower($contentstruct['name']);
	if( !empty($contentstruct['slug']) )
	{
		$slug = $contentstruct['slug'];
	}

	load_class('chapters/model/_chapter.class.php', 'Chapter');

	$new_Chapter = new Chapter(NULL, $Blog->ID);
	$new_Chapter->set('name', $contentstruct['name']);
	$new_Chapter->set('urlname', $slug);
	$new_Chapter->set('parent_ID', intval($contentstruct['parent_id']));

	if( !empty($contentstruct['description']) )
	{	// Set decription
		$new_Chapter->set('description', $contentstruct['description']);
	}

	$cat_ID = $new_Chapter->dbinsert();

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval($cat_ID, 'int') );
}


$wordpressdeletecategory_doc = 'Remove category.';
$wordpressdeletecategory_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * wp.deleteCategory
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.deleteCategory
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 category_id (int): Category ID to delete
 */
function wp_deletecategory( $m )
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

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	if( ! $current_User->check_perm( 'blog_cats', 'edit', false, $Blog->ID ) )
	{	// Permission denied
		return xmlrpcs_resperror( 5, 'You are not allowed to delete categories in this blog.' );
	}

	/**
	 * @var Comment
	 */
	if( ! $edited_Chapter = & xmlrpcs_get_Chapter( $m, 3 ) )
	{	// Return (last) error:
		return xmlrpcs_resperror();
	}

	$restriction_Messages = $edited_Chapter->check_relations( 'delete_restrictions' );
	if( $restriction_Messages->count() )
	{
		return xmlrpcs_resperror( 5, $restriction_Messages->get_string( T_('The following relations prevent deletion:'),
					T_('Please delete related objects before you proceed.'), "  //  \n", 'xmlrpc' ) );
	}

	$ok = (bool) $edited_Chapter->dbdelete();

	logIO( 'Category deleted: '.($ok ? 'yes' : 'no') );

	return new xmlrpcresp( new xmlrpcval( $ok, 'boolean' ) );
}


$wordpressgetcommentstatuslist_doc = 'Retrieve all of the comment status.';
$wordpressgetcommentstatuslist_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString));
/**
 * wp.getCommentStatusList
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getCommentStatusList
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 */
function wp_getcommentstatuslist( $m )
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

	$statuses = array();
	foreach( get_allowed_statuses() as $status )
	{
		switch($status)
		{
			case 'draft':
				$statuses[] = new xmlrpcval('hold');

			case 'published':
				$statuses[] = new xmlrpcval('approve');

			case 'deprecated':
				$statuses[] = new xmlrpcval('spam');
		}
	}

	if( $current_User->check_perm('blog_comment!trash', '', false, $Blog->ID) )
	{
		$statuses[] = new xmlrpcval('trash');
	}

	return new xmlrpcresp( new xmlrpcval($statuses,'struct') );
}


$wordpressgetcomments_doc = 'Gets a set of comments for a given post.';
$wordpressgetcomments_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct));
/**
 * wp.getComments
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getComments
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 params (struct): Filter array
 *						- post_id : The post where the comment is posted. Empty string shows all comments.
 *						- status (defaults to published) : Filter by status (published, deprecated, draft, trash)
 *						- number : Total number of comments to retrieve.
 *						- offset : Not used
 */
function wp_getcomments( $m )
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

	$limit = 10;
	$contentstruct['number'] = abs( intval($contentstruct['number']) );
	if( $contentstruct['number'] > 0 )
	{
		$limit = $contentstruct['number'];
	}

	$status = '';
	if( !empty($contentstruct['status']) )
	{
		$status = wp_or_b2evo_comment_status( $contentstruct['status'], 'b2evo' );
	}

	$item_ID = isset($contentstruct['post_id']) ? $contentstruct['post_id'] : 0;

	$comments = xmlrpc_get_comments( array(
			'limit'		=> $limit,
			'statuses'	=> $status,
			'item_ID'	=> $item_ID,
		), $Blog );

	if( empty($comments) )
	{
		return new xmlrpcresp( new xmlrpcval( array(), 'array' ) );
	}

	$data = array();
	foreach( $comments as $comment )
	{
		$data[] = new xmlrpcval( $comment, 'struct' );
	}
	logIO( 'OK.' );

	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}


$wordpressgetcomment_doc = 'Gets a comment, given it\'s comment ID';
$wordpressgetcomment_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * wp.getComments
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getComment
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 comment_id (int) : Requested comment ID
 */
function wp_getcomment( $m )
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

	$comment_ID = $m->getParam(3);
	$comment_ID = abs($comment_ID->scalarval());

	$comments = xmlrpc_get_comments( array(
			'comment_ID' => $comment_ID,
		), $Blog );

	if( empty($comments) )
	{
		return xmlrpcs_resperror( 6, 'Requested comment ('.$comment_ID.') does not exist.' );
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $comments[0], 'struct' ) );
}


$wordpressgetcommentcount_doc = 'Retrieve comment count for a specific post.';
$wordpressgetcommentcount_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * wp.getCommentCount
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getCommentCount
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 post_id (int): The id of the post
 */
function wp_getcommentcount( $m )
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

	$approved = $awaiting = $spam = $total = 0;
	if( $Blog->get_setting( 'allow_comments' ) != 'never' )
	{
		$item_ID = $m->getParam(3);
		$item_ID = $item_ID->scalarval();

		$approved	= generic_ctp_number($item_ID, 'feedbacks');
		$awaiting	= generic_ctp_number($item_ID, 'feedbacks', 'draft');
		$spam		= generic_ctp_number($item_ID, 'feedbacks', 'deprecated');
		$total		= generic_ctp_number($item_ID, 'feedbacks', 'total');
	}

	// Maybe we should do a check_perm here?
	$data = array(
			'approved'				=> new xmlrpcval( $approved, 'int' ),
			'awaiting_moderation'	=> new xmlrpcval( $awaiting, 'int' ),
			'spam'					=> new xmlrpcval( $spam,'int' ),
			'total_comment'			=> new xmlrpcval( $total,'int' ),
		);

	logIO( "published: $approved, draft: $awaiting, deprecated: $spam, total: $total" );

	return new xmlrpcresp( new xmlrpcval( $data, 'struct' ) );
}


$wordpressnewcomment_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt,$xmlrpcStruct));
$wordpressnewcomment_doc = 'Create new comment.';
/**
 * wp.newComment
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.newComment
 *
 * Leave the second and third parameter blank to send anonymous comments
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 post_id (int): Target post ID
 *					4 params (struct):
 *						- comment_parent (int)
 *						- content (string)
 *						- author (string)
 *						- author_url (string)
 *						- author_email (string)
 */
function wp_newcomment( $m )
{
	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Blog not found
		return xmlrpcs_resperror();
	}

	if( ! $commented_Item = & xmlrpcs_get_Item( $m, 3 ) )
	{	// Item not found
		return xmlrpcs_resperror();
	}

	$username = $m->getParam(1);
	$username = $username->scalarval();

	$password = $m->getParam(2);
	$password = $password->scalarval();

	$options = $m->getParam(4);
	$options = xmlrpc_decode_recurse($options);

	logIO( 'Params: '.var_export($options, true) );

	$User = NULL;
	if( !empty($password) || !empty($username) )
	{	// Not an anonymous comment, let's check username

		// CHECK LOGIN:
		/**
		 * @var User
		 */
		if( ! $User = & xmlrpcs_login( $m, 1, 2 ) )
		{	// Login failed, return (last) error:
			return xmlrpcs_resperror();
		}
	}

	$params = array(
			'User'				=> & $User,
			'password'			=> $password,
			'username'			=> $username,
			'content'			=> $options['content'],
			'comment_parent'	=> intval($options['comment_parent']),
			'author'			=> $options['author'],
			'author_url'		=> $options['author_url'],
			'author_email'		=> $options['author_email'],
		);

	return xmlrpcs_new_comment( $params, $commented_Item );
}


$wordpresseditcomment_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt,$xmlrpcStruct));
$wordpresseditcomment_doc = 'Edit comment.';
/**
 * wp.editComment
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.editComment
 *
 * Leave the second and third parameter blank to send anonymous comments
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 comment_id (int): Target post ID
 *					4 params (struct):
 *						- status (string)
 *						- date_created_gmt (string)
 *						- content (string)
 *						- author (string)
 *						- author_url (string)
 *						- author_email (string)
 */
function wp_editcomment( $m )
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
	{	// Blog not found
		return xmlrpcs_resperror();
	}

	/**
	 * @var Comment
	 */
	if( ! $edited_Comment = & xmlrpcs_get_Comment( $m, 3 ) )
	{	// Return (last) error:
		return xmlrpcs_resperror();
	}

	if( ! $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $edited_Comment ) )
	{	// Permission denied
		return xmlrpcs_resperror(3);
	}

	$options = $m->getParam(4);
	$options = xmlrpc_decode_recurse($options);

	//logIO( 'Params: '.var_export($options, true) );

	$params = array(
			'status'		=> wp_or_b2evo_comment_status($options['status'], 'b2evo'),
			'date'			=> _mw_decode_date($options),
			'content'		=> $options['content'],
			'author'		=> isset($options['author']) ? $options['author'] : '',
			'author_url'	=> isset($options['author_url']) ? $options['author_url'] : '',
			'author_email'	=> isset($options['author_email']) ? $options['author_email'] : '',
		);

	return xmlrpcs_edit_comment( $params, $edited_Comment );
}


$wordpressdeletecomment_doc = 'Remove comment.';
$wordpressdeletecomment_sig =  array(array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * wp.deleteComment
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.deleteComment
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 comment_id (int): Comment ID to delete
 */
function wp_deletecomment( $m )
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

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	/**
	 * @var Comment
	 */
	if( ! $edited_Comment = & xmlrpcs_get_Comment( $m, 3 ) )
	{	// Return (last) error:
		return xmlrpcs_resperror();
	}

	if( ! $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $edited_Comment ) )
	{	// Permission denied
		return xmlrpcs_resperror(3);
	}

	$ok = (bool) $edited_Comment->dbdelete();

	logIO( 'Comment deleted: '.($ok ? 'yes' : 'no') );

	return new xmlrpcresp( new xmlrpcval( $ok, 'boolean' ) );
}


$wordpressgetoptions_doc = 'Retrieve blog options';
$wordpressgetoptions_sig =  array(
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString),
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcStruct),
		array($xmlrpcStruct,$xmlrpcInt,$xmlrpcString,$xmlrpcString,$xmlrpcArray),
	);
/**
 * wp.getOptions
 *
 * @see http://codex.wordpress.org/XML-RPC_wp#wp.getOptions
 *
 * Note: If passing in a struct, search for options listed within it.
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (int): Unique identifier of the blog.
 *					1 username (string): User login.
 *					2 password (string): Password for said username.
 *					3 options (struct)
 */
function wp_getoptions( $m )
{
	global $Settings;

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

	if( isset($m->params[3]) )
	{
		$options = $m->getParam(3);
		$options = xmlrpc_decode_recurse($options);
	}

	$defaults = array(
			'software_name'			=> array( 'desc' => 'Software Name', 'value' => 'WordPress' ), // Pretend that we are running WP
			'software_version'		=> array( 'desc' => 'Software Version', 'value' => '3.3.2' ),
			'blog_url'				=> array( 'desc' => 'Site URL', 'value' => $Blog->gen_blogurl() ),
			'blog_title'			=> array( 'desc' => 'Site TitleL', 'value' => $Blog->get('name') ),
			'blog_tagline'			=> array( 'desc' => 'Site Tagline', 'value' => $Blog->get('tagline') ),
			'date_format'			=> array( 'desc' => 'Date Format', 'value' => locale_datefmt() ),
			'time_format'			=> array( 'desc' => 'Time Format', 'value' => locale_timefmt() ),
			'users_can_register'	=> array( 'desc' => 'Allow new users to sign up', 'value' => $Settings->get('newusers_canregister') ),

			// We are using default thumbnail sizes from config
			'thumbnail_crop'		=> array( 'desc' => 'Crop thumbnail to exact dimensions', 'value' => false ),
			'thumbnail_size_w'		=> array( 'desc' => 'Thumbnail Width', 'value' => '160' ),
			'thumbnail_size_h'		=> array( 'desc' => 'Thumbnail Height', 'value' => '160' ),
			'medium_size_w'			=> array( 'desc' => 'Medium size image width', 'value' => '320' ),
			'medium_size_h'			=> array( 'desc' => 'Medium size image height', 'value' => '320' ),
			'large_size_w'			=> array( 'desc' => 'Large size image width', 'value' => '720' ),
			'large_size_h'			=> array( 'desc' => 'Large size image height', 'value' => '500' ),
		);

	$data = array();
	if( empty($options) )
	{	// No specific options where asked for, return all of them
		foreach( $defaults as $k => $opt )
		{
			$data[$k] = new xmlrpcval(array(
					'desc' => new xmlrpcval( $opt['desc'] ),
					'readonly' => new xmlrpcval( true, 'boolean' ),
					'value' => new xmlrpcval( $opt['value'] ),
				),'struct');
		}
		logIO('Retrieving all options');
	}
	else
	{
		foreach( $options as $k )
		{
			if( !isset($defaults[$k]) ) continue;

			$data[$k] = new xmlrpcval(array(
					'desc' => new xmlrpcval( $defaults[$k]['desc'] ),
					'readonly' => new xmlrpcval( true, 'boolean' ),
					'value' => new xmlrpcval( $defaults[$k]['value'] ),
				),'struct');

			logIO( 'Retrieving option: '.$k );
		}
	}
	logIO('OK.');

	return new xmlrpcresp( new xmlrpcval( $data, 'struct' ) );
}


// ========= Items
$xmlrpc_procs['wp.getPage'] = array( // OK
				'function' => 'wp_getpage',
				'signature' => $wordpressgetpage_sig,
				'docstring' => $wordpressgetpage_doc);

$xmlrpc_procs['wp.getPages'] = array( // OK
				'function' => 'wp_getpages',
				'signature' => $wordpressgetpages_sig,
				'docstring' => $wordpressgetpages_doc);

$xmlrpc_procs['wp.getPageList'] = array( // OK
				'function' => 'wp_getpagelist',
				'signature' => $wordpressgetpagelist_sig,
				'docstring' => $wordpressgetpagelist_doc);

$xmlrpc_procs['wp.getPageStatusList'] = array( // Incomplete (minor): protected and redirected
				'function' => 'wp_getpagestatuslist',
				'signature' => $wordpressgetpagestatuslist_sig,
				'docstring' => $wordpressgetpagestatuslist_doc);

$xmlrpc_procs['wp.getPostStatusList'] = array( // Alias to wp.getPageStatusList
				'function' => 'wp_getpagestatuslist',
				'signature' => $wordpressgetpagestatuslist_sig,
				'docstring' => $wordpressgetpoststatuslist_doc);

$xmlrpc_procs['wp.getPostFormats'] = array( // Incomplete (minor): 'show-supported'
				'function' => 'wp_getpostformats',
				'signature' => $wordpressgetpostformats_sig,
				'docstring' => $wordpressgetpostformats_doc);

$xmlrpc_procs['wp.newPage'] = array( // Untested
				'function' => 'wp_newpage',
				'signature' => $wordpressnewpage_sig,
				'docstring' => $wordpressnewpage_doc);

$xmlrpc_procs['wp.editPage'] = array( // Untested
				'function' => 'wp_editpage',
				'signature' => $wordpresseditpage_sig,
				'docstring' => $wordpresseditpage_doc);

$xmlrpc_procs['wp.deletePage'] = array( // OK
				'function' => 'wp_deletepage',
				'signature' => $wordpressdeletepage_sig,
				'docstring' => $wordpressdeletepage_doc);

/*
$xmlrpc_procs['wp.getPageTemplates'] = array( // Useless in b2evo
				'function' => 'wp_getpagetemplates',
				'signature' => $wordpressgetpagetemplates_sig,
				'docstring' => $wordpressgetpagetemplates_doc);
*/

// ========= Categories
$xmlrpc_procs['wp.getCategories'] = array( // OK
				'function' => 'wp_getcategories',
				'signature' => $wordpressgetcats_sig,
				'docstring' => $wordpressgetcats_doc );

$xmlrpc_procs['wp.newCategory'] = array( // OK
				'function' => 'wp_newcategory',
				'signature' => $wordpressnewcategory_sig,
				'docstring' => $wordpressnewcategory_doc);

$xmlrpc_procs['wp.deleteCategory'] = array( // OK
				'function' => 'wp_deletecategory',
				'signature' => $wordpressdeletecategory_sig,
				'docstring' => $wordpressdeletecategory_doc);

$xmlrpc_procs['wp.suggestCategories'] = array( // OK
				'function' => 'wp_suggestcategories',
				'signature' => $wordpresssuggestcats_sig,
				'docstring' => $wordpresssuggestcats_doc);


// ========= Comments
$xmlrpc_procs['wp.getComment'] = array( // OK
				'function' => 'wp_getcomment',
				'signature' => $wordpressgetcomment_sig,
				'docstring' => $wordpressgetcomment_doc);

$xmlrpc_procs['wp.getComments'] = array( // OK
				'function' => 'wp_getcomments',
				'signature' => $wordpressgetcomments_sig,
				'docstring' => $wordpressgetcomments_doc);

$xmlrpc_procs['wp.getCommentStatusList'] = array( // OK
				'function' => 'wp_getcommentstatuslist',
				'signature' => $wordpressgetcommentstatuslist_sig,
				'docstring' => $wordpressgetcommentstatuslist_doc);

$xmlrpc_procs['wp.newComment'] = array( // OK
				'function' => 'wp_newcomment',
				'signature' => $wordpressnewcomment_sig,
				'docstring' => $wordpressnewcomment_doc);

$xmlrpc_procs['wp.editComment'] = array( // OK
				'function' => 'wp_editcomment',
				'signature' => $wordpresseditcomment_sig,
				'docstring' => $wordpresseditcomment_doc);

$xmlrpc_procs['wp.getCommentCount'] = array( // OK
				'function' => 'wp_getcommentcount',
				'signature' => $wordpressgetcommentcount_sig,
				'docstring' => $wordpressgetcommentcount_doc);

$xmlrpc_procs['wp.deleteComment'] = array( // OK
				'function' => 'wp_deletecomment',
				'signature' => $wordpressdeletecomment_sig,
				'docstring' => $wordpressdeletecomment_doc);


// ========= Other
$xmlrpc_procs['wp.uploadFile'] = array( // OK
				'function' => 'wp_uploadfile',
				'signature' => $wordpressUploadFile_sig,
				'docstring' => $wordpressUploadFile_doc);

$xmlrpc_procs['wp.getTags'] = array( // OK
				'function' => 'wp_gettags',
				'signature' => $wordpressgettags_sig,
				'docstring' => $wordpressgettags_doc);

$xmlrpc_procs['wp.getUsersBlogs'] = array( // OK
				'function' => 'wp_getusersblogs',
				'signature' => $wordpressgetusersblogs_sig ,
				'docstring' => $wordpressgetusersblogs_doc );

$xmlrpc_procs['wp.getAuthors'] = array( // OK
				'function' => 'wp_getauthors',
				'signature' => $wordpressgetauthors_sig,
				'docstring' => $wordpressgetauthors_doc);

$xmlrpc_procs['wp.getOptions'] = array( // OK
				'function' => 'wp_getoptions',
				'signature' => $wordpressgetoptions_sig,
				'docstring' => $wordpressgetoptions_doc);

/*
$xmlrpc_procs['wp.setOptions'] = array( // TODO
				'function' => 'wp_setoptions',
				'signature' => $wordpresssetoptions_sig,
				'docstring' => $wordpresssetoptions_doc);

$xmlrpc_procs['wp.getMediaItem'] = array( // TODO
				'function' => 'wp_getmediaitem',
				'signature' => $wordpressgetmediaitem_sig,
				'docstring' => $wordpressgetmediaitem_doc);

$xmlrpc_procs['wp.getMediaLibrary'] = array( // TODO
				'function' => 'wp_getmedialibrary',
				'signature' => $wordpressgetmedialibrary_sig,
				'docstring' => $wordpressgetmedialibrary_doc);
*/

?>