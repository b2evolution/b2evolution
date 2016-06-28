<?php
/**
 * This is the template that displays the front page of a collection (when front page enabled)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in a *.main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
	echo '<div class="evo_content_block">'; // Beginning of posts display
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

// ------------------------- "Front Page Main Area" CONTAINER EMBEDDED HERE --------------------------
	// Display container and contents:
	skin_container( NT_('Front Page Main Area'), array(
	// The following params will be used as defaults for widgets included in this container:
		'block_start'       => '<div class="evo_widget $wi_class$">',
		'block_end'         => '</div>',
		'block_title_start' => '<h2 class="page-header">',
		'block_title_end'   => '</h2>',
	) );
// ----------------------------- END OF "Front Page Main Area" CONTAINER -----------------------------

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
		'item_before_post'     => get_icon( 'file_message' ),
	) );
// ---------------------------------- END OF CONTENT HIERARCHY ---------------------------------

if( ! empty( $intro_Item ) )
{
	global $c, $ReqURI;
	$c = 1; // Display comments
	echo '<div class="evo_content_block">'; // Beginning of posts display
	// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php', array_merge( array(
			'before_section_title' => '<h3 class="evo_comment__list_title">',
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