<?php
/**
 * This is the template that displays the help screen for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=help
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// TODO: add more help stuff here
// TODO: maked editable through admin (special post type?) and use the contents below as default only

echo '<h2>'.T_('Content issues').'</h2>';

echo '<p>'.sprintf( T_('In case of concerns with the contents of this blog/site, please <a %s>contact the owner of this blog</a>.'), 'href="'.$Blog->get_contact_url( true ).'"' ).'</p>';

echo '<p><a href="'.get_manual_url( 'content-issues' ).'">'.T_('What to do if the owner doesn\'t respond').' &raquo;</a></p>';

?>