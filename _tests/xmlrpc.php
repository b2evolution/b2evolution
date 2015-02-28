<?php
/**
 * XML-RPC Tests
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package tests
 */
require_once  dirname(__FILE__).'/../blogs/conf/_config.php';

define( 'EVO_MAIN_INIT', true );

/**
 * class loader
 */
require_once $inc_path.'_core/_class'.floor(PHP_VERSION).'.funcs.php';
require_once $inc_path.'_core/_misc.funcs.php';

load_funcs('xmlrpc/model/_xmlrpc.funcs.php');
load_funcs('locales/_locale.funcs.php');

echo '<h1>XML-RPC tests</h1>';

$target = 'local';

echo "<p>Target: $target</p>";

switch( $target )
{
	case 'local':
		$test_user = 'admin';
		$test_pass = $install_password;
		pre_dump( $test_user, $test_pass );
		$client = new xmlrpc_client( $basesubpath.$xmlsrv_subdir.'xmlrpc.php', $basehost, $baseport );
		break;

	default:
		die('unknown target');
}

$bloggerAPIappkey = 'testkey';

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>system.listMethods</h2>';
	{
		evo_flush();
		$client->debug = 0;
		$message = new xmlrpcmsg( 'system.listMethods' );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		//pre_dump( $ret );
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>system.getCapabilities</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'system.getCapabilities' );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		pre_dump( $ret );
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>system.methodHelp</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'system.methodHelp', array(
															new xmlrpcval( 'system.multicall' ),
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		pre_dump( $ret );
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>system.methodSignature</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'system.methodSignature', array(
															new xmlrpcval( 'system.multicall' ),
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		pre_dump( $ret );
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>blogger.getUserInfo</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'blogger.getUserInfo', array(
															new xmlrpcval($bloggerAPIappkey),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass)
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		pre_dump( $ret );
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>blogger.getUsersBlogs</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'blogger.getUsersBlogs', array(
															new xmlrpcval($bloggerAPIappkey),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass)
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		// pre_dump( $ret );
		if( is_array( $ret ) )foreach( $ret as $a )
		{
			echo '<li><a href="'.$a['url'].'">'.$a['blogName'].'</a></li>';
		}
	}

 	// ----------------------------------------------------------------------------------------------------

	echo '<h2>b2.getCategories</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'b2.getCategories', array(
															new xmlrpcval(1),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass)
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		// pre_dump( $ret );
		if( is_array( $ret ) )foreach( $ret as $a )
		{
			echo '<li>'.$a['categoryName'].'</li>';
		}
	}

 	// ----------------------------------------------------------------------------------------------------

	echo '<h2>mt.getCategoryList</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'mt.getCategoryList', array(
															new xmlrpcval(1),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass)
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		// pre_dump( $ret );
		if( is_array( $ret ) )foreach( $ret as $a )
		{
			echo '<li>'.$a['categoryName'].'</li>';
		}
	}

 	// ----------------------------------------------------------------------------------------------------

	echo '<h2>metaWeblog.getCategories</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'metaWeblog.getCategories', array(
															new xmlrpcval(1),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass)
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		//pre_dump( $ret );
		if( is_array( $ret ) )foreach( $ret as $a )
		{
			echo '<li><a href="'.$a['htmlUrl'].'">'.$a['description'].'</a></li>';
		}
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>metaWeblog.newPost</h2>';
	{
		evo_flush();
		$post_text = 'XML-RPC metaWeblog.newPost : random # '.rand( 1, 10000 );
		echo 'Post_text : '.$post_text;
		$client->debug = 0;
		$message = new xmlrpcmsg( 'metaWeblog.newPost', array(
				new xmlrpcval( 1 ), // blog
				new xmlrpcval($test_user),
				new xmlrpcval($test_pass),
				new xmlrpcval( array(
						'title'             => new xmlrpcval('<i>'.$post_text),
						'description'       => new xmlrpcval($post_text),
						'categories'        => new xmlrpcval( array(
                        								new xmlrpcval( 'News' ),
																				new xmlrpcval( 'Fun' )
																		), 'array' ),
					), 'struct' ),
				new xmlrpcval( false, 'boolean' ),		// NOT Published
			) );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		if( empty($ret)  )
		{
			die( 'ERROR' );
		}
		$msg_ID = xmlrpc_decode_recurse($result->value());
		echo '<p>OK - Message ID: '.$msg_ID.'</p>';
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>metaWeblog.editPost</h2>';
	{
		evo_flush();
		$client->debug = 0;
		$message = new xmlrpcmsg( 'metaWeblog.editPost', array(
				new xmlrpcval( $msg_ID ), // post ID
				new xmlrpcval( $test_user ),
				new xmlrpcval( $test_pass ),
				new xmlrpcval( array(
						'title'       => new xmlrpcval( '<i>'.$post_text ),
						'description' => new xmlrpcval( $post_text."\n* Edited *" ),
						'categories'  => new xmlrpcval( array(
                        					new xmlrpcval( 'News' ),
																	new xmlrpcval( 'Fun' )
															), 'array' ),
					), 'struct' ),
				new xmlrpcval( true, 'boolean' ),		// Published
			) );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		// pre_dump( $ret );
		if( $ret == 1 )
		{
			echo 'OK';
		}
		else
		{
			die('ERROR');
		}
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>metaWeblog.getPost</h2>';
	{
		evo_flush();
		$client->debug = 0;
		$message = new xmlrpcmsg( 'metaWeblog.getPost', array(
															new xmlrpcval($msg_ID),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass),
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		pre_dump( $ret );

 		echo '<p>Content: '.( ! empty( $ret['content'] ) ? htmlspecialchars( $ret['content'] ) : '' );
		echo '</p>';
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>b2.newPost</h2>';
	{
		evo_flush();
		$post_text = 'XML-RPC b2.newPost : random # '.rand( 1, 10000 );
		echo 'Post_text : '.$post_text;
		$client->debug = 0;
		$message = new xmlrpcmsg( 'b2.newPost', array(
															new xmlrpcval( '' ),
															new xmlrpcval( '' ),
															new xmlrpcval( $test_user ),
															new xmlrpcval( $test_pass ),
															new xmlrpcval( "<p>$post_text</p>\n" ),
															new xmlrpcval( true,"boolean"),		// Published
															new xmlrpcval( '<i>'.$post_text ),	// TITLE
															new xmlrpcval( 1 ), // Category
															new xmlrpcval( '' ) // Date
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		if( empty($ret)  )
		{
			die( 'ERROR' );
		}
		$msg_ID = xmlrpc_decode_recurse($result->value());
		echo '<p>OK - Message ID: '.$msg_ID.'</p>';
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>mt.setPostCategories</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'mt.setPostCategories', array(
															new xmlrpcval( $msg_ID ),
															new xmlrpcval( $test_user ),
															new xmlrpcval( $test_pass ),
															new xmlrpcval( array(
																	new xmlrpcval( array(
																			'categoryId' => new xmlrpcval(2), // Category
																			'isPrimary' => new xmlrpcval(false,"boolean"),
																		), 'struct' ),
																	new xmlrpcval( array(
																			'categoryId' => new xmlrpcval(4), // Category
																			'isPrimary' => new xmlrpcval(false,"boolean"),
																		), 'struct' )
																), 'array' )
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		pre_dump( $ret );
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>mt.getPostCategories</h2>';
	{
		evo_flush();
		$client->debug = 0;
		$message = new xmlrpcmsg( 'mt.getPostCategories', array(
															new xmlrpcval( $msg_ID ),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass)
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		pre_dump( $ret );
		if( is_array( $ret ) )foreach( $ret as $a )
		{
			echo '<li>'.$a['categoryName'].'</li>';
		}
	}

	// ----------------------------------------------------------------------------------------------------
	echo '<h2>blogger.newPost</h2>';
	{
		evo_flush();
		$post_text = 'XML-RPC post : random # '.rand( 1, 10000 );
		echo 'Post_text : '.$post_text;
		$client->debug = 0;

		$content = "<title><i>$post_text</title>
								<p>$post_text</p>\n";
		$content .= '<category>2,03</category>';


		$message = new xmlrpcmsg( 'blogger.newPost', array(
															new xmlrpcval( $bloggerAPIappkey ),
															new xmlrpcval( 1 ),
															new xmlrpcval( $test_user ),
															new xmlrpcval( $test_pass ),
															new xmlrpcval( $content ),
															new xmlrpcval( true, 'boolean' )		// published
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		if( empty($ret)  )
		{
			die( 'ERROR' );
		}
		$msg_ID = xmlrpc_decode_recurse($result->value());
		echo '<p>OK - Message ID: '.$msg_ID.'</p>';
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>metaWeblog.getRecentPosts</h2>';
	{
		evo_flush();
		$client->debug = 0;
		$message = new xmlrpcmsg( 'metaWeblog.getRecentPosts', array(
															new xmlrpcval(1), // blog
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass),
															new xmlrpcval(5, 'int' ),
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );

		//pre_dump( $ret );
		if( is_array( $ret ) )foreach( $ret as $a )
		{
			echo '<li>'.htmlspecialchars($a['title']).'</li>';
		}
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>blogger.getRecentPosts</h2>';
	{
		evo_flush();
		$client->debug = false;
	$message = new xmlrpcmsg( 'blogger.getRecentPosts', array(
														new xmlrpcval($bloggerAPIappkey),
														new xmlrpcval(1),
														new xmlrpcval($test_user),
														new xmlrpcval($test_pass),
														new xmlrpcval(6, 'int' ),
													)  );
	$result = $client->send($message);
	$ret = xmlrpc_displayresult( $result );

	// pre_dump( $ret );
	if( is_array( $ret ) )foreach( $ret as $a )
	{
		echo '<li>'.htmlspecialchars($a['content']).'</li>';
	}

	// Get latest message:
	$latest = $ret[0];
	pre_dump( $latest );

	echo '<p>Message ID: '.$latest['postid'];
	if( $latest['postid'] == $msg_ID )
	{
		echo '- OK match';
	}
	else
	{
		die( 'ERROR' );
	}
	echo '</p>';

	echo '<p>Content: '.htmlspecialchars($latest['content']);
	if( strpos( $latest['content'], $post_text ) )
	{
		echo ' - OK';
	}
	else
	{
		die( 'ERROR' );
	}
	echo '</p>';


	if( strpos( $ret[3]['content'], 'XML-RPC post :' ) )
	{	// This is a previous XML-RPC test post
		$delete_post = $ret[3]['postid'];
	}
	if( strpos( $ret[4]['content'], 'XML-RPC b2.newPost :' ) )
	{	// This is a previous XML-RPC test post
		$delete_post2 = $ret[4]['postid'];
	}
	if( strpos( $ret[5]['content'], 'XML-RPC metaWeblog.newPost :' ) )
	{	// This is a previous XML-RPC test post
		$delete_post3 = $ret[5]['postid'];
	}
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>blogger.editPost</h2>';
	{
		evo_flush();
		$client->debug = 0;

		// Add something to message:
		$post_content = $latest['content']."\n* This has been edited! *";

		$message = new xmlrpcmsg( 'blogger.editPost', array(
															new xmlrpcval( $bloggerAPIappkey),
															new xmlrpcval( $msg_ID),
															new xmlrpcval( $test_user),
															new xmlrpcval( $test_pass),
															new xmlrpcval( $post_content ),
															new xmlrpcval( true, 'boolean')		// PUBLISH !!
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		// pre_dump( $ret );
		if( $ret == 1 )
		{
			echo 'OK';
		}
		else
		{
			die('ERROR');
		}
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>blogger.getPost</h2>';
	{
		evo_flush();
		$client->debug = false;
		$message = new xmlrpcmsg( 'blogger.getPost', array(
															new xmlrpcval($bloggerAPIappkey),
															new xmlrpcval($msg_ID),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass),
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		// pre_dump( $ret );

 		echo '<p>Content: '.htmlspecialchars($ret['content']);
		if( strpos( $ret['content'], $post_text ) && strpos( $ret['content'], '* This has been edited! *' ) )
		{
			echo ' - OK';
		}
		else
		{
			die( 'ERROR' );
		}
		echo '</p>';
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>b2.getPostURL</h2>';
	{
		evo_flush();
		$client->debug = 0;
		$message = new xmlrpcmsg( 'b2.getPostURL', array(
															new xmlrpcval(0),
															new xmlrpcval(''),
															new xmlrpcval($test_user),
															new xmlrpcval($test_pass),
															new xmlrpcval($msg_ID),
														)  );
		$result = $client->send($message);
		$ret = xmlrpc_displayresult( $result );
		// pre_dump( $ret );

		echo 'OK - <a href="'.$ret.'">'.$ret.'</a>';
	}

	// ----------------------------------------------------------------------------------------------------

	echo '<h2>blogger.deletePost</h2>';
	{
		evo_flush();
		if( empty( $delete_post ) )
		{
			echo 'no post to delete yet. run again.';
		}
		else
		{
			$client->debug = false;
			$message = new xmlrpcmsg( 'blogger.deletePost', array(
																new xmlrpcval($bloggerAPIappkey),
																new xmlrpcval( $delete_post ),
																new xmlrpcval($test_user),
																new xmlrpcval($test_pass),
																new xmlrpcval(0,'boolean'),
															)  );
			$result = $client->send($message);
			$ret = xmlrpc_displayresult( $result );
			// pre_dump( $ret );
			if( $ret == 1 )
			{
				echo "OK<br/>\n";
			}
			else
			{
				die('ERROR');
			}
		}

		if( empty( $delete_post2 ) )
		{
			echo 'no post2 to delete yet. run again.';
		}
		else
		{
			$client->debug = false;
			$message = new xmlrpcmsg( 'blogger.deletePost', array(
																new xmlrpcval($bloggerAPIappkey),
																new xmlrpcval( $delete_post2 ),
																new xmlrpcval($test_user),
																new xmlrpcval($test_pass),
																new xmlrpcval(0,'boolean'),
															)  );
			$result = $client->send($message);
			$ret = xmlrpc_displayresult( $result );
			// pre_dump( $ret );
			if( $ret == 1 )
			{
				echo "OK<br/>\n";
			}
			else
			{
				die('ERROR');
			}
		}

		if( empty( $delete_post3 ) )
		{
			echo 'no post3 to delete yet. run again.';
		}
		else
		{
			$client->debug = false;
			$message = new xmlrpcmsg( 'blogger.deletePost', array(
																new xmlrpcval($bloggerAPIappkey),
																new xmlrpcval( $delete_post3 ),
																new xmlrpcval($test_user),
																new xmlrpcval($test_pass),
																new xmlrpcval(0,'boolean'),
															)  );
			$result = $client->send($message);
			$ret = xmlrpc_displayresult( $result );
			// pre_dump( $ret );
			if( $ret == 1 )
			{
				echo "OK<br/>\n";
			}
			else
			{
				die('ERROR');
			}
		}
	}

	// ----------------------------------------------------------------------------------------------------

// Missing tests:

// metaWeblog.newMediaObject
?>
