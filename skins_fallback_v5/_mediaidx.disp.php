<?php
/**
 * This is the template that displays the media index for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=arcdir
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// ------------------------- "Photo Index" CONTAINER EMBEDDED HERE --------------------------
skin_container( NT_('Photo Index'), array(
	// The following params will be used as defaults for widgets included in this container:
	// This will enclose each widget in a block:
	'block_start'    => '<div class="evo_widget $wi_class$">',
	'block_end'      => '</div>',
	'grid_start'     => '<div class="image_index">',
	'grid_end'       => '</div>',
	'grid_colstart'  => '',
	'grid_colend'    => '',
	'grid_cellstart' => '<div><span>',
	'grid_cellend'   => '</span></div>',
) );
// ----------------------------- END OF "Photo Index" CONTAINER -----------------------------

?>