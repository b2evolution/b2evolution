<?php
/**
 * This is the template that displays the page to compare several posts
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in a *.main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// ------------------ "Compare Main Area" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
widget_container( 'compare_main_area', array(
		// The following params will be used as defaults for widgets included in this container:
		'container_display_if_empty' => false, // If no widget, don't display container at all
		'block_start' => '<div class="evo_widget $wi_class$">',
		'block_end'   => '</div>',
	) );
// --------------------- END OF "Compare Main Area" CONTAINER -----------------------

?>