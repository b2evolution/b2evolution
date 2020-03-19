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

$Form = new Form( NULL, 'user_checkchanges' );

$Form->global_icon( T_('Cancel importing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Import users') );

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
		$edited_User = new User();
		$GroupCache = & get_GroupCache();
		$Form->select_object( 'grp_ID', $edited_User->grp_ID, $GroupCache, sprintf( T_('<span %s>Primary</span> user group'), 'class="label label-primary"' ) );

		$Form->buttons( array( array( 'submit', 'actionArray[import]', T_('Import'), 'SaveButton' ) ) );
	}

$Form->end_form();

?>