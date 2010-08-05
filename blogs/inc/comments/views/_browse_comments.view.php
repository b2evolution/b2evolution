<?php
/**
 * This file implements the comment browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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
global $CommentList, $show_statuses;

global $dispatcher;


/*
 * Display comments:
 */

$CommentList->query();

$block_item_Widget = new Widget( 'block_item' );

if( $CommentList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$block_item_Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=comments&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3 );
}
$block_item_Widget->title = T_('Feedback (Comments, Trackbacks...)');
$block_item_Widget->disp_template_replaced( 'block_start' );

// Display filters title
echo $CommentList->get_filter_title( '<h3>', '</h3>', '<br />', NULL, 'htmlbody' );

$display_params = array(
				'header_start' => '<div class="NavBar center">',
					'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
					'header_text_single' => T_('1 page'),
				'header_end' => '</div>',
				'footer_start' => '',
					'footer_text' => '<div class="NavBar center"><strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$</div>',
					'footer_text_single' => '',
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
				'footer_end' => ''
			);

$CommentList->display_if_empty();

$CommentList->display_init( $display_params );

// Display navigation:
$CommentList->display_nav( 'header' );

load_funcs( 'comments/model/_comment_js.funcs.php' );

// Display list of comments:
// comments_container value is -1, because in this case we have to show all comments in current blog (Not just one item comments)
echo '<div id="comments_container" value="-1">';
require dirname(__FILE__).'/_comment_list.inc.php';
echo '</div>';

// Display navigation:
$CommentList->display_nav( 'footer' );

$block_item_Widget->disp_template_replaced( 'block_end' );


/*
 * $Log$
 * Revision 1.10  2010/08/05 08:04:12  efy-asimo
 * Ajaxify comments on itemList FullView and commentList FullView pages
 *
 * Revision 1.9  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.8  2010/03/15 17:12:11  efy-asimo
 * Add filters to Comment page
 *
 * Revision 1.7  2010/02/08 17:52:13  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.6  2010/01/30 18:55:22  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.5  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.4  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/11/03 21:04:26  fplanque
 * skin cleanup
 *
 * Revision 1.2  2007/09/03 18:32:50  fplanque
 * enhanced dashboard / comment moderation
 *
 * Revision 1.1  2007/06/25 10:59:42  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.13  2007/04/26 00:11:06  fplanque
 * (c) 2007
 */
?>