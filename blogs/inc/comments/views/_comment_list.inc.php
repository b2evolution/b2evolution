<?php
/**
 * This file implements the comment list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Comment
 */
global $Comment;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var CommentList
 */
global $CommentList;

global $current_User;

global $dispatcher;

// If rediret_to was not set, create new redirect
$redirect_to = param( 'redirect_to', 'string', regenerate_url( '', 'filter=restore', '', '&' ) );
$redirect_to = rawurlencode( $redirect_to );
$save_context = param( 'save_context', 'boolean', 'true' );
$show_comments = param( 'show_comments', 'string', 'all' );

$item_id = param( 'item_id', 'integer', 0 );
$currentpage = param( 'currentpage', 'integer', 0 );
$comments_number = param( 'comments_number', 'integer', 0 );
if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{
	echo_pages( $item_id, $currentpage, $comments_number );
}

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	if( ( $show_comments == 'draft' ) && ( $Comment->get( 'status' ) != 'draft' ) )
	{ // if show only draft comments, and current comment status isn't draft, then continue with the next comment
		continue;
	}
	echo '<div id="comment_'.$Comment->ID.'">';
	echo_comment( $Comment->ID, $redirect_to, $save_context );
	echo '</div>';
} //end of the loop, don't delete

if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{
	echo_pages( $item_id, $currentpage, $comments_number );
}

/*
 * $Log$
 * Revision 1.23  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.22  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.21  2010/09/28 13:03:16  efy-asimo
 * Paged comments on item full view
 *
 * Revision 1.20  2010/08/05 08:04:12  efy-asimo
 * Ajaxify comments on itemList FullView and commentList FullView pages
 *
 * Revision 1.19  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.18  2010/06/23 09:30:55  efy-asimo
 * Comments display and Antispam ban form modifications
 *
 * Revision 1.17  2010/06/17 06:42:44  efy-asimo
 * Fix comment actions redirect on item full page
 *
 * Revision 1.16  2010/05/24 19:04:19  sam2kb
 * Comment header split into 2 lines
 *
 * Revision 1.15  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.14  2010/02/28 23:38:39  fplanque
 * minor changes
 *
 * Revision 1.13  2010/02/26 08:34:33  efy-asimo
 * dashboard -> ban icon should be javascripted task
 *
 * Revision 1.12  2010/02/08 17:52:13  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.11  2010/01/31 17:40:04  efy-asimo
 * delete url from comments in dashboard and comments form
 *
 * Revision 1.10  2010/01/22 13:42:22  efy-isaias
 * avatar
 *
 * Revision 1.9  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.8  2008/12/18 00:34:13  blueyed
 * - Add Comment::get_author() and make Comment::author() use it
 * - Add Comment::get_title() and use it in Dashboard and Admin comment list
 *
 * Revision 1.7  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.6  2007/12/18 23:51:33  fplanque
 * nofollow handling in comment urls
 *
 * Revision 1.5  2007/11/29 21:00:10  fplanque
 * comment ratings shown in BO
 *
 * Revision 1.4  2007/09/07 21:11:10  fplanque
 * superstylin' (not even close)
 *
 * Revision 1.3  2007/09/04 19:51:28  fplanque
 * in-context comment editing
 *
 * Revision 1.2  2007/09/03 18:32:50  fplanque
 * enhanced dashboard / comment moderation
 *
 * Revision 1.1  2007/06/25 10:59:43  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.12  2007/05/20 20:54:49  fplanque
 * better comment moderation links
 *
 * Revision 1.11  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.10  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 */
?>