<?php
/**
 * This is the main/default page template for the "manual" skin.
 *
 * This skin only uses one single template which includes most of its features.
 * It will also rely on default includes for specific dispays (like the comment form).
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// --------------------------------- START OF CONTENT HIERARCHY --------------------------------
echo '<h2 class="table_contents">'.T_('Table of contents').'</h2>';
skin_widget( array(
		// CODE for the widget:
		'widget' => 'content_hierarchy',
		// Optional display params
		'display_blog_title'   => false,
		'open_children_levels' => 20,
		'class_selected'       => '',
		'item_before_opened'   => get_icon( 'collapse' ),
		'item_before_closed'   => get_icon( 'expand' ),
		'item_before_post'     => get_icon( 'post' ),
	) );
// ---------------------------------- END OF CONTENT HIERARCHY ---------------------------------
?>