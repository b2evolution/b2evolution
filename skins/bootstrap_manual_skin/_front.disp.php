<?php
/**
 * This is the template that displays the front page of a collection (when front page enabled)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in a *.main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Home page, display full categories list

// ------------------------- "Front Page Main Area" CONTAINER EMBEDDED HERE --------------------------
	// Display container and contents:
	skin_container( NT_('Front Page Main Area'), array_merge( array(
		// The following params will be used as defaults for widgets included in this container:
		'block_start'       => '<div class="evo_widget $wi_class$">',
		'block_end'         => '</div>',
		'block_title_start' => '<h2 class="page-header">',
		'block_title_end'   => '</h2>',
		'intro_class'       => 'jumbotron',
		'featured_class'    => 'featurepost',

		// Template params for "Content Hierarchy" widget:
		'widget_content_hierarchy_params' => array(
				'class_selected'       => '',
				'custom_title'         => '<h2 class="table_contents">'.T_('Table of contents').'</h2>',
				'item_before_opened'   => get_icon( 'collapse' ),
				'item_before_closed'   => get_icon( 'expand' ),
				'item_before_post'     => get_icon( 'file_message' ),
			),
	), $Skin->get_template( 'disp_params' ) ) );
// ----------------------------- END OF "Front Page Main Area" CONTAINER -----------------------------

$intro_Item = & get_featured_Item( 'front' );
if( ! empty( $intro_Item ) )
{
	global $c, $ReqURI;
	$c = 1; // Display comments
	echo '<div class="evo_content_block">'; // Beginning of posts display
	// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
	skin_include( '_item_feedback.inc.php', array_merge( array(
			'disp_comments'        => true,
			'disp_comment_form'    => true,
			'disp_trackbacks'      => false,
			'disp_trackback_url'   => false,
			'disp_pingbacks'       => false,
			'disp_webmentions'     => false,
			'disp_meta_comments'   => false,
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