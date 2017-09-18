<?php
/**
 * This file implements the evo salted Password Driver class.
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
 * evoSaltedPasswordDriver Class
 *
 * @package evocore
 */
class evoSaltedPasswordDriver extends PasswordDriver
{
	protected $code = 'evo$salted';


	/**
	 * Hash password
	 *
	 * @param string Password
	 * @param string Salt
	 * @return string Hashed password
	 */
	public function hash( $password, $salt = '' )
	{
		if( $salt == '' )
		{	// Generate new salt:
			$this->last_generated_salt = generate_random_key( 8 );
			$salt = $this->last_generated_salt;
		}

		return md5( $salt.$password );
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
		$js_code = 'hex_md5( '.$var_salt_name.' + '.$var_password_name.' )';

		return $js_code;
	}
}
?>