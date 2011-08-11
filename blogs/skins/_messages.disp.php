<?php
/**
 * This is the template that displays a thread messages and message form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
	{
		unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Thread') ), 'error' );
		$Messages->display();
	}
}

// Preload users to show theirs avatars
load_messaging_thread_recipients( $thrd_ID );

// Display menu
echo '<div class="tabs">';
$selected = $disp;
if( $selected == 'messages' )
{ // There is no messages menu item, it belongs to threads.
	$selected = 'threads';
}
$entries = get_messaging_sub_entries( false );
foreach( $entries as $entry => $entry_data )
{
	if( $entry == $selected )
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
echo '</div>'; // Display menu end

// init params
if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class' => 'bComment',
	'form_action' => $htsrv_url.'messaging.php',
	'form_name' => '',
	'form_layout' => NULL,
	'cols' => 40 
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
 * Revision 1.1  2011/08/11 09:05:10  efy-asimo
 * Messaging in front office
 *
 */
?>