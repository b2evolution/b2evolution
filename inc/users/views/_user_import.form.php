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
		
		$selected_group = ( get_param( 'grp_ID' ) > 0 )? get_param( 'grp_ID' ) : $edited_User->grp_ID;
		$Form->select_object( 'grp_ID', $selected_group, $GroupCache, sprintf( T_('<span %s>Primary</span> user group'), 'class="label label-primary"' ) );
		
		$selected_duplicate_login = ( is_numeric( get_param( 'on_duplicate_login' ) ))? get_param( 'on_duplicate_login' ) : 1;
		$Form->radio( 'on_duplicate_login', $selected_duplicate_login, array(
				array( 1, T_('Update existing user') ),
				array( 0, T_('Ignore the user from the CSV file') )
			), T_('On duplicate login'), false, '' );
		
		$selected_duplicate_email = ( is_numeric( get_param( 'on_duplicate_email' ) ))? get_param( 'on_duplicate_email' ) : 1;
		$Form->radio( 'on_duplicate_email', $selected_duplicate_email, array(
				array( 1, T_('Update existing user') ),
				array( 0, T_('Ignore the user from the CSV file') )
			), T_('On duplicate email'), false, '' );
		
		$Form->buttons( array( array( 'submit', 'actionArray[import]', T_('Import'), 'SaveButton' ) ) );
	}

$Form->end_form();

?>