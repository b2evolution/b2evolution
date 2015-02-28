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

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * @var Region
 */
global $edited_Region;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'region_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this region!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('region') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New region') : T_('Region') );

	$Form->add_crumb( 'region' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',rgn_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'rgn_ctry_ID', $edited_Region->ctry_ID, $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );

	$Form->text_input( 'rgn_code', $edited_Region->code, 6, T_('Code'), '', array( 'maxlength'=> 6, 'required'=>true ) );

	$Form->text_input( 'rgn_name', $edited_Region->name, 40, T_('Name'), '', array( 'maxlength'=> 40, 'required'=>true ) );

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