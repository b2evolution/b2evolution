<?php
/**
 * This file implements the ayment class
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get payment user for table cell
 *
 * @param integer User ID
 * @return string
 */
function payment_td_user( $user_ID )
{
	return empty( $user_ID )
		? '<span class="note">'.T_('Anon.').'</span>'
		: get_user_identity_link( NULL, $user_ID );
}
?>