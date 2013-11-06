<?php
/**
 * This is the HTML template of email message when message was sent
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
$message_footer = str_replace( "\n", '<br />', $params['message_footer'] );

// show sender name
$message_header = sprintf( T_('%s has sent you this message:'), $params['sender_name'] ).'<br /><br />';

// show sender email address
$message_footer = sprintf( T_( 'By replying, your email will go directly to %s.' ), $params['sender_address'] ).'<br /><br />'.$params['message_footer'];

if( !empty( $Blog ) )
{
	echo $message_header;
	echo $params['message'];
	echo '<br /><br />-- <br />';
	echo sprintf( T_('This message was sent via the messaging system on %s.'), $Blog->name ).'<br />';
	echo get_link_tag( $Blog->get('url') ).'<br /><br />';
	echo $message_footer;
}
else
{
	echo $message_header;
	echo $params['message'];
	echo '<br /><br />-- <br />';
	echo sprintf( T_('This message was sent via the messaging system on %s.'), get_link_tag( $baseurl ) ).'<br /><br />';
	echo $message_footer;
}
?>