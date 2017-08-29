<?php
/**
 * This file implements the evo md5 Password Driver class.
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


load_class( 'users/model/passwords/_passworddriver.class.php', 'PasswordDriver' );

/**
 * evoMd5PasswordDriver Class
 *
 * @package evocore
 */
class evoMd5PasswordDriver extends PasswordDriver
{
	protected $code = 'evo$md5';


	/**
	 * Hash password
	 *
	 * @param string Password
	 * @param string Salt (Not used by this password driver)
	 * @return string Hashed password
	 */
	public function hash( $password, $salt = '' )
	{
		return md5( $password );
	}


	/**
	 * Get JavaScript code to hash password on browser/client side
	 *
	 * @param string Name of password variable in JS code
	 * @param string Name of salt variable in JS code
	 * @return string
	 */
	public function get_javascript_hash_code( $var_password_name, $var_salt_name )
	{
		$js_code = 'hex_md5( '.$var_password_name.' )';

		return $js_code;
	}
}
?>