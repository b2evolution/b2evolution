<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
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

$Form->global_icon( TB_('Delete this region!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('region') ) );
$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ?  TB_('New region') : TB_('Region') ).get_manual_link( 'regions-editing' ) );

	$Form->add_crumb( 'region' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',rgn_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'rgn_ctry_ID', $edited_Region->ctry_ID, $CountryCache, TB_('Country'), array( 'allow_none' => true, 'required' => true ) );

	$Form->text_input( 'rgn_code', $edited_Region->code, 6, TB_('Code'), '', array( 'maxlength'=> 6, 'required'=>true ) );

	$Form->text_input( 'rgn_name', $edited_Region->name, 40, TB_('Name'), '', array( 'maxlength'=> 40, 'required'=>true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', TB_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', TB_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', TB_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>