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

load_class( 'regional/model/_country.class.php', 'Country' );

global $Settings, $Plugins;

global $Collection, $Blog, $rsc_path, $rsc_url, $dummy_fields;

global $display_invitation;

// Default params:
$params = array_merge( array(
		'register_page_before'      => '',
		'register_page_after'       => '',
		'form_class_register'       => 'bComment',
		'register_use_placeholders' => false, // Set TRUE to use placeholders instead of notes for input fields
		'register_field_width'      => 140,
		'register_form_footer'      => true,
	), $params );


echo $params['register_page_before'];

// ------------------ "Register" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
skin_container( NT_('Register'), array_merge( $params, array(
		// The following (optional) params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start'       => '<br><div class="evo_widget $wi_class$">',
		'block_end'         => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<h4>',
		'block_title_end'   => '</h4>',
	) ) );
// --------------------- END OF "Register" CONTAINER -----------------------

if( $params['register_form_footer'] )
{ // Display register form footer
	echo '<div class="notes standard_login_link"><a href="'.get_htsrv_url( true ).'register.php?source='.rawurlencode( $source ).'&amp;redirect_to='.rawurlencode( $redirect_to ).'&amp;return_to='.rawurlencode( $return_to ).'">'.T_( 'Use standard registration form instead').' &raquo;</a></div>';

	echo '<div class="form_footer_notes">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';
}

echo $params['register_page_after'];

// Display javascript password strength indicator bar
display_password_indicator( array( 'field-width' => $params['register_field_width'] ) );

// Display javascript login validator
display_login_validator();

?>