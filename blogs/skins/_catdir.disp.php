<?php
/**
 * This is the template that displays the category directory for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=catdir
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Call the Categories plugin:
$Plugins->call_by_code( 'evo_Cats', array(
			// 'block_title_start' => '<h2>',
			// 'block_title_end'   => '</h2>',
			// 'title'             => T_('Categories'),
			'title'             => '',	// Title already displayed by request_title()
		  'block_start'       => '',
		  'block_end'         => ''
		)
	);

/*
 * $Log$
 * Revision 1.1  2007/03/04 21:42:49  fplanque
 * category directory / albums
 *
 */
?>