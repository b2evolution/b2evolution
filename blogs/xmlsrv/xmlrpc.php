<?php
/**
 * XML-RPC APIs
 *
 * This file implements the XML-RPC handler, to be called by remote clients.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package xmlsrv
 *
 * @version $Id$
 */

// use xmlrpc_debugmsg() to add debug messages to responses;

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


require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';
load_funcs('xmlrpc/model/_xmlrpc.funcs.php');

if( CANUSEXMLRPC !== TRUE || ! $Settings->get('general_xmlrpc') )
{	// We cannot use XML-RPC: send an error response ( "17 Internal server error" ).
	$errMessage = CANUSEXMLRPC !== TRUE
					? ( 'Cannot use XML-RPC. Probably the server is missing the XML extension. Error: '.CANUSEXMLRPC )
					: 'XML-RPC services are disabled on this system.';
	$errResponse = & new xmlrpcresp( 0, 17, $errMessage );
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
$s = & new xmlrpc_server( $xmlrpc_procs, false );
// Use the request encoding for the response:
$s->response_charset_encoding = 'auto';
// DO THE SERVING:
$s->service();


/*
 * $Log$
 * Revision 1.154  2009/08/31 18:07:06  waltercruz
 * Support to wordpress aliases to metaweblog APIS
 *
 * Revision 1.153  2009/08/31 16:47:55  tblue246
 * Use error 17 (Internal server error) for error response if XML-RPC cannot be used (error/disabled by admin)
 *
 * Revision 1.152  2009/08/31 16:32:26  tblue246
 * Check whether XML-RPC is enabled in xmlsrv/xmlrpc.php
 *
 * Revision 1.151  2009/08/30 15:13:28  tblue246
 * minor/doc
 *
 * Revision 1.150  2009/08/29 19:46:41  tblue246
 * XML-RPC: Revert previous commit and auto-detect response encoding. Props to: waltercruz
 *
 * Revision 1.149  2009/03/08 23:58:16  fplanque
 * 2009
 *
 * Revision 1.148  2008/01/21 09:35:44  fplanque
 * (c) 2008
 *
 * Revision 1.147  2008/01/18 15:53:42  fplanque
 * Ninja refactoring
 *
 * Revision 1.146  2008/01/14 07:22:08  fplanque
 * Refactoring
 *
 * Revision 1.145  2008/01/12 22:51:11  fplanque
 * RSD support
 *
 * Revision 1.144  2008/01/12 08:06:15  fplanque
 * more xmlrpc tests
 *
 * Revision 1.143  2008/01/12 02:13:44  fplanque
 * XML-RPC debugging
 *
 * Revision 1.142  2008/01/12 00:03:44  fplanque
 * refact of XML-RPC
 *
 */
?>
