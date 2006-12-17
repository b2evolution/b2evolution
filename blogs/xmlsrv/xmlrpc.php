<?php
/**
 * XML-RPC APIs
 *
 * This file implements the following XML-RPC remote procedures, to be called by remote clients:
 * - the B2 API for b2evo (this is used by w.bloggar for example...)
 * - the BLOGGER API for b2evo, see {@link http://www.blogger.com/developers/api/1_docs/}
 * - Metaweblog API
 * - Movable Type API (partial)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package xmlsrv
 *
 * @version $Id$
 *
 * @modified wendall911 at users dot sourceforge.net
 */

/**
 * Initialize everything:
 */

// Disable Cookies
$_COOKIE = array();

// Trim requests (used by XML-RPC library); fix for mozBlog and other cases where '<?xml' isn't on the very first line
if ( isset($HTTP_RAW_POST_DATA) )
{
	// TODO: dh> xmlrpc should use php://input instead.. see http://bugs.php.net/bug.php?id=22338
	$HTTP_RAW_POST_DATA = trim( $HTTP_RAW_POST_DATA );
}

/**
 * Set to TRUE to do HTML sanity checking as in the browser interface, set to
 * FALSE if you trust the editing tool to do this (more features than the
 * browser interface)
	* @todo fp> have a global setting with 3 options: check|nocheck|userdef => each then has his own setting to define if his tool does the checking or not.
	* fp> Also, there should be a permission to say if members of a given group can or cannot post insecure content. If they cannot, then they cannot disable the sanity check
	* fp> note: if allowed unsecure posting, disabling the sanity cjecker should also be allowed in the html backoffice
 */
$xmlrpc_htmlchecking = true;
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';
require_once $inc_path.'_misc/ext/_xmlrpc.php';
require_once $model_path.'items/_itemlist2.class.php';

if( CANUSEXMLRPC !== TRUE )
{ // We cannot use XML-RPC: send a error response ( "1 Unknown method" ).
    //this should be structured as an xml response
	$errResponse = new xmlrpcresp( 0, 1, 'Cannot use XML-RPC. Probably the server is missing the XML extension. Error: '.CANUSEXMLRPC );
	die( $errResponse->serialize() );
}


// Handle "Really Simple Discovery":
if ( isset( $_GET['rsd'] ) ) { // http://archipelago.phrasewise.com/rsd
header('Content-type: text/xml; charset=' . $evo_charset, true);

?>
<?php echo '<?xml version="1.0" encoding="'.$evo_charset.'"?'.'>'; ?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
  <service>
    <engineName>b2evolution</engineName>
    <engineLink>http://b2evolution.net/</engineLink>
    <homePageLink><?php echo $baseurl ?></homePageLink>
    <apis>
      <api name="Movable Type" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php" />
      <api name="MetaWeblog" preferred="true" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php" />
      <api name="Blogger" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php" />
    </apis>
  </service>
</rsd>
<?php
exit;
}

// We can't display standard error messages. We must return XMLRPC responses.
$DB->halt_on_error = false;
$DB->show_errors = false;

$post_default_title = ''; // posts submitted via the xmlrpc interface get that title

/**
 * Used for logging, only if {@link $debug_xmlrpc_logging} is true
 *
 * @return boolean Have we logged?
 */
function logIO($io,$msg)
{
	global $debug_xmlrpc_logging;

	if( ! $debug_xmlrpc_logging )
	{
		return false;
	}

	$date = date('Y-m-d H:i:s ');
	$iot = ($io == 'I') ? ' Input: ' : ' Output: ';

	$fp = fopen( dirname(__FILE__).'/xmlrpc.log', 'a+' );
	fwrite($fp, $date.$iot.$msg."\n");
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
function b2newpost($m)
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
	$current_User = & $UserCache->get_by_login( $username );

	// Check if category exists
	if( get_the_category_by_ID( $category, false ) === false )
	{ // Cat does not exist:
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}

	$blog_ID = get_catblog($category);

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
	$post_ID = $edited_Item->insert( $current_User->ID, $post_title, $content, $now, $category, array(), 'published', $current_User->locale );
	if( $DB->error )
	{ // DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	logIO( 'O', 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	return new xmlrpcresp(new xmlrpcval($post_ID));
}




$b2getcategories_doc='given a blogID, gives a struct that list categories in that blog, using categoryID and categoryName. categoryName is there so the user would choose a category name from the client, rather than just a number. however, when using b2.newPost, only the category ID number should be sent.';
$b2getcategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.getCategories
 *
 * B2 API
 *
 * Gets also used for mt.getCategoryList. Is this correct?
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog to query
 *					1 username (string): Login for a Blogger user who is member of the blog.
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function b2getcategories( $m )
{
	return _b2_or_mt_get_categories('b2', $m);
}




$b2_getPostURL_doc = 'Given a blog ID, username, password, and a post ID, returns the URL to that post.';
$b2_getPostURL_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * b2.getPostURL
 *
 * B2 API
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

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.' );
	}

	$ItemCache = & get_Cache( 'ItemCache' );
	if( ( $Item = & $ItemCache->get_by_ID( $post_ID ) ) === false )
	{ // Post does not exist
		return new xmlrpcresp(0, $xmlrpcerruser+7,
						'This post ID ('.$post_ID.') does not correspond to any post here.' );
	}

	return new xmlrpcresp( new xmlrpcval( $Item->get_permanent_url() ) );
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
function bloggernewpost( $m )
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings, $Messages;

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

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	$post_categories = xmlrpc_getpostcategories($content);

	if( ! $post_categories )
	{ // There were no categories passed in the content:
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'No category given.'); // user error 5
	}

	$main_cat = array_shift($post_categories);
	logIO('I', 'Main cat: '.$main_cat);

	// Check if category exists
	if( get_the_category_by_ID( $main_cat, false ) === false )
	{ // Cat does not exist:
		return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
	}

	$blog_ID = get_catblog($main_cat);

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog_ID ) )
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

	logIO('O', "Posted ! ID: $edited_Item->ID");

	logIO( 'O', 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	logIO("O","All done.");

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
function bloggereditpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Messages;

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

	$ItemCache = & get_Cache( 'ItemCache' );
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID ) ) )
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
	logIO('I',"Publish: $publish -> Status: $status");

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

	$blog_ID = get_catblog($main_cat);

	// Check permission:
	if( ! $current_User->check_perm( 'blog_post_statuses', $status, false, $blog_ID ) )
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

	logIO( 'O', 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	return new xmlrpcresp(new xmlrpcval("1", "boolean"));
}




$bloggerdeletepost_doc = 'Deletes a post, blogger-api like';
$bloggerdeletepost_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.editPost deletes a given post.
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
function bloggerdeletepost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;

	$post_ID = $m->getParam(1);
	$post_ID = $post_ID->scalarval();
	// logIO("O","finished getting post_id ...".$post_ID);

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
function bloggergetusersblogs($m)
{
	global $xmlrpcerruser;
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


	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );
	logIO("O","Got Current user (ID ".$current_User->ID.')');


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
	global $xmlrpcerruser, $DB;

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

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.' ); // user error 2
	}


	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = & $BlogCache->get_by_ID( $blog_ID );

	// Get the posts to display:
	$MainList = & new ItemList2( $Blog, NULL, NULL, $numposts );

	$MainList->set_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated' ),
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





$bloggergettemplate_doc = 'returns the default template file\'s code';
$bloggergettemplate_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * blogger.getTemplate returns text of the main or archive index template for a given blog.
 *
 * Currently, in b2evo, this will return the templates of the 'custom' skin.
 *
 * see {@link http://www.blogger.com/developers/api/1_docs/xmlrpc_getTemplate.html}
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
	global $xmlrpcerruser;

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

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'templates' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.');
	}

	// Determine the edit folder:
	$edit_folder = $skins_path.'custom/';

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
	global $xmlrpcerruser, $blogfilename;

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

	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );

	// Check permission:
	if( ! $current_User->check_perm( 'templates' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
				'Permission denied.');
	}

	// Determine the edit folder:
	$edit_folder = $skins_path.'custom/';

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



//---------- Tor Jan 2005 Metaweblog  API ----------------
//
//	Tor Dec 2004
//
// image upload
//  image is supplied coded in the info struct as bits
//
// To do - do not overwrite existing pics with same name
//		-	security, password etc.
//
//
function mwnewMediaObject($m) {
	global $xmlrpcerruser; // import user errcode value
	global $Settings, $baseurl,$fileupload_allowedtypes;

	logIO("O","start of _newmediaobject...");
	$blogid = $m->getParam(0);
	$blogid = $blogid->scalarval();
	$username = $m->getParam(1);
	$username = $username->scalarval();
	$password = $m->getParam(2);
	$password = $password->scalarval();
	if( !user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
				 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	if( ! $Settings->get('upload_enabled') )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
				 'Object upload not allowed ');
	}
	$BlogCache = & get_Cache('BlogCache');
	$Blog = & $BlogCache->get_by_ID($blogid);

	// Get the main data - and decode it properly for the image - sorry, binary object
	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode($xcontent);
	logIO("O", 'Got first contentstruct!'."\n");

	// This call seems to go wrong from Marsedit under certain circumstances - Tor 04012005
	$data = $contentstruct['bits']; // decoding was done transparantly by xmlrpclibs xmlrpc_decode
	logIO("O", 'Have decoded data data?'."\n");

	// TODO: check filesize
	$filename = $contentstruct['name'];
	logIO("O", 'Found filename ->'. $filename ."\n");
	$type = $contentstruct['type'];
	logIO("O", 'Done type ->'. $type ."\n");
	$data = $contentstruct['bits'];
	logIO("O", 'Done bits ' ."\n");

	// Split into path + name:
	$filepath = dirname($filename);
	$filename = basename($filename);

	$filepath_parts = explode('/', $filepath);
	if( in_array('..', $filepath_parts) )
	{ // invalid relative path:
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Invalid relative part in file path: '.$filepath);
	}

	// Check valid filename/extension:
	load_funcs('MODEL/files/_file.funcs.php');
	if( $error_filename = validate_filename($filename) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Invalid objecttype for upload ('.$filename.'): '.$error_filename);
	}

	$fileupload_path = $Blog->get_media_dir();
	if( ! $fileupload_path )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Error accessing Blog media directory.');
	}

	// Handle subdirs, if any:
	if( strlen($filepath) && $filepath != '.' )
	{
		$fileupload_path .= $filepath;
		if( ! mkdir_r(dirname($fileupload_path)) )
		{
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
				'Error creating sub directories: '.rel_path_to_base($fileupload_path));
		}
	}

	logIO("O", 'fileupload_path ->'. $fileupload_path ."\n");
	$fh = @fopen($fileupload_path.$filename, 'wb');
	logIO("O", 'Managed to open file ->'. $filename ."\n");
	if (!$fh)
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Error opening file for writing.');
	}

	logIO("O", 'Managed to open file for writing ->'. $fileupload_path.$filename."\n");
	$ok = @fwrite($fh, $data);
	@fclose($fh);

	if (!$ok)
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
			'Error while writing to file.');
	}

	// chmod uploaded file:
	$oldumask = umask(0000);
	@chmod($fileupload_path.$filename, $Settings->get('fm_default_chmod_file'));
	umask($oldumask);

	$url = $Blog->get_media_url().$filepath.$filename;
	logIO("O", 'Full returned filename ->'. $fileupload_path . '/' . $filename ."\n");
	logIO("O", 'Full returned url ->'. $url ."\n");

	// - return URL as XML
	$urlstruct = new xmlrpcval(array(
			'url' => new xmlrpcval($url, 'string')
		), 'struct');
	return new xmlrpcresp($urlstruct);
}



// metaWeblog.newMediaObject
$mwnewMediaObject_sig = array(array(
	//  return type
	$xmlrpcStruct,		// "url" element
	// params
	$xmlrpcString,		// blogid
	$xmlrpcString,		// username
	$xmlrpcString,		// password
	$xmlrpcStruct		// 'name', 'type' and 'bits'
));
$mwnewMediaObject_doc = 'Uploads a file to the media library of the blog';



$mwnewpost_doc='Adds a post, blogger-api like, +title +category +postdate';
$mwnewpost_sig =  array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));

/**
 * mw.newPost
 *
 * mw API
 * Tor 2004
 *
 * NB! (Tor Feb 2005) status in metaweblog API speak dictates whether static html files are generated or not, so fairly misleading
 */
function mwnewpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings;

	logIO("O","start of mwnewpost...");

	$blog_ID = $m->getParam(0);
	$blog_ID = $blog_ID->scalarval();

	$username = $m->getParam(1);
	$username = $username->scalarval();
	logIO("O","finished getting username ...");
	$password = $m->getParam(2);
	$password = $password->scalarval();
	logIO("O","finished getting password ...".starify($password));

	if( ! user_pass_ok($username,$password) )
	{
	logIO("O","error during checking password ...");
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	logIO("O","finished checking password ...");


	$xcontent = $m->getParam(3);
//	$xcontent = $xcontent->scalarval();
	logIO("O","finished getting xcontent ...");
	xmlrpc_debugmsg( 'Getting xcontent'  );

	// getParam(4) should now be a flag for publish or draft
	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO('I',"Publish: $xstatus -> Status: $status");
	logIO("O","finished getting xstatus ->". $xstatus);

	$contentstruct = xmlrpc_decode($xcontent); //this does not work properly.... need better decoding
	logIO("O","finished getting contentstruct ...");
//	$content = format_to_post($contentstruct['description']);
	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];
	logIO("O","finished getting title ...".$post_title);


	// Categories:
	$cat_IDs = _mw_get_cat_IDs($contentstruct, $blog_ID);

	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;
	}


	if( empty($contentstruct['dateCreated']) )
	{
		$postdate = date('Y-m-d H:i:s', (time() + $Settings->get('time_difference')));
		logIO("O","no contentstruct dateCreated, using now...".$postdate);
	}
	else
	{
		$postdate = $contentstruct['dateCreated'];
		logIO("O","finished getting contentstruct dateCreated...".$postdate);
	}

	// Check permission:
	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );
	logIO("O","currentuser ...". $current_User->ID);

	if( ! $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog_ID ) )
	{
		logIO("O","user error 9 ...");
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}
	logIO("O","finished checking permissions ...");

	// CHECK and FORMAT content - error occur after this line
	//$post_title = format_to_post($post_title, 0, 0);
	//logIO("O","finished converting post_title ...",$post_title);

	//$content = format_to_post($content, 0, 0);  // 25122004 tag - security !!!
	//logIO("O","finished converting content ...".$content); // error occurs before this line

	//	if( $errstring = $Messages->get_string( 'Cannot post, please correct these errors:', '' ) )
	//	{
	//		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	//	}
	//logIO("O","finished checking if errors exists, ready to insert into DB ...");

	// INSERT NEW POST INTO DB:
	// Tor - comment this out to stop inserts into database
	$edited_Item = & new Item();
	$post_ID = $edited_Item->insert( $current_User->ID, $post_title, $content, $postdate, $cat_IDs[0], $cat_IDs, $status, $current_User->locale );

	if( $DB->error )
	{	// DB error
		logIO("O","user error 9 ...");
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

	logIO( 'O', 'Handling notifications...' );
	// Execute or schedule notifications & pings:
	$edited_Item->handle_post_processing();

	return new xmlrpcresp(new xmlrpcval($post_ID));
}


// Movable Type API: {{{

$mt_setPostCategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcArray));
$mt_setPostCategories_doc = "Sets the categories for a post.";

function mt_setPostCategories($m) {
	global $xmlrpcerruser,$Settings;
	global $DB, $Messages;

	$post_ID = $m->getParam(0);
	$post_ID = $post_ID->scalarval();
	$username = $m->getParam(1);
	$username = $username->scalarval();
	$password = $m->getParam(2);
	$password = $password->scalarval();

	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

// ok - pick up new category from call
	$xcontent = $m->getParam(3); // This is now a array of structs
	$iSize = $xcontent->arraysize(); // The number of objects ie categories
	logIO("O","finished getting - iSize ...".$iSize); // number of categories entry has set

	logIO("O","finished getting contentstruct ...");
	$categories = array();
	if ($iSize > 0)
	{
		for ($i=0;$i<$iSize;$i++)
		{
			logIO("O","finished getting - i ...>".$i); // works!
			$struct = $xcontent->arraymem($i); // get a struct object from array
			$tempcat = $struct->structmem('categoryId');
			$tempcat = $tempcat->scalarval();
			$tempPrimary = $struct->structmem('isPrimary');// Start finding the primary category
			$tempPrimary = $tempPrimary->scalarval();
			if ($tempPrimary != 0)
			{
				logIO("O","got primary category and there should only be one...".$tempcat);
				$category = $tempcat;
			}
			logIO("O","finished getting - tempcat ...".$tempcat); // works!
			$categories[$i] = $tempcat;
			logIO("O","finished getting - categories ...".$categories[$i]);
		}
	}

	// UPDATE POST CATEGORIES IN DB:
	logIO("O","bpost_update - category ...".$category); // works!
	$ItemCache = & get_Cache( 'ItemCache' );
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}
	$edited_Item->set( 'main_cat_ID', $category );
	$edited_Item->set( 'extra_cat_IDs', $categories );

	$edited_Item->dbupdate();

	return new xmlrpcresp(new xmlrpcval(1));
}


$mt_getPostCategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$mt_getPostCategories_doc = "Returns a list of all categories to which the post is assigned.";

function mt_getPostCategories($m) {
	global $xmlrpcerruser;
	global $DB;

	$post_ID = $m->getParam(0);
	$post_ID = $post_ID->scalarval();
	logIO("O","mt_getPostCategories postID  ...".$post_ID);
	$username = $m->getParam(1);
	$username = $username->scalarval();
	$password = $m->getParam(2);
	$password = $password->scalarval();
	if( user_pass_ok($username,$password) )
	{
		// First get the primary category in postdata
		$postdata = get_postdata($post_ID);
		$dato = $postdata["Date"];
			logIO("O","mt_getPostCategories get_postdata argument postID  ...".$post_ID);
			logIO("O","mt_getPostCategories postdata argument date  ...".$dato);
		$Category = $postdata["Category"];// Primary category - nb also present in separate table so will not be used
			logIO("O","mt_getPostCategories postdata argument Category  ...".$Category);
		$categories = postcats_get_byID( $post_ID ); // Secondary categories
			logIO("O","mt_getPostCategories postcats_get_byID  ...".$categories);
		$iSize = count($categories); // The number of objects ie categories
		logIO("O","mt_getgategorylist  no of categories...".$iSize);// works
		$struct = array();
		for ($i=0;$i<$iSize;$i++)
		{
			logIO("O","mt_getPostCategories categories  ...".$categories[$i]);
// In database cat_ID and cat_name from tablecategories
			$sql = "SELECT * FROM T_categories WHERE  cat_ID = $categories[$i] ";
			logIO("O","mt_getgategorylist  sql...".$sql);
			$rows = $DB->get_results( $sql );
			foreach( $rows as $row )
			{
				$Categoryname =  $row->cat_name;
				logIO("O","mt_getPostCategories Categoryname  ...".$Categoryname);
			}
			if( $postdata['Date'] != '' )
			{
				logIO("O","mt_getPostCategories date ok  ...");
				if ($i > 0) {
				logIO("O","mt_getPostCategories found secondary  ...".$categories[$i]);
					$isPrimary = "0";
				}
			else
			{
				logIO("O","mt_getPostCategories found primary  ...".$categories[$i]);
				$isPrimary = "1";
			}
			$struct[$i] = new xmlrpcval(array("categoryId" => new xmlrpcval($categories[$i]),    // Look up name from ID separately
											"categoryName" => new xmlrpcval($Categoryname),
											"isPrimary" => new xmlrpcval($isPrimary)
											),"struct");
		}
	}
	return new xmlrpcresp(new xmlrpcval($struct, "array") );
//		else
//		{
//		return new xmlrpcresp(0, $xmlrpcerruser+7, // user error 7
//					 "No such post #$post_ID");
//		}
	}
	else
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
}


$mt_getCategoryList_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mt_getCategoryList_doc = 'Get category list';
/**
 * Provides mt.getCategoryList
 * @see http://www.sixapart.com/developers/xmlrpc/movable_type_api/mtgetcategorylist.html
 */
function mt_getCategoryList($m) {
	logIO("O","mt_getCategoryList  start");
	return _b2_or_mt_get_categories('mt', $m);
}

// }}}


$mweditpost_doc='Edits a post, blogger-api like, +title +category +postdate';
$mweditpost_sig =  array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));
/**
 * mw.EditPost (metaWeblog.editPost)
 *
 * mw API
 *
 * Tor - TODO
 *		- Sort out sql select with blog ID
 *		- screws up posts with multiple categories
 *		  partly due to the fact that Movable Type calls to this API are different to Metaweblog API calls when handling categories.
 */

function mweditpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $DB;
	global $Settings;
	global $Messages;
	global $xmlrpc_htmlchecking;

	logIO("O","start of mweditpost...");
	$post_ID = $m->getParam(0);
	$post_ID = $post_ID->scalarval();
	logIO("O","finished getting post_ID ...".$post_ID);

	// Username/Password
	$username = $m->getParam(1);
	$username = $username->scalarval();
	logIO("O","finished getting username ...");
	$password = $m->getParam(2);
	$password = $password->scalarval();
	logIO("O","finished getting password ...".$password);
	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	logIO("O","finished checking password ...");

	// getParam(4) should now be a flag for publish or draft
	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO('I',"Publish: $xstatus -> Status: $status");
	logIO("O","finished getting xstatus ->". $xstatus);


	// Get Item:
	$ItemCache = & get_Cache( 'ItemCache' );
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}

	// Check permission:
	$UserCache = & get_Cache( 'UserCache' );
	$User = & $UserCache->get_by_login( $username );
	logIO('O','User ID ...'.$User->ID);
	if( ! $User->check_perm( 'blog_post_statuses', $status, false, $edited_Item->blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.'); // user error 2
	}
	logIO("O","finished checking permissions ...");


	$xcontent = $m->getParam(3);
//	$xcontent = $xcontent->scalarval();
	logIO("O","finished getting xcontent ...");
	$contentstruct = xmlrpc_decode($xcontent); //this does not work properly.... need better decoding
	logIO("O","finished getting contentstruct ...");


	// Categories:
	$cat_IDs = _mw_get_cat_IDs($contentstruct, $blog_ID, true /* empty is ok */);

	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;
	}


	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];
	logIO("O","finished getting title ...".$post_title);

	$postdate = $contentstruct['dateCreated'];
	logIO("O","finished getting contentstruct dateCreated...".$postdate);


	if( ! empty($xmlrpc_htmlchecking) )
	{ // CHECK and FORMAT content
		$post_title = format_to_post($post_title, 0, 0);
	}
	logIO("O","finished converting post_title ...->".$post_title);
	if( ! empty($xmlrpc_htmlchecking) )
	{
		$content = format_to_post($content, 0, 0);  // 25122004 tag - security issue - need to sort !!!
	}
	logIO("O","finished converting content ...".$content);
	if( $errstring = $Messages->get_string( 'Cannot post, please correct these errors:', '' ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+6, $errstring ); // user error 6
	}
	logIO("O","finished checking if errors exists, ready to insert into DB ...");
	xmlrpc_debugmsg( 'post_ID: '.$post_ID  );

	// UPDATE POST IN DB:
	$edited_Item->set( 'title', $post_title );
	$edited_Item->set( 'content', $content );
	$edited_Item->set( 'status', $status );
	if( ! empty($postdate) )
	{
		$edited_Item->set( 'datestart', $postdate );
	}
	if( ! empty($cat_IDs) )
	{ // Update cats:
		$edited_Item->set('main_cat_ID', $cat_IDs[0]);

		if( count($cat_IDs) > 1 )
		{ // Extra-Cats:
			$edited_Item->set('extra_cat_IDs', $cat_IDs);
		}
	}
	$edited_Item->dbupdate();
	if( $DB->error )
	{	// DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}

// Time to perform trackbacks NB NOT WORKING YET
//
// NB Requires a change to the _trackback library
//
// 			load_funcs( '_misc/_trackback.funcs.php' );
// function trackbacks( $post_trackbacks, $content, $post_title, $post_ID )

// first extract these from posting as post_trackbacks array, then rest is easy
// 	<member>
//		<name>mt_tb_ping_urls</name>
//	<value><array><data>
//		<value><string>http://archive.scripting.com/2005/04/17</string></value>
//	</data></array></value>
//	</member>
// First check that trackbacks are allowed - mt_allow_pings
	$trackback_ok = 0;
	$trackbacks = array();
	$trackback_ok = $contentstruct['mt_allow_pings'];
	logIO("O","Trackback OK  ...".$trackback_ok);
	if ($trackback_ok == 1)
	{
		$trackbacks = $contentstruct['mt_tb_ping_urls'];
		logIO("O","Trackback url 0  ...".$trackbacks[0]);
		$no_of_trackbacks = count($trackbacks);
		logIO("O","Number of Trackbacks  ...".$no_of_trackbacks);
		if ($no_of_trackbacks > 0)
		{
			logIO("O","Calling Trackbacks  ...");
			load_funcs( '_misc/_trackback.funcs.php' );
 			$result = trackbacks( $trackbacks, $content, $post_title, $post_ID );
			logIO("O","Returned from  Trackbacks  ...");
 		}

	}
	return new xmlrpcresp(new xmlrpcval($post_ID));
}



$mwgetcats_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mwgetcats_doc = 'Get categories of a post, MetaWeblog API-style';
/**
 * metaWeblog.getCategories
 * @see http://www.xmlrpc.com/metaWeblogApi#metawebloggetcategories
 */
function mwgetcats( $m )
{
	global $xmlrpcerruser, $DB;

	logIO('O','Start of mwgetcats');
	$blogid = $m->getParam(0);
	$blogid = $blogid->scalarval();
	$username = $m->getParam(1);
	$username = $username->scalarval();
	$password = $m->getParam(2);
	$password = $password->scalarval();
	logIO('O','Got params 0, 1 , 2');
	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	$sql = "SELECT cat_ID, cat_name
					FROM T_categories ";

	$BlogCache = & get_Cache('BlogCache');
	$current_Blog = $BlogCache->get_by_ID( $blog );
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
	{	// DB error
		return new xmlrpcresp(0, $xmlrpcerruser+9, 'DB error: '.$DB->last_error ); // user error 9
	}
	xmlrpc_debugmsg( 'Categories:'.count($rows) );

	$ChapterCache = & get_Cache('ChapterCache');
	$data = array();
	foreach( $rows as $row )
	{
		$Chapter = & $ChapterCache->get_by_ID($row->cat_ID);
		if( ! $Chapter )
		{
			continue;
		}
		$data[] = new xmlrpcval( array(
				'categoryId' => new xmlrpcval( $row->cat_ID ), // not in RFC (http://www.xmlrpc.com/metaWeblogApi)
				'description' => new xmlrpcval( $row->cat_name ), // not in RFC (http://www.xmlrpc.com/metaWeblogApi)
				'categoryName' => new xmlrpcval( $row->cat_name ),
				'htmlUrl' => new xmlrpcval( $Chapter->get_permanent_url() ),
				'rssUrl' => new xmlrpcval( url_add_param($Chapter->get_permanent_url(), 'tempskin=_rss2') )
			//	mb_convert_encoding( $row->cat_name, "utf-8", "iso-8859-1")  )
			),"struct");
	}
	return new xmlrpcresp( new xmlrpcval($data, "array") );
}





$metawebloggetrecentposts_doc = 'fetches X most recent posts, blogger-api like';
$metawebloggetrecentposts_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt));

function metawebloggetrecentposts( $m )
{
	global $xmlrpcerruser, $DB;
	global $blog;

	$blog_ID = $m->getParam(0);
		logIO("O","In metawebloggetrecentposts, current blog_id is ...". $blog_ID);
	$blog_ID = $blog_ID->scalarval();
			logIO("O","In metawebloggetrecentposts, current blog_id is ...". $blog_ID);
	$username = $m->getParam(1);
		logIO("O","In metawebloggetrecentposts, current username is ...". $username);
	$username = $username->scalarval();
			logIO("O","In metawebloggetrecentposts, current username is ...". $username);
	$password = $m->getParam(2);
	$password = $password->scalarval();
	$numposts = $m->getParam(3);
			logIO("O","In metawebloggetrecentposts, current numposts is ...". $numposts);
	$numposts = $numposts->scalarval();
				logIO("O","In metawebloggetrecentposts, current numposts is ...". $numposts);

	if( ! user_pass_ok($username, $password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
	logIO("O","In metawebloggetrecentposts, user and pass ok...");
	$UserCache = & get_Cache( 'UserCache' );
	$current_User = & $UserCache->get_by_login( $username );
	logIO( 'O', 'In metawebloggetrecentposts, current user is ...'.$current_User->ID );
	// Check permission:
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog_ID ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, 'Permission denied.' ); // user error 2
	}
	logIO("O","In metawebloggetrecentposts, permissions ok...");
	logIO("O","In metawebloggetrecentposts, current blog is ...". $blog_ID);

	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = & $BlogCache->get_by_ID( $blog_ID );

	// Get the posts to display:
	$MainList = & new ItemList2( $Blog, NULL, NULL, $numposts );

	$MainList->set_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'draft', 'deprecated' ),
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
		$content = $Item->content;
		$content = str_replace("\n",'',$content); // Tor - kludge to fix bug in xmlrpc libraries
		// Load Item's creator User:
		$Item->get_creator_User();
		$authorname = $Item->creator_User->get('preferredname');
		// need a loop here to extract all categoy names
		// $extra_cat_IDs is the variable for the rest of the IDs
		$hope_cat_name = get_the_category_by_ID($Item->main_cat_ID);
		$test = $Item->extra_cat_IDs[0];
		xmlrpc_debugmsg( 'postcats:'.$hope_cat_name["cat_name"]);
		xmlrpc_debugmsg( 'test:'.$test);
		$data[] = new xmlrpcval(array(
									"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
									"userid" => new xmlrpcval($Item->creator_user_ID),
									"postid" => new xmlrpcval($Item->ID),
				"categories" => new xmlrpcval(array(new xmlrpcval($hope_cat_name["cat_name"])),'array'),
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

	global $xmlrpcerruser;



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
							// Kludge to fix library problem str_replace(#10,'',$content)
        $content = str_replace("\n",'',$content); // Tor - kludge to fix bug in xmlrpc libraries
			$struct = new xmlrpcval(array("link" => new xmlrpcval(''),
											"title" => new xmlrpcval($postdata["Title"]),
											"description" => new xmlrpcval($content),
											"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
											"userid" => new xmlrpcval(""),
											"postid" => new xmlrpcval($post_ID),
											"content" => new xmlrpcval($content),
											"permalink" => new xmlrpcval(""),
											"categories" => new xmlrpcval($postdata["Category"]),
											"mt_excerpt" => new xmlrpcval($content),
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


/**
 * Helper for {@link b2getcategories()} and {@link mt_getPostCategories()}, because they differ
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

	$BlogCache = & get_Cache('BlogCache');
	$current_Blog = $BlogCache->get_by_ID( $blog );
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

	return new xmlrpcresp( new xmlrpcval($data, "array") );
}


/**
 *
 * @param array struct
 * @param integer blog ID
 * @param boolean Return empty array (instead of error), if no cats given in struct?
 * @return array|xmlrpcresp A list of category IDs or xmlrpcresp in case of error.
 */
function _mw_get_cat_IDs($contentstruct, $blog_ID, $empty_struct_ok = false)
{
	global $DB, $xmlrpcerruser;

	if( isset($contentstruct['categories']) )
	{
		$categories = $contentstruct['categories'];
	}
	else
	{
		$categories = array();
	}
	logIO("O","finished getting contentstruct categories...".implode( ', ', $categories ) );

	if( $empty_struct_ok && empty($categories) )
	{
		return $categories;
	}

	xmlrpc_debugmsg( 'Categories: '.implode( ', ', $categories ) );

	// for cross-blog-entries, the cat_blog_ID WHERE clause should be removed (but cats are given by name!)
	if( ! empty($categories) )
	{
		$sql = "
			SELECT cat_ID FROM T_categories
			 WHERE cat_blog_ID = $blog_ID
				 AND cat_name IN ( ";
		foreach( $categories as $l_cat )
		{
			$sql .= $DB->quote($l_cat).', ';
		}
		if( ! empty($categories) )
		{
			$sql = substr($sql, 0, -2); // remove ', '
		}
		$sql .= ' )';
		logIO("O","sql for finding IDs ...".$sql);

		$cat_IDs = $DB->get_col( $sql );
		if( $DB->error )
		{	// DB error
			logIO("O","user error finding categories info ...");
		}
	}
	else
	{
		$cat_IDs = array();
	}

	if( ! empty($cat_IDs) )
	{ // categories requested to be set:

		// Check if category exists
		if( get_the_category_by_ID( $cat_IDs[0], false ) === false )
		{ // Main cat does not exist:
			logIO("O","usererror 5 ...");
			return new xmlrpcresp(0, $xmlrpcerruser+5, 'Requested category does not exist.'); // user error 5
		}
		logIO("O","finished checking if main category exists ...".$cat_IDs[0]);
	}
	else
	{ // No category given/valid - use the first for the blog:
		logIO("O","No category for post given ...");

		$first_cat = $DB->get_var( '
			SELECT cat_ID
			  FROM T_categories
			 WHERE cat_blog_ID = '.$blog_ID.'
			 ORDER BY cat_name
			 LIMIT 1' );
		if( empty($first_cat) )
		{
			logIO("O", 'No categories for this blog...');
			return new xmlrpcresp(0, $xmlrpcerruser+5, 'No categories for this blog.'); // user error 5
		}
		else
		{
			$cat_IDs = array($first_cat);
		}
	}

	return $cat_IDs;
}



/**** SERVER FUNCTIONS ARRAY ****/
// dh> TODO: Plugin hook here, so that Plugins can provide own callbacks?!
// fp> The current implementation of this file is not optimal (file is way too large)
// fp> xmlrpc.php should actually only be a switcher and it should load the function to execute once it has been identified
// fp> maybe it would make sense to register xmlrpc apis/functions in a DB table
// fp> it would probably make sense to have *all* xmlrpc methods implemented as plugins (maybe 1 plugin per API; it should be possible to add a single func to an API with an additional plugin)
// dh> NOTE: some tools may use different API entry points, e.g. for extended methods.. (But I'm not sure..)
// fp> from a security standpoint it would make a lot of sense to disable any rpc that is not needed

require_once $inc_path.'_misc/ext/_xmlrpcs.php'; // This will add generic remote calls

$s = new xmlrpc_server(
	array(
		"metaWeblog.newMediaObject" =>
			array(
				"function" => "mwnewMediaObject",
				"signature" => $mwnewMediaObject_sig,
				"docstring" => $mwnewMediaObject_doc),

		"metaWeblog.newPost" =>
			array(
				"function" => "mwnewpost",
				"signature" => $mwnewpost_sig,
				"docstring" => $mwnewpost_doc),

		"metaWeblog.editPost" =>
			array(
				"function" => "mweditpost",
				"signature" => $mweditpost_sig,
				"docstring" => $mweditpost_doc),

		"metaWeblog.getPost" =>
			array(
				"function" => "mwgetpost",
				"signature" => $mwgetpost_sig,
				"docstring" => $mwgetpost_doc),

		"metaWeblog.getCategories" =>
			array(
				"function" => "mwgetcats",
				"signature" => $mwgetcats_sig,
				"docstring" => $mwgetcats_doc),

		"metaWeblog.getRecentPosts" =>
			array(
				"function" => "metawebloggetrecentposts",
				"signature" => $metawebloggetrecentposts_sig,
				"docstring" => $metawebloggetrecentposts_doc),

		"b2.newPost" =>
			array(
				"function" => "b2newpost",
				"signature" => $b2newpost_sig,
				"docstring" => $b2newpost_doc),

		"b2.getCategories" =>
			array(
				"function" => "b2getcategories",
				"signature" => $b2getcategories_sig,
				"docstring" => $b2getcategories_doc),

		"b2.getPostURL" =>
			array(
				"function" => "b2_getPostURL",
				"signature" => $b2_getPostURL_sig,
				"docstring" => $b2_getPostURL_doc),

		"blogger.newPost" =>
			array(
				"function" => "bloggernewpost",
				"signature" => $bloggernewpost_sig,
				"docstring" => $bloggernewpost_doc),

		"blogger.editPost" =>
			array(
				"function" => "bloggereditpost",
				"signature" => $bloggereditpost_sig,
				"docstring" => $bloggereditpost_doc),

		"blogger.deletePost" =>
			array(
				"function" => "bloggerdeletepost",
				"signature" => $bloggerdeletepost_sig,
				"docstring" => $bloggerdeletepost_doc),

		"blogger.getUsersBlogs" =>
			array(
				"function" => "bloggergetusersblogs",
				"signature" => $bloggergetusersblogs_sig,
				"docstring" => $bloggergetusersblogs_doc),

		"blogger.getUserInfo" =>
			array(
				"function" => "bloggergetuserinfo",
				"signature" => $bloggergetuserinfo_sig,
				"docstring" => $bloggergetuserinfo_doc),

		"blogger.getPost" =>
			array(
				"function" => "bloggergetpost",
				"signature" => $bloggergetpost_sig,
				"docstring" => $bloggergetpost_doc),

		"blogger.getRecentPosts" =>
			array(
				"function" => "bloggergetrecentposts",
				"signature" => $bloggergetrecentposts_sig,
				"docstring" => $bloggergetrecentposts_doc),

		"blogger.getTemplate" =>
			array(
				"function" => "bloggergettemplate",
				"signature" => $bloggergettemplate_sig,
				"docstring" => $bloggergettemplate_doc),

		"blogger.setTemplate" =>
			array(
				"function" => "bloggersettemplate",
				"signature" => $bloggersettemplate_sig,
				"docstring" => $bloggersettemplate_doc),

		"mt.getPostCategories" =>
			array(
				"function" => "mt_getPostCategories",
				"signature" => $mt_getPostCategories_sig,
				"docstring" => $mt_getPostCategories_doc),

		"mt.getCategoryList" =>
			array(
				"function" => "mt_getCategoryList",
				"signature" => $mt_getCategoryList_sig,
				"docstring" => $mt_getCategoryList_doc),

		"mt.setPostCategories" =>
			array(
				"function" => "mt_setPostCategories",
				"signature" => $mt_setPostCategories_sig,
				"docstring" => $mt_setPostCategories_doc),
	)
);

/*
 * $Log$
 * Revision 1.130  2006/12/17 23:42:40  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.129  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.128  2006/12/06 17:49:11  blueyed
 * Moved strip_all_but_one_link() to obsolete2.php; doc (pingback removed)
 *
 * Revision 1.127  2006/12/05 07:23:22  blueyed
 * Fixed categories handling also for blogger.editPost; doc
 *
 * Revision 1.126  2006/12/05 06:31:41  blueyed
 * Nuked $default_category from XMLRPC
 *
 * Revision 1.125  2006/12/05 06:22:25  blueyed
 * Fixed blogger.newPost to accept a list of categories, as given by w.bloggar
 *
 * Revision 1.124  2006/12/03 18:22:58  blueyed
 * Nuked deprecated fileupload globals
 *
 * Revision 1.122  2006/12/03 01:24:38  blueyed
 * "htmlUrl" and "rssUrl" for metaWeblog.getCategories
 *
 * Revision 1.121  2006/12/02 19:51:08  blueyed
 * "categoryId" case fixes; see http://forums.b2evolution.net/viewtopic.php?p=47650#47650
 *
 * Revision 1.120  2006/11/16 19:14:10  fplanque
 * minor
 *
 * Revision 1.119  2006/11/13 20:49:53  fplanque
 * doc/cleanup :/
 *
 * Revision 1.118  2006/11/05 20:13:57  fplanque
 * minor
 *
 * Revision 1.117  2006/10/10 19:23:51  blueyed
 * Use set()/dbupdate() instead of update() in bloggereditpost()
 *
 * Revision 1.116  2006/10/01 20:08:39  blueyed
 * Removed DEBUG_XMLRPC_LOGGING constant again. It gave a notice when using the xmlrpcclient e.g. for sending pings and is not as flexible as a global.
 *
 * Revision 1.115  2006/09/22 21:27:53  blueyed
 * Minor fixes for RSD, formatting, whitespace.
 *
 * Revision 1.114  2006/09/22 19:11:20  wendall911
 * Added rsd support, restored 0.9.x functionality
 *
 * Revision 1.113  2006/09/06 21:39:23  fplanque
 * ItemList2 fixes
 *
 * Revision 1.112  2006/09/06 20:45:34  fplanque
 * ItemList2 fixes
 *
 * Revision 1.111  2006/09/06 18:34:07  fplanque
 * Finally killed the old stinkin' ItemList(1) class which is deprecated by ItemList2
 *
 * Revision 1.110  2006/08/29 00:26:12  fplanque
 * Massive changes rolling in ItemList2.
 * This is somehow the meat of version 2.0.
 * This branch has gone officially unstable at this point! :>
 *
 * Revision 1.109  2006/08/21 16:07:45  fplanque
 * refactoring
 *
 * Revision 1.108  2006/08/21 00:03:13  fplanque
 * obsoleted some dirty old thing
 *
 * Revision 1.107  2006/08/19 07:56:32  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.106  2006/08/19 02:15:09  fplanque
 * Half kille dthe pingbacks
 * Still supported in DB in case someone wants to write a plugin.
 *
 * Revision 1.105  2006/08/01 23:32:45  blueyed
 * Moved $xmlrpc_logging into /conf/_advanced.php (and renamed it to $debug_xmlrpc_logging), so it can get overridden easily.
 *
 * Revision 1.104  2006/08/01 22:56:38  blueyed
 * Fixed "perm denied"
 *
 * Revision 1.103  2006/08/01 22:53:31  blueyed
 * Fix
 *
 * Revision 1.102  2006/08/01 21:53:02  blueyed
 * Fixes
 *
 * Revision 1.101  2006/08/01 19:23:55  blueyed
 * fixes
 *
 * Revision 1.100  2006/08/01 18:43:27  blueyed
 * Removed last deprecated $table.. occurences.
 *
 * Revision 1.99  2006/08/01 18:24:10  blueyed
 * Fixes:
 *  - metaWeblog.editPost:
 *    - respect publish/status param
 *    - check perms
 *    - only update relevant parts of item
 *  - mt.setPostCategories: set only categories (keep status etc)
 * Features:
 *  - support categories for metaWeblog.editPost/newPost
 * Code cleanup!
 * Completely untested.. :/
 *
 * Revision 1.97  2006/07/24 00:05:46  fplanque
 * cleaned up skins
 *
 * Revision 1.96  2006/07/06 19:56:31  fplanque
 * no message
 *
 * Revision 1.95  2006/07/02 21:53:31  blueyed
 * time difference as seconds instead of hours; validate user#1 on upgrade; bumped new_db_version to 9300.
 *
 * Revision 1.94  2006/05/30 20:32:57  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.93  2006/03/24 01:04:33  blueyed
 * Fix for mt.getCategoryList? At least E_NOTICE fixed.
 *
 * Revision 1.91  2006/03/18 19:17:54  blueyed
 * Removed remaining use of $img_url
 *
 * Revision 1.90  2006/03/12 23:09:31  fplanque
 * doc cleanup
 *
 * Revision 1.89  2006/03/09 22:30:02  fplanque
 * cleaned up permanent urls
 *
 * Revision 1.88  2006/03/09 20:40:41  fplanque
 * cleanup
 *
 * Revision 1.87  2006/02/23 21:12:54  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.86  2006/01/06 16:42:41  fplanque
 * bugfix
 *
 * Revision 1.80  2005/10/29 19:46:45  tor_gisvold
 * Bug fix for all blogger API routines - all of these errored due to lack of global cache definition
 * I also hope that I have fixed the pesky double line spacing done by my CVS frontend - if not I apologise and will fix.
 *
 * Revision 1.79  2005/10/23 18:14:24  tor_gisvold
 * Metaweblog API and Movable Type API first cut for new version of b2evolution
 * Tor 23102005
 */
?>