<?php
/**
 * This is the template that displays user threads and contacts
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 */

// Load classes
load_class( 'messaging/model/_thread.class.php', 'Thread' );
load_class( 'messaging/model/_message.class.php', 'Message' );

// init variables
global $inc_path;
global $Messages;
global $edited_Thread;
global $edited_Message;

global $Skin;
if( !empty( $Skin ) ) {
	$display_params = array_merge( $Skin->get_template( 'Results' ), $Skin->get_template( 'messages' ) );
} else {
	$display_params = NULL;
}

if( !is_logged_in() )
{
	debug_die( 'You are not logged in!' );
}

if( !isset( $disp ) )
{
	$disp = 'threads';
}

// Get action parameter from request:
$action = param_action( 'view' );

// Preload users to show theirs avatars
load_messaging_threads_recipients( $current_User->ID );

switch( $action )
{
	case 'new':
		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'reply', true );

		// We don't have a model to use, start with blank object:
		$edited_Thread = new Thread();
		$edited_Message = new Message();
		$edited_Message->Thread = & $edited_Thread;
		break;

	default:
		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'reply', true );
		break;
}

// ----------------------- End Init variables --------------------------

echo '<div class="tabs">';
$entries = get_messaging_sub_entries( false );
foreach( $entries as $entry => $entry_data )
{
	if( $entry == $disp )
	{
		echo '<div class="selected">';
	}
	else
	{
		echo '<div class="option">';
	}
	echo '<a href='.$entry_data['href'].'>'.$entry_data['text'].'</a>';
	echo '</div>';
}
echo '</div>';

// set params
if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class' => 'bComment',
	'form_title' => '',
	'form_action' => $samedomain_htsrv_url.'messaging.php',
	'form_name' => '',
	'form_layout' => NULL,
	'cols' => 40
	), $params );

switch( $disp )
{
	case 'threads':
		if( $action == 'new' )
		{
			require $inc_path.'messaging/views/_thread.form.php';
		}
		else
		{
			require $inc_path.'messaging/views/_thread_list.view.php';
		}
		break;

	case 'contacts':
		require $inc_path.'messaging/views/_contact_list.view.php';
		break;

	default:
		debug_die( "Unknown user tab" );
}

/**
 * $Log$
 * Revision 1.4  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.3  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.2  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.1  2011/08/11 09:05:10  efy-asimo
 * Messaging in front office
 *
 */
?>