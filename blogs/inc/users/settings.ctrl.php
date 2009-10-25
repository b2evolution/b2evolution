<?php

if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

$AdminUI->set_path( 'users', 'usersettings' );

param_action();

switch ( $action )
{
	case 'update':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// UPDATE general settings:
		param( 'uset_nickname_editing', 'string', 'edited-user' );
		param( 'uset_login_multiple_sessions', 'string', 'default-no' );

		$Settings->set_array( array(
									array( 'nickname_editing', $uset_nickname_editing),
									array( 'login_multiple_sessions', $uset_login_multiple_sessions) ) );

		if( ! $Messages->count('error') )
		{
			if( $Settings->dbupdate() )
			{
				$Messages->add( T_('General settings updated.'), 'success' );
			}
		}

		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_settings.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.2  2009/10/25 19:20:30  efy-maxim
 * users settings
 *
 * Revision 1.1  2009/10/25 18:22:14  efy-maxim
 * users setting controller
 *
 */
?>