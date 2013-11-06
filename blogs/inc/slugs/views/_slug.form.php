<?php
/**
 * This file display the slugs form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author evfy-asimo: Attila Simo.
 *
 * @version $Id$
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

$Form->begin_form( 'fform', $creating ?  T_('New Slug') : T_('Slug') );

	$Form->add_crumb( 'slug' );
	$Form->hidden( 'action',  $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',slug_ID' : '' ) ) );

	$Form->text_input( 'slug_title', $edited_Slug->get( 'title' ), 50, T_('Slug'), '', array( 'maxlength'=> 255, 'required'=>true ) );

	$Form->radio_input( 'slug_type', $creating ? 'item' : $edited_Slug->get( 'type' ), array( 
						array( 'value' => 'item', 'label' => T_( 'Item' ) ),
						array( 'value' => 'help', 'label' => T_( 'Help' ) ) ),
						T_('Type'), array( 'lines' => 1 ) );

	$Form->text_input( 'slug_object_ID', $edited_Slug->get( 'itm_ID' ), 50, T_('Object ID'), '', array( 'maxlength'=> 11, 'required'=>false ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
							array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
							array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


/*
 * $Log$
 * Revision 1.5  2013/11/06 08:04:54  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>