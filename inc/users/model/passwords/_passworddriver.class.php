<?php
/**
 * This file implements the Password Driver class.
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


/**
 * PasswordDriver Class
 *
 * @package evocore
 */
class PasswordDriver
{
	/**
	* Check if hash type is supported
	*
	* @return boolean TRUE if supported, FALSE if not
	*/
	public function is_supported()
	{
		return true;
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
		if( ! $password_is_hashed )
		{	// If the checking password is not hashed try to do this by this password driver:
			$password = $this->hash( $password, $salt );
		}

		return $this->string_compare( $hash, $password );
	}


	/**
	 * Compare two strings byte by byte
	 *
	 * @param string The first string
	 * @param string The second string
	 *
	 * @return boolean TRUE if strings are the same, FALSE if not
	 */
	public function string_compare( $string_a, $string_b )
	{
		// Return if input variables are not strings or if length does not match
		if( !is_string( $string_a ) || !is_string( $string_b ) || strlen( $string_a ) != strlen( $string_b ) )
		{
			return false;
		}

		// Use hash_equals() if it's available:
		if( function_exists( 'hash_equals' ) )
		{
			return hash_equals( $string_a, $string_b );
		}

		$difference = 0;

		for( $i = 0; $i < strlen( $string_a ) && $i < strlen( $string_b ); $i++ )
		{
			$difference |= ord( $string_a[ $i ] ) ^ ord( $string_b[ $i ] );
		}

		return $difference === 0;
	}
}
?>