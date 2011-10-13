<?php
/**
 * This is the template that displays a thread messages and message form
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
global $DB;
global $Skin;

if( !empty( $Skin ) ) {
	$display_params = array_merge( $Skin->get_template( 'Results' ), $Skin->get_template( 'messages' ) );
} else {
	$display_params = NULL;
}

if( !is_logged_in() )
{
	debug_die( "User must be logged in to see this page." );
}

// Check minimum permission:
$current_User->check_perm( 'perm_messaging', 'reply', true );

if( $thrd_ID = param( 'thrd_ID', 'integer', '', true) )
{// Load thread from cache:
	$ThreadCache = & get_ThreadCache();
	if( ($edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false )) === false )
	{	// Thread doesn't exists with this ID
		unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Thread') ), 'error' );
	}
	else if( ! $edited_Thread->check_thread_recipient( $current_User->ID ) )
	{	// Current user is not recipient of this thread
		unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( sprintf( T_('You are not allowed to view this &laquo;%s&raquo;.'), T_('Thread') ), 'error' );
	}
}

if( ( empty( $thrd_ID ) ) || ( empty( $edited_Thread ) ) )
{
	$Messages->add( T_( 'Can\'t show messages without thread!' ), 'error' );
	$Messages->display();
}
else
{
	// Preload users to show theirs avatars
	load_messaging_thread_recipients( $thrd_ID );
}

// init params
if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class' => 'bComment',
	'form_action' => $samedomain_htsrv_url.'messaging.php',
	'form_name' => '',
	'form_layout' => NULL,
	'cols' => 35 
	), $params );

// Display messages list:
if( isset( $edited_Thread ) )
{
	global $action;
	$action = 'create';
	require $inc_path.'messaging/views/_message_list.view.php';
}

/*
 * $Log$
 * Revision 1.8  2011/10/13 18:08:17  efy-yurybakh
 * fix bug in the message list
 *
 * Revision 1.7  2011/10/11 05:52:15  efy-asimo
 * Messages menu link widget
 *
 * Revision 1.6  2011/10/07 05:43:45  efy-asimo
 * Check messaging availability before display
 *
 * Revision 1.5  2011/10/03 12:00:33  efy-yurybakh
 * Small messaging UI design changes
 *
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