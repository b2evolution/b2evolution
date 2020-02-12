<?php
/**
 * This file implements the bcrypt 2y Password Driver class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'users/model/passwords/bcrypt.php', 'bcryptPasswordDriver' );

/**
 * bcryptPasswordDriver Class
 *
 * @package evocore
 */
class bcrypt2yPasswordDriver extends bcryptPasswordDriver
{
	protected $code = 'bb$2y';
}
?>