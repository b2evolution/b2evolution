<?php
/**
 * This file implements the register form
 *
 * This file is not meant to be called directly.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'register_page_before'      => '<div class="evo_panel__register">',
		'register_page_after'       => '</div>',
		'form_class_register'       => 'evo_form__register',
		'register_use_placeholders' => true, // Set TRUE to use placeholders instead of notes for input fields
		'register_field_width'      => 252,
		'register_form_footer'      => true,
		'register_buttons_before'   => '<div class="evo_register_buttons">',
		'register_buttons_after'    => '</div>',
	), $params );

echo $params['register_page_before'];

// Display the form messages:
messages( array(
		'block_start' => '<div class="action_messages">',
		'block_end'   => '</div>',
	) );

// ------------------ "Register" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
skin_container( NT_('Register'), array_merge( $params, array(
		// The following (optional) params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start'       => '<div class="panel panel-default skin-form evo_widget $wi_class$">',
		'block_end'         => '</div><br>',
		// This will enclose the title of each widget:
		'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
		'block_title_end'   => '</h4></div>',
		// This will enclose the body of each widget:
		'block_body_start'  => '<div class="panel-body">',
		'block_body_end'    => '</div>',
	) ) );
// --------------------- END OF "Register" CONTAINER -----------------------

if( $params['register_form_footer'] )
{	// Display register form footer:
	global $Hit;
	echo '<div class="evo_login_dialog_standard_link"><a href="'.get_htsrv_url( 'login' ).'register.php?source='.rawurlencode( get_param( 'source' ) ).'&amp;redirect_to='.rawurlencode( get_param( 'redirect_to' ) ).'&amp;return_to='.rawurlencode( get_param( 'return_to' ) ).'">'.T_( 'Use basic registration form instead').' &raquo;</a></div>';
	echo '<div class="evo_login_dialog_footer text-muted">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';
}

echo $params['register_page_after'];
?>