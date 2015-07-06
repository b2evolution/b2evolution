<?php
/**
 * This file implements the in-skin lost possword form
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

global $blog, $dummy_fields, $source;

// Default params:
$params = array_merge( array(
		'skin_form_before'     => '',
		'skin_form_after'      => '',
		'form_title_lostpass'  => '',
		'lostpass_page_class'  => '',
		'login_form_inskin'    => true,
		'login_page_before'    => '',
		'login_page_after'     => '',
		'form_class_login'     => 'bComment',
		'lostpass_form_params' => NULL,
		'lostpass_form_footer' => true,
		'display_form_messages'=> false,
		'abort_link_text'      => '',
	), $params );

$form_params = array(
	'form_before'     => str_replace( '$form_title$', $params['form_title_lostpass'], $params['skin_form_before'] ),
	'form_after'      => $params['skin_form_after'],
	'inskin'          => $params['login_form_inskin'],
	'form_class'      => $params['form_class_login'],
	'form_template'   => $params['lostpass_form_params'],
	'abort_link_text' => $params['abort_link_text'],
);

$redirect_to = param( 'redirect_to', 'url', '' );
$return_to = param( 'return_to', 'url', '' );
$login = param( $dummy_fields[ 'login' ], 'string', '' );
$params_hidden = array(
	'inskin' => true,
	'blog' => $blog,
	'redirect_to' => regenerate_url( 'disp', 'disp=login' )
);

echo str_replace( '$form_class$', $params['lostpass_page_class'], $params['login_page_before'] );

if( $params['display_form_messages'] )
{ // Display the form messages before form inside wrapper
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
}

// display lost password form
display_lostpassword_form( $login, $params_hidden, $form_params );

if( $params['lostpass_form_footer'] )
{ // Display lost password form footer
	echo '<div class="notes standard_login_link"><a href="'.$secure_htsrv_url.'login.php?action=lostpassword&amp;source='.rawurlencode( $source ).'&amp;redirect_to='.rawurlencode( $redirect_to ).'&amp;return_to='.rawurlencode( $return_to ).'">'.T_( 'Use standard password recovery form instead').' &raquo;</a></div>';

	echo '<div class="form_footer_notes">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';
}

echo $params['login_page_after'];

?>