<?php
/**
 * XML-RPC APIs
 *
 * This file implements the XML-RPC handler, to be called by remote clients.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package xmlsrv
 *
 * @version $Id$
 */

/**
 * Initialize everything:
 */

// Disable Cookies
$_COOKIE = array();

if( ! isset($HTTP_RAW_POST_DATA) )
{
	$HTTP_RAW_POST_DATA = implode("\r\n", file('php://input'));
}
// Trim requests (used by XML-RPC library); fix for mozBlog and other cases where '<?xml' isn't on the very first line
$HTTP_RAW_POST_DATA = trim( $HTTP_RAW_POST_DATA );


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
load_funcs('_ext/xmlrpc/_xmlrpc.php');
load_class('items/model/_itemlist.class.php');

if( CANUSEXMLRPC !== TRUE )
{ // We cannot use XML-RPC: send a error response ( "1 Unknown method" ).
    //this should be structured as an xml response
	$errResponse = new xmlrpcresp( 0, 1, 'Cannot use XML-RPC. Probably the server is missing the XML extension. Error: '.CANUSEXMLRPC );
	die( $errResponse->serialize() );
}


// Handle "Really Simple Discovery":
// fp> TODO: this should probably be moved over to the main pages
if ( isset( $_GET['rsd'] ) )
{ // http://archipelago.phrasewise.com/rsd
	header('Content-type: text/xml; charset=' . $evo_charset, true);

	?>
	<?php echo '<?xml version="1.0" encoding="'.$evo_charset.'"?'.'>'; ?>
	<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
	  <service>
	    <engineName>b2evolution</engineName>
	    <engineLink>http://b2evolution.net/</engineLink>
	    <homePageLink><?php echo $baseurl ?></homePageLink>
	    <apis>
	      <api name="Blogger" preferred="true" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php" />
	      <api name="b2" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php" />
	      <api name="MetaWeblog" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php" />
	      <api name="Movable Type" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php" />
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
 * Array defining the available Remote Procedure Calls:
 */
$xmlrpc_procs = array();

// fp> TODO: auto detec .api files and loop
include_once dirname(__FILE__).'/apis/_blogger.api.php';
include_once dirname(__FILE__).'/apis/_b2.api.php';
include_once dirname(__FILE__).'/apis/_metaweblog.api.php';
include_once dirname(__FILE__).'/apis/_mt.api.php';




/**** SERVER FUNCTIONS ARRAY ****/
// dh> TODO: Plugin hook here, so that Plugins can provide own callbacks?!
// fp> The current implementation of this file is not optimal (file is way too large)
// fp> xmlrpc.php should actually only be a switcher and it should load the function to execute once it has been identified
// fp> maybe it would make sense to register xmlrpc apis/functions in a DB table
// fp> it would probably make sense to have *all* xmlrpc methods implemented as plugins (maybe 1 plugin per API; it should be possible to add a single func to an API with an additional plugin)
// dh> NOTE: some tools may use different API entry points, e.g. for extended methods.. (But I'm not sure..)
// fp> from a security standpoint it would make a lot of sense to disable any rpc that is not needed

load_funcs('_ext/xmlrpc/_xmlrpcs.php'); // This will add generic remote calls

$s = new xmlrpc_server( $xmlrpc_procs );



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

	$blog = $m->getParam(0);
	$blog = $blog->scalarval();

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




/*
 * $Log$
 * Revision 1.142  2008/01/12 00:03:44  fplanque
 * refact of XML-RPC
 *
 */
?>