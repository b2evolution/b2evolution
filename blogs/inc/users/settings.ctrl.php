<?php

if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

global $demo_mode;

$AdminUI->set_path( 'users', 'usersettings' );

param_action();

switch ( $action )
{
	case 'update':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// UPDATE general settings:

		param( 'allow_avatars', 'integer', 0 );
		$Settings->set( 'allow_avatars', $allow_avatars );

		param( 'uset_nickname_editing', 'string', 'edited-user' );
		if( $demo_mode )
		{
			$uset_multiple_sessions = 'always';
			$Messages->add( 'Demo mode requires multiple sessions setting to be set to always.', 'note' );
		}
		else
		{
			param( 'uset_multiple_sessions', 'string', 'default-no' );
		}

		$Settings->set_array( array(
									array( 'nickname_editing', $uset_nickname_editing ),
									array( 'multiple_sessions', $uset_multiple_sessions ) ) );

		if( ! $Messages->count('error') )
		{
			if( $Settings->dbupdate() )
			{
				$Messages->add( T_('General settings updated.'), 'success' );
			}
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=settings' );


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
 * Revision 1.6  2009/12/12 19:14:08  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.5  2009/12/06 22:55:19  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.4  2009/11/12 00:46:34  fplanque
 * doc/minor/handle demo mode
 *
 * Revision 1.3  2009/10/25 19:24:51  efy-maxim
 * multiple_sessions param
 *
 * Revision 1.2  2009/10/25 19:20:30  efy-maxim
 * users settings
 *
 * Revision 1.1  2009/10/25 18:22:14  efy-maxim
 * users setting controller
 *
 */
?>