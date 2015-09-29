<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_currency.class.php', 'Currency' );

/**
 * @var Country
 */
global $edited_Country;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'country_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this country!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('country') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New country') : T_('Country') ).get_manual_link( 'countries-editing' ) );

	$Form->add_crumb( 'country' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ctry_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'ctry_code', $edited_Country->code, 2, T_('Code'), '', array( 'maxlength'=> 2, 'required'=>true ) );

	$Form->text_input( 'ctry_name', $edited_Country->name, 40, T_('Name'), '', array( 'maxlength'=> 40, 'required'=>true ) );

	$CurrencyCache = & get_CurrencyCache();

	$Form->select_input_object( 'ctry_curr_ID', $edited_Country->curr_ID, $CurrencyCache, T_('Default Currency'), array( 'allow_none' => true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>