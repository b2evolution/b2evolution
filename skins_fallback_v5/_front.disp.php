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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$params = array_merge( array(
		'author_link_text'              => 'auto',
		'featured_intro_before'         => '',
		'featured_intro_after'          => '',
		'front_block_first_title_start' => '<h3>',
		'front_block_first_title_end'   => '</h3>',
		'front_block_title_start'       => '<h3>',
		'front_block_title_end'         => '</h3>',
	), $params );

// ------------------ "Front Page Main Area" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
skin_container( NT_('Front Page Main Area'), array(
		// The following params will be used as defaults for widgets included in this container:
		'author_link_text'        => $params['author_link_text'],
		'featured_intro_before'   => $params['featured_intro_before'],
		'featured_intro_after'    => $params['featured_intro_after'],
		'block_first_title_start' => $params['front_block_first_title_start'],
		'block_first_title_end'   => $params['front_block_first_title_end'],
		'block_title_start'       => $params['front_block_title_start'],
		'block_title_end'         => $params['front_block_title_end'],
	) );
// --------------------- END OF "Front Page Main Area" CONTAINER -----------------------

?>