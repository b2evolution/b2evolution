<?php
/**
 * This file implements the UI view for the user/group list for user/group editing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}

users_results_block( array(
		'display_sec_groups' => true,
		'display_params'     => $display_params,
	) );

if( is_admin_page() )
{	// Call plugins event:
	global $Plugins;
	$Plugins->trigger_event( 'AdminAfterUsersList' );
}

load_funcs( 'users/model/_user_js.funcs.php' );
?>