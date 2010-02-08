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
global $CommentList;

global $dispatcher;


/*
 * Display comments:
 */
$block_item_Widget = new Widget( 'block_item' );

$block_item_Widget->title = T_('Feedback (Comments, Trackbacks...)');
$block_item_Widget->disp_template_replaced( 'block_start' );


$CommentList->display_if_empty();

// Display list of comments:
require dirname(__FILE__).'/_comment_list.inc.php';

$block_item_Widget->disp_template_replaced( 'block_end' );


/*
 * $Log$
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