<?php
/**
 * This is the PLAIN TEXT template of email message for unread message reminder
 *
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

global $htsrv_url;

// Default params:
$params = array_merge( array(
		'unread_threads' => '',
		'threads_link'   => '',
	), $params );


echo T_( 'Hello $login$ !' );
echo "\n\n";

echo T_( 'You have unread messages in the following conversations:' )."\n";

if( count( $params['unread_threads'] ) > 0 )
{
	foreach( $params['unread_threads'] as $unread_thread )
	{
		echo "\t - ".$unread_thread."\n";
	}
	echo "\n";
}
echo T_( 'Click here to read your messages:' ).' '.$params['threads_link'];
echo "\n\n";

echo T_( 'If you don\'t want to receive notifications for unread messages any more, please click here:' ).' ';
echo $htsrv_url.'quick_unsubscribe.php?type=unread_msg&user_ID=$user_ID$&key=$unsubscribe_key$';
?>