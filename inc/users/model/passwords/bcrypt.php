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
	 * @param string Old hash, used to extract a salt from old hash, can be useful on checking with entered password
	 * @return string Hashed password
	 */
	public function hash( $password, $salt = '', $old_hash = '' )
	{
		// The 2x and 2y prefixes of bcrypt might not be supported
		// Revert to 2a if this is the case
		$code = ( ! $this->is_supported() ) ? 'bb$2a' : $this->get_code();

		// Do not support 8-bit characters with $2a$ bcrypt
		// Also see http://www.php.net/security/crypt_blowfish.php
		if( $code === $this->get_code() )
		{
			if( ord( $password[ strlen( $password ) - 1 ] ) & 128 )
			{
				return false;
			}
		}

		if( $salt == '' )
		{	// If salt is not provided:
			if( $old_hash != '' )
			{	// Try to extract salt from old hash if it is provided:
				$salt = $this->extract_salt( $old_hash );
			}
			if( empty( $salt ) )
			{	// Try to generate new random if it is could not extracte from old hash above:
				$salt = $this->get_random_salt();
			}
		}

		$hash = crypt( $password, $salt );
		if( strlen( $hash ) < 60 )
		{
			return false;
		}

		return $this->clear_hash( $hash );
	}


	/**
	* Get a random salt value with a length of 22 characters
	*
	* @return string Salt for password hashing
	*/
	protected function get_random_salt()
	{
		return $this->get_prefix().$this->hash_encode64( generate_random_key( 22 ), 22 );
	}


	/**
	 * Extract a salt from hash
	 *
	 * @param string Hash
	 */
	protected function extract_salt( $hash )
	{
		$salt = substr( $this->get_prefix().$hash, 0, 29 );
		if( strlen( $salt ) != 29 )
		{
			return false;
		}

		return $salt;
	}


	/**
	 * Check password against the supplied hash
	 *
	 * @param string Password
	 * @param string Salt
	 * @param string Hash
	 * @param boolean Is the password parameter already hashed?
	 * @return boolean TRUE if password is correct, else FALSE
	 */
	public function check( $password, $salt, $hash, $password_is_hashed = false )
	{
		if( ! ( $salt = $this->extract_salt( $hash ) ) )
		{	// Wrong hash, impossible to extract salt:
			return false;
		}

		return parent::check( $password, $salt, $hash, $password_is_hashed );
	}
}
?>