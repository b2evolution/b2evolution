<?php
/**
 * This is the template that displays users
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load classes
load_class( 'users/model/_user.class.php', 'User' );

// init variables
global $inc_path;
global $Users;
global $edited_User;

global $Skin;
if( !empty( $Skin ) )
{
	$display_params = array_merge( $Skin->get_template( 'Results' ), $Skin->get_template( 'users' ) );
}
else
{
	$display_params = NULL;
}


// ----------------------- End Init variables --------------------------

// set params
if( !isset( $params ) )
{
	$params = array();
}

$params = array_merge( array(
	'form_class' => 'bComment',
	'form_title' => '',
	'form_action' => '',
	'form_name' => '',
	'form_layout' => NULL,
	'cols' => 40
	), $params );


require $inc_path.'users/views/_user_list_short.view.php';

?>