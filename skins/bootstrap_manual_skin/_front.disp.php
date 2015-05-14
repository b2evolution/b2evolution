<?php
/**
 * This is the template that displays the front page of a collection (when front page enabled)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in a *.main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Home page, display full categories list

// Go Grab the featured post:
$intro_Item = & get_featured_Item( 'front' ); // $intro_Item is used below for comments form
$Item = $intro_Item;
if( !empty( $Item ) )
{ // We have a featured/intro post to display:
	echo '<div id="styled_content_block">'; // Beginning of posts display TODO: get rid of this ID, use class .evo_content_block instead
	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array(
			'feature_block'     => true,
			'content_mode'      => 'auto',		// 'auto' will auto select depending on $disp-detail
			'intro_mode'        => 'normal',	// Intro posts will be displayed in normal mode
			'item_class'        => 'jumbotron evo_content_block evo_post',
			'disp_comment_form' => false,
			'item_link_type'    => 'none',
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------
	echo '</div>'; // End of posts display
}

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

if( ! empty( $intro_Item ) )
{
	global $c, $ReqURI;
	$c = 1; // Display comments
	echo '<div id="styled_content_block">'; // Beginning of posts display TODO: get rid of this ID, use class .evo_content_block instead
	// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php', array_merge( array(
			'before_section_title' => '<h3 class="comments_list_title">',
			'after_section_title'  => '</h3>',
			'Item'                 => $intro_Item,
			'form_title_text'      => T_('Comment form'),
			'comments_title_text'  => T_('Comments on this chapter'),
			'form_comment_redirect_to' => $ReqURI,
		), $Skin->get_template( 'disp_params' ) ) );
	// Note: You can customize the default item feedback by copying the generic
	// /skins/_item_feedback.inc.php file into the current skin folder.
	// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	echo '</div>'; // End of posts display
}

?>