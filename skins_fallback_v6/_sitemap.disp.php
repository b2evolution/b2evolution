<?php
/**
 * This is the template that displays the site map (the real one, not the XML thing) for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=postidx
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: this is a very imperfect sitemap, but it's a start :)

// ------------------------- "Sitemap" CONTAINER EMBEDDED HERE --------------------------
// Display container and contents:
widget_container( 'sitemap', array(
	// The following params will be used as defaults for widgets included in this container:
	'container_display_if_empty' => false, // If no widget, don't display container at all
	'container_start' => '<div class="evo_container $wico_class$">',
	'container_end'   => '</div>',
	'block_start'     => '<div class="evo_widget $wi_class$">',
	'block_end'       => '</div>',
) );
// ----------------------------- END OF "Sitemap" CONTAINER -----------------------------
?>