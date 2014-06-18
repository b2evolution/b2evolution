<?php
/**
 * This file implements the in-skin lost possword form
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

global $blog, $dummy_fields;

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$parasm_hidden = array( 'inskin' => true, 'blog' => $blog, 'redirect_to' => regenerate_url( 'disp', 'disp=login' ) );

// display lost password form
display_lostpassword_form( $login, $parasm_hidden );

?>