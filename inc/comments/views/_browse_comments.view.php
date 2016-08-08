<?php
/**
 * This file implements the comment browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Comment
 */
global $Comment;
/**
 * @var Blog
 */
global $Collection, $Blog;
/**
 * @var CommentList
 */
global $CommentList, $show_statuses;

global $current_User, $admin_url, $tab3;

/*
 * Display comments:
 */

$CommentList->query();

// Dispay a form to mass delete the comments:
display_comment_mass_delete( $CommentList );

$block_item_Widget = new Widget( 'block_item' );

if( $CommentList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$block_item_Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=comments&amp;blog='.$Blog->ID.'&amp;tab3='.$tab3.'&amp;filter=reset', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

if( check_comment_mass_delete( $CommentList ) )
{	// A form for mass deleting is available, Display link
	$block_item_Widget->global_icon( T_('Delete all comments!'), 'recycle', regenerate_url( 'action', 'action=mass_delete' ), T_('Mass delete...'), 3, 3 );
}

$emptytrash_link = '';
// Display recycle bin placeholder, because users may have rights to recycle particular comments
$opentrash_link = '<span id="recycle_bin" class="pull-right"></span>';
if( $tab3 != 'meta' && $current_User->check_perm( 'blogs', 'editall' ) )
{
	if( $CommentList->is_trashfilter() )
	{
		$block_item_Widget->global_icon( T_('Empty recycle bin'), 'recycle_empty', $admin_url.'?ctrl=comments&amp;blog='.$CommentList->Blog->ID.'&amp;action=emptytrash', T_('Empty recycle bin...'), 5, 3 );
	}
	else
	{
		global $blog;
		$block_item_Widget->global_icon( T_('Open recycle bin'), 'recycle_full', $admin_url.'?ctrl=comments&amp;blog='.$blog.'&amp;'.$CommentList->param_prefix.'show_statuses[]=trash', T_('Open recycle bin'), 5, 3,
			array(
				// Display recycle bin placeholder, because users may have rights to recycle particular comments
				'before' => '<span id="recycle_bin">',
				'after'  => '</span>',
			) );
	}
}
$block_item_Widget->title = $opentrash_link.$emptytrash_link.( $tab3 == 'meta' ? T_('Meta comments') : T_('Feedback (Comments, Trackbacks...)') );
$block_item_Widget->disp_template_replaced( 'block_start' );

// Display filters title
echo $CommentList->get_filter_title( '<h3>', '</h3>', '<br />', NULL, 'htmlbody' );

$block_item_Widget->disp_template_replaced( 'block_end' );

// This block is used to keep correct css style for the comment status banners
echo '<div class="block_item evo_content_block">';

global $AdminUI;
$admin_template = $AdminUI->get_template( 'Results' );

$display_params = array(
		'header_start' => str_replace( 'class="', 'class="NavBar center ', $admin_template['header_start'] ),
		'footer_start' => str_replace( 'class="', 'class="NavBar center ', $admin_template['footer_start'] ),
	);

$CommentList->display_if_empty();

$CommentList->display_init( $display_params );

// Display navigation:
$CommentList->display_nav( 'header' );

load_funcs( 'comments/model/_comment_js.funcs.php' );

// Display list of comments:
echo '<a id="comments"></a>'; // Used to animate a moving the deleting comment to trash by ajax
// comments_container value is -1, because in this case we have to show all comments in current blog (Not just one item comments)
echo '<div id="comments_container" value="-1" class="evo_comments_container evo_comments_container__full_list">';
require dirname(__FILE__).'/_comment_list.inc.php';
echo '</div>';

// Display navigation:
$CommentList->display_nav( 'footer' );

echo '</div>'; // END OF <div class="evo_content_block">

?>