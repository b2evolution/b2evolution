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
require_once(dirname(__FILE__).'/../blogs/'.$core_subdir.'_misc.funcs.php');
require_once(dirname(__FILE__).'/../blogs/'.$lib_subdir.'_xmlrpc.php');

echo '<h1>XML-RPC tests</h1>';

$target = 'local';

echo "<p>Target: $target</p>";

switch( $target )
{
	case 'local':
		$test_user = 'admin';
		$test_pass = 'testpwd';
		$client = new xmlrpc_client('/b2evolution/blogs/'.$xmlsrv_subdir.'xmlrpc.php', 'localhost', 8088);
		break;
		
	default:
		die('unknown target');
}


$bloggerAPIappkey = 'testkey';

?>


<?php


	echo '<h2>system.listMethods</h2>';
	$client->debug = false;
	$message = new xmlrpcmsg( 'system.listMethods', array(
															new xmlrpcval('test')
														)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );
	



	echo '<h2>blogger.getUsersBlogs</h2>';
	$client->debug = false;
	$message = new xmlrpcmsg( 'blogger.getUsersBlogs', array( 
														new xmlrpcval($bloggerAPIappkey),	
														new xmlrpcval($test_user),	
														new xmlrpcval($test_pass)
													)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );



	echo '<h2>b2.getCategories</h2>';
	$client->debug = false;
	$message = new xmlrpcmsg( 'b2.getCategories', array( 
														new xmlrpcval(1),
														new xmlrpcval($test_user),	
														new xmlrpcval($test_pass)
													)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );



	echo '<h2>blogger.getRecentPosts</h2>';
	$client->debug = true;
	$message = new xmlrpcmsg( 'blogger.getRecentPosts', array( 
														new xmlrpcval($bloggerAPIappkey),	
														new xmlrpcval(1),
														new xmlrpcval($test_user),	
														new xmlrpcval($test_pass),
														new xmlrpcval(5, "int"),
													)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );
	
	
	
	echo '<h2>blogger.newPost</h2>';
	$client->debug = true;
	$message = new xmlrpcmsg( 'blogger.newPost', array( 
														new xmlrpcval($bloggerAPIappkey),	
														new xmlrpcval(1),	
														new xmlrpcval($test_user),	
														new xmlrpcval($test_pass),
														new xmlrpcval( "<title>FP test</title>
														<category>123456</category>
														test content" ),
														new xmlrpcval(false	,"boolean")		// DRAFT !!
													)  );
//														<category>1</category>
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );



?>