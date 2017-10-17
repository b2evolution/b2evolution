<?php
/**
 * This is the template that displays a widget page in a collection
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
display_if_empty();

if( $Item = & mainlist_get_item() )
{	// If Item is found for current filter request (for item type usage 'widget-page'):
	$widget_params = array(
			'widget_context' => 'item',	// Signal that we are displaying within an Item
			// The following (optional) params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			// Restrict Page Container with these item ID and item type ID:
			'container_item_ID' => $Item->ID,
			'container_ityp_ID' => $Item->get_type_setting( 'ID' ),
			// This will enclose each widget in a block:
			'block_start' => '<div class="evo_widget $wi_class$">',
			'block_end' => '</div>',
			// This will enclose the title of each widget:
			'block_title_start' => '<h3>',
			'block_title_end' => '</h3>',
			// Template params for "Item Link" widget
			'widget_item_link_before'    => '<p class="evo_post_link">',
			'widget_item_link_after'     => '</p>',
			// Template params for "Item Tags" widget
			'widget_item_tags_before'    => '<nav class="small post_tags">'.T_('Tags').': ',
			'widget_item_tags_after'     => '</nav>',
			// Params for skin file "_item_content.inc.php"
			'widget_item_content_params' => $params,
			// Template params for "Item Attachments" widget:
			'widget_item_attachments_params' => array(
					'limit_attach'       => 1000,
					'before'             => '<div class="evo_post_attachments"><h3>'.T_('Attachments').':</h3><ul class="evo_files">',
					'after'              => '</ul></div>',
					'before_attach'      => '<li class="evo_file">',
					'after_attach'       => '</li>',
					'before_attach_size' => ' <span class="evo_file_size">(',
					'after_attach_size'  => ')</span>',
				),
		);

	// ------------------------- "Widget Page Section 1" CONTAINER EMBEDDED HERE --------------------------
	widget_container( 'widget_page_section_1', $widget_params );
	// ----------------------------- END OF "Widget Page Section 1" CONTAINER -----------------------------

	// ------------------------- "Widget Page Section 2" CONTAINER EMBEDDED HERE --------------------------
	widget_container( 'widget_page_section_2', $widget_params );
	// ----------------------------- END OF "Widget Page Section 2" CONTAINER -----------------------------

	// ------------------------- "Widget Page Section 3" CONTAINER EMBEDDED HERE --------------------------
	widget_container( 'widget_page_section_3', $widget_params );
	// ----------------------------- END OF "Widget Page Section 3" CONTAINER -----------------------------
}
?>