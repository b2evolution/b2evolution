<?php
/**
 * This file handles trackback requests
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */


/**
 * Initialize everything:
 */
require_once( dirname(__FILE__).'/../evocore/_main.inc.php' );

// statuses allowed for acting on:
$show_statuses = array( 'published', 'protected', 'private' );

param( 'tb_id', 'integer' );
param( 'url', 'string' );
param( 'title', 'string' );
param( 'excerpt', 'html' );
param( 'blog_name', 'string' );


if( empty($tb_id) )
{ // No parameter for ID, get if from URL:
	$path_elements = explode( '/', $ReqPath, 30 );
	$tb_id = intval( $path_elements[count($path_elements)-1] );
}

if( (strlen(''.$tb_id)) && (empty($_GET['__mode'])) && (strlen(''.$url)) )
{
	@header('Content-Type: text/xml');

	$comment_post_ID = $tb_id;
	$postdata = get_postdata($comment_post_ID);
	$blog = $postdata['Blog'];
	if( !( $Blog =& $BlogCache->get_by_ID( $blog ) ) )
	{
		trackback_response( 1, 'Sorry, could not get the post\'s weblog.' );
	}

	if( !$Blog->get('allowtrackbacks') )
	{
		trackback_response( 1, 'Sorry, this weblog does not allow you to trackback its posts.' );
	}

	$title = strip_tags($title);
	$title = (strlen($title) > 255) ? substr($title, 0, 252).'...' : $title;
	$excerpt = strip_tags($excerpt);
	$excerpt = (strlen($excerpt) > 255) ? substr($excerpt, 0, 252).'...' : $excerpt;
	$blog_name = htmlspecialchars($blog_name);
	$blog_name = (strlen($blog_name) > 255) ? substr($blog_name, 0, 252).'...' : $blog_name;

	$comment = "<strong>$title</strong><br />$excerpt";

	$original_comment = $comment;

	$user_ip = getIpList( true );
	$user_domain = gethostbyaddr($user_ip);
	$now = date('Y-m-d H:i:s', $localtimenow );

	// CHECK and FORMAT content
	if( $error = validate_url( $url, $comments_allowed_uri_scheme ) )
	{
		$Messages->add( T_('Supplied URL is invalid: ').$error );
	}
	$comment = format_to_post($comment,1,1);

	if( $errstring = $Messages->getString( 'Cannot insert trackback, please correct these errors:', '' ) )
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
	$AuthorUser =& $UserCache->get_by_ID( $postdata['Author_ID'] );
	if( $AuthorUser->notify )
	{ // Author wants to be notified:
		locale_temp_switch( $AuthorUser->get( 'locale' ) );
		$recipient = $AuthorUser->get( 'email' );
		$subject = sprintf( T_('New trackback on your post #%d "%s"'), $comment_post_ID, $postdata['Title'] );

		$notify_message = sprintf( T_('New trackback on your post #%d "%s"'), $comment_post_ID, $postdata['Title'] )."\n";
											.url_add_param( $Blog->get('blogurl'), "p=$comment_post_ID&tb=1\n\n", '&' );
											.T_('Website').": $comment_author (IP: $user_ip , $user_domain)\n";
											.T_('Url').": $comment_author_url\n";
											.T_('Excerpt').": \n".$original_comment."\n\n";
											.T_('Edit/Delete').': '.$admin_url.'b2browse.php?blog='.$blog.'&p='.$comment_post_ID."&c=1\n\n";

		send_mail( $recipient, $subject, $notify_message, $notify_from );

		locale_restore_previous();
	}

	trackback_response( 0, 'ok' );
}

?>