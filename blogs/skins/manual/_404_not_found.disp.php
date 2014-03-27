<?php
/**
 * This is the template that displays the 404 disp content
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $requested_404_title;

echo '<div class="error_404">';

echo '<h1>404 Not Found</h1>';

echo '<p>'.T_('The manual page you are requesting doesn\'t seem to exist (yet).').'</p>';

$post_title = '';
$post_urltitle = '';
if( !empty( $requested_404_title ) )
{	// Set title & urltitle for new post
	$post_title = str_replace( ' ', '%20', ucwords( str_replace( '-', ' ', $requested_404_title ) ) );
	$post_urltitle = $requested_404_title;
}

// Button to create a new page
$write_new_post_url = $Blog->get_write_item_url( 0, $post_title, $post_urltitle );
if( !empty( $write_new_post_url ) )
{	// Display button to write a new post
	echo '<a href="'.$write_new_post_url.'" class="roundbutton roundbutton_text_noicon">'.T_('Create this page now').'</a>';
}

echo '<p>'.T_('You can search the manual below.').'</p>';

echo '</div>';

if( !empty( $requested_404_title ) )
{	// Initialize a prefilled search form
	set_param( 's', str_replace( '-', ' ', $requested_404_title ) );
	set_param( 'sentence', 'OR' );
	set_param( 'title', '' ); // Empty this param to exclude a filter by post_urltitle

	// Init the MainList object:
	init_MainList( $Blog->get_setting('posts_per_page') );

	skin_include( '_search.disp.php' );
}
else
{	// Display a search form with TOC
	echo '<div class="error_additional_content">';
	// --------------------------------- START OF SEARCH FORM --------------------------------
	// Call the coll_search_form widget:
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'coll_search_form',
			// Optional display params:
			'block_start' => '',
			'block_end' => '',
			'title' => T_('Search this manual:'),
			'disp_search_options' => true,
			'block_title_start' => '<h3>',
			'block_title_end' => '</h3>',
		) );
	// ---------------------------------- END OF SEARCH FORM ---------------------------------

	echo '<p>'.T_('or you can browse the table of contents below:').'</p>';

	echo '<h2 class="table_contents">'.T_('Table of contents').'</h2>';
	$Skin->display_chapters( array(
			'display_blog_title' => false,
			'display_children'   => true,
			'class_selected'     => ''
		) );

	echo '</div>';
}
?>