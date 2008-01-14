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
	global $xmlrpcerruser, $DB;

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

	// CHECK PERMISSION: (we need at least one post/edit status)
	if( ! $current_User->check_perm( 'blog_post_statuses', 1, false, $Blog->ID ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


	$sql = 'SELECT *
					  FROM T_categories ';

	$BlogCache = & get_Cache('BlogCache');
	$current_Blog = $BlogCache->get_by_ID( $Blog->ID );
	$aggregate_coll_IDs = $current_Blog->get_setting('aggregate_coll_IDs');
	if( empty( $aggregate_coll_IDs ) )
	{	// We only want posts from the current blog:
		$sql .= 'WHERE cat_blog_ID ='.$current_Blog->ID;
	}
	else
	{	// We are aggregating posts from several blogs:
		$sql .= 'WHERE cat_blog_ID IN ('.$aggregate_coll_IDs.')';
	}

	$sql .= " ORDER BY cat_name ASC";

	$rows = $DB->get_results( $sql );
	if( $DB->error )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	xmlrpc_debugmsg( 'Categories:'.count($rows) );

	$categoryIdName = ( $type == 'b2' ? 'categoryID' : 'categoryId' );
	$data = array();
	foreach( $rows as $row )
	{
		$data[] = new xmlrpcval( array(
				$categoryIdName => new xmlrpcval($row->cat_ID),
				'categoryName' => new xmlrpcval( $row->cat_name )
			//	mb_convert_encoding( $row->cat_name, "utf-8", "iso-8859-1")  )
			), 'struct' );
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval($data, "array") );
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

  /**
   * This may be needed globally for status permissions in ItemList2, etc..
	 * @var User
	 */
	global $current_User;

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
 *
 * @param integer
 * @param string
 * @return xmlrpcresp
 */
function xmlrpcs_resperror( $errcode = NULL )
{
	global $xmlrpcs_errcode, $xmlrpcs_errmsg;

	switch( $errcode )
	{
		case 3:
			$xmlrpcs_errcode = $xmlrpcs_errmsg + 3;
			$xmlrpcs_errmsg = 'Permission denied.';
			break;

		case 11:
			$xmlrpcs_errcode = $xmlrpcs_errmsg + 11;
			$xmlrpcs_errmsg = 'Requested category not found in requested blog.';
			break;

		case 12:
			$xmlrpcs_errcode = $xmlrpcs_errmsg + 12;
			$xmlrpcs_errmsg = 'No default category found for requested blog.';
			break;

		default:
			// NULL... will use the last error.
	}

	logIO( 'ERROR: '.$xmlrpcs_errcode.' - '.$xmlrpcs_errmsg );

  return new xmlrpcresp( 0, $xmlrpcs_errcode, $xmlrpcs_errmsg );
}

/*
 * $Log$
 * Revision 1.1  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 */
?>