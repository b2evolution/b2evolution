<?php
/**
 * This file implements the phpass Password Driver class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'users/model/passwords/salted_md5.php', 'saltedMd5PasswordDriver' );

/**
 * phpassPasswordDriver Class
 *
 * @package evocore
 */
class phpassPasswordDriver extends saltedMd5PasswordDriver
{
	protected $code = 'bb$P';
}
?>