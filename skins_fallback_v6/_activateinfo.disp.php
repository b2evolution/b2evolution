<?php
/**
 * This file implements the user activate info form
 *
 * This file is not meant to be called directly.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog;

$redirect_to = param( 'redirect_to', 'url', '' );
if( empty( $redirect_to ) )
{
	$redirect_to = regenerate_url( 'disp' );
}

// Default params:
$params = array_merge( array(
		'skin_form_before'     => '',
		'skin_form_after'      => '',
		'activate_form_title'  => '',
		'activate_page_before' => '',
		'activate_page_after'  => '',
		'activate_form_params' => NULL,
		'use_form_wrapper'     => true,
		'display_form_messages'=> false,
	), $params );

$display_params = array(
	'use_form_wrapper' => $params['use_form_wrapper'],
	'form_before'      => str_replace( '$form_title$', $params['activate_form_title'], $params['skin_form_before'] ),
	'form_after'       => $params['skin_form_after'],
	'form_action'      => $secure_htsrv_url.'login.php',
	'form_name'        => 'activateinfo_form',
	'form_class'       => 'evo_form__activateinfo',
	'form_layout'      => NULL,
	'redirect_to'      => url_rel_to_same_host( $redirect_to, $secure_htsrv_url ),
	'inskin'           => true,
	'blog'             => ( ( isset( $blog ) ) ? $blog : NULL ),
	'form_template'    => $params['activate_form_params'],
);

echo $params['activate_page_before'];

if( $params['display_form_messages'] )
{ // Display the form messages before form inside wrapper
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
}

// display account activate info
display_activateinfo( $display_params );

echo $params['activate_page_after'];
?>