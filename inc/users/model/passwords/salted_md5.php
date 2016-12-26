<?php
/**
 * This file implements the salted md5 Password Driver class.
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
 * saltedMd5PasswordDriver Class
 *
 * @package evocore
 */
class saltedMd5PasswordDriver extends PasswordDriver
{
	protected $code = 'bb$H';

	/**
	 * Length of salt
	 * @var integer
	 */
	protected $salt_length = 9;


	/**
	 * Hash password
	 *
	 * @param string Password
	 * @param string Salt
	 * @return string Hashed password
	 */
	public function hash( $password, $salt = '' )
	{
		if( empty( $salt ) )
		{	// Generate salt for new hashing:
			$salt = $this->get_random_salt();
		}

		if( ( $count_log2 = $this->get_count_setting( $salt ) ) === false )
		{
			// Return md5 of password if settings do not
			// comply with our standards. This will only
			// happen if pre-determined settings are
			// directly passed to the driver. The manager
			// will not do this. Same as the old hashing
			// implementation in phpBB 3.0
			return md5( $password );
		}

		$hash = md5( substr( $salt, 1 ).$password, true );
		do
		{
			$hash = md5( $hash.$password, true );
		}
		while( --$count_log2 );

		return $this->hash_encode64( $hash, 16 );
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
		$js_code = '
function salted_md5_hash_encode64( input, count )
{
	var output = "";
	var i = 0;
	var itoa64 = "'.$this->itoa64.'";

	do
	{
		value = input[ i++ ].charCodeAt(0);
		output += itoa64[ value & 0x3f ];

		if( i < count )
		{
			value |= input[ i ].charCodeAt(0) << 8;
		}

		output += itoa64[ (value >> 6) & 0x3f ];

		if( i++ >= count )
		{
			break;
		}

		if( i < count )
		{
			value |= input[ i ].charCodeAt(0) << 16;
		}

		output += itoa64[ ( value >> 12 ) & 0x3f ];

		if( i++ >= count )
		{
			break;
		}

		output += itoa64[ ( value >> 18 ) & 0x3f ];
	}
	while( i < count );

	return output;
}

function salted_md5_get_count_setting( salt )
{
	if( salt == "" || salt.length != '.$this->salt_length.' )
	{	// Wrong salt format:
		return false;
	}

	var itoa64 = "'.$this->itoa64.'";
	var count_log2 = itoa64.indexOf( salt[0] );

	if( count_log2 < 7 || count_log2 > 30 )
	{	// Not allowed value:
		return false;
	}

	return 1 << count_log2;
}

function salted_md5_hash( password, salt )
{
	var count_log2 = salted_md5_get_count_setting( salt );
	if( count_log2 === false )
	{
		return hex_md5( password );
	}

	var hash = rstr_md5( salt.substring( 1 ) + password );
	do
	{
		hash = rstr_md5( hash + password );
	}
	while( --count_log2 );

	return salted_md5_hash_encode64( hash, 16 );
}

salted_md5_hash( '.$var_password_name.', '.$var_salt_name.' );
';

		return $js_code;
	}


	/**
	 * Get a random salt value
	 *
	 * @return string Salt for password hashing
	 */
	protected function get_random_salt()
	{
		$count = 6;

		$random = generate_random_key( $count );

		$salt = $this->itoa64[ min( $count + 5, 30 ) ];
		$salt .= $this->hash_encode64( $random, $count );

		// Save last generated salt to know what value write in DB on user password updating:
		$this->last_generated_salt = $salt;

		return $salt;
	}


	/**
	 * Get setting count_log2 from salt to generate new hash
	 *
	 * @param string The salt that contains the setting in first char
	 *
	 * @return boolean|integer Containing the count_log2
	 *         or false if supplied salt is incorrect
	 */
	public function get_count_setting( $salt )
	{
		if( empty( $salt ) || strlen( $salt ) != $this->salt_length )
		{	// Wrong salt format:
			return false;
		}

		$count_log2 = strpos( $this->itoa64, $salt[0] );

		if( $count_log2 < 7 || $count_log2 > 30 )
		{	// Not allowed value:
			return false;
		}

		return 1 << $count_log2;
	}
}
?>