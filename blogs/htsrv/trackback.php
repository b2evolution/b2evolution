<?php
/**
 * This file handles trackback requests
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */

/**
 * Initialize everything:
 */
require_once (dirname(__FILE__).'/../b2evocore/_main.php');

// statuses allowed for acting on:
$show_statuses = array( 'published', 'protected', 'private' );

param( 'tb_id', 'integer' );
param( 'url', 'string' );
param( 'title', 'string' );
param( 'excerpt', 'html' );
param( 'blog_name', 'string' );
if(empty($tb_id))
{	// No parameter for ID, get if from URL:
	$path_elements = explode( '/', $ReqPath, 30 );
	$tb_id = intval( $path_elements[count($path_elements)-1] );
}

if ((strlen(''.$tb_id)) && (empty($HTTP_GET_VARS['__mode'])) && (strlen(''.$url)))
{
	@header('Content-Type: text/xml');

	$comment_post_ID = $tb_id;
	$postdata = get_postdata($comment_post_ID);
	$blog = $postdata['Blog'];
	$blogparams = get_blogparams_by_ID( $blog );

	if( !get_bloginfo('allowtrackbacks', $blogparams) )
	{
		trackback_response(1, 'Sorry, this weblog does not allow you to trackback its posts.');
	}

	$title = strip_tags($title);
	$title = (strlen($title) > 255) ? substr($title, 0, 252).'...' : $title;
	$excerpt = strip_tags($excerpt);
	$excerpt = (strlen($excerpt) > 255) ? substr($excerpt, 0, 252).'...' : $excerpt;
	$blog_name = htmlspecialchars($blog_name);
	$blog_name = (strlen($blog_name) > 255) ? substr($blog_name, 0, 252).'...' : $blog_name;

	$comment = "<strong>$title</strong><br />$excerpt";

	$original_comment = $comment;

	$user_ip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
	$user_domain = gethostbyaddr($user_ip);
	$now = date('Y-m-d H:i:s', $localtimenow );

	// CHECK and FORMAT content
	if( $error = validate_url( $url, $comments_allowed_uri_scheme ) )
	{
		$Messages->add( T_('Supplied URL is invalid: ').$error );
	}
	$comment = format_to_post($comment,1,1);

	if( $errstring = $Messages->string( 'Cannot insert trackback, please correct these errors:', '' ) )
	{
		trackback_response(2, $errstring);	// TODO: check that error code 2 is ok
		die();
	}

	$comment_author = $blog_name;
	$comment_author_email = '';
	$comment_author_url = $url;

	$query = "INSERT INTO T_comments( comment_post_ID, comment_type, comment_author,
																				comment_author_email, comment_author_url, comment_author_IP,
																				comment_date, comment_content)
						VALUES( $comment_post_ID, 'trackback', '".$DB->escape($comment_author)."',
										'".$DB->escape($comment_author_email)."', '".$DB->escape($comment_author_url)."', '".$DB->escape($user_ip)."',
										'$now', '".$DB->escape($comment)."' )";
	if( !$DB->query( $query ) )
	{
		trackback_response(2, "There is an error with the database, it can't store your comment...<br />Contact the <a href=\"mailto:$admin_email\">webmaster</a>");	// TODO: check that error code 2 is ok
		die ();
	}


	/*
	 * New trackback notification:
	 */
	$authordata = get_userdata($postdata['Author_ID']);
	if( get_user_info( 'notify', $authordata ) )
	{	// Author wants to be notified:
		locale_temp_switch( get_user_info( 'locale', $authordata ) );
		$recipient = get_user_info( 'email', $authordata );
		$subject = sprintf( T_('New trackback on your post #%d "%s"'), $comment_post_ID, $postdata['Title'] );

		// fplanque added:
		$comment_blogparams = get_blogparams_by_ID( $blog );

		$notify_message  = sprintf( T_('New trackback on your post #%d "%s"'), $comment_post_ID, $postdata['Title'] )."\n";
		$notify_message .= url_add_param( get_bloginfo('blogurl', $comment_blogparams), "p=$comment_post_ID&tb=1\n\n", '&' );
		$notify_message .= T_('Website').": $comment_author (IP: $user_ip , $user_domain)\n";
		$notify_message .= T_('Url').": $comment_author_url\n";
		$notify_message .= T_('Excerpt').": \n".$original_comment."\n\n";
		$notify_message .= T_('Edit/Delete').': '.$admin_url.'b2browse.php?blog='.$blog.'&p='.$comment_post_ID."&c=1\n\n";

		send_mail( $recipient, $subject, $notify_message, $notify_from );
		locale_restore_previous();

	}

	trackback_response(0,'ok');


}


?>