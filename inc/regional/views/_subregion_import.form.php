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

global $admin_url;

load_class( 'regional/model/_country.class.php', 'Country' );

$Form = new Form( NULL, 'subregion_checkchanges' );

$Form->global_icon( T_('Cancel importing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Import sub-regions') );

	$Form->add_crumb( 'subregion' );
	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	// Display a panel to upload files before import:
	$import_files = display_importer_upload_panel( array(
			'folder'      => 'sub-regions',
			'help_slug'   => 'subregions-import',
			'refresh_url' => $admin_url.'?ctrl=subregions&amp;action=csv',
		) );

	if( ! empty( $import_files ) )
	{
		$CountryCache = & get_CountryCache();
		$Form->select_country( 'ctry_ID', get_param( 'ctry_ID' ), $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );

		$Form->checkbox( 'auto_create_regions', get_param( 'auto_create_regions' ), T_('Auto-create regions'), T_('When an imported sub-region refers to a region code that doesn\'t exist yet, automatically create that new region.') );

		$Form->buttons( array( array( 'submit', 'actionArray[import]', T_('Import'), 'SaveButton' ) ) );
	}

$Form->end_form();
?>