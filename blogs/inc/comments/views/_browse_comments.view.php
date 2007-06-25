<?php
/**
 * This file implements the comment browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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
?>
<div class="bFeedback">
<h2><?php echo T_('Feedback (Comments, Trackbacks...)') ?></h2>
<?php

$CommentList->display_if_empty( '<div class="bComment"><p>'.T_('No feedback yet...').'</p></div>' );

// Display list of comments:
require dirname(__FILE__).'/_comment_list.inc.php';

?>
</div>

<?php
/*
 * $Log$
 * Revision 1.1  2007/06/25 10:59:42  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.13  2007/04/26 00:11:06  fplanque
 * (c) 2007
 */
?>