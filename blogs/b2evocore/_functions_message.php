<?php
/**
 * Email Messaging Functions
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 * @author This file built upon code from original b2 - http://cafelog.com/
 */

/**
 * Display "Message User" title if it has been requested
 *
 * {@internal msg_title(-) }}
 *
 * @param string Prefix to be displayed if something is going to be displayed
 * @param mixed Output format, see {@link format_to_output()} or false to
 *								return value instead of displaying it
 */
function msgform_title( $prefix = ' ', $display = 'htmlbody' )
{
	global $disp;

	if( $disp == 'email' )
	{
		$info = $prefix.T_('Message User');
		if ($display)
			echo format_to_output( $info, $display );
		else
			return $info;
	}
}


/**
 * Display the url for the message form for the parameters supplied
 *
 * {@internal msgform_url(-) }}
 *
 * @param integer Recipeint id that is used for the mail form
 * @param integer Post id that is used for the mail form
 * @param integer comment id that is used for the mail form
 */
function msgform_url( $recipient_id = null , $post_id = null, $comment_id = null )
{
	global $Blog;

	$url = $Blog->get('msgformurl');
	if( ! empty($recipient_id) ) $url = url_add_param( $url, 'recipient_id='.$recipient_id );
	if( ! empty($post_id) )      $url = url_add_param( $url, 'post_id='.$post_id );
	if( ! empty($comment_id) )   $url = url_add_param( $url, 'comment_id='.$comment_id );

	return $url;
}

?>
