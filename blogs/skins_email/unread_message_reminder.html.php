<?php
/**
 * This is the HTML template of email message for unread message reminder
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
echo '<br /><br />';

echo T_( 'You have unread messages in the following conversations:' ).'<br />';

if( count( $params['unread_threads'] ) > 0 )
{
	echo '<ul>';
	foreach( $params['unread_threads'] as $unread_thread )
	{
		echo '<li>'.$unread_thread.'</li>';
	}
	echo '</ul><br />';
}
echo T_( 'Click here to read your messages:' ).' '.get_link_tag( $params['threads_link'] );
echo '<br /><br />';

echo T_( 'If you don\'t want to receive notifications for unread messages any more, please click here:' ).' ';
echo get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=unread_msg&user_ID=$user_ID$&key=$unsubscribe_key$' );
?>