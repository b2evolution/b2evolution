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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Collection, $Blog;
// TODO: add more help stuff here
// TODO: maked editable through admin (special post type?) and use the contents below as default only

if( isset( $Blog ) && $Blog->get_setting( 'allow_access' ) != 'public' )
{ // Display the following only if collection is not public access:

	echo '<h2>'.T_('This is a private website or section').'</h2>';

	echo '<p>'.T_('You need special permissions to access the contents of this website/section.').'</p>';

	echo '<p>'.sprintf( T_('If you need assistance, please <a %s>contact the owner of this website/section</a>.'), 'href="'.$Blog->get_contact_url().'"'.( $Blog->get_setting( 'msgform_nofollowto' ) ? ' rel="nofollow"' : '' ) ).'</p>';
}

// ------------------ "Help" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
widget_container( 'help', array(
		// The following params will be used as defaults for widgets included in this container:
		'container_display_if_empty' => false, // If no widget, don't display container at all
		'block_start'       => '<div class="evo_widget $wi_class$">',
		'block_end'         => '</div>',
		'block_title_start' => '<h2>',
		'block_title_end'   => '</h2>',
	) );
// --------------------- END OF "Help" CONTAINER -----------------------


echo '<h2>'.T_('Resolving remaining issues').'</h2>';

echo '<p>'.T_('This is an independent website published solely under the reponsibility of its owner.').'</p>';

echo '<p>'.sprintf( T_('In case of concerns with the contents of this website/section, please <a %s>contact the owner of this website/section</a>.'), 'href="'.$Blog->get_contact_url().'"'.( $Blog->get_setting( 'msgform_nofollowto' ) ? ' rel="nofollow"' : '' ) ).'</p>';

echo '<p><a href="'.get_manual_url( 'content-issues' ).'">'.T_('What to do if the owner doesn\'t respond').' &raquo;</a></p>';

?>