<?php
/**
 * This is the template that displays the archive directory for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=arcdir
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Call the Archives plugin WITH NO LIMIT & NO MORE LINK:
$Plugins->call_by_code( 'evo_Arch', array( 'title'=>'',
	                                          'block_start'=>'',
	                                          'block_end'=>'',
	                                          'limit'=>'',
	                                          'more_link'=>'' ) );

?>