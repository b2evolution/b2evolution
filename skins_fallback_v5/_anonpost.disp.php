<?php
/**
 * This is the template that loads the item/post form for anonymous user
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( $Blog->get_ajax_form_enabled() )
{	// Load form by AJAX if it is allowed by collection setting:
	display_ajax_form( array(
			'action' => 'get_item_form',
			'blog'   => $Blog->ID,
			'cat'    => get_param( 'cat' ),
		) );
}
else
{	// Display a form to create new post by anonymous user:
	skin_include( '_item_new_form.inc.php' );
}
?>