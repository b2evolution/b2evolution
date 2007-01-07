<?php
/**
 * This file implements the UI controller for skins management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check permission to display:
$current_User->check_perm( 'options', 'view', true );

param( 'action', 'string', 'list' );

if( param( 'skin_ID', 'integer', '', true) )
{// Load file type:
	$SkinCache = & get_Cache( 'SkinCache' );
	if( ($edited_Skin = & $SkinCache->get_by_ID( $skin_ID, false )) === false )
	{	// We could not find the skin to edit:
		unset( $edited_Skin );
		forget_param( 'skin_ID' );
		$Messages->head = T_('Cannot edit skin!');
		$Messages->add( T_('Requested is not installed any longer.'), 'error' );
		$action = 'nil';
	}
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'create':
		param( 'skin_folder', 'string', true );

		// Check permission to edit:
		$current_User->check_perm( 'options', 'edit', true );

		// CREATE NEW SKIN:
		load_class( 'MODEL/skins/_skin.class.php' );
	 	$edited_Skin = & new Skin();

		$edited_Skin->set( 'name', $skin_folder );
		$edited_Skin->set( 'folder', $skin_folder );
		$edited_Skin->set( 'type', substr($skin_folder,0,1) == '_' ? 'feed' : 'normal' );

		// INSERT NEW SKIN INTO DB:
		$edited_Skin->dbinsert();

		$Messages->add( T_('Skin has been installed.'), 'success' );

		// We want to highlight the edited object on next list display:
 		$Session->set( 'fadeout_array', array( 'skin_ID' => array($edited_Skin->ID) ) );

		// PREVENT RELOAD & Switch to list mode:
		header_redirect( '?ctrl=skins' );
		break;


	case 'update':
		// Update skin properties:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an skin_ID:
		param( 'skin_ID', 'integer', true );

		// load data from request
		if( $edited_Skin->load_from_Request() )
		{	// We could load data from form without errors:
			// Update in DB:
			$edited_Skin->dbupdate();
			$Messages->add( T_('Skin properties updated.'), 'success' );

			// We want to highlight the edited object on next list display:
 			$Session->set( 'fadeout_array', array( 'skin_ID' => array($edited_Skin->ID) ) );

			$action = 'list';
		}
		break;


	case 'delete':
		// Uninstall a skin:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an skin_ID:
		param( 'skin_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Skin &laquo;%s&raquo; uninstalled.'), $edited_Skin->dget('name') );
			$edited_Skin->dbdelete( true );
			unset( $edited_Skin );
			forget_param( 'skin_ID' );
			$Messages->add( $msg, 'success' );

			// PREVENT RELOAD & Switch to list mode:
			header_redirect( '?ctrl=skins' );
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Skin->check_delete( sprintf( T_('Cannot uninstall skin &laquo;%s&raquo;'), $edited_Skin->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}

		break;
}


$AdminUI->set_path( 'options', 'skins' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

/**
 * Display Payload:
 */
switch( $action )
{
	case 'new':
		// Display VIEW:
		$AdminUI->disp_view( 'skins/_available_list.view.php' );
		break;

	case 'edit':
	case 'update':	// we return in this state after a validation error
		// Display VIEW:
		$AdminUI->disp_view( 'skins/_skin.form.php' );
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_Skin->confirm_delete(
				sprintf( T_('Uninstall skin &laquo;%s&raquo;?'),  $edited_Skin->dget( 'name' ) ),
				$action, get_memorized( 'action' ) );
	case 'list':
		// Display VIEW:
		$AdminUI->disp_view( 'skins/_skin_list.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.3  2007/01/07 05:32:12  fplanque
 * added some more DB skin handling (install+uninstall+edit properties ok)
 * still useless though :P
 * next step: discover containers in installed skins
 *
 * Revision 1.2  2006/12/29 01:10:06  fplanque
 * basic skin registering
 *
 * Revision 1.1  2006/12/05 05:41:42  fplanque
 * created playground for skin management
 *
 */
?>