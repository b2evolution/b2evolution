<?php
/**
 * This file displays the Form to mass change Item text renderers
 * 
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Plugins;

$Form = new Form( $admin_url, 'item_mass_renderer_checkchanges', 'post' );

$Form->begin_form();

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'action', 'mass_change_renderer' );
	$Form->hidden( 'blog', $blog );
	foreach( $selected_items as $item_ID )
	{
		$Form->hidden( 'selected_items[]', $item_ID );
	}
	$Form->hidden( 'renderer_change_type', $renderer_change_type );
	$Form->hidden( 'redirect_to', $redirect_to );
	$Form->add_crumb( 'items' );

	echo $Plugins->get_renderer_checkboxes( NULL, array(
			'Blog' => $Blog,
			'setting_name' => 'coll_apply_rendering',
			'ignored_apply_rendering' => array( 'always', 'stealth', 'never' )
		) );

	$Form->buttons( array( array( 'submit', 'actionArray[mass_change_renderer]', T_('Change renderer'), 'SaveButton' ) ) );

$Form->end_form();
?>
