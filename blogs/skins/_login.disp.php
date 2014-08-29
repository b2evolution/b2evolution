<?php
/**
 * This file implements the login form
 *
 * This file is not meant to be called directly.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _login.disp.php 7237 2014-08-15 15:30:04Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $action, $disp, $rsc_url, $Settings, $rsc_path, $transmit_hashed_password, $dummy_fields;

if( is_logged_in() )
{ // already logged in
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

// Default params:
$params = array_merge( array(
		'form_title_login'  => '',
		'form_class_login'  => '',
		'login_form_inskin' => true,
		'login_page_before' => '',
		'login_page_after'  => '',
		'login_form_before' => '',
		'login_form_after'  => '',
		'login_form_class'  => 'bComment',
	), $params );

$login = evo_strtolower( param( $dummy_fields[ 'login' ], 'string', '' ) );
$action = param( 'action', 'string', '' );
$redirect_to = param( 'redirect_to', 'url', '' );
$source = param( 'source', 'string', 'inskin login form' );
$login_required = ( $action == 'req_login' );

global $admin_url, $ReqHost, $secure_htsrv_url;

if( !isset( $redirect_to ) )
{
	$redirect_to = regenerate_url( 'disp' );
}

$login_form_params = array(
	'form_before' => str_replace( '$form_title$', $params['form_title_login'], $params['login_form_before'] ),
	'form_after' => $params['login_form_after'],
	'source' => $source,
	'login_required' => $login_required,
	'redirect_to' => $redirect_to,
	'login' => $login,
	'action' => $action,
	'transmit_hashed_password' => $transmit_hashed_password,
	'inskin' => $params['login_form_inskin'],
	'form_class' => $params['login_form_class'],
);

echo str_replace( '$form_class$', $params['form_class_login'], $params['login_page_before'] );

display_login_form( $login_form_params );

echo $params['login_page_after'];

echo '<div class="notes standard_login_link"><a href="'.$secure_htsrv_url.'login.php?source='.rawurlencode($source).'&redirect_to='.rawurlencode( $redirect_to ).'">'.T_( 'Use standard login form instead').' &raquo;</a></div>';

echo '<div class="form_footer_notes">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';

echo '<div class="clear"></div>';

?>