<?php
/**
 * This file implements the login form
 *
 * This file is not meant to be called directly.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $action, $disp, $rsc_url, $Settings, $rsc_path, $transmit_hashed_password, $dummy_fields;

if( is_logged_in() )
{ // already logged in
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$action = param( 'action', 'string', '' );
$redirect_to = param( 'redirect_to', 'string', '' );
$source = param( 'source', 'string', 'inskin login form' );
$login_required = ( $action == 'req_login' );

global $admin_url, $ReqHost, $secure_htsrv_url;

if( !isset( $redirect_to ) )
{
	$redirect_to = regenerate_url( 'disp' );
}

$params = array(
	'source' => $source,
	'login_required' => $login_required,
	'redirect_to' => $redirect_to,
	'login' => $login,
	'action' => $action,
	'transmit_hashed_password' => $transmit_hashed_password,
);

display_login_form( $params );

echo '<div class="notes" style="margin: 1em"><a href="'.$secure_htsrv_url.'login.php?source='.rawurlencode($source).'&redirect_to='.$redirect_to.'">'.T_( 'Use standard login form instead').' &raquo;</a></div>';

echo '<div class="form_footer_notes">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';

echo '<div class="clear"></div>';


/*
 * $Log$
 * Revision 1.23  2013/11/06 08:05:36  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>