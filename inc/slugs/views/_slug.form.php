<?php
/**
 * This file display the slugs form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Slug
 */
global $edited_Slug;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'slug_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New Slug') . get_manual_link('slug-form') : T_('Slug') . get_manual_link('slug-form') );

	$Form->add_crumb( 'slug' );
	$Form->hidden( 'action',  $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',slug_ID' : '' ) ) );

	$Form->text_input( 'slug_title', $edited_Slug->get( 'title' ), 50, T_('Slug'), '', array( 'maxlength'=> 255, 'required'=>true ) );

	$Form->radio_input( 'slug_type', $creating ? 'item' : $edited_Slug->get( 'type' ), array( 
						array( 'value' => 'item', 'label' => T_( 'Item' ) ),
						array( 'value' => 'help', 'label' => T_( 'Help' ) ) ),
						T_('Type'), array( 'lines' => 1 ) );

	$Form->text_input( 'slug_object_ID', $edited_Slug->get( 'itm_ID' ), 50, T_('Object ID'), '', array( 'maxlength'=> 11, 'required'=>false ) );

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );

?>
