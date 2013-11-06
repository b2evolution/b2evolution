<?php
/**
 * This is the PLAIN TEXT template of email message when message was sent
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl;

// Default params:
$params = array_merge( array(
		'sender_name'      => '',
		'sender_address'   => '',
		'message_footer'   => '',
		'Blog'             => NULL,
		'message'          => '',
	), $params );


$Blog = $params['Blog'];

// show sender name
$message_header = sprintf( T_('%s has sent you this message:'), $params['sender_name'] )."\n\n";

// show sender email address
$message_footer = sprintf( T_( 'By replying, your email will go directly to %s.' ), $params['sender_address'] )."\n\n".$params['message_footer'];

if( !empty( $Blog ) )
{
	echo $message_header;
	echo $params['message'];
	echo "\n\n-- \n";
	echo sprintf( T_('This message was sent via the messaging system on %s.'), $Blog->name )."\n";
	echo $Blog->get('url')."\n\n";
	echo $message_footer;
}
else
{
	echo $message_header;
	echo $params['message'];
	echo "\n\n-- \n";
	echo sprintf( T_('This message was sent via the messaging system on %s.'), $baseurl )."\n\n";
	echo $message_footer;
}
?>