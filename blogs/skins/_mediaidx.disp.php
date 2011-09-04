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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF MEDIA INDEX --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_media_index',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'thumb_size' => 'fit-80x80',
		'thumb_layout' => 'grid',
		'grid_start' => '<table class="image_index" cellspacing="3">',
		'grid_end' => '</table>',
		'grid_nb_cols' => 8,
		'grid_colstart' => '<tr>',
		'grid_colend' => '</tr>',
		'grid_cellstart' => '<td>',
		'grid_cellend' => '</td>',
		'order_by' => $Blog->get_setting('orderby'),
		'order_dir' => $Blog->get_setting('orderdir'),
		'limit' => 1000,
	) );
// ---------------------------------- END OF MEDIA INDEX ---------------------------------


/*
 * $Log$
 * Revision 1.8  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.7  2010/02/08 17:56:12  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.6  2009/03/08 23:57:56  fplanque
 * 2009
 *
 * Revision 1.5  2008/09/24 08:47:12  fplanque
 * enhanced mediaidx widget
 *
 * Revision 1.4  2008/09/23 09:04:33  fplanque
 * moved media index to a widget
 *
 * Revision 1.3  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/12/23 20:10:49  fplanque
 * removed suspects
 *
 * Revision 1.1  2007/11/25 19:45:26  fplanque
 * cleaned up photo/media index a little bit
 *
 * Revision 1.10  2007/05/14 02:43:07  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.9  2007/04/26 00:11:03  fplanque
 * (c) 2007
 *
 * Revision 1.8  2007/03/18 01:39:57  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.7  2007/03/11 20:39:44  fplanque
 * little fix
 *
 * Revision 1.6  2007/01/23 09:25:39  fplanque
 * Configurable sort order.
 *
 * Revision 1.5  2007/01/23 03:46:24  fplanque
 * cleaned up presentation
 *
 * Revision 1.4  2007/01/15 20:48:19  fplanque
 * constrained photoblog image size
 * TODO: sharpness issue
 *
 * Revision 1.3  2006/12/14 23:02:28  fplanque
 * the unbelievable hack :P
 *
 * Revision 1.1  2006/12/14 22:29:37  fplanque
 * thumbnail archives proof of concept
 *
 */
?>
