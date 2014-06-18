<?php
/**
 * This file implements the Comment List (table) view.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _comment_list_table.view.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;
/**
 * @var CommentList
 */
global $CommentList;

/*
 * Display comments:
 */
$CommentList->query();

// Dispay a form to mass delete the comments:
display_comment_mass_delete( $CommentList );

// Display title depending on selection params:
echo $CommentList->get_filter_title( '<h2>', '</h2>', '<br />', NULL, 'htmlbody' );

$CommentList->title = T_('Comment List');

if( check_comment_mass_delete( $CommentList ) )
{	// A form for mass deleting is availabl, Display link
	$CommentList->global_icon( T_('Delete all comments!'), 'delete', regenerate_url( 'action', 'action=mass_delete' ), T_('Mass delete...'), 3, 3 );
}

if( $CommentList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$CommentList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=comments&amp;blog='.$Blog->ID.'&amp;tab3=listview&amp;filter=reset', T_('Reset filters'), 3, 3 );
}

// Initialize Results object
comments_results( $CommentList );

$CommentList->display();

?>