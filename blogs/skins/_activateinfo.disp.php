<?php
/**
 * This file implements the user activate info form
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
 * @version $Id: $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog;

$redirect_to = param( 'redirect_to', 'url', '' );
if( empty( $redirect_to ) )
{
	$redirect_to = regenerate_url( 'disp' );
}

$display_params = array(
	'form_action' => $secure_htsrv_url.'login.php',
	'form_name' => 'activateinfo_form',
	'form_class' => 'bComment',
	'form_layout' => NULL,
	'redirect_to' => url_rel_to_same_host( $redirect_to, $secure_htsrv_url ),
	'inskin' => true,
	'blog' => ( ( isset( $blog ) ) ? $blog : NULL )
);

// display account activate info
display_activateinfo( $display_params );

?>