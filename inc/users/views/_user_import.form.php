<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $Settings;

$Form = new Form( NULL, 'user_checkchanges' );

$Form->global_icon( TB_('Cancel importing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', TB_('Import users') );

	$Form->add_crumb( 'user' );
	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	// Display a panel to upload files before import:
	$import_files = display_importer_upload_panel( array(
			'folder'      => 'users',
			'help_slug'   => 'users-import',
			'refresh_url' => $admin_url.'?ctrl=user&amp;action=csv',
		) );

	if( ! empty( $import_files ) )
	{
		$GroupCache = & get_GroupCache();
		$Form->select_object( 'grp_ID', param( 'grp_ID', 'integer', $Settings->get( 'newusers_grp_ID' ) ), $GroupCache, sprintf( TB_('<span %s>Primary</span> user group'), 'class="label label-primary"' ) );

		$Form->radio( 'on_duplicate_login', param( 'on_duplicate_login', 'integer', 1 ), array(
				array( 1, TB_('Update existing user') ),
				array( 0, TB_('Ignore the user from the CSV file') )
			), T_('On duplicate login'), false, '' );

		$Form->radio( 'on_duplicate_email', param( 'on_duplicate_email', 'integer', 1 ), array(
				array( 1, TB_('Update existing user') ),
				array( 0, TB_('Ignore the user from the CSV file') )
			), T_('On duplicate email'), false, '' );
		
		$Form->buttons( array( array( 'submit', 'actionArray[import]', T_('Import'), 'SaveButton' ) ) );
	}

$Form->end_form();

?>