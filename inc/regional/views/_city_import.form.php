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

$Form = new Form( NULL, 'city_checkchanges', 'post', 'compact', 'multipart/form-data' );

$Form->global_icon( T_('Cancel importing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Import cities') );

	echo T_('Select a country and upload a CSV file with the following columns:');
	echo '<div style="padding:10px 0 10px 40px">';
	echo T_('1. Postcode').'<br />';
	echo T_('2. City name').'<br />';
	echo T_('3. Optional: sub region code');
	echo '</div>';

	$Form->add_crumb( 'city' );
	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'ctry_ID', get_param( 'ctry_ID' ), $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );

	$Form->input_field( array( 'label' => T_('CSV File'), 'name' => 'csv', 'type' => 'file', 'required' => true ) );

$Form->end_form( array( array( 'submit', 'actionArray[import]', T_('Import'), 'SaveButton' ) ) );

?>