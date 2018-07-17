<?php
/**
 * This is the template that displays the item/post form for anonymous user
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


$params = array_merge( array(
		'item_new_form_start' => '<div class="evo_item_new_form panel panel-default">'
															.'<div class="panel-heading">'
																.'<h3 class="panel-title">'
																	.sprintf( T_('New [%s]'), $Blog->get_default_item_type_name() )
																.'</h3>'
															.'</div>'
															.'<div class="panel-body">',
		'item_new_form_end'   => '</div></div>',
	), $params );

// Require new item form from v5 skins with overwritten v6 params above:
require skin_fallback_path( '_item_new_form.inc.php', 5 );
?>