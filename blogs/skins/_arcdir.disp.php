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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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


/*
 * $Log$
 * Revision 1.2  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/11/29 19:29:22  fplanque
 * normalized skin filenames
 *
 * Revision 1.13  2007/04/26 00:11:03  fplanque
 * (c) 2007
 *
 * Revision 1.12  2007/03/18 01:39:55  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.11  2006/12/14 21:56:25  fplanque
 * minor optimization
 *
 * Revision 1.10  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.9  2006/04/11 22:28:58  blueyed
 * cleanup
 *
 * Revision 1.8  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>