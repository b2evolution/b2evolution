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
 * @subpackage bootstrap_forums_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$params = array_merge( array(
		'item_new_submit_text' => T_('Create topic'),
	), $params );

// Require new item form from v5 skins with overwritten v6 params above:
require skin_fallback_path( '_item_new_form.inc.php' );
?>