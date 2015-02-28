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

// Check minimum permission:
$current_User->check_perm( 'users', 'view', true );

$AdminUI->set_path( 'users', 'usersettings', 'accountclose' );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'accountclose' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// UPDATE the account closing settings:
		param( 'account_close_enabled', 'integer', 0 );
		param( 'account_close_intro', 'text' );
		param( 'account_close_reasons', 'text' );
		param( 'account_close_byemsg', 'text' );

		$Settings->set_array( array(
									 array( 'account_close_enabled', $account_close_enabled ),
									 array( 'account_close_intro', $account_close_intro ),
									 array( 'account_close_reasons', $account_close_reasons ),
									 array( 'account_close_byemsg', $account_close_byemsg ) ) );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{
				// invalidate all PageCaches
				invalidate_pagecaches();

				$Messages->add( T_('The settings of account closing have been updated.'), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=accountclose', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('Account closing'), '?ctrl=accountclose' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_account_close_setting.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>