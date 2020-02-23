<?php
/**
 * This file displays the Form to mass change main category or add extra categories
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Form = new Form( $admin_url, 'item_mass_cats_checkchanges', 'post' );

$Form->begin_form();

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'action', 'mass_change_cat' );
	$Form->hidden( 'blog', $blog );
	foreach( $selected_items as $item_ID )
	{
		$Form->hidden( 'selected_items[]', $item_ID );
	}
	$Form->hidden( 'cat_type', $cat_type );
	$Form->hidden( 'redirect_to', $redirect_to );
	$Form->add_crumb( 'items' );

	cat_select( $Form, true, true, array(
			'display_main'  => ( $cat_type == 'main' ),
			'display_extra' => ( $cat_type == 'extra' || $cat_type == 'remove_extra' ),
			'display_order' => false,
			'display_new'   => false,
		) );

	$Form->buttons( array( array( 'submit', 'actionArray[mass_change_cat]', T_('Change main category'), 'SaveButton' ) ) );

$Form->end_form();
?>
