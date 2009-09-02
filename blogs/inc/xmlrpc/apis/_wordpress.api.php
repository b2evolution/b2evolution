<?php
/**
 * XML-RPC : Wordpress API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author waltercruz
 *
 * @see http://codex.wordpress.org/XML-RPC_wp
 *
 * @package xmlsrv
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$wordpressgetpagelist_doc = 'Get an array of all the pages on a blog. Just the minimum details, lighter than wp.getPages. ';
$wordpressgetpagelist_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));
/**
 * wp.getPageList
 *
 * @see http://codex.wordpress.org/XML-RPC_wp
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
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
	load_class( 'items/model/_itemlistlight.class.php' );
	$MainList = & new ItemListLight( $Blog, NULL, NULL,  50000 );

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
	/**
	 * @var Item
	 */
	while( $Item = & $MainList->get_item() )
	{
		logIO( 'Item:'.$Item->title.
					' - Issued: '.$Item->issue_date.
					' - Modified: '.$Item->mod_date );
		$post_date = mysql2date('U', $Item->issue_date);
		$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);
		$data[] = new xmlrpcval(array(
				'dateCreated' => new xmlrpcval($post_date,'dateTime.iso8601'),
				'page_id' => new xmlrpcval($Item->ID),
				'page_title' => new xmlrpcval($Item->title),
				'page_parent_id ' => new xmlrpcval(0),
			),'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}

$wordpressgetusersblogs_doc='Retrieve the blogs of the users.';
$wordpressgetusersblogs_sig=array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString));
/**
 * wp.getUsersBlogs returns information about all the blogs a given user is a member of.
 *
 * Data is returned as an array of <struct>s containing the ID (blogid), name (blogName),
 * and URL (url) of each blog.
 *
 * Non official: Also return a boolean stating wether or not the user can edit th eblog templates
 * (isAdmin). Also return a value for xmlrpc url (xmlrpc.
 *
 * see {@link http://codex.wordpress.org/XML-RPC_wp#wp.getUsersBlogs}
 * @see http://comox.textdrive.com/pipermail/wp-xmlrpc/2008-June/000206.html
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
function wp_getusersblogs($m)
{
	logIO('wp_getusersblogs start');
	return _wp_or_blogger_getusersblogs( 'wp', $m );
}


$wordpressgetpagestatuslist_doc = 'Retrieve all of the WordPress supported page statuses.';
$wordpressgetpoststatuslist_doc = 'Retrieve post statuses.';
$wordpressgetpagestatuslist_sig =  array(array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString));
/**
 * wp.getPageStatusList
 * wp.getPostStatusList
 *
 * @see http://codex.wordpress.org/XML-RPC_wp
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
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
		$status_list['published'] = new xmlrpcval(T_('Published')) ;
	}

	if( $current_User->check_perm( 'blog_post!protected', 'edit', false, $Blog->ID ) )
	{
		$status_list['protected'] = new xmlrpcval(T_('Protected')) ;
	}

	if( $current_User->check_perm( 'blog_post!private', 'edit', false, $Blog->ID ) )
	{
		$status_list['private'] = new xmlrpcval(T_('Private')) ;
	}

	if( $current_User->check_perm( 'blog_post!draft', 'edit', false, $Blog->ID ) )
	{
		$status_list['draft'] = new xmlrpcval(T_('Draft')) ;
	}

	if( $current_User->check_perm( 'blog_post!deprecated', 'edit', false, $Blog->ID ) )
	{
		$status_list['deprecated'] = new xmlrpcval(T_('Deprecated')) ;
	}

	if( $current_User->check_perm( 'blog_post!redirected', 'edit', false, $Blog->ID ) )
	{
		$status_list['redirected'] = new xmlrpcval(T_('Redirected')) ;
	}
	return new xmlrpcresp(  new xmlrpcval($status_list,'struct') );
}

$wordpressUploadFile_doc = 'Uploads a file to the media library of the blog';
$wordpressUploadFile_sig = array(array( $xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcStruct ));
/**
 * metaWeblog.newMediaObject  image upload
 *
 * image is supplied coded in the info struct as bits
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metaweblognewmediaobject
 *
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 * 							- name : filename
 * 							- type : mimetype
 * 							- bits : base64 encoded file
 * @return xmlrpcresp XML-RPC Response
 */
function wp_uploadfile($m)
{
	logIO('wp_uploadfile start');
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
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 */
function wp_getcategories( $m )
{
	return _wp_mw_getcategories ( $m ) ;
}


// Wordpress has some aliases to metaweblog APIS.

$xmlrpc_procs['wp.getCategories'] = array(
				'function' => 'wp_getcategories',
				'signature' => $wordpressgetcats_sig,
				'docstring' => $wordpressgetcats_doc );

$xmlrpc_procs['wp.uploadFile'] = array(
				'function' => 'wp_uploadfile',
				'signature' => $wordpressUploadFile_sig,
				'docstring' => $wordpressUploadFile_doc);

//very similar to blogger api
$xmlrpc_procs['wp.getUsersBlogs'] = array(
				'function' => 'wp_getusersblogs',
				'signature' => $wordpressgetusersblogs_sig ,
				'docstring' => $wordpressgetusersblogs_doc );

// and these we are implementing here.

$xmlrpc_procs['wp.getPageList'] = array(
				'function' => 'wp_getpagelist',
				'signature' => $wordpressgetpagelist_sig,
				'docstring' => $wordpressgetpagelist_doc);

$xmlrpc_procs['wp.getPageStatusList'] = array(
				'function' => 'wp_getpagestatuslist',
				'signature' => $wordpressgetpagestatuslist_sig,
				'docstring' => $wordpressgetpagestatuslist_doc);

$xmlrpc_procs['wp.getPostStatusList'] = array(
				'function' => 'wp_getpagestatuslist',
				'signature' => $wordpressgetpagestatuslist_sig,
				'docstring' => $wordpressgetpoststatuslist_doc);

?>
