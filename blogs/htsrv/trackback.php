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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */


/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

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
	$commented_Item = & $ItemCache->get_by_ID( $comment_post_ID );
	if( !( $Blog = & $commented_Item->get_Blog() ) )
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

	$now = date('Y-m-d H:i:s', $localtimenow );

	// CHECK and FORMAT content
	if( $error = validate_url( $url, $comments_allowed_uri_scheme ) )
	{
		$Messages->add( T_('Supplied URL is invalid: ').$error, 'error' );
	}

	$comment = format_to_post($comment,1,1);
	if( empty($comment) )
	{ // comment should not be empty!
		$Messages->add( T_('Please do not send empty comment'), 'error' );
	}
	elseif( antispam_check( strip_tags($comment) ) )
	{
		$Messages->add( T_('Supplied comment is invalid'), 'error' );
	}

	if( $errstring = $Messages->get_string( 'Cannot insert trackback, please correct these errors:', '' ) )
	{
		trackback_response(2, $errstring);	// TODO: check that error code 2 is ok
		die();
	}


	/*
	 * ----------------------------
	 * Create and record trackback:
	 * ----------------------------
	 */
	$Comment = & new Comment();
	$Comment->set( 'type', 'trackback' );
	$Comment->set_Item( $commented_Item );
	$Comment->set( 'author', $blog_name );
	$Comment->set( 'author_url', $url );
	$Comment->set( 'author_IP', $Hit->IP );
	$Comment->set( 'date', $now );
	$Comment->set( 'content', $comment );

	if( ! $Comment->dbinsert() )
	{
		trackback_response(2, "There is an error with the database, it can't store your comment...<br />Contact the <a href=\"mailto:$admin_email\">webmaster</a>");	// TODO: check that error code 2 is ok
		die ();
	}


	/*
	 * ----------------------------
	 * New trackback notification:
	 * ----------------------------
	 */
	$Comment->send_email_notifications();


	trackback_response( 0, 'ok' );


}

?>