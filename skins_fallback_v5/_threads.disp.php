<?php
/**
 * This is the template that displays user threads and contacts
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
$thrdtype =  'individual';  // alternative: discussion

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

// ----------------------- End Init variables --------------------------

// set params
if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class_thread' => 'bComment',
	'form_title' => '',
	'form_action' => $samedomain_htsrv_url.'action.php?mname=messaging',
	'form_name' => '',
	'form_layout' => NULL,
	'cols' => 40,
	'thrdtype' => $thrdtype,
	), $params );

switch( $disp )
{
	case 'threads':
		if( in_array( $action, array( 'new', 'preview', 'create' ) ) )
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

?>