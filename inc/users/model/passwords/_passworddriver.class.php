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
	 * Length of salt
	 * @var integer
	 */
	protected $salt_length = 0;

	/**
	 * base64 alphabet
	 * @var string
	 */
	public $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

	/**
	 * Last salt value that was generated to store this in DB T_users -> user_salt
	 * @var string
	 */
	protected $last_generated_salt = '';


	/**
	 * Get code of the password driver
	 *
	 * @return string
	 */
	public function get_code()
	{
		return $this->code;
	}


	/**
	 * Get prefix of the password driver
	 * E.g. prefix of code `bb$2y` must be `$2y$`
	 * such prefix value is required to encrypt e.g. by password driver "bcrypt"
	 *
	 * @return string
	 */
	public function get_prefix()
	{
		return preg_replace( '#^[a-z]+#', '', $this->code ).'$';
	}


	/**
	 * Get last generated salt
	 *
	 * @return string
	 */
	public function get_last_generated_salt()
	{
		return $this->last_generated_salt;
	}


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

	/**
	* Base64 encode hash
	*
	* @param string Input string
	* @param integer $count Input string length
	*
	* @return string base64 encoded string
	*/
	public function hash_encode64( $input, $count )
	{
		$output = '';
		$i = 0;

		do
		{
			$value = ord( $input[ $i++ ] );
			$output .= $this->itoa64[ $value & 0x3f ];

			if( $i < $count )
			{
				$value |= ord( $input[ $i ] ) << 8;
			}

			$output .= $this->itoa64[ ($value >> 6) & 0x3f ];

			if( $i++ >= $count )
			{
				break;
			}

			if( $i < $count )
			{
				$value |= ord( $input[ $i ] ) << 16;
			}

			$output .= $this->itoa64[ ( $value >> 12 ) & 0x3f ];

			if( $i++ >= $count )
			{
				break;
			}

			$output .= $this->itoa64[ ( $value >> 18 ) & 0x3f ];
		}
		while( $i < $count );

		return $output;
	}


	/**
	 * Extract a salt from hash
	 *
	 * @param string Full hash with prefix and salt
	 * @return string Salt
	 */
	public function extract_salt( $hash )
	{
		$salt = substr( $hash, strlen( $this->get_prefix() ), $this->salt_length );

		if( strlen( $salt ) != $this->salt_length )
		{
			return '';
		}

		return $salt;
	}


	/**
	 * Clear a hash from password driver prefix and password salt
	 *
	 * @param string Full hash with prefix and salt
	 * @return string Password without prefix and salt
	 */
	public function clear_hash( $hash )
	{
		// Remove the driver prefix and password salt from the generated hash:
		return substr( $hash, strlen( $this->get_prefix() ) + $this->salt_length );
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
		return '';
	}
}
?>