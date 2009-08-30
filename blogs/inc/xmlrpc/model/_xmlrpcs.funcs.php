<?php
/**
 * @see http://phpxmlrpc.sourceforge.net/
 * @see http://xmlrpc.usefulinc.com/doc/
 * @copyright Edd Dumbill <edd@usefulinc.com> (C) 1999-2002
 *
 * @package evocore
 * @subpackage xmlrpc
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( CANUSEXMLRPC !== TRUE )
{
	return;
}

/**
 * Include XML-RPC for PHP SERVER library
 */
load_funcs('_ext/xmlrpc/_xmlrpcs.inc.php');


// --------------------------------------- SUPPORT FUNCTIONS ----------------------------------------


/**
 * Used for logging, only if {@link $debug_xmlrpc_logging} is true
 *
 * @return boolean Have we logged?
 */
function logIO( $msg, $newline = false )
{
	global $debug_xmlrpc_logging, $basepath, $xmlsrv_subdir;

	if( ! $debug_xmlrpc_logging )
	{
		return false;
	}

	$fp = fopen( $basepath.$xmlsrv_subdir.'xmlrpc.log', 'a+' );
	if( $newline )
	{
		$date = date('Y-m-d H:i:s ');
		fwrite( $fp, "\n\n".$date );
	}
	fwrite($fp, $msg."\n");
	fclose($fp);

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


/**
 * Helper for {@link b2_getcategories()} and {@link mt_getPostCategories()}, because they differ
 * only in the "categoryId" case ("categoryId" (b2) vs "categoryID" (MT))
 *
 * @param string Type, either "b2" or "mt"
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog to query
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function _b2_or_mt_get_categories( $type, $m )
{
	global $DB, $Settings;

	// CHECK LOGIN:
	// Tblue> Note on perms: I think an user doesn't need any special perms
	//                       to get a list of blog categories; the only
	//                       requirement for the user is to have an account
	//                       in the system.
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

	$sql = 'SELECT *
					  FROM T_categories ';

	$BlogCache = & get_Cache('BlogCache');
	$current_Blog = $BlogCache->get_by_ID( $Blog->ID );
	$sql .= 'WHERE '.$Blog->get_sql_where_aggregate_coll_IDs('cat_blog_ID');
	if( $Settings->get('chapter_ordering') == 'manual' )
	{	// Manual order
		$sql .= ' ORDER BY cat_order';
	}
	else
	{	// Alphabetic order
		$sql .= ' ORDER BY cat_name';
	}

	$rows = $DB->get_results( $sql );
	if( $DB->error )
	{ // DB error
		return xmlrpcs_resperror( 99, 'DB error: '.$DB->last_error ); // user error 99
	}

	xmlrpc_debugmsg( 'Categories: '.$DB->num_rows );

	$categoryIdName = ( $type == 'b2' ? 'categoryID' : 'categoryId' );
	$data = array();
	foreach( $rows as $row )
	{
		$data[] = new xmlrpcval( array(
				$categoryIdName => new xmlrpcval( $row->cat_ID ),
				'categoryName' => new xmlrpcval( $row->cat_name )
			//	mb_convert_encoding( $row->cat_name, "utf-8", "iso-8859-1")  )
			), 'struct' );
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval($data, 'array') );
}


/**
 * Get current_User for an XML-RPC request - Includes login (password) check.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of login param in XML-RPC Message
 * @param integer idx of pass param in XML-RPC Message
 * @return User or NULL
 */
function & xmlrpcs_login( $m, $login_param, $pass_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$username = $m->getParam( $login_param );
	$username = $username->scalarval();

	$password = $m->getParam( $pass_param );
	$password = $password->scalarval();

  /**
	 * @var UserCache
	 */
	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	if( empty( $current_User ) || ! $current_User->check_password( $password, false ) )
	{	// User not found or password doesn't match
		$xmlrpcs_errcode = $xmlrpcerruser+1;
		$xmlrpcs_errmsg = 'Wrong username/password combination: '.$username.' / '.starify($password);
		$r = NULL;
		return $r;
	}

	logIO( 'Login OK - User: '.$current_User->ID.' - '.$current_User->login );

  // This may be needed globally for status permissions in ItemList2, etc..
	$GLOBALS['current_User'] = & $current_User;

	return $current_User;
}


/**
 * Get current Blog for an XML-RPC request.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of blog param
 * @return Blog or NULL
 */
function & xmlrpcs_get_Blog( $m, $blog_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$blog = $m->getParam( $blog_param );
	$blog = $blog->scalarval();
	// waltercruz> qtm: http://qtm.blogistan.co.uk/ inserts some spacing before/after blogID.
	$blog = (int) trim($blog);
	/**
	 * @var BlogCache
	 */
	$BlogCache = & get_Cache( 'BlogCache' );
	/**
	 * @var Blog
	 */
	$Blog = & $BlogCache->get_by_ID( $blog, false, false );

	if( empty( $Blog ) )
	{	// Blog not found
		$xmlrpcs_errcode = $xmlrpcerruser+2;
		$xmlrpcs_errmsg = 'Requested blog/Collection ('.$blog.') does not exist.';
		$r = NULL;
		return $r;
	}

	logIO( 'Requested Blog: '.$Blog->ID.' - '.$Blog->name );

	return $Blog;
}


/**
 * Get current Item for an XML-RPC request.
 *
 * @param xmlrpcmsg XML-RPC Message
 * @param integer idx of postid param
 * @return Blog or NULL
 */
function & xmlrpcs_get_Item( $m, $postid_param )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	$postid = $m->getParam( $postid_param );
	$postid = $postid->scalarval();

  /**
	 * @var ItemCache
	 */
	$ItemCache = & get_Cache( 'ItemCache' );
  /**
	 * @var Item
	 */
	$edited_Item = & $ItemCache->get_by_ID( $postid, false, false );

	if( empty( $edited_Item ) )
	{	// Blog not found
		$xmlrpcs_errcode = $xmlrpcerruser+6;
		$xmlrpcs_errmsg = 'Requested post/Item ('.$postid.') does not exist.';
		$r = NULL;
		return $r;
	}

	logIO( 'Requested Item: '.$edited_Item->ID.' - '.$edited_Item->title );

	return $edited_Item;
}


/**
 * If no errcode or errmsg given, will use the last one that has been set previously.
 *
 * @param integer
 * @param string
 * @return xmlrpcresp
 */
function xmlrpcs_resperror( $errcode = NULL, $errmsg = NULL )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg, $xmlrpcerruser;

	if( !empty($errcode) )
	{ // Transform into user error code
		$xmlrpcs_errcode = $xmlrpcerruser + $errcode;
	}

	if( !empty($errmsg) )
	{	// Custom message
		$xmlrpcs_errmsg = $errmsg;
	}
	else
	{	// Use a standard messsage
		switch( $errcode )
		{
			case 3:
				$xmlrpcs_errmsg = 'Permission denied.';
				break;

			case 11:
				$xmlrpcs_errmsg = 'Requested category not found in requested blog.';
				break;

			case 12:
				$xmlrpcs_errmsg = 'No default category found for requested blog.';
				break;

			case 21:
				$xmlrpcs_errmsg = 'Invalid post title.';
				break;

			case 22:
				$xmlrpcs_errmsg = 'Invalid post contents.';
				break;

			case 99:
				$xmlrpcs_errmsg = 'Database error.';
				break;

			default:
				$xmlrpcs_errmsg = 'Unknown error.';
		}
	}

	logIO( 'ERROR: '.$xmlrpcs_errcode.' - '.$xmlrpcs_errmsg );

  return new xmlrpcresp( 0, $xmlrpcs_errcode, $xmlrpcs_errmsg );
}


/**
 * Create a new Item and return an XML-RPC response
 *
 * @param string HTML
 * @param string HTML
 * @param string date
 * @param integer main category
 * @param array of integers : extra categories
 * @param string status
 * @param string Tags
 * @param string Comment status. See {@link Item::$comment_status}.
 * @return xmlrpcmsg
 */
function xmlrpcs_new_item( $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags = '', $allow_comments = 'open' )
{
  /**
	 * @var User
	 */
	global $current_User;

	global $Messages;
	global $DB;

	// CHECK HTML SANITY:
	if( ($post_title = check_html_sanity( $post_title, 'xmlrpc_posting' )) === false )
	{
		return xmlrpcs_resperror( 21, $Messages->get_string( 'Invalid post title, please correct these errors:', '' ) );
	}
	if( ($content = check_html_sanity( $content, 'xmlrpc_posting' )) === false  )
	{
		return xmlrpcs_resperror( 22, $Messages->get_string( 'Invalid post contents, please correct these errors:'."\n", '', NULL, "  //  \n", 'xmlrpc' ) );
	}

	// INSERT NEW POST INTO DB:
	load_class( 'items/model/_item.class.php' );
	$edited_Item = & new Item();
	$edited_Item->set( 'title', $post_title );
	$edited_Item->set( 'content', $content );
	$edited_Item->set( 'datestart', $post_date );
	$edited_Item->set( 'main_cat_ID', $main_cat );
	$edited_Item->set( 'extra_cat_IDs', $cat_IDs );
	$edited_Item->set( 'status', $status );
	$edited_Item->set_tags_from_string( $tags );
	$edited_Item->set( 'locale', $current_User->locale );
	$edited_Item->set_creator_User( $current_User );

	// Comment status:
	$edited_Item->load_Blog();
	if( $edited_Item->Blog->allowcomments == 'post_by_post' )
	{
		$edited_Item->set( 'comment_status', $allow_comments );
	}

	$edited_Item->dbinsert();
	if( empty($edited_Item->ID) )
	{ // DB error
		return xmlrpcs_resperror( 99, 'Error while inserting item: '.$DB->last_error );
	}
	logIO( 'Posted with ID: '.$edited_Item->ID );

	// Execute or schedule notifications & pings:
	logIO( 'Handling notifications...' );
	$edited_Item->handle_post_processing();

 	logIO( 'OK.' );
	return new xmlrpcresp(new xmlrpcval($edited_Item->ID));
}


/**
 * Edit an Item and return an XML-RPC response
 *
 * @param Item
 * @param string HTML
 * @param string HTML
 * @param string date
 * @param integer main category
 * @param array of integers : extra categories
 * @param string status
 * @param NULL|string Tags (NULL to leave tags unchanged)
 * @return xmlrpcmsg
 */
function xmlrpcs_edit_item( & $edited_Item, $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags = NULL )
{
  /**
	 * @var User
	 */
	global $current_User;

	global $Messages;
	global $DB;

	// CHECK HTML SANITY:
	if( ($post_title = check_html_sanity( $post_title, 'xmlrpc_posting' )) === false )
	{
		return xmlrpcs_resperror( 21, $Messages->get_string( 'Invalid post title, please correct these errors:', '' ) );
	}
	if( ($content = check_html_sanity( $content, 'xmlrpc_posting' )) === false  )
	{
		return xmlrpcs_resperror( 22, $Messages->get_string( 'Invalid post contents, please correct these errors:'."\n", '', NULL, "  //  \n", 'xmlrpc' ) );
	}

	// UPDATE POST IN DB:
	$edited_Item->set( 'title', $post_title );
	$edited_Item->set( 'content', $content );
	$edited_Item->set( 'status', $status );
	if( !empty($post_date) )
	{
		$edited_Item->set( 'issue_date', $post_date );
	}
	if( !empty($main_cat) )
	{ // Update cats:
		$edited_Item->set('main_cat_ID', $main_cat);
	}
	if( !empty($cat_IDs) )
	{ // Extra-Cats:
		$edited_Item->set('extra_cat_IDs', $cat_IDs);
	}
	if( $tags !== NULL )
	{
		$edited_Item->set_tags_from_string( $tags );
	}
	$edited_Item->dbupdate();
	if( $DB->error )
	{ // DB error
		return xmlrpcs_resperror( 99, 'Error while updating item: '.$DB->last_error );
	}

	// Execute or schedule notifications & pings:
	logIO( 'Handling notifications...' );
	$edited_Item->handle_post_processing();

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( 1, 'boolean' ) );
}


/**
 * Check that an User can view a specific Item.
 *
 * @param object The Item (by reference).
 * @param object The User (by reference).
 * @return boolean True if permission granted, false otherwise.
 */
function xmlrpcs_can_view_item( & $Item, & $current_User )
{
	$can_view_post = false;
	switch( $Item->status )
	{
		case 'published':
		case 'redirected':
			$can_view_post = true;
			break;
		case 'protected':
		case 'draft':
		case 'deprecated':
			$can_view_post = $current_User->check_perm( 'blog_ismember', 'view', false, $Item->get_blog_ID() );
			break;
		case 'private':
			$can_view_post = ( $Item->creator_user_ID == $current_User->ID );
			break;
	}

	logIO( 'xmlrpcs_can_view_item(): Post status: '.$Item->status );
	logIO( 'xmlrpcs_can_view_item( Item(#'.$Item->ID.'), User(#'.$current_User->ID.') ): Permission '.( $can_view_post ? 'granted' : 'DENIED' ) );
	return $can_view_post;
}


/**
 * Get a main category that exists and is allowed by the current crossposting
 * settings.
 *
 * If the category doesn't exist, the blog's default category is returned.
 * If the value of $allow_cross_posting doesn't allow changing of the post's
 * main category across blogs and the category doesn't belong to the supplied
 * blog, an XML-RPC error is returned.
 * If no errors occurred, this function returns its $maincat argument.
 * 
 * @param integer The main category to check.
 * @param object The Blog to which the category is supposed to belong to (by reference).
 * @param array Extra categories for the post (by reference).
 *
 * @return object|integer An usable category or a XML-RPC error (object).
 */
function xmlrpcs_get_maincat( $maincat, & $Blog, & $extracats )
{
	global $allow_cross_posting;

	$ChapterCache = & get_Cache( 'ChapterCache' );
	if( $ChapterCache->get_by_ID( $maincat, false ) === false )
	{	// Category does not exist, use default:
		$new_maincat = $Blog->get_default_cat_ID();

		// Remove old category from extra cats:
		if( ( $key = array_search( $maincat, $extracats ) ) !== false )
		{
			unset( $extracats[$key] );
		}
		// Add new category to extracats:
		if( ! in_array( $new_maincat, $extracats ) )
		{
			$extracats[] = $new_maincat;
		}

		return (int)$new_maincat;
	}
	else if( $allow_cross_posting < 3 && get_catblog( $maincat ) != $Blog->ID )
	{	// We cannot use a maincat of another blog than the current one:
		return xmlrpcs_resperror( 11 );
	}

	// Main category is OK:
	return (int)$maincat;
}


/*
 * $Log$
 * Revision 1.12  2009/08/30 17:06:25  tblue246
 * Let xmlrpcs_new_item() handle Blog::allowcomments.
 *
 * Revision 1.11  2009/08/30 16:50:19  tblue246
 * Minor
 *
 * Revision 1.10  2009/08/30 15:50:52  waltercruz
 * Adding support for mt_allow_comments
 *
 * Revision 1.9  2009/08/29 12:23:56  tblue246
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
 * Revision 1.8  2009/08/28 19:15:06  waltercruz
 * Fixing post creation in qtm
 *
 * Revision 1.7  2009/08/27 17:46:13  tblue246
 * Metaweblog API: Use mt_keywords for item tags (set/get)
 *
 * Revision 1.6  2009/01/28 21:23:23  fplanque
 * Manual ordering of categories
 *
 * Revision 1.5  2009/01/26 00:11:22  fplanque
 * fixing bugs resulting from someone tumble DRYing the code :(
 *
 * Revision 1.4  2009/01/23 00:05:25  blueyed
 * Add Blog::get_sql_where_aggregate_coll_IDs, which adds support for '*' in list of aggregated blogs.
 *
 * Revision 1.3  2008/01/19 10:57:11  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.2  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.1  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 */
?>
