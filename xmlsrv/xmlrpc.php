<?php
/**
 * XML-RPC APIs
 *
 * This file implements the XML-RPC handler, to be called by remote clients.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package xmlsrv
 */

// use xmlrpc_debugmsg() to add debug messages to responses or logIO() to
// log them to a local file (preferred).

/**
 * Initialize everything:
 */

if( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST' )
{
	header('Content-Type: text/plain');
	die('XML-RPC server accepts POST requests only.');
}

// Disable Cookies
$_COOKIE = array();

if( ! isset($HTTP_RAW_POST_DATA) )
{
	$HTTP_RAW_POST_DATA = implode("\r\n", file('php://input'));
}
// Trim requests (used by XML-RPC library); fix for mozBlog and other cases where '<?xml' isn't on the very first line
$HTTP_RAW_POST_DATA = trim( $HTTP_RAW_POST_DATA );


require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';
load_funcs('xmlrpc/model/_xmlrpc.funcs.php');

if( CANUSEXMLRPC !== TRUE || ! $Settings->get('general_xmlrpc') )
{	// We cannot use XML-RPC: send an error response ( "17 Internal server error" ).
	$errMessage = CANUSEXMLRPC !== TRUE
					? ( 'Cannot use XML-RPC. Probably the server is missing the XML extension. Error: '.CANUSEXMLRPC )
					: 'XML-RPC services are disabled on this system.';
	$errResponse = new xmlrpcresp( 0, 17, $errMessage );
	die( $errResponse->serialize() );
}


// We can't display standard error messages. We must return XMLRPC responses.
$DB->halt_on_error = false;
$DB->show_errors = false;

$post_default_title = ''; // posts submitted via the xmlrpc interface get that title


/**
 * Array defining the available Remote Procedure Calls:
 */
$xmlrpc_procs = array();


// Load APIs:
include_once $inc_path.'xmlrpc/apis/_blogger.api.php';
include_once $inc_path.'xmlrpc/apis/_b2.api.php';
include_once $inc_path.'xmlrpc/apis/_metaweblog.api.php';
include_once $inc_path.'xmlrpc/apis/_mt.api.php';
include_once $inc_path.'xmlrpc/apis/_wordpress.api.php';


// fp> xmlrpc.php should actually only load the function/api/plugin to execute once it has been identified
// fp> maybe it would make sense to register xmlrpc apis/functions in a DB table (before making plugins)
// fp> it would probably make sense to have *all* xmlrpc methods implemented as plugins (maybe 1 plugin per API; it should be possible to add a single func to an API with an additional plugin)

load_funcs('xmlrpc/model/_xmlrpcs.funcs.php'); // This will add generic remote calls

// Set up the XML-RPC server:
$s = new xmlrpc_server( $xmlrpc_procs, false );
// Use the request encoding for the response:
$s->response_charset_encoding = 'auto';
// DO THE SERVING:
$s->service();

?>