<?php
/**
 * XML-RPC Tests
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package tests
 */
require_once(dirname(__FILE__).'/../blogs/conf/_config.php');
require_once(dirname(__FILE__)."/../blogs/$core_subdir/_functions.php");
require_once(dirname(__FILE__)."/../blogs/$core_subdir/_functions_xmlrpc.php");

$test_user = 'test';
$test_pass = 'test';
$bloggerAPIappkey = 'testkey';

?>

<h1>XML-RPC tests</h1>
<?php

	$client = new xmlrpc_client("/b2evolution/blogs/$xmlsrv_subdir/xmlrpc.php", 'localhost', 8088);
	$client->debug = 1;

	// Get blogs:
/*	$message = new xmlrpcmsg( 'blogger.getUsersBlogs', array( 
														new xmlrpcval($bloggerAPIappkey),	
														new xmlrpcval($test_user),	
														new xmlrpcval($test_pass)
													)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );

	// Get categories:
	$message = new xmlrpcmsg( 'b2.getCategories', array( 
														new xmlrpcval(2),	// blog #2
														new xmlrpcval($test_user),	
														new xmlrpcval($test_pass)
													)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );
*/

	// edit post:
	$message = new xmlrpcmsg( 'blogger.editPost', array( 
														new xmlrpcval($bloggerAPIappkey),	
														new xmlrpcval(135),	
														new xmlrpcval($test_user),	
														new xmlrpcval($test_pass),
														new xmlrpcval( "content" ),
														new xmlrpcval(true,"boolean")
													)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );

?>