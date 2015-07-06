<?php
/**
 * This file implements the login form
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

global $blog, $action, $disp, $rsc_url, $Settings, $rsc_path, $transmit_hashed_password, $dummy_fields;

if( is_logged_in() )
{ // already logged in
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

$login = utf8_strtolower( param( $dummy_fields[ 'login' ], 'string', '' ) );
$action = param( 'action', 'string', '' );
$redirect_to = param( 'redirect_to', 'url', '' );
$return_to = param( 'return_to', 'url', '' );
$source = param( 'source', 'string', 'inskin login form' );
$login_required = ( $action == 'req_login' );

global $admin_url, $ReqHost, $secure_htsrv_url;

if( !isset( $redirect_to ) )
{
	$redirect_to = regenerate_url( 'disp' );
}

// Default params:
$params = array_merge( array(
		'skin_form_before'         => '',
		'skin_form_after'          => '',
		'form_title_login'         => '',
		'login_page_class'         => '',
		'login_page_before'        => '',
		'login_page_after'         => '',
		'login_form_action'        => $action,
		'login_form_name'          => 'login_form',
		'login_form_title'         => '',
		'login_form_layout'        => '',
		'form_class_login'         => 'evo_form__login',
		'login_form_source'        => $source,
		'login_form_inskin'        => true,
		'login_form_inskin_urls'   => true,
		'login_form_required'      => $login_required,
		'login_validate_required'  => NULL,
		'login_form_redirect_to'   => $redirect_to,
		'login_form_return_to'     => $return_to,
		'login_form_login'         => $login,
		'login_action_value'       => '',
		'login_form_reqID'         => '',
		'login_form_sessID'        => '',
		'transmit_hashed_password' => $transmit_hashed_password,
		'display_abort_link'       => true,
		'abort_link_position'      => 'above_form',
		'abort_link_text'          => T_('Abort login!'),
		'display_reg_link'         => false,
		'display_form_messages'    => false,
		'login_form_footer'        => true,
	), $params );

$login_form_params = array(
	'form_before'              => str_replace( '$form_title$', $params['form_title_login'], $params['skin_form_before'] ),
	'form_after'               => $params['skin_form_after'],
	'form_action'              => $params['login_form_action'],
	'form_name'                => $params['login_form_name'],
	'form_title'               => $params['login_form_title'],
	'form_layout'              => $params['login_form_layout'],
	'form_class'               => $params['form_class_login'],
	'source'                   => $params['login_form_source'],
	'inskin'                   => $params['login_form_inskin'],
	'inskin_urls'              => $params['login_form_inskin_urls'],
	'login_required'           => $params['login_form_required'],
	'validate_required'        => $params['login_validate_required'],
	'redirect_to'              => $params['login_form_redirect_to'],
	'return_to'                => $params['login_form_return_to'],
	'login'                    => $params['login_form_login'],
	'action'                   => $params['login_action_value'],
	'reqID'                    => $params['login_form_reqID'],
	'sessID'                   => $params['login_form_sessID'],
	'transmit_hashed_password' => $params['transmit_hashed_password'],
	'display_abort_link'       => $params['display_abort_link'],
	'abort_link_position'      => $params['abort_link_position'],
	'abort_link_text'          => $params['abort_link_text'],
	'display_reg_link'         => $params['display_reg_link'],
);

echo str_replace( '$form_class$', $params['login_page_class'], $params['login_page_before'] );

if( $params['display_form_messages'] )
{ // Display the form messages before form inside wrapper
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
}

display_login_form( $login_form_params );

if( $params['login_form_footer'] )
{ // Display login form footer
	echo '<div class="evo_login_dialog_standard_link"><a href="'.$secure_htsrv_url.'login.php?source='.rawurlencode( $source ).'&amp;redirect_to='.rawurlencode( $redirect_to ).'&amp;return_to='.rawurlencode( $return_to ).'">'.T_( 'Use standard login form instead').' &raquo;</a></div>';

	echo '<div class="evo_login_dialog_footer text-muted">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';
}

echo $params['login_page_after'];
?>