<?php
/**
 * This file implements the Comment List (table) view.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
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
echo $CommentList->get_filter_title( '<h2 class="page-title">', '</h2>', '<br />', NULL, 'htmlbody' );

$CommentList->title = T_('Comment List').get_manual_link( 'comments-tab' );

if( $CommentList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$CommentList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=comments&amp;blog='.$Blog->ID.'&amp;tab3=listview&amp;filter=reset', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

if( check_comment_mass_delete( $CommentList ) )
{	// A form for mass deleting is availabl, Display link
	$CommentList->global_icon( T_('Delete all comments!'), 'recycle', regenerate_url( 'action', 'action=mass_delete' ), T_('Mass delete...'), 3, 3 );
}

// Initialize Results object
comments_results( $CommentList );

$CommentList->display();

?>