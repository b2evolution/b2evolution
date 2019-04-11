<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2019 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

$Form = new Form( NULL, 'subregion_checkchanges', 'post', 'compact', 'multipart/form-data' );

$Form->global_icon( T_('Cancel importing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Import sub-regions').get_manual_link( 'subregions-import' ) );

	echo T_('Select a country and upload a CSV file with the following columns:');
	echo '<div style="padding:10px 0 10px 40px">';
	echo '1. '.T_('Region code').'<br />';
	echo '2. '.T_('Sub-region code').'<br />';
	echo '3. '.T_('Sub-region name').'<br />';
	echo '</div>';

	$Form->add_crumb( 'subregion' );
	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'ctry_ID', get_param( 'ctry_ID' ), $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );

	$Form->input_field( array( 'label' => T_('CSV File'), 'name' => 'csv', 'type' => 'file', 'required' => false ) );

$Form->end_form( array( array( 'submit', 'actionArray[import]', T_('Import'), 'SaveButton' ) ) );

?>