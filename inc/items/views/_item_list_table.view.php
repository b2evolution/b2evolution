<?php
/**
 * This file implements the post browsing in tracker mode
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;
/**
 * @var ItemList2
 */
global $ItemList;

global $edit_item_url, $delete_item_url;
global $tab, $tab_type;
global $Session;

if( $highlight = param( 'highlight', 'integer', NULL ) )
{	// There are lines we want to highlight:
	$result_fadeout = array( 'post_ID' => array($highlight) );

}
elseif ( $highlight = $Session->get( 'highlight_id' ) )
{
	$result_fadeout = array( 'post_ID' => array($highlight) );
	$Session->delete( 'highlight_id' );
}
else
{	// Nothing to highlight
	$result_fadeout = NULL;
}


// Display title depending on selection params:
echo $ItemList->get_filter_title( '<h2 class="page-title">', '</h2>', '<br />', NULL, 'htmlbody' );


/*
	**
	 * Callback to add filters on top of the result set
	 *
	function filter_on_post_title( & $Form )
	{
		global $pagenow, $post_filter;

		$Form->hidden( 'filter_on_post_title', 1 );
		$Form->text( 'post_filter', $post_filter, 20, T_('Task title'), '', 60 );
	}
	$ItemList->filters_callback = 'filter_on_post_title';
*/


$ItemList->title = sprintf( /* TRANS: list of "posts"/"intros"/"custom types"/etc */ T_('"%s" list'), $tab_type );

// Initialize Results object
items_results( $ItemList, array(
		'tab' => $tab,
	) );

if( $ItemList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$ItemList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
{	// We have permission to add a post with at least one status:
	$selected_tab = ( $tab == 'type' ) ? strtolower( $tab_type ) : $tab;
	switch( $selected_tab )
	{
		case 'pages':
			$label = T_('New page');
			$title = T_('Create a new page...');
			$new_ityp_ID = 1000;
			$perm = 'page';
			break;

		case 'intros':
			$label = T_('New intro');
			$title = T_('Write a new intro text...');
			$new_ityp_ID = 1600;
			$perm = 'intro';
			break;

		case 'podcasts':
			$label = T_('New episode');
			$title = T_('Package a new podcast episode...');
			$new_ityp_ID = 2000;
			$perm = 'podcast';
			break;

		case 'links':
			$label = T_('New link');
			$title = T_('Add a sidebar link...');
			$new_ityp_ID = 3000;
			$perm = 'sidebar';
			break;

		case 'ads':
			$label = T_('New advertisement');
			$title = T_('Add an advertisement...');
			$new_ityp_ID = 4000;
			$perm = 'sidebar';
			break;

		default:
			$label = T_('New post');
			$title = T_('Write a new post...');
			$new_ityp_ID = 1;
			$perm = ''; // No need to check

			$ItemList->global_icon( T_( 'Create multiple posts...' ), 'new', '?ctrl=items&amp;action=new_mass&amp;blog='.$Blog->ID.'&amp;item_typ_ID='.$new_ityp_ID, T_( 'Mass create' ).' &raquo;', 3, 4 );

			break;
	}

	if( empty( $perm ) || $current_User->check_perm( 'blog_'.$perm, 'edit', false, $Blog->ID ) )
	{	// We have the permission to create and edit posts with this post type:
		$ItemList->global_icon( T_('Mass edit the current post list...'), 'edit', '?ctrl=items&amp;action=mass_edit&amp;filter=restore&amp;blog='.$Blog->ID.'&amp;redirect_to='.regenerate_url( 'action', '', '', '&'), T_('Mass edit').' &raquo;', 3, 4 );
		$ItemList->global_icon( $title, 'new', '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'&amp;item_typ_ID='.$new_ityp_ID, $label.' &raquo;', 3, 4 );
	}
}


// EXECUTE the query now:
$ItemList->restart();

// Initialize funky display vars now:
global $postIDlist, $postIDarray;
$postIDlist = $ItemList->get_page_ID_list();
$postIDarray = $ItemList->get_page_ID_array();

// DISPLAY table now:
$ItemList->display( NULL, $result_fadeout );

?>