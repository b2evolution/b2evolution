<?php
/**
 * This file implements the bcrypt Password Driver class.
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
 * bcryptPasswordDriver Class
 *
 * @package evocore
 */
class bcryptPasswordDriver extends PasswordDriver
{
	protected $code = 'bb$2a';

	/**
	 * Length of salt
	 * @var integer
	 */
	protected $salt_length = 22;


	/**
	 * Get prefix of the password driver
	 *
	 * @return string
	 */
	public function get_prefix()
	{
		return parent::get_prefix().'10$';
	}


	/**
	 * Hash password
	 *
	 * @param string Password
	 * @param string Salt
	 * @return string Hashed password
	 */
	public function hash( $password, $salt = '' )
	{
		// The 2x and 2y prefixes of bcrypt might not be supported
		// Revert to 2a if this is the case
		$code = ( ! $this->is_supported() ) ? 'bb$2a' : $this->get_code();

		// Do not support 8-bit characters with $2a$ bcrypt
		// Also see http://www.php.net/security/crypt_blowfish.php
		if( $code === 'bb$2a' )
		{
			if( ord( $password[ strlen( $password ) - 1 ] ) & 128 )
			{
				return false;
			}
		}

		if( empty( $salt ) )
		{	// Generate new random if salt is not provided:
			$salt = $this->get_random_salt();
		}

		$hash = crypt( $password, $this->get_prefix().$salt );
		if( strlen( $hash ) < 60 )
		{
			return false;
		}

		return $this->clear_hash( $hash, $salt );
	}


	/**
	* Get a random salt value with a length of 22 characters
	*
	* @return string Salt for password hashing
	*/
	protected function get_random_salt()
	{
		$salt = $this->hash_encode64( generate_random_key( $this->salt_length ), $this->salt_length );

		// Save last generated salt to know what value write in DB on user password updating:
		$this->last_generated_salt = $salt;

		return $salt;
	}
}
?>